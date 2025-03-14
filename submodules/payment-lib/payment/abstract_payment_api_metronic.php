<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * METRONIC
 *
 * * METRONIC_PAYMENT_API, ID: 6290
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_metronic extends Abstract_payment_api {
    const RESULT_CODE_SUCCESS = true;
    const CALLBACK_SUCCESS     = true;
    const RETURN_SUCCESS_CODE  = 'success';

    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);
    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null){}
    protected function processPaymentUrlFormPost($params) {}

    public function manualPaymentUrlForm($saleOrder, $playerId, $amount, $orderDateTime, $payment_account_id = null, $playerBankDetailsId = null) {

        $this->CI->load->model(array('payment_account', 'sale_orders_notes'));
        $paymentAccount = $this->CI->payment_account->getPaymentAccount($payment_account_id);
		$playerDepositBankList = $this->CI->playerbankdetails->getPlayerDepositBankList($playerId);
		$playerBankCode = null;
		$playerBankAccountNumber = null;
        $playerBankAccountName = null;
        $company_bank_code =null;

        $company_bank_code = $this->mappingBankCodeById($this->getSystemInfo('convert_to_collection_bank_code'), $paymentAccount->id);
        if (empty($company_bank_code)) {
            $this->CI->utils->debug_log("=====================bankCode is not support by metronic");
            return;
        }

        if (is_array($playerDepositBankList)){
            foreach ($playerDepositBankList as $key => $value) {
                if($playerDepositBankList[$key]['playerBankDetailsId'] == $playerBankDetailsId){
                    $playerBankCode = $playerDepositBankList[$key]['bank_code'];
                    $playerBankAccountNumber = $playerDepositBankList[$key]['bankAccountNumber'];
                    $playerBankAccountName = $playerDepositBankList[$key]['bankAccountFullName'];
                }
            }
        }else{
            $this->CI->utils->debug_log("=====================metronic playerDepositBankList is not array",$playerDepositBankList);
            return;
        }
        
        $playerDetails = $this->getPlayerDetails($playerId);
        $username = (isset($playerDetails[0]) && !empty($playerDetails[0]['username'])) ? $playerDetails[0]['username'] : 'no username';

        $params = array();
        $params['player_bank_code']            = empty($playerBankCode) ? 'none' : $playerBankCode;
        $params['player_bank_account_number']  = empty($playerBankAccountNumber) ? 'none' : $playerBankAccountNumber;
        $params['player_bank_account_name']    = empty($playerBankAccountName) ? 'none' : $playerBankAccountName;
        $params['company_bank_code']           = empty($company_bank_code) ? 'none' : $company_bank_code;
        $params['company_bank_account_number'] = $paymentAccount->payment_account_number;
        $params['company_bank_account_name']   = $paymentAccount->payment_account_name;
        $params['amount']                      = $this->convertAmountToCurrency($amount);
        $params['transaction_date']            = date("Y/m/d");
        $params['username']                    = $username;
        $params['order_id']                    = $saleOrder['secure_id'];

        $this->CI->utils->debug_log("=====================metronic manualPaymentUrlForm params", $params);
    
        $url = $this->getSystemInfo('url');
        $encrypt_params = $this->encrypt(urlencode(http_build_query($params)));
        $encryptData = array();
        $encryptData['s'] = $encrypt_params;
        $this->CI->utils->debug_log("=====================metronic manualPaymentUrlForm encrypt_params", $encryptData);
        
        $response = $this->submitPostForm($url, $encryptData, false, $params['order_id']);

        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('=====================metronic processPaymentUrlFormURL response to json', $decode_data);

        if($decode_data['success'] == self::RESULT_CODE_SUCCESS) {
            $message = 'send to metronic, succeeded';
        }else{
            $message = 'send to metronic, failed';
        }

        $this->CI->sale_orders_notes->add($message,Users::SUPER_ADMIN_ID,Sale_orders_notes::ACTION_LOG,$saleOrder['id']);
        return;
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

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->CI->utils->debug_log('=====================metronic getOrderIdFromParameters flds', $flds);  
        $raw_post_data = file_get_contents('php://input', 'r');
        $flds = json_decode($raw_post_data, true);

        if (isset($flds['order_id'])) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($flds['order_id']);
            return $order->id;
        }
        else {
            $this->utils->debug_log('=====================metronic callbackOrder cannot get any order_id when getOrderIdFromParameters', $flds);
            return;
        }
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $raw_post_data = file_get_contents('php://input', 'r');
        $params = json_decode($raw_post_data, true);
        
        $this->CI->utils->debug_log("=====================metronic callbackFrom $source params", $params);

        if($source == 'server' ){
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        if ($order->status == Sale_order::STATUS_SETTLED || $order->status == Sale_order::STATUS_DECLINED) {
            $msg = "=====================metronic callbackFromServer the order status is already updated.";
            $this->writePaymentErrorLog($msg, $params);
            $this->CI->utils->debug_log($msg);
			$result['return_error_msg'] = self::RETURN_SUCCESS_CODE;
            return $result;
        }

        if ($params['order_status'] != self::CALLBACK_SUCCESS) {
			$this->CI->sale_order->setStatusToDeclined($orderId);
			$this->writePaymentErrorLog("=====================metronic callbackFromServer status is failed. set to decline", $params);
            $this->CI->utils->debug_log("=====================metronic callbackFromServer status is failed. set to decline");
            
            //action log
            $message = 'metronic callback status is failed. set to decline';
            $this->CI->sale_orders_notes->add($message,Users::SUPER_ADMIN_ID,Sale_orders_notes::ACTION_LOG,$orderId);

			$result['return_error_msg'] = self::RETURN_SUCCESS_CODE;
			return $result;
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['order_id'], '', null, null, $response_result_id);
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


    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'api_key', 'username', 'order_id', 'order_status', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================metronic checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================metronic checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['api_key'] != $this->getSystemInfo('key')) {
            $this->writePaymentErrorLog("======================metronic checkCallbackOrder api_key do not match");
            return false;
        }

        if ($fields['order_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================metronic checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
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
        return number_format($amount, 2, '.', '');
    }

    public function getPlayerDetails($playerId) {
        $this->CI->load->model(array('player_model'));
        $player = $this->CI->player_model->getPlayerDetails($playerId);
        return $player;
    }

    public function mappingBankCodeById($mappingCodeArr, $id){
        $bankCode = null;
		if(is_array($mappingCodeArr)){
            foreach ($mappingCodeArr as $code => $mappingId) {
                if (is_array($mappingId)){
                    if(in_array($id, $mappingId)){
                        $bankCode = $code;
                        $this->CI->utils->debug_log("====================mappingBankCodeById", $bankCode);
                        return $bankCode;
                    }
                } else {
                    return null;
                }
            }
        }else{
        	return null;
        }
	}

    # -- 加密 --
	protected function encrypt($data){
        $iv = $this->getSystemInfo('metronic_iv');
        $key = $this->getSystemInfo('key');

        $encrypted = openssl_encrypt($data, 'DES-CBC', $key, OPENSSL_RAW_DATA, $iv);
		return base64_encode($encrypted);
	}

    # -- 解密 --
    private function createSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        return md5($signStr);
    }

    private function validateSign($params) {
        $sign = $this->createSign($params);
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

}