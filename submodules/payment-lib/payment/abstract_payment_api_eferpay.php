<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * EFERPAY
 *
 * * EFERPAY_BANKCARD_PAYMENT_API, ID: 5191
 * * EFERPAY_WITHDRAWAL_PAYMENT_API, ID: 5192
 * * EFERPAY_QUICKPAY_PAYMENT_API, ID: 5212
 *
 * Required Fields:
 * * Account
 * * Key
 * * Secret
 * * URL
 *
 * Field Values:
 * * Account: ## APP ID ##
 * * Key: ## APP KEY ##
 * * Secret: ## APP SECRET ##
 * * URL: https://www.eferpay.com/
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_eferpay extends Abstract_payment_api {

    const RESULT_CODE_SUCCESS = 0;
    const CALLBACK_SUCCESS = 3;

    const RETURN_SUCCESS_CODE = 'success';

    protected $login_url;
    protected $confpay_url;
    protected $deposit_url;

    public function __construct($params = null) {
        parent::__construct($params);
        $this->login_url = $this->getSystemInfo('login_url', 'https://www.eferpay.com/oss/auth/login');
        $this->confpay_url = $this->getSystemInfo('confpay_url', 'https://www.eferpay.com/oss/wallet/confpay_order');
        $this->deposit_url = $this->getSystemInfo('url');
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        #----Login----
            $response = $this->processCurl($this->login_url, null, $order->secure_id);
            if(isset($response['code']) && $response['code'] == self::RESULT_CODE_SUCCESS){
                $token   = $response['data']['user_token'];
                $expired = $response['data']['expired'];
            }
            else if(isset($response['code']) && $response['code'] > self::RESULT_CODE_SUCCESS){
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => lang('EFERPAY Login failed').': ['.$response['code'].']'.$response['msg']
                );
            }
            else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => lang("EFERPAY Login failed for unknown reason")
                );
            }

        #----Create Order----
            $params = array();
            $params['order_money']    = $this->convertAmountToCurrency($amount);
            $this->configParams($params, $order->direct_pay_extra_info);
            $params['order_trade_sn'] = $order->secure_id;
            $params['order_notify']   = urlencode($this->getNotifyUrl($orderId));
            $response = $this->processCurl($this->deposit_url, $params, $order->secure_id, $token);

            if(isset($response['code']) && $response['code'] == 0){
                $this->CI->sale_order->updateExternalInfo($order->id, $response['data']['order_sn']);
                return $this->processPaymentUrlForm($response['data']);
            }
            else if(isset($response['code']) && $response['code'] > 0){
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => lang('EFERPAY Create order failed').': ['.$response['code'].']'.$response['msg']
                );
            }
            else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => lang("EFERPAY Create order failed")
                );
            }
    }

    protected function handlePaymentFormResponse($handle) {
        $success = true;
        $data = array();
        $button = '<input type="text" class="form-control" id="external_data">
        <button type="button" class="btn btn-primary" id="setExternalApi_btn">'.lang("Submit").'</button>';

        $data['External Order']      = $handle['order_sn'];
        $data['Beneficiary Bank']    = $handle['card_bankname'];
        $data['Beneficiary Account'] = $handle['card_number'];
        $data['Beneficiary Name']    = $handle['card_realname'];
        $data['cashier.54']          = $button;

        $this->CI->utils->debug_log("=====================eferpay handlePaymentFormResponse params", $data);

        $collection_text_transfer = '';
        $collection_text = $this->getSystemInfo("collection_text_transfer", array(''));
        if(is_array($collection_text)){
            $collection_text_transfer = $collection_text;
        }
        $is_not_display_recharge_instructions = $this->getSystemInfo('is_not_display_recharge_instructions');

        return array(
            'success' => $success,
            'type' => self::REDIRECT_TYPE_STATIC,
            'setExternalApi_btn' => true,
            'data' => $data,
            'collection_text_transfer' => $collection_text_transfer,
            'is_not_display_recharge_instructions' => $is_not_display_recharge_instructions
        );
    }

    protected function processPaymentUrlFormRedirect($handle) {
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_URL,
            'url' => $handle['order_qrcode'],
        );
    }

    #player center page, click #setExternalApi_btn
    public function setExternalApiValueByOrder($saleOrder, $bank_order_id){
        $this->CI->sale_order->updateExternalInfo($saleOrder->id, null, $bank_order_id);
        $this->CI->utils->debug_log("=====================eferpay setExternalApiValueByOrder bank_order_id", $bank_order_id);
        return $this->setBankOrderId($saleOrder, $bank_order_id);
    }

    #incase player close deposit page before enter bank_order_id
    public function checkDepositStatus($secureId, $bank_order_id = null) {
        $saleOrder = $this->CI->sale_order->getSaleOrderBySecureId($secureId);
        if(!is_null($saleOrder->bank_order_id)){
            $result = $this->setBankOrderId($saleOrder, $bank_order_id);
            if($result['success'] == true){
                $this->CI->sale_order->updateExternalInfo($saleOrder->id, null, $bank_order_id);
            }
            return $result;
        }
        $this->CI->sale_order->updateExternalInfo($saleOrder->id, null, $bank_order_id);
        return $this->setBankOrderId($saleOrder, $bank_order_id);
    }

    public function setBankOrderId($saleOrder, $bank_order_id) {
        #----Login----
        $response = $this->processCurl($this->login_url, null, $saleOrder->secure_id);
        $success = false;
        if(isset($response['code']) && $response['code'] == self::RESULT_CODE_SUCCESS){
            $token   = $response['data']['user_token'];
            $expired = $response['data']['expired'];
            #----Confirm Order----
                $confs = array();
                $confs['order_sn']     = $saleOrder->external_order_id;
                $confs['order_pay_sn'] = $bank_order_id;

                $response = $this->processCurl($this->confpay_url, $confs, $saleOrder->secure_id, $token);
                if(isset($response['code']) && $response['code'] == self::RESULT_CODE_SUCCESS){
                    $success = true;
                    $message = $response['msg'];
                }
                else if(isset($response['code']) && $response['code'] > 0){
                    $message = lang('EFERPAY confirm failed').': ['.$response['code'].']'.$response['msg'];
                }
                else{
                    $message = lang("EFERPAY Login failed for unknown reason");
                }
            #----Confirm Order----
        }
        else if(isset($response['code']) && $response['code'] > 0){
            $message = lang('EFERPAY Login failed').': ['.$response['code'].']'.$response['msg'];
        }
        else{
            $message = lang("EFERPAY Login failed for unknown reason");
        }
        #----Login----
        return array('success' => $success, 'message' => $message);
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
        $this->CI->utils->debug_log("=====================eferpay callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================eferpay raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data, true);
                $this->CI->utils->debug_log("=====================eferpay json_decode params", $params);
            }

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
            $this->CI->sale_order->updateExternalInfo($order->id, $fields['order_sn'], null, null, null, $response_result_id);
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
            'order_state', 'order_trade_sn', 'order_money'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================eferpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================eferpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['order_state'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================eferpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['order_money'] != $this->convertAmountToCurrency($order->amount)) {
            #because player need to enter amount at Alipay
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $this->CI->utils->debug_log('=====================eferpay amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['amount'], $notes);

            }
            else{
                $this->writePaymentErrorLog("=====================eferpay Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['order_trade_sn'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================eferpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    protected function processCurl($url, $params, $secure_id, $token = null) {

        $sign_item = array(
            'api_version' => '1.0.0',
            'app_id'      => $this->getSystemInfo('account'),
            'app_secret'  => $this->getSystemInfo('secret'),
            'spbill_ip'   => '0.0.0.0',
        );
        if(!is_null($token)){
            $sign_item['user_token'] = $token;
            $sign_item = array_merge($sign_item, $params);
        }
        $headers = array(
            "cache-control: no-cache",
            "content-type: application/x-www-form-urlencoded",
            "api-version: 1.0.0",
            "app-id: ".$this->getSystemInfo('account'),
            "app-secret: ".$this->getSystemInfo('secret'),
            "spbill-ip: 0.0.0.0",
            "sign: ". $this->sign($sign_item),
        );


        $ch = curl_init();
        if(!is_null($token)){
            $headers[] = "user-token: ". $token;
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeoutSecond());
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('=====================eferpay processCurl url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $this->CI->utils->debug_log('=====================eferpay processCurl response', $response);
        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $secure_id);
        $response = json_decode($response, true);
        return $response;
    }

    # -- signing --
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(sha1($signStr));
        return $sign;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return $signStr.$this->getSystemInfo('key');
    }

    protected function validateSign($params) {
        $keys = array('app_id', 'order_sn', 'order_trade_sn', 'order_money', 'order_state');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                if($key == "order_money"){
                    $signStr .= $key.'='.$this->convertAmountToCurrency($params[$key]).'&';
                }
                else{
                    $signStr .= $key.'='.$params[$key].'&';
                }
            }
        }

        $sign = sha1($signStr.$this->getSystemInfo('key'));
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }
}