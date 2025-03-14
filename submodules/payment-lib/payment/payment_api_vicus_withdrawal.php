<?php
require_once dirname(__FILE__) . '/abstract_payment_api_vicus.php';

/**
 * Vicus
 *
 *
 * * VICUS_WITHDRAWAL_PAYMENT_API, ID: 810
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra_info
 *
 * Field Values:
 * * URL: https://www.vicussolutions.net/Payapi_Index_PayoutToUser.html
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 * * Extra_info: { "check_status_url": "https://www.vicussolutions.net/Payapi_Orderenquiry_payout.html" }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_vicus_withdrawal extends Abstract_payment_api_vicus {
	const CALLBACK_STATUS_SUCCESS = 100;

	public function getPlatformCode() {
		return VICUS_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'vicus_withdrawal';
	}

	# Implement abstract function but do nothing
	protected function configParams(&$params, $direct_pay_extra_info) {}


	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false, 'message' => 'payment failed');
		$success = false;
		$message = 'Payment failed';

		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			return $result;
		}

		# look up bank code
		$bankInfo = $this->getBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================vicus withdrawal bank whose bankTypeId=[$bank] is not supported by vicus");
			return array('success' => false, 'message' => 'Bank not supported by vicus');
			$bank = '无';
		}

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
		$url = $this->getWithdrawUrl();
		list($response, $response_result) = $this->submitPostForm($url, array($params), true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

		return $decodedResult;
	}

	# Note: to avoid breaking current APIs, these abstract methods are not marked abstract
	# APIs with withdraw function need to implement these methods
	## This function returns the URL to submit withdraw request to
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url');
	}

	## This function returns the params to be submitted to the withdraw URL
	## Note that $bank param is the bank_type ID in database, we compare it with the supported bank_codes by this AP
	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

		$bankInfo = $this->getBankInfo();
        $bankName = $bankInfo[$bank]['name'];

        # look up bank detail
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
		if(!empty($playerBankDetails)){
			$bankBranch = empty($playerBankDetails['branch']) ? "无" : $playerBankDetails['branch'];
			$province = empty($playerBankDetails['province']) ? "无" : $playerBankDetails['province'];
			$city = empty($playerBankDetails['city']) ? "无" : $playerBankDetails['city'];
		} else {
			$bankBranch = '无';
			$province = '无';
			$city = '无';
		}

		$params = array();
        $params['merchantId'] = $this->getSystemInfo("account");
        $params['merchantTransactionId'] = $transId;
        $params['currencyCode'] = "RMB";
		$params['accountName'] = $name;
		$params['accountNum'] = $accNum;
        $params['transactionAmount'] = $this->convertAmountToCurrency($amount);
		$params['bankName'] = $bankName;
        $params['requestTime'] = date("Y-m-d H:i:s");
        $params['bankProv'] = $province;
		$params['bankCity'] = $city;
		$params['callback'] = $this->getNotifyUrl($transId);
		$params['signData'] = $this->sign($params);

		return $params;
	}

	## This function takes in the return value of the URL and translate it to the following structure
	## array('success' => false, 'message' => 'Error message')
	public function decodeResult($resultString, $queryAPI = false) {
		if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
		#different return type
		$this->utils->debug_log("=========================vicus checkWithdrawStatus resultString", $resultString);
		if($queryAPI) {
			$result = json_decode($resultString, true);
			$returnCode = $result['orderStatus'];
			$this->utils->debug_log("=========================vicus checkWithdrawStatus decoded result string", $result);
			$this->utils->debug_log("=========================vicus checkWithdrawStatus orderStatus", $returnCode);
		}
		else {
			$result = json_decode($resultString, true);
			$returnCode = $result['responseCode'];
			$returnDesc = $result['responseMessage'];
			$this->utils->debug_log("=========================vicus withdrawal decoded result string", $result);
			$this->utils->debug_log("=========================vicus withdrawal returnDesc", $returnDesc);
		}

		#when success
		if($returnCode == self::CALLBACK_STATUS_SUCCESS) {
			$message = "Vicus withdrawal response successful, merchantTransactionId: ". $result['merchantTransactionId'];
			if($queryAPI) {
				$message = "Vicus withdrawal success! merchantTransactionId: ". $result['merchantTransactionId'];
			}
			return array('success' => true, 'message' => $message);
		} else {
			if($queryAPI) {
				if($returnCode == "101"){
					$message = "Vicus withdrawal failed, Code: ".$returnCode;
					$this->CI->wallet_model->withdrawalAPIReturnFailure($result['merchantTransactionId'], $message);
					return array('success' => false, 'message' => $message);
				}
				else {
					$message = "Vicus withdrawal response status, Code: ".$returnCode;
					return array('success' => false, 'message' => $message);
				}
			}

			$message = "Vicus withdrawal response failed, Code: ".$returnCode.", Desc: ".$returnDesc;
			return array('success' => false, 'message' => $message);
		}

		return array('success' => false, 'message' => "Decode failed");
	}

	## This function provides a way to manually check withdraw status. Useful when API does not provide a callback.
	## Returns array('success' => false, 'payment_fail' => false, 'message' => 'Error message')
	## 'success' means whether payment is successful, 'payment_fail' means if payment is not successful, shall we mark it as failed or shall we wait
	public function checkWithdrawStatus($transId) {

        $params = array();
		$params['merchantId'] = $this->getSystemInfo("account");
		$params['merchantTransactionId'] = $transId;
		$params['signData'] = $this->sign($params);

		$url = $this->getSystemInfo('check_status_url');
		$response = $this->submitPostForm($url, array($params), true, $transId);
		$decodedResult = $this->decodeResult($response, true);
		return $decodedResult;
    }


	public function callbackFromServer($transId, $params) {
		$result = array('success' => false, 'message' => 'Payment failed');

		$this->CI->utils->debug_log('=========================vicus process withdrawalResult order id', $transId);
		$this->CI->utils->debug_log("=========================vicus withdrawal checkCallback params", $params);

		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		if (!$this->checkCallbackOrder($order, $params)) {
			return $result;
		}

		if ($params['status'] != self::CALLBACK_STATUS_SUCCESS) {
			$msg = sprintf('Vicus withdrawal payment was not successful: status code [%s]. '.$params['failedReason'], $params['responseCode']);
			$this->writePaymentErrorLog($msg, $fields);
			$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
			$result['message'] = $msg;
		} else {
			$msg = sprintf('Vicus withdrawal payment was successful: trade ID [%s]', $params['merchantTransactionId']);
			$this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

			$response['merchantTransactionId'] = $params['merchantTransactionId'];
			$response['merchantId'] = $params['merchantId'];
			$response['received'] = self::CALLBACK_STATUS_SUCCESS;

			$result['message'] = json_encode($response);
			$result['success'] = true;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields) {
		# does all required fields exist in the header?
		$requiredFields = array(
			'responseCode', 'merchantId', 'merchantTransactionId', 'accountName', 'accountNum', 'transactionAmount', 'bankName', 'transactionResult', 'failedReason', 'completeTime', 'signData'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("======================vicus withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		if (!$this->verifySign($fields)) {
			$this->writePaymentErrorLog('=========================vicus withdrawal checkCallback signature Error', $fields);
			return false;
		}

		if ($fields['transactionAmount'] != $order['amount']) {
			$this->writePaymentErrorLog('=========================vicus withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
			return false;
		}

		if ($fields['merchantTransactionId'] != $order['transactionCode']) {
			$this->writePaymentErrorLog('=========================vicus withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function callbackFromBrowser($transId, $params) {
		return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
	}


   # -- signing --
	public function sign($params) {
		$this->utils->debug_log("=======================vicus getting sign for request", $params);
		$signStr = '';
		foreach($params as $key => $value) {
			if($key == 'signData' ) {
				continue;
			}
			$signStr .= $value;
		}
		$sign = md5($signStr.$this->getSystemInfo('key'));
		return $sign;
	}

	public function verifySign($params){
		if($this->sign($params) == $params["signData"]){
			return true;
		} else {
			return false;
		}
	}

	/*Customized functions*/
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                if(isset($bankInfoItem['name'])){
                    $bankInfo[$system_bank_type_id]['name'] = $bankInfoItem['name'];
                }
                if(isset($bankInfoItem['code'])){
                    $bankInfo[$system_bank_type_id]['code'] = $bankInfoItem['code'];
                }
            }
            $this->utils->debug_log("=========================vicus bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => array('name' => '中国工商银行', 'code' => 'ICBC'),
                '2' => array('name' => '招商银行', 'code' => 'CMB'),
                '3' => array('name' => '中国建设银行', 'code' => 'CCB'),
                '4' => array('name' => '中国农业银行', 'code' => 'ABC'),
                '5' => array('name' => '交通银行', 'code' => 'BCOM'),
                '6' => array('name' => '中国银行', 'code' => 'BOC'),
                '11' => array('name' => '民生银行', 'code' => 'CMBC'),
                '12' => array('name' => '中国邮政储蓄银行', 'code' => 'PSBC'),
                '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                '14' => array('name' => '华夏银行', 'code' => 'HXB'),
                '15' => array('name' => '平安银行', 'code' => 'PABC'),
                '20' => array('name' => '光大银行', 'code' => 'CEB'),
            );
			$this->utils->debug_log("=======================getting vicus bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}
}