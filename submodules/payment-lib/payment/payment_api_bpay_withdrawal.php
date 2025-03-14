<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bpay168.php';

/**
 * bpay取款
 *
 * * BPAY_WITHDRAWAL_PAYMENT_API, ID: 6059
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://bpay168.com/transfer/apply
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bpay_withdrawal extends Abstract_payment_api_bpay168 {
	const CALLBACK_SUCCESS = "1";
    const REQUEST_SUCCESS = "0";

	public function getPlatformCode() {
		return BPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'bpay_withdrawal';
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

        $this->utils->debug_log("==================bpay withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $pix_number  = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
        }

		$params = array();
        $params['amount']      =  $this->convertAmountToCurrency($amount);
        $params['merchant']    = $this->getSystemInfo("account");
        $params['bankname']    = 'PIX';
        $params['subbankname'] = 'CPF/CNPJ';
        $params['cardno']      = $pix_number;
        $params['cardname']    = $name;
        $params['notifyurl']   = $this->getNotifyUrl($transId);
        $params['outtransferno'] = $transId;
        $params['sign']        = $this->sign($params);

		$this->CI->utils->debug_log('=========================bpay withdrawal paramStr before sign', $params);
		return $params;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }
        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================bpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================bpay submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================bpay submitWithdrawRequest response ', $response_result);
        $this->CI->utils->debug_log('======================================bpay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================bpay json_decode result", $result);

        if(isset($result['code'])) {
            if($result['code'] == self::REQUEST_SUCCESS) {
                $message = "bpay withdrawal response successful, code:[".$result['code']."]: ".$result['results'];
                return array('success' => true, 'message' => $message);
            }
            $message = "bpay withdrawal response failed. [".$result['code']."]: ".$result['results'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['results']){
            $message = 'bpay withdrawal response: '.$result['message'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "bpay decoded fail.");
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
        $this->CI->utils->debug_log('=========================bpay process withdrawalResult order id', $transId);
        $this->CI->utils->debug_log("=========================bpay checkCallback params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('bpay withdrawal was successful: trade ID [%s]', $params['outtransferno']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('bpay withdrawal was not success: [%s]', $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = array('transferno', 'outtransferno', 'transferamount', 'status', 'sign');

        $this->CI->utils->debug_log("=========================bpay checkCallback detailData", $fields);

        foreach ($requiredFields as $f) {
           if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================stars withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
               $this->writePaymentErrorLog('=====================stars withdrawal checkCallbackOrder Signature Error', $fields);
               return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['outtransferno'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================stars withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
            return false;
        }

        if ($fields['transferamount']  != $order['amount']) {
            $this->writePaymentErrorLog("======================paysec withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
            return false;
        }

        return true;
    }
}
