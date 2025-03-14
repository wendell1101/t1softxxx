<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * NEWSPAY
 * *
 * * NEWSPAY_PAYMENT_API, ID: 5984
 * * NEWSPAY_WITHDRAWAL_PAYMENT_API, ID: 5985
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Token
 *
 * Field Values:
 * * URL: https://onepay.news/api/v1/order/receive
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 * * Token: ## Token ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_newspay extends Abstract_payment_api {

    const RESULT_CODE_SUCCESS = 200;
    const CALLBACK_SUCCESS_CODE = 2;
    const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAIL_CODE = 'fail';

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
        $playerDetails = $this->getPlayerDetails($playerId);
        $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName'] : 'no firstName';
        $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName'] : 'no lastName';
        $phone      = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '8615551234567';
        $email      = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email'] : 'sample@example.com';
        $pixNumber = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';

        $randomNumber = $this->uuid();

        $params = array();
        $params['orderNo']       = $order->secure_id;
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['amount']        = $this->convertAmountToCurrency($amount);
        $params['notifyUrl']     = urlencode($this->getNotifyUrl($orderId));
        $params['uid']           = $playerId;
        $params['customerName']  = $lastname.$firstname;
        $params['customerEmail'] = $email;
        $params['customerPhone'] = $phone;
        $params['nonce']         = $randomNumber;
        $params['memberExpand1'] = $pixNumber;
        $params['memberExpand2'] = $pixNumber;
        $params['remark']        = 'Deposit';

        $this->CI->utils->debug_log('=====================newspay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormRedirect($params) {

        $url = $this->getSystemInfo('url');
        $response = $this->processCurl($params, $url);

        if(isset($response['code']) && !empty($response['code'])) {
            if($response['code'] == self::RESULT_CODE_SUCCESS){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['data']['payUrl'],
                );
            }
        }
        else if(isset($response['message']) && !empty($response['message'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $response['message'],
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => lang('Invalidte API response')
            );
        }
    }

    protected function processCurl($params, $url) {
        $ch = curl_init();
        $token = $this->getSystemInfo("account");
        $postJsonData['data'] = $this->encryptionAes($params);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postJsonData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: '.$token)
        );

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $postJsonData, 'postJsonData', $params, 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['orderNo']);
        $response = json_decode($response, true);

        $this->CI->utils->debug_log('=====================newspay processCurl decoded response', $response);
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
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $decryptParams = $this->decryptAes($params['data']);

        $this->CI->utils->debug_log("=====================newspay callbackFrom $source params", $params);

        $this->CI->utils->debug_log("=====================newspay callbackFrom $source decryptParams", $decryptParams);

        if($source == 'server'){
            if (!$order || !$this->checkCallbackOrder($order, $decryptParams, $processed)) {
                return $result;
            }
        }

        # Update order payment status and balance
        $success = true;

        # Update player balance based on order status
        # if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $decryptParams);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            # update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $decryptParams['merchantNo'], '', null, null, $response_result_id);
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
            'merchantNo', 'amount', 'status', 'orderNo', 'status'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================newspay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass


        if ($fields['status'] != self::CALLBACK_SUCCESS_CODE) {
            $this->writePaymentErrorLog("======================newspay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            #because player need to enter amount at Alipay
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $this->CI->utils->debug_log('=====================newspay amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['amount'], $notes);

            }
            else{
                $this->writePaymentErrorLog("======================newspay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['merchantNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================newspay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    public function encryptionAes($params)
    {
        $jsonData  = json_encode($params,true);
        $passWord  = $this->getSystemInfo('key');
        $aesSecret = bin2hex(openssl_encrypt($jsonData, 'AES-128-CBC', $passWord,  OPENSSL_RAW_DATA, $passWord));
        return $aesSecret;
    }

    public function decryptAes($aesSecret = '')
    {
        $data = '';
        $passWord  = $this->getSystemInfo('key');
        if(!empty($aesSecret)){
            $str="";
            for($i=0;$i<strlen($aesSecret)-1;$i+=2){
                $str.=chr(hexdec($aesSecret[$i].$aesSecret[$i+1]));
            }
            $jsonData =  openssl_decrypt($str, 'AES-128-CBC', $passWord, OPENSSL_RAW_DATA, $passWord);
            $data = json_decode($jsonData,true);
            return $data;
        }else{
            return $data;
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
        return number_format($amount*100, 0, '.', '');
    }

    public function uuid(){
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s', str_split(bin2hex($data), 4));
    }

    public function getPlayerDetails($playerId) {
        $this->CI->load->model(array('player_model'));
        $player = $this->CI->player_model->getPlayerDetails($playerId);
        return $player;
    }
}