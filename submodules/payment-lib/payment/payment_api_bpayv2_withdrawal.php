<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bpayv2.php';

/**
 * bpayv2取款
 *
 * * BPAYV2_WITHDRAWAL_PAYMENT_API, ID: 6151
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.transfersmile.com/api.v1.html#pix_payout
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bpayv2_withdrawal extends Abstract_payment_api_bpayv2 {
	const CALLBACK_SUCCESS = 'SUCCESS';
	const REQUEST_SUCCESS = 200;

	public function getPlatformCode() {
		return BPAYV2_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'bpayv2_withdrawal';
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

        $this->utils->debug_log("==================bpayv2 withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName'] : 'none';
            $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName'] : 'none';
            $phone      = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : 'none';
            $email      = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email'] : 'none';
            $pix_number = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
        }

		$params = array();
        $params['merchantNo']        = $this->getSystemInfo("account");
        $params['merchantOrderNo']   = $transId;
        $params['countryCode']       = $this->getSystemInfo("countryCode");
        $params['currencyCode']      = $this->getSystemInfo("currencyCode");
        $params['transferType']      = self::CHANNEL_CODE_PIX_WITHDRAWAL;
        $params['transferAmount']    = $this->convertAmountToCurrency($amount); //元
        $params['feeDeduction']      = $this->getSystemInfo("feeDeduction");
        $params['remark']            = 'withdraw';
        $params['notifyUrl']         = $this->getNotifyUrl($transId);
        $params['extendedParams']    = 'payeeName^'.$name.'|PIX^'.$pix_number.'|pixType^CPF'.'|payeePhone^'.$phone.'|payeeEmail^'.$email.'|payeeCPF^'.$pix_number;
        $params['sign']              = $this->sign($params);
		$this->CI->utils->debug_log('=========================bpayv2 withdrawal paramStr before sign', $params);
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
        list($content, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);
        $this->CI->utils->debug_log('=====================bpayv2 submitWithdrawRequest received response', $content);
        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

		return $decodedResult;

	}

	public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================bpayv2 json_decode result", $result);
        if(!empty($result) && isset($result)){
            if(!empty($result['code']) && isset($result['code']) && $result['code'] == self::REQUEST_SUCCESS ){
                return array('success' => true, 'message' => 'bpayv2 withdrawal request successful.');
            }else if(isset($result['message']) && !empty($result['message'])){
                $errorMsg = $result['message'];
                return array('success' => false, 'message' => $errorMsg);
            }
            else{
                return array('success' => false, 'message' => 'bpayv2 withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'bpayv2 withdrawal exist errors');
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
        $this->CI->utils->debug_log('=========================bpayv2 process withdrawalResult order id', $transId);
        $this->CI->utils->debug_log("=========================bpayv2 checkCallback params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['transferStatus'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('bpayv2 withdrawal was successful: trade ID [%s]', $params['merchantOrderNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('bpayv2 withdrawal was not success: [%s]', $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
    $requiredFields = array(
            'merchantNo', 'transferStatus', 'merchantOrderNo', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================bpayv2 withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================pay4go checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        if ($fields['merchantOrderNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================bpayv2 withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        if ($fields['orderAmout'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================bpayv2 withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }
}
