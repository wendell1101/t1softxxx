<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ONEGOPAY
 *
 * * ONEGOPAY_ALIPAY_PAYMENT_API, ID: 5230
 * * ONEGOPAY_ALIPAY_H5_PAYMENT_API, ID: 5231
 * * ONEGOPAY_PAYMENT_API, ID:5774
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: https://www.onegopay.com/api/transaction
 * * Key: ## Access Token ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_onegopay extends Abstract_payment_api {

    const CALLBACK_SUCCESS = 'success';
    const RETURN_SUCCESS_CODE = 'ok';


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
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'no firstName';
        $lastname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName']      : '';
        $paid_name = (!empty($lastname))? $firstname.$lastname : $firstname; //for ole777cn

        $params = array();
        $params['amount']       = $this->convertAmountToCurrency($amount);
        $params['out_trade_no'] = $order->secure_id;
        $params['notify_url']   = $this->getNotifyUrl($orderId);
        $params['return_url']   = $this->getReturnUrl($orderId);
        $params['paid_name']    = $paid_name;
        $this->configParams($params, $order->direct_pay_extra_info);
        $this->CI->utils->debug_log('=====================onegopay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    # Implement processPaymentUrlForm
    protected function processPaymentUrlFormQRCode($params) {
        $response = $this->processCurl($params);

        if(isset($response['uri'])) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['out_trade_no']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['trade_no']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $response['uri'],
            );
        }
        else if(isset($response['message'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $response['message']
            );
        }
        else if($response) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $response
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

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->processCurl($params);

        if(isset($response['uri'])) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['out_trade_no']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['trade_no']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['uri'],
            );
        }
        else if(isset($response['message'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $response['message']
            );
        }
        else if($response) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $response
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
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

        $this->CI->utils->debug_log("=====================onegopay callbackFrom $source params", $params);

        if($source == 'server' ){
            $raw_post_data = file_get_contents('php://input');
            $this->CI->utils->debug_log("=====================onegopay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================onegopay json_decode params", $params);



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
            $this->CI->sale_order->updateExternalInfo($order->id, null, null, null, null, $response_result_id);
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
            'amount', 'out_trade_no', 'status'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================onegopay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================onegopay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================onegopay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================onegopay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['out_trade_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================onegopay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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

        $body = file_get_contents('php://input');
        $sign = base64_encode(hash_hmac('sha256', $body, $this->getSystemInfo('key'), true));

        $headers = $this->CI->input->request_headers();
        $hmac = isset($headers['X-Gopay-Signature']) ? $headers['X-Gopay-Signature'] : FALSE;

        if($hmac){
            if(hash_equals($sign, $hmac)){
                return true;
            }
            else{
                if($this->getSystemInfo('recheck', true)){
                    $this->writePaymentErrorLog("=======================onegopay verify signature rechecking signature is [$hmac], expected [$sign].");
                     #the broken sign would contain a space
                    $sub = explode(" ", $hmac); #seperate the signature by space
                    if(sizeof($sub) == 2){ #if the signature contains exactly a space
                        if((strpos($sign, $sub[0]) !== false) && (strpos($sign, $sub[1]) !== false)) { #check if the rest parts can match
                            return true;
                        }
                    }
                    return false;
                }
            }
        }
        else{
            $this->writePaymentErrorLog("===================onegopay Validating Signing can not find headers X-Gopay-Signature", $headers);
            return false;
        }
    }

    # -- Private functions --
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    protected function processCurl($params) {
        $ch = curl_init();
        $url = $this->getSystemInfo('url');
        $token = $this->getSystemInfo('key');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token)
        );

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['out_trade_no']);


        $this->CI->utils->debug_log('=====================onegopay processCurl response', $response);
        $response = json_decode($response, true);

        $this->CI->utils->debug_log('=====================onegopay processCurl decoded response', $response);
        return $response;
    }
}