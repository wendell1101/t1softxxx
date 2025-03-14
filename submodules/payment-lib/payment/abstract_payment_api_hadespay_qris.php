<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * hadespay qris
 *
 * * HADESPAY_VIRTUAL_ACCOUNT_PAYMENT_API, ID: 6596
 * * HADESPAY_QRIS_PAYMENT_API, ID: 6597
 * * HADESPAY_EWALLET_PAYMENT_API, ID: 6598
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_hadespay_qris extends Abstract_payment_api {
    const RESPONSE_SUCCESS       = 200;
    const RESPONSE_FAIL          = 500;
    const RESPONSE_PROCESSING    = 404;         
    const CALLBACK_SUCCESS       = 'success';
    const RETURN_SUCCESS_CODE    = 'SUCCESS';
    const RETURN_FAIL_CODE       = 'FAIL';

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
        $params['reference']      = $order->secure_id;
        $params['amount']         = (float)$this->convertAmountToCurrency($amount);
        $params['expiryMinutes']  = $this->getSystemInfo('expiryMinutes');
        $params['viewName']       = $playerId;
        $params['additionalInfo'] = array(
            "callback" => $this->getNotifyUrl($orderId),
        );

        $this->configParams($params, $order->direct_pay_extra_info);

        $this->CI->utils->debug_log('=====================hadespay_qris generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->processCurl($params, $this->getSystemInfo('url'));
        $this->CI->utils->debug_log('=====================hadespay_qris processPaymentUrlFormRedirect response', $response);

        if(isset($response['responseCode']) && $response['responseCode'] == self::RESPONSE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'base64' => $response['responseData']['qris']['image'],
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

        $this->CI->utils->debug_log("=====================hadespay_qris callbackFrom $source params", $params);


        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================hadespay_qris json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['responseData']['id'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                if ($params['responseData']['status'] == self::CALLBACK_SUCCESS) {
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['return_error'] = self::RETURN_FAIL_CODE;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'status', 'merchantRef'
        );

        // foreach ($requiredFields as $f) {
        //     if (!array_key_exists($f, $fields)) {
        //         $this->writePaymentErrorLog("=====================hadespay_qris checkCallbackOrder Missing parameter: [$f]", $fields);
        //         return false;
        //     }
        // }

        // # is signature authentic?
        // if (!$this->validateSign($fields)) {
        //     $this->writePaymentErrorLog('=====================hadespay_qris checkCallbackOrder Signature Error', $fields);
        //     return false;
        // }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['responseData']['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================hadespay_qris checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['responseData']['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================hadespay_qris checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['responseData']['merchantRef'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================hadespay_qris checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $this->CI->utils->debug_log('=====================hadespay_qris sign signStr', $signStr);
        $sign = hash_hmac('sha512', $signStr, $this->getSystemInfo('token'));
        return $sign;
    }

    public function createSignStr($params) {
        $bodyJson = json_encode($params, JSON_UNESCAPED_UNICODE);
        // $bodyJson = str_replace('/', '\\/', $bodyJson);
        $signStr = $this->getSystemInfo('key'). $bodyJson;
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

        $key = $this->getSystemInfo('key');
        $token = $this->getSystemInfo('token');
        $signature = $this->sign($params);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            'On-Key: '. $key,
            'On-Token: '. $token,
            'On-Signature: '. $signature,
          )
        );

        // $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        $this->CI->utils->debug_log('=====================hadespay_qris', $key, $token, $signature, $params);
        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['reference']);
        $response = json_decode($response, true);

        $this->CI->utils->debug_log('=====================hadespay_qris processCurl decoded response', $response);
        return $response;
    }
}