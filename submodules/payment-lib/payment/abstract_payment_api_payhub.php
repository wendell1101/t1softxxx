<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * PAYHUB 365PAYHUB
 * *
 * * PAYHUB_ALIPAY_PAYMENT_API, ID: 5014
 * * PAYHUB_ALIPAY_H5_PAYMENT_API, ID: 5015
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Token
 *
 * Field Values:
 * * URL: https://www.365payhub8.com/api/pay_qr
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 * * Token: ## Token ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_payhub extends Abstract_payment_api {

    const PAYTYPE_ALIPAY    = 'alipay';

    const RESULT_CODE_SUCCESS = '0';
    const RESULT_ERROR_MSG = array(
        "-100" => "充值金额不符合规定",
        "-101" => "有同样金额交易正在处理中，请稍后再试",
        "-102" => "充值金额超过上限",
        "-103" => "接口参数有误",
        "-104" => "通道尚未部署",
        "-200" => "交易逾时",
    );
    const CALLBACK_SUCCESS_CODE = 'success';
    const RETURN_SUCCESS_CODE = '888888';


    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();
        $params['account']   = $this->getSystemInfo('account');
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['payMoney']  = $this->convertAmountToCurrency($amount);
        $params['ip']        = $this->getClientIP();
        $params['notifyURL'] = $this->getNotifyUrl($orderId);
        $params['orderNo']   = $order->secure_id;
        $this->CI->utils->debug_log('=====================payhub generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormRedirect($params) {

        $url = $this->getSystemInfo('url');
        $response = $this->processCurl($params, $url);

        if($response['retCode'] == self::RESULT_CODE_SUCCESS) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['orderNo']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['uuid']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['qrContent'],
            );
        }
        else if(isset($response['retMsg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => '['.$response['retCode'].']: '.$response['retMsg']
            );
        }
        else if(isset($response['retCode'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => '['.$response['retCode'].']: '.self::RESULT_ERROR_MSG[$response['retCode']]
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidte API response')
            );
        }
    }

    protected function processCurl($params, $url) {
        $ch = curl_init();
        $token = $this->getSystemInfo('token');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.$token)
        );

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['orderNo']);
        $response = json_decode($response, true);

        $this->CI->utils->debug_log('=====================payhub processCurl decoded response', $response);
        return $response;
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================payhub callbackFrom $source params", $params);

        if($source == 'server'){
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        # Update order payment status and balance
        $success = true;

        # Update player balance based on order status
        # if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            # update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $params['uuid'], '', null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['return_error'] = 'Error';
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'account', 'nonceStr', 'orderNo', 'payMoney', 'payStatus', 'uuid','sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================payhub checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================payhub checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass


        if ($fields['payStatus'] != self::CALLBACK_SUCCESS_CODE) {
            $this->writePaymentErrorLog("======================payhub checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['payMoney'] != $this->convertAmountToCurrency($order->amount)) {
            #because player need to enter amount at Alipay
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $this->CI->utils->debug_log('=====================payhub amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['amount'], $notes);

            }
            else{
                $this->writePaymentErrorLog("======================payhub checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['orderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================payhub checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    private function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if( ($key == 'sign') || (empty($value)) ) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $api_key = $this->getSystemInfo('key');
        $signStr .= "key=".$api_key;
        $sign = strtoupper(hash_hmac('sha256', $signStr, $api_key));

        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    # -- Private functions --
    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }
}