<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sypay.php';

/**
 * sypay取款
 *
 * * SYPAY_WITHDRAWAL_PAYMENT_API, ID: 6076
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
class Payment_api_sypay_withdrawal extends Abstract_payment_api_sypay {
    const PAYMENT_TYPE_WITHDRAWAL = 'T08';

	public function getPlatformCode() {
		return SYPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'sypay_withdrawal';
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

        $this->utils->debug_log("==================sypay withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $pix_number  = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
        }

		$params = array();
        $params['Mch_Id']            = $this->getSystemInfo("account");
        $params['TimeStamp']         = time();
        $params['MerchantOrderNo']   = $transId.'000';
        $params['PayOutCode']        = self::PAYMENT_TYPE_WITHDRAWAL;
        $params['Amount']            = $this->convertAmountToCurrency($amount);
        $params['Type']              = 'CPF';
        $params['BankName']          = 'BrazilPayout';
        $params['BankNo']            = $pix_number;
        $params['UserName']          = $name;
        $params['NotifyUrl']         = $this->getNotifyUrl($transId);
        $params['Attach']            = 'BR';
        $params['sign']              = $this->sign($params);
		$this->CI->utils->debug_log('=========================sypay withdrawal paramStr before sign', $params);
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

        $this->CI->utils->debug_log('======================================sypay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================sypay submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================sypay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

	public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================sypay json_decode result", $result);
        if(!empty($result) && isset($result)){
            if(!empty($result['succeeded']) && isset($result['succeeded']) && $result['succeeded']){
                return array('success' => true, 'message' => 'sypay withdrawal request successful.');
            }else if(isset($result['errors']) && !empty($result['errors'])){
                $errorMsg = $result['errors'];
                return array('success' => false, 'message' => $errorMsg);
            }else{
                return array('success' => false, 'message' => 'sypay withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'sypay withdrawal exist errors');
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
            $this->CI->utils->debug_log("=====================sypay withdrawal callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("=====================sypay withdrawal callbackFromServer json_decode params", $params);
        }

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['OrderState'] == self::CALLBACK_RESULT_SUCCESS) {
            $msg = sprintf('sypay withdrawal was successful: trade ID [%s]', substr($params['MerchantOrderNo'], 0, -3));
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('sypay withdrawal was not success: [%s]', substr($params['MerchantOrderNo'], 0, -3));
            $this->writePaymentErrorLog($msg, $params);
            $result['success'] = true;
            $result['message'] = self::RETURN_SUCCESS_CODE;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = array('MerchantOrderNo','Mch_Id', 'Amount', 'PayTime', 'OrderState');

        $this->CI->utils->debug_log("=========================sypay checkCallback detailData", $fields);

        foreach ($requiredFields as $f) {
           if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================sypay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
           $this->writePaymentErrorLog('=====================sypay withdrawal checkCallbackOrder Signature Error', $fields);
           return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if (substr($fields['MerchantOrderNo'], 0, -3) != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================sypay withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
            return false;
        }

        if ($fields['Amount']  != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog("======================sypay withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
            return false;
        }

        return true;
    }
}
