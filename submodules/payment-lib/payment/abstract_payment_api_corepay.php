<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * corepay
 *
 * * COREPAY_PAYMENT_API, ID: 6250
 * * COREPAY_BANK_PAYMENT_API , ID: 6252
 * * COREPAY_TRUEMONEY_PAYMENT_API , ID: 6253
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://vippay.corepaypro.com/trade/repay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
abstract class Abstract_payment_api_corepay extends Abstract_payment_api {

    const REPONSE_CODE_SUCCESS = '0';
    const CHANNEL_PROMPTPAY = 'PROMPTPAY';
    const CHANNEL_BANK = 'BANK';
    const CHANNEL_TRUEMONEY = 'TRUEMONEY';
    const CHANNEL_BIND_PROMPTPAY = 'BIND-PROMPTPAY';

    const RETURN_SUCCESS_CODE  = 'SUCCESS';
    const CALLBACK_SUCCESS = 1;

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json','charset:Utf-8');
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
       
        $unSignParams = array();
        $unSignParams['mch_order_no'] = $order->secure_id;
        $unSignParams['amount']       = $this->convertAmountToCurrency($amount);
        $this->configParams($unSignParams, $order->direct_pay_extra_info);
        $unSignParams['mch_id']       = $this->getSystemInfo("account");
        $unSignParams['appid']        = $this->getSystemInfo("app_id");
        $unSignParams['repay_url']    = $this->getReturnUrl($orderId);
        $signStr = $this->encrypt($unSignParams);

        $params = array();
        $params['mch_order_no'] = $order->secure_id;
        $params['data'] = array (
               'partner_key' => $this->getSystemInfo("key"),
               'en_data' => $signStr
        );

        $this->CI->utils->debug_log("=====================corepay  generatePaymentUrlForm unSignParams", $unSignParams);
        $this->CI->utils->debug_log("=====================corepay  generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {
        $orderId = $params['mch_order_no'];
        unset($params['mch_order_no']);
        $this->CI->utils->debug_log("=====================corepayparams", $params);
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $orderId);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================corepay processPaymentUrlFormPost response json to array', $response);

        $msg = lang('Invalidate API response');
        if( isset($response['code']) && $response['code'] == self::REPONSE_CODE_SUCCESS ){
            if(isset($response['data']['redirect']) && !empty($response['data']['redirect'])){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['data']['redirect']
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => $msg
                );
            }
        }else {
            if(isset($response['message']) && !empty($response['message'])) {
                $msg = $response['message'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $msg
            );
        }
    }

      # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->CI->utils->debug_log('=====================corepay getOrderIdFromParameters flds', $flds);

        if(empty($flds)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $flds = json_decode($raw_post_data ,true);
            $this->utils->debug_log('======corepay getOrderIdFromParameters raw_post flds ' , $flds);
        }

        if(isset($flds['data']['mch_order_no'])) {

            if(substr($flds['data']['mch_order_no'],0,1) == 'D'){
                $order = $this->CI->sale_order->getSaleOrderBySecureId($flds['data']['mch_order_no']);
                return $order->id;
            }else{
                $transId = $flds['data']['mch_order_no'];
                $this->CI->load->model(array('wallet_model'));
                $walletAccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
                return $walletAccount['transactionCode'];
            }
        }
        else {
            $this->utils->debug_log('=====================corepay getOrderIdFromParameters cannot get merchantTransactionRef', $flds);
            return;
        }
    }

    ## This will be called when the payment is async, API server calls our callback page
    ## When that happens, we perform verifications and necessary database updates to mark the payment as successful
    ## Reference: sample code, callback.php
    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    ## This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================corepay callbackFrom $source params", $params);

        if($source == 'server' ){

            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================corepay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================corepay json_decode data", $params);    
            
            if(substr($params['data']['mch_order_no'] , 0, 1) == 'W'){
                $result = $this->isWithdrawal($params, $params['data']['mch_order_no']);
                $this->CI->utils->debug_log('=======================corepay callbackFrom mch_order_no', $params['data']['mch_order_no']);
                return $result;
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['data']['mch_order_no'], '', null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    #For Withdrawal
    public function isWithdrawal($params, $orderId){
        $result = array('success' => false, 'message' => 'Payment failed');
        $processed = false;

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($orderId);
        $this->CI->utils->debug_log("=====================corepay isWithdrawal order", $order);

        $data = $this->decrypt($params);
        $this->CI->utils->debug_log("=====================corepay isWithdrawal data", $data);

        if(!$data){
            $msg = sprintf("corepay withdrawal payment decrypt fail: trade ID =%s", $params);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
            return $result;
        }    

        if (!$this->checkCallbackWithdrawalOrder($order, $data, $processed)) {
            return $result;
        }

        if ($data['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('corepay withdrawal success: trade ID [%s]', $data['mch_order_no']);
            $this->withdrawalSuccess($orderId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        } else {
            $msg = sprintf("corepay withdrawal payment unsuccessful or pending: status=%s", $data['status']);
            $this->writePaymentErrorLog($msg, $data);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackWithdrawalOrder($order, $fields, &$processed = false) {

        $requiredFields = array(
            'mch_order_no', 'status', 'amount'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================corepay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        $processed = true;

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================corepay withdrawal checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================corepay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['mch_order_no'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================corepay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false) {

        $checkFields = $this->decrypt($fields);
        if(!$checkFields){
            $this->writePaymentErrorLog('=====================corepay decrypt data Error', $fields);
            return false;
        }

        $requiredFields = array(
            'mch_order_no', 'status', 'amount', 'pay_time'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $checkFields)) {
                $this->writePaymentErrorLog("=====================corepay checkCallbackOrder Missing parameter: [$f]", $checkFields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($checkFields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================corepay checkCallbackOrder Payment status is not success", $checkFields);
            return false;
        }

        if ($checkFields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================corepay Payment amounts do not match, expected [$order->amount]", $checkFields);
            return false;
        }

        if ($checkFields['mch_order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================corepay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $checkFields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    # Reference: PHP Demo
    public function encrypt($params) {
        $timeStamp = time();
        $signStr = $this->createSignStr($params ,$timeStamp);
        $params['sign'] = $signStr;
        $params['timestamp'] = $timeStamp;

        $key = $this->getSystemInfo('corepay_key');
        $iv = $this->getSystemInfo('corepay_iv');
        $encrypted = openssl_encrypt(json_encode($params), 'AES-128-CBC', $key, 0, $iv);
        return $encrypted;
    }

    public function createSignStr($params ,$timeStamp) {
        ksort($params);
        $signStr = $this->getSystemInfo('secret') . '*|*' . json_encode($params) . '@!@' . $timeStamp;
        return md5(md5($signStr));
    }

    public function decrypt($params) {
        $data = $params['data']['en_data'];
        $key = $this->getSystemInfo('corepay_key');
        $iv = $this->getSystemInfo('corepay_iv');
        $decrypted = openssl_decrypt(json_encode($data), 'AES-128-CBC', $key, 0, $iv);
        return json_decode($decrypted,true);
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## After payment is complete, the gateway will send redirect back to this URL
    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount) {
        return floatval(number_format($amount, 2, '.', ''));
    }

}