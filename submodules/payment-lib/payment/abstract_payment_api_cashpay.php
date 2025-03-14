<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * cashpay
 *
 * * CASHPAY_PAYMENT_API, ID: 6186
 *
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://payment.dev.mspays.xyz/haoli711/orders/v3/scan
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_cashpay extends Abstract_payment_api {
    const RESPONSE_SUCCESS       = 200;
    const CALLBACK_SUCCESS       = '01';
    const RETURN_SUCCESS_CODE    = 'SUCCESS';
    const RETURN_FAIL_CODE       = 'FAIL';
    const PAYMENTTYPE_BANKTOBANK = 'BANKTOBANK';


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
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);

        $firstname = (!empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'none';
        $lastname  = (!empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName']      : 'none';
        $emailAddr = (!empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : 'sample@nothing.com';
        $phone     = (!empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '8615551234567';
        $address   = (!empty($playerDetails[0]['address']))       ? $playerDetails[0]['address']       : 'none';
        $pixNumber = (!empty($playerDetails[0]['pix_number']))       ? $playerDetails[0]['pix_number'] : 'none';

        $params = array();
        $params['amount']           = $this->convertAmountToCurrency($amount);
        $params['merchantOrderId']  = $order->secure_id;
        $params['notifyUrl']        = $this->getNotifyUrl($orderId);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['customerEmail']    = $emailAddr;
        $params['customerPhone']    = $phone;
        $params['customerName']     = $lastname.' '.$firstname;
        $params['customerCert']     = $pixNumber;
        $params['returnUrl']        = $this->getReturnUrl($orderId);
        $params['merchantUserId']   = $this->getSystemInfo('account');
        $this->CI->utils->debug_log('=====================cashpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->processCurl($params, $this->getSystemInfo('url'));
        $this->CI->utils->debug_log('=====================cashpay processPaymentUrlFormRedirect response', $response);

        if(isset($response['code']) && $response['code'] == self::RESPONSE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['payUrl'],
            );
        }
        else if(isset($response['msg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => json_encode($response['msg'],JSON_UNESCAPED_UNICODE)
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

        $this->CI->utils->debug_log("=====================cashpay callbackFrom $source params", $params);


        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================cashpay json_decode params", $params);
        }

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['merchantOrderId'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                if ($params['status'] == self::CALLBACK_SUCCESS) {
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
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
            'status', 'merchantOrderId', 'sign', 'traceId'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================cashpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================cashpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================cashpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================cashpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['merchantOrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================cashpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    public function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign' || $key == 'mp' || empty($value)) {
                continue;
            }
            $signStr .= "$key=$value|";
        }
        $signStr = rtrim($signStr, '|');
        $signStr .= $this->getSystemInfo('key');
        return $signStr;
    }

    public function callbackCreateSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }

        $signStr .= 'key='.$this->getSystemInfo('md5key');
        return md5($signStr);
    }

    public function validateSign($params) {
        $sign = $this->callbackCreateSignStr($params);
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 0, '.', '') ;
    }

    protected function processCurl($params, $url) {
        $ch = curl_init();

        $token = base64_encode($this->getSystemInfo('account').':'.$this->getSystemInfo('key'));;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Basic '.$token
            )
        );

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['merchantOrderId']);
        $response = json_decode($response, true);

        $this->CI->utils->debug_log('=====================cashpay processCurl decoded response', $response);
        return $response;
    }
}