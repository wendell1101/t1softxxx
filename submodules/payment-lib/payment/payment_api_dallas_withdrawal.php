<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dallas.php';

/**
 * dallas取款
 *
 * * DALLAS_WITHDRAWAL_PAYMENT_API, ID: 6134
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.stars-pay.com/api/gateway/withdraw
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dallas_withdrawal extends Abstract_payment_api_dallas {

	public function getPlatformCode() {
		return DALLAS_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dallas_withdrawal';
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

        $this->utils->debug_log("==================dallas withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $pix_number  = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
        }

		$params = array();
        $bankdata['bankCode'] = 'CPF';
        $bankdata['accountNo'] = $pix_number;
        $bankdata['accountName'] = $name;

        $params['appId']            = $this->getSystemInfo("account");
        $params['merOrderNo']       = $transId;
        $params['currency']         = 'BRL';
        $params['amount']           = $this->convertAmountToCurrency($amount);
        $params['notifyUrl']        = $this->getNotifyUrl($transId);
        $params['extra']            = $bankdata;
        $params['sign']             = $this->sign($params);
		$this->CI->utils->debug_log('=========================dallas withdrawal paramStr before sign', $params);
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

        list($content, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================dallas submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================dallas submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================dallas submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }


	public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================dallas json_decode result", $result);
        if(!empty($result) && isset($result)){
            if(isset($result['data']['status']) && $result['data']['status'] == self::PAY_RESULT_SUCCESS){
                return array('success' => true, 'message' => 'dallas withdrawal request successful.');
            }else if(isset($result['message']) && !empty($result['message'])){
                $errorMsg = $result['message'];
                return array('success' => false, 'message' => $errorMsg);
            }else{
                return array('success' => false, 'message' => 'dallas withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'dallas withdrawal exist errors');
        }
    }

	public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================dallas withdrawal callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("=====================dallas withdrawal callbackFromServer json_decode params", $params);
        }

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['status'] == self::CALLBACK_RESULT_SUCCESS) {
            $msg = sprintf('dallas withdrawal was successful: trade ID [%s]', $params['merOrderNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('dallas withdrawal was not success: [%s]', $params['merOrderNo']);
            $this->writePaymentErrorLog($msg, $params);
            $result['success'] = true;
            $result['message'] = self::RETURN_SUCCESS_CODE;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = array('status', 'merOrderNo', 'amount', 'sign');

        $this->CI->utils->debug_log("=========================dallas checkCallback detailData", $fields);

        foreach ($requiredFields as $f) {
           if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================dallas withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
           $this->writePaymentErrorLog('=====================dallas withdrawal checkCallbackOrder Signature Error', $fields);
           return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['merOrderNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================dallas withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
            return false;
        }

        if ($fields['amount']  != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog("======================dallas withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
            return false;
        }

        return true;
    }
}
