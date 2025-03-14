<?php
require_once dirname(__FILE__) . '/abstract_payment_api_daypaybd.php';

/**
 * daypaybd取款
 *
 * * DAYPAYBD_WITHDRAWAL_PAYMENT_API, ID: 6066
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.daypaybd.com/openApi/payout/createOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_daypaybd_withdrawal extends Abstract_payment_api_daypaybd {
	const CALLBACK_SUCCESS = 'PAY_SUCCESS';
	const REQUEST_SUCCESS = 200;
	const RETURN_SUCCESS_CODE = 'ok';

	public function getPlatformCode() {
		return DAYPAYBD_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'daypaybd_withdrawal';
	}

	# Implement abstract function but do nothing
	protected function configParams(&$params, $direct_pay_extra_info) {}

	/**
	 * detail: override common API functionsh
	 *
	 * @return void
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		return $this->returnUnimplemented();
	}

	public function processPaymentUrlForm($params) {
		return $this->returnUnimplemented();
	}

	# APIs with withdraw function need to implement these methods
	## This function returns the URL to submit withdraw request to
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url');
	}

	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);

        $this->utils->debug_log("==================daypaybd withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $pix_number  = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
            $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'no firstName';
            $lastname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName']      : 'no lastName';
            $phone     = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '8615551234567';
            $email     = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : 'sample@example.com';
        }

		$params = array();
        $params['merchant']     = $this->getSystemInfo("account");
        $params['orderId']      = $transId;
        $params['amount']       = $this->convertAmountToCurrency($amount); //元
        $params['customName']   = $lastname.$firstname;
        $params['customMobile'] = $phone;
        $params['customEmail']  = $email;
        $params['bankAccount']  = $pix_number;
        $params['documentType'] = 'CPF';
        $params['documentId']   = $pix_number;
        $params['notifyUrl']    = $this->getNotifyUrl($transId);
        $params['accountDigit'] = '1';
        $params['sign']         = $this->sign($params);
		$this->CI->utils->debug_log('=========================daypaybd withdrawal paramStr before sign', $params);
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
        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);
        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('======================================guaranapay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================guaranapay submitWithdrawRequest decoded Result', $decodedResult);
        return $decodedResult;

    }

	public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================daypaybd json_decode result", $result);
        if(!empty($result) && isset($result)){
            if(!empty($result['code']) && isset($result['code']) && $result['code'] == self::REQUEST_SUCCESS ){
                return array('success' => true, 'message' => 'daypaybd withdrawal request successful.');
            }else if(isset($result['errorMessages']) && !empty($result['errorMessages'])){
                $errorMsg = $result['errorMessages'];
                return array('success' => false, 'message' => $errorMsg);
            }
            else{
                return array('success' => false, 'message' => 'daypaybd withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'daypaybd withdrawal exist errors');
        }
    }

	private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================daypaybd process withdrawalResult order id', $transId);
        $this->CI->utils->debug_log("=========================daypaybd checkCallback params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('daypaybd withdrawal was successful: trade ID [%s]', $params['orderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('daypaybd withdrawal was not success: [%s]', $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
    $requiredFields = array(
            'orderId', 'amount', 'status','sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================daypaybd withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================daypaybd checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================dywinpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['orderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================dywinpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }
        # everything checked ok
        return true;
    }
}
