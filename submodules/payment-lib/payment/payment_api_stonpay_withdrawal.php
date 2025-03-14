<?php
require_once dirname(__FILE__) . '/abstract_payment_api_stonpay.php';

/**
 * StonPay 四通 取款
 *
 * * STONPAY_WITHDRAWAL_PAYMENT_API, ID: 5087
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://gateway-o.stonpay.xyz/payForAnother/paid
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_stonpay_withdrawal extends Abstract_payment_api_stonpay {
    const RETURN_SUCCESS_CODE = '0000';
    const RETURN_SUCCESS_STATUS = 'REMIT_SUCCESS';
    const RETURN_PROCESSING_CODE = '9996';
    const RETURN_PROCESSING_STATUS = 'REMITTING';

    const CALLBACK_SUCCESS = 'REMIT_SUCCESS';
    const CALLBACK_FAILED  = 'REMIT_FAIL';
	const PROXY_TYPE = 'D0';
	const BANKACCOUNTTYPE = 'PRIVATE_DEBIT_ACCOUNT';
	const CALLBACK_MSG_SUCCESS = 'SUCCESS';

	public function getPlatformCode() {
		return STONPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'stonpay_withdrawal';
	}

	# Implement abstract function but do nothing
	protected function configParams(&$params, $direct_pay_extra_info) {}
	protected function processPaymentUrlForm($params) {}

	# APIs with withdraw function need to implement these methods
	## This function returns the URL to submit withdraw request to
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url');
	}

	## This function returns the params to be submitted to the withdraw URL
	## Note that $bank param is the bank_type ID in database, we compare it with the supported bank_codes by this API
	private $errMsg = 'Payment failed'; # This variable is used to store error message that's available upon submit
	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
		# look up bank code
		$bankInfo = $this->getBankInfo();
        $bankcode = $bankInfo[$bank]['code'];

		$params = array();
        $params['appid']             = $this->getSystemInfo("account");
        $params['outTradeNo']        = $transId;
        $params['orderIp']           = $this->getClientIp();
        $params['amount']            = $this->convertAmountToCurrency($amount);
        $params['proxyType']         = self::PROXY_TYPE;
        $params['bankAccountType']   = self::BANKACCOUNTTYPE;
        $params['phoneNo']           = '';
        $params['receiverName']      = $name;
        $params['receiverAccountNo'] = $accNum;
        $params['bankClearNo']       = '';
        $params['bankCode']          = $bankcode;
        $params['notifyUrl']         = $this->getNotifyUrl($transId);
        $params['randomStr']         = $this->uuid();
        $params['sign']              = $this->sign($params);
		$this->CI->utils->debug_log('=========================stonpay withdrawal paramStr after sign', $params);

		return $params;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false, 'message' => 'payment failed');
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================stonpay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by stonpay");
            return array('success' => false, 'message' => 'Bank not supported by Stonpay');
        }

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
		$url = $this->getSystemInfo('url');
        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('========================stonpay submitWithdrawRequest decoded Result', $decodedResult);

		return $decodedResult;
	}

	public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result_array = json_decode($resultString, true);
        $this->CI->utils->debug_log('==============stonpay submitWithdrawRequest decodeResult json decoded', $result_array);

        if(isset($result_array['resultCode'])){
            $returnCode   = $result_array['resultCode'];
            $returnDesc   = $result_array['errMsg'];

    		if($returnCode == self::RETURN_SUCCESS_CODE || $returnCode == self::RETURN_PROCESSING_CODE) {
                $returnStatus = $result_array['remitStatus'];
    			if($returnStatus == self::RETURN_SUCCESS_STATUS || $returnStatus == self::RETURN_PROCESSING_STATUS){
    				$msg = 'Stonpay withdrawal response successful, ['.$returnCode.']: '. $returnStatus." - ".$returnDesc;
    				return array('success' => true, 'message' => $msg);
    			}else{
    				$msg = 'Stonpay withdrawal response failed, ['.$returnCode.']: '.$returnStatus." - ".$returnDesc;
    				return array('success' => false, 'message' => $msg);
    			}
    		}
            else{
                $msg = 'Stonpay withdrawal response failed, ['.$returnCode.']: '.$returnDesc;
                return array('success' => false, 'message' => $msg);
            }
        }
		else{
			$msg = 'Stonpay withdrawal decode failed.'. $resultString;
			return array('success' => false, 'message' => $msg);
		}
	}

	public function getBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("stonpay_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
				$bankInfo[$system_bank_type_id] = array('name' => $bankInfoItem['name'], 'code' => $bankInfoItem['code']);
			}
			$this->utils->debug_log("==================getting stonpay bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				'1' => array('name' => '工商银行', 'code' => 'ICBC'),
				'2' => array('name' => '招商银行', 'code' => 'CMB'),
				'3' => array('name' => '建设银行 ', 'code' => 'CCB'),
				'4' => array('name' => '中国农业银行', 'code' => 'ABC'),
				'5' => array('name' => '交通银行', 'code' => 'BOCM'),
				'6' => array('name' => '中国银行', 'code' => 'BOC'),
				// '7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
				'8' => array('name' => '广发银行', 'code' => 'CGB'),
				'10' => array('name' => '中信银行', 'code' => 'CITIC'),
				'11' => array('name' => '中国民生银行', 'code' => 'CMBC'),
				'12' => array('name' => '中国邮政', 'code' => 'PSBC'),
				'13' => array('name' => '兴业银行', 'code' => 'CIB'),
				'14' => array('name' => '华夏银行', 'code' => 'HXB'),
				'15' => array('name' => '平安银行', 'code' => 'PINGANBANK'),
				//'17' => array('name' => '广州银行', 'code' => 'GZCB'),
				'18' => array('name' => '南京银行', 'code' => 'NJCB'),
				'20' => array('name' => '光大银行', 'code' => 'CEB'),
				'24' => array('name' => '浦发银行', 'code' => 'SPDB'),
			);
			$this->utils->debug_log("=======================getting stonpay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================stonpay process withdrawalResult order id', $transId);
        $this->CI->utils->debug_log("=========================stonpay checkCallback params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['remitStatus'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('Stonpay withdrawal was successful: trade ID [%s]', $params['outTradeNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::CALLBACK_MSG_SUCCESS;
            $result['success'] = true;
        }
        elseif($params['remitStatus'] == self::CALLBACK_FAILED){
            $msg = sprintf('Stonpay withdrawal was failed: trade ID [%s]', $params['outTradeNo']);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = self::CALLBACK_MSG_SUCCESS;
        }
        else {
            $msg = sprintf('Stonpay withdrawal was not success: [%s]', $params['remitStatus']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array('appid', 'outTradeNo', 'settAmount', 'settFee', 'proxyType','remitStatus','sign');

        foreach ($requiredFields as $f) {
        	if (!array_key_exists($f, $fields)) {
        		$this->writePaymentErrorLog("======================stonpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
        		return false;
        	}
        }

        if ($fields['outTradeNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================stonpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        $newAmount = ($fields['settAmount'] - $fields['settFee']) ;
        if ($newAmount != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================stonpay withdrawal checkCallbackOrder amount is wrong, expected =>'. $order['amount'], $newAmount, $fields);
            return false;
        }

        if ($fields["sign"] != $this->validateSign($fields)) {
        	$this->writePaymentErrorLog('=========================stonpay withdrawal checkCallback signature Error', $validateSign);
        	return false;
        }

        # everything checked ok
        return true;
    }

    public function uuid() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s', str_split(bin2hex($data), 4));
    }

	# -- signing --
	public function sign($params) {
	    $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
 		
		return $sign;
	}

	private function createSignStr($params) {
		ksort($params);
		$signStr = '';
		foreach ($params as $key => $value) {
			if(is_null($value) || is_null($value) || $key == 'sign' || $value==''){
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'key='. $this->getSystemInfo('key');
		return $signStr;
	}

    public function validateSign($data) {
        $callback_sign = $data['sign'];
        $signStr = $this->createSignStr($data);
        $sign = strtoupper(md5($signStr));
        
        return $sign;
    }
}
