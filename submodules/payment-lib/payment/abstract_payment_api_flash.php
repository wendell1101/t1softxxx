<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * flash
 *
 * * FLASH_ALIPAY_PAYMENT_API, ID: 5505
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_flash extends Abstract_payment_api {

    const CALLBACK_STATUS_SUCCESS = 'TRADE_FINISHED';
    const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '1';

    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement these for specific pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'flash_pub_key', 'flash_priv_key');
        return $secretsInfo;
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : 'no firstName';

        $params['amount'] = $this->convertAmountToCurrency($amount); //元
        $params['client_id'] = $this->getSystemInfo("account");
        $params['client_order_id'] = $order->secure_id;
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['currency'] = 'CNY';
        $params['description'] = 'Deposit';
        $params['lifetime'] = '300';
        $params['return_url'] = $this->getReturnUrl($orderId);
        $params['callback_url'] = $this->getNotifyUrl($orderId);
        $params['user_identity'] = $firstname;
        $params['sign_data'] = $this->sign($params);
        $this->CI->utils->debug_log("=====================flash generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        // 1: acquire access token first
        $get_token_params['client_id'] = $this->getSystemInfo("client_id");
        $get_token_params['client_secret'] = $this->getSystemInfo('key');
        $get_token_params['grant_type'] = 'client_credentials';
        $response_token = $this->submitPostForm($this->getSystemInfo('token_url'), $get_token_params, false, $params['client_order_id']);
        $response_token = json_decode($response_token,true);
        $this->utils->debug_log('=====================flash response_token', $response_token);
        $response = $this->processCurl($params, $response_token);
        $msg = lang('Invalidate API response');
        if(isset($response['data']['url']) && !empty($response['data']['url'])) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data']['url'],
            );
        }else {
            if(isset($response['error']) && !empty($response['error']) && is_array($response['error'])) {
                $msg = json_encode($response['error']);
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
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

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
            if(empty($params)){
                $raw_post_data = file_get_contents('php://input', 'r');
                $params = json_decode($raw_post_data, true);
            }
            $this->CI->utils->debug_log('=======================flash callbackFromServer server callbackFrom', $params);
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        # Update order payment status and balance
        $success=true;

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['client_order_id'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($success) {
            $return_message = array('status'=>self::RETURN_SUCCESS_CODE);
            $result['message'] = json_encode($return_message);
        } else {
            $result['message'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {

        $requiredFields = array('id', 'amount','client_order_id','status','sign_data');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================flash missing parameter: [$f]", $fields);
                return false;
            }
        }
        if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['amount'] ) ) {
            $this->writePaymentErrorLog("=====================flash Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['client_order_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================flash checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }
        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================flash checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        if ($fields['status'] != self::CALLBACK_STATUS_SUCCESS) {
            $this->writePaymentErrorLog('=======================flash checkCallbackOrder payment was not successful', $fields);
            return false;
        }


        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- Private functions --
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 0, '.', '');
    }

    public function sign($data){
        if (is_array($data)) {
            $joinData = implode('|', $data);
        } else {
            $joinData = $data;
        }
        $privateKeyId = $this->getPrivKey();
        openssl_sign($joinData, $signature, $privateKeyId, 'sha1WithRSAEncryption');
        return $this->strToHex($signature);
    }

    public function verifySignature($data)
    {
        $signature = $this->hexToStr($data['sign_data']);
        $publicKeyId = $this->getPubKey();
        unset($data['sign_data']);
        if (is_array($data)) {
            $joinData = implode('|', $data);
        } else {
            $joinData = $data;
        }
        $result = openssl_verify($joinData, $signature, $publicKeyId, 'sha1WithRSAEncryption');

        return $result;
    }

    public function strToHex($string)
    {
        $hex = '';
        for ($i = 0; $i < strlen($string); $i++) {
            $ord = ord($string[$i]);
            $hexCode = dechex($ord);
            $hex .= substr('0' . $hexCode, -2);
        }
        return strToUpper($hex);
    }

    public static function hexToStr($hex)
    {
        $string = '';
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        }
        return $string;
    }

    public function processCurl($params, $response_token) {
        $ch = curl_init();
        $url = $this->getSystemInfo('url');
        $token = $response_token['access_token'];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.$token
        ];
        $this->CI->utils->debug_log(__METHOD__, 'headers', $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['client_order_id']);


        $this->CI->utils->debug_log('=====================flash processCurl response', $response);
        $response = json_decode($response, true);

        $this->CI->utils->debug_log('=====================flash processCurl decoded response', $response);
        return $response;
    }

    # Returns public key given by gateway
    public function getPubKey() {
        $flash_pub_key = $this->getSystemInfo('flash_pub_key');

        $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($flash_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($pub_key);
    }

    # Returns the private key generated by merchant
    public function getPrivKey() {
        $flash_priv_key = $this->getSystemInfo('flash_priv_key');

        $priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($flash_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($priv_key);
    }

}
