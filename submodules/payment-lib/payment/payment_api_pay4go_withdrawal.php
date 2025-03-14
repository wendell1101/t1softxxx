<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pay4go.php';

/**
 * pay4go取款
 *
 * * PAY4GO_WITHDRAWAL_PAYMENT_API, ID: 6072
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.pay4go.com/openApi/payout/createOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_pay4go_withdrawal extends Abstract_payment_api_pay4go {
    const PAY_RESULT_PENDING = 102;
    const PAY_RESULT_SUCCESS = 201;
    const VERIFY_RESULT_CODE_SUCCESS = 200;

	public function getPlatformCode() {
		return PAY4GO_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'pay4go_withdrawal';
	}

	# Implement abstract function but do nothing
	protected function configParams(&$params, $direct_pay_extra_info) {}

	/**
	 * detail: override common API functionsh
	 *
	 * @return void
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
	}

	public function processPaymentUrlForm($params) {
	}

	# APIs with withdraw function need to implement these methods
	## This function returns the URL to submit withdraw request to
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url');
	}

	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);

        $this->utils->debug_log("==================pay4go withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $pix_number  = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : '';
            $email     = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : '';
        }

		$params = array();
        $params['amount']               = $this->convertAmountToCurrency($amount); //元
        $params['merchantInvoiceId']    = $transId;
        $params['language']             = $this->getSystemInfo("language");
        $params['currency']             = $this->getSystemInfo("currency");
        $params['confirmationUrl']      = $this->getNotifyUrl($transId);
        $params['merchantId']           = $this->getSystemInfo("account");
        $params['targetCustomerMainId'] = $pix_number;
        $params['targetCustomerEmail']  = $email;
        $params['pixKeyType']           = $this->getSystemInfo("pixKeyType");
        $params['hash']                 = $this->sign($params);
		$this->CI->utils->debug_log('=========================pay4go withdrawal paramStr before sign', $params);
		return $params;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');
        $url = $this->getSystemInfo('url');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $this->_custom_curl_header = ["Content-Type: application/json"];
        list($content, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);
        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('======================================pay4go submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================pay4go submitWithdrawRequest decoded Result', $decodedResult);
        return $decodedResult;

    }

    public function submitPay4FunVerify($playerId, $redirectPage, $amount, $walletAccountId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $order = $this->CI->wallet_model->getWalletAccountById($walletAccountId);

        if(!empty($playerId)){
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $firstname     = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))? $playerDetails[0]['firstName'] : '';
            $lastname      = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))? $playerDetails[0]['lastName'] : '';
            $birthdate     = (isset($playerDetails[0]) && !empty($playerDetails[0]['birthdate']))? $playerDetails[0]['birthdate'] : '';
            $email         = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))? $playerDetails[0]['email'] : '';
            $contactNumber = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber']))? $playerDetails[0]['contactNumber'] : '';
            $pix_number    = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : '';
            $areacode      = substr($contactNumber, 0, 2);
            $phone         = substr($contactNumber, 2, 9);

        }

        $params = array();
        $params['amount']                               = $this->convertAmountToCurrency($amount); //元
        $params['merchantInvoiceId']                    = $order['transactionCode'];
        $params['language']                             = $this->getSystemInfo("language");
        $params['currency']                             = $this->getSystemInfo("currency");
        $params['okUrl']                                = $this->CI->utils->site_url_with_http($redirectPage);
        $params['notOkUrl']                             = $this->CI->utils->site_url_with_http($redirectPage);
        $params['confirmationUrl']                      = $this->getNotifyUrl($order['transactionCode']);
        $params['merchantId']                           = $this->getSystemInfo("account");
        $params['targetCustomerMainId']                 = $pix_number;
        $params['targetCustomerEmail']                  = $email;
        $params['fullName']                             = $lastname.' '.$firstname;
        $params['targetCustomerBirthDate']              = $birthdate;
        $params['targetCustomerPhoneNumberCountryCode'] = ($this->getSystemInfo("countrycode"))? $this->getSystemInfo("countrycode") : '55';
        $params['targetCustomerPhoneNumberAreaCode']    = $areacode;
        $params['targetCustomerPhoneNumber']            = $phone;
        $params['hash']                                 = $this->sign($params);
        $this->CI->utils->debug_log('=========================pay4go submitPay4FunVerify', $params);

        $this->_custom_curl_header = ["Content-Type: application/json"];
        $response = $this->submitPostForm($this->getSystemInfo('verifyUrl'), $params, true, $order['transactionCode']);
        $response = json_decode($response, true);

        $this->CI->utils->debug_log('=========================pay4go submitPay4FunVerify decode response', $response);

        if(isset($response['code']) && !empty($response['code']) && $response['code'] == self::VERIFY_RESULT_CODE_SUCCESS) {
            if(isset($response['url']) && !empty($response['url'])){
                return array('status' => true, 'url' => $response['url']);
            }else{
                return array('status' => false, 'msg' => lang('Invalidte API response'));
            }
        }else{
            return array('status' => false, 'msg' => lang('Invalidte API response'));
        }
    }

	public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================pay4go json_decode result", $result);
        if(!empty($result) && isset($result)){
            if(!empty($result['status']) && isset($result['status']) && $result['status'] == self::PAY_RESULT_PENDING){
                return array('success' => true, 'message' => 'pay4go withdrawal request successful.');
            }else if(isset($result['message']) && !empty($result['message'])){
                $errorMsg = $result['message'];
                return array('success' => false, 'message' => $errorMsg);
            }
            else{
                return array('success' => false, 'message' => 'pay4go withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'pay4go withdrawal exist errors');
        }
    }

    protected function convertAmountToCurrency($amount) {
        if(!empty($this->getSystemInfo("convert_amount_to_currency_unit"))){
            $convert_amount_to_currency_unit = $this->getSystemInfo("convert_amount_to_currency_unit");
        }else{
            $convert_amount_to_currency_unit = 1;
        }
        return number_format($amount *  $convert_amount_to_currency_unit, 2, '.', '');
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================pay4go process withdrawalResult order id', $transId);
        $this->CI->utils->debug_log("=========================pay4go checkCallback params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['Process'] == 'Screening'){
            $this->CI->load->model(array('walletaccount_notes'));
            if($params['Status'] == self::PAY_RESULT_PENDING) {
                $withdrawal_notes = "Pay4Fun Verify Status: Verify was successful.";
            }else {
                $withdrawal_notes = "Pay4Fun Verify Status: Verify was failed.";
            }
            $this->CI->walletaccount_notes->add($withdrawal_notes, Users::SUPER_ADMIN_ID, Walletaccount_notes::ACTION_LOG, $order['walletAccountId']);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }else if($params['Process'] == 'PayOut'){
            if($params['Status'] == self::PAY_RESULT_SUCCESS) {
                $msg = sprintf('pay4go withdrawal was successful: trade ID [%s]', $params['MerchantInvoiceId']);
                $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
                $result['message'] = self::RETURN_SUCCESS_CODE;
                $result['success'] = true;
            }
            else {
                $msg = sprintf('pay4go withdrawal was not success: [%s]', $params['Status']);
                $this->writePaymentErrorLog($msg, $params);
                $result['message'] = $msg;
            }
        }else{
            $msg = sprintf('pay4go withdrawal was not success: [%s]', $params['Status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
    $requiredFields = array(
            'MerchantInvoiceId', 'Process', 'Amount', 'Status', 'Hash'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================pay4go withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=======================pay4go checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        if ($fields['Amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================pay4go withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['MerchantInvoiceId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================pay4go withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }
        # everything checked ok
        return true;
    }

    public function sign($data) {
        $amount = $data['amount'] * 100;
        $signStr = $this->getSystemInfo('account').$amount.$data['merchantInvoiceId'].$data['targetCustomerEmail'].$this->getSystemInfo('key');
        $sign = strtoupper(hash_hmac('sha256', $signStr, $this->getSystemInfo('hashkey')));
        return $sign;
    }

    public function validateSign($data) {
        $amount = $data['Amount'] * 100;
        $signStr = $this->getSystemInfo('account').$amount.$data['MerchantInvoiceId'].$data['CustomerEmail'];
        $sign = hash_hmac('sha256', $signStr, $this->getSystemInfo('hashkey'));
        if ( $data['Hash'] == strtoupper($sign)) {
            return true;
        } else {
            return false;
        }
    }
}
