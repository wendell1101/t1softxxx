<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ffpay.php';

/**
 * FFpay 取款
 *
 * * FFPAY_WITHDRAWAL_PAYMENT_API, ID: 5131
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://gateway-o.ffpay.xyz/payForAnother/paid
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ffpay_withdrawal extends Abstract_payment_api_ffpay {
	const CALLBACK_SUCCESS = '2';

	public function getPlatformCode() {
		return FFPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'ffpay_withdrawal';
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

	## This function returns the params to be submitted to the withdraw URL
	## Note that $bank param is the bank_type ID in database, we compare it with the supported bank_codes by this API
	private $errMsg = 'Payment failed'; # This variable is used to store error message that's available upon submit
	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$params = array();
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("===============================ffpay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
		if(!empty($playerBankDetails)){
			$province = $playerBankDetails['province'];
			$city = $playerBankDetails['city'];
			$bankBranch = $playerBankDetails['branch'];
			$bankSubBranch = $playerBankDetails['branch'];
		} else {
			$province = '无';
			$city = '无';
			$bankBranch = '无';
			$bankSubBranch = '无';
		}

		$province = empty($province) ? "无" : $province;
		$city = empty($city) ? "无" : $city;
		$bankBranch = empty($bankBranch) ? "无" : $bankBranch;
		$bankSubBranch = empty($bankSubBranch) ? "无" : $bankSubBranch;

        
		# look up bank code
		$bankInfo = $this->getHuidpayBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================ffpay withdrawal bank whose bankTypeId=[$bank] is not supported by ffpay");
			return array('success' => false, 'message' => 'Bank not supported by ffpay');
			$bank = '无';
		}
        $bankno = $bankInfo[$bank]['code'];	//开户行名称
        $bank = $bankInfo[$bank]['name'];	//开户行名称
        

		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("=========================ffpay withdrawal get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

		if(!empty($playerBankDetails)){
			$bankBranch = $playerBankDetails['branch'];
			$bankSubBranch = $playerBankDetails['branch'];
			$province = $playerBankDetails['province'];	//开户省  卡号的开户省
			$city = $playerBankDetails['city'];	//开户市 卡号的开户市
			
		}

		$params = array();
		$params['MerchantCode'] = $this->getSystemInfo("account");
		$params['OrderId'] = $transId;
		$params['BankCardNum'] = $accNum;
		$params['BankCardName'] = $name;
		$params['Province'] = $province;
		$params['City'] = $city;
		$params['Area'] = $city;
		$params['Branch'] = $bankBranch;
		$params['BankCode'] = $bankno;
		$params['Amount'] = $this->convertAmountToCurrency($amount);
		$params['NotifyUrl'] = $this->getNotifyUrl($transId); 
		$params['OrderDate'] = $this->getMillisecond();
		// $params['bank_name'] = $bank;
		// $params['drawBankBranchName'] = $bankSubBranch;
        $params['Sign'] = $this->sign($params);
		$this->CI->utils->debug_log('=========================ffpay withdrawal paramStr after sign', $params);

		return $params;
	}

	protected function getMillisecond() { 
	    list($s1, $s2) = explode(' ', microtime()); 
	    return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000); 
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false, 'message' => 'payment failed');		
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
		
		$this->CI->utils->debug_log('======================================ffpay submitWithdrawRequest params: ', $params);

		$url = $this->getSystemInfo('url');	

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================ffpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================ffpay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================ffpay submitWithdrawRequest decoded Result', $decodedResult);
		return $decodedResult;

	}

	public function decodeResult($resultString, $queryAPI = false) {
		if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result_array = json_decode($resultString, true);
        $this->CI->utils->debug_log('==============ffpay submitWithdrawRequest decodeResult json decoded', $result_array);
		if($queryAPI) {
		}
		else {
			if($result_array['success']) {
				$returnCode = $result_array['data']['data']['status'];
				$returnDesc = $this->getMappingErrorMsg($result_array['data']['data']['status']);

				if ($returnCode == '1'){
					$message = 'ffpay withdrawal response successful, result Code:'.$returnCode.", Desc: ".$returnDesc;
					return array('success' => true, 'message' => $message);
				}else if($returnCode == '2'){
					$msg = "ffpay withdrawal failed for Code:".$returnCode.", Desc: ".$returnDesc;
					$transId = $result_array['outTradeNo'];
					$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
					return array('success' => false, 'message' => $msg);
				}else if ($returnCode == '0') {
					$message = "ffpay withdrawal failed for Code:".$returnCode.", Desc: ".$returnDesc;
					return array('success' => false, 'message' => $message);
				}
				else{
					$message = "ffpay withdrawal failed for:".$resultString;
					return array('success' => false, 'message' => $message);
				}
			} 
			else{
				$message = $this->errMsg = 'ffpay withdrawal failed result_code is Query failed'.$resultString;
				return array('success' => false, 'message' => $message);
			} 		
		}
	}	

	private function getMappingErrorMsg($state) {
		$msg = "";
		switch ($state) {
			case '1':
				$msg = "成功";
				break;

			case '2':
				$msg = "失败";
				break;

			case '0':
				$msg = "等待回调确认状态";
				break;			

			default:
				$msg = "";
				break;
		}
		return $msg;
	}

	public function getHuidpayBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("ffpay_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
				$bankInfo[$system_bank_type_id] = array('name' => $bankInfoItem['name'], 'code' => $bankInfoItem['code']);
			}
			$this->utils->debug_log("==================getting ffpay bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				'1' => array('name' => '工商银行', 'code' => 'ICBC'),
				'2' => array('name' => '招商银行', 'code' => 'CMB'),	
				'3' => array('name' => '建设银行 ', 'code' => 'CCB'),
				'4' => array('name' => '农业银行', 'code' => 'ABC'),
				'5' => array('name' => '交通银行', 'code' => 'BOCM'),
				'6' => array('name' => '中国银行', 'code' => 'BOC'),
				'7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
				'8' => array('name' => '广发银行', 'code' => 'GDB'),
				'10' => array('name' => '中信银行', 'code' => 'ECITIC'),
				'11' => array('name' => '民生银行', 'code' => 'CMBC'),
				'12' => array('name' => '邮政储蓄银行', 'code' => 'PSBC'),
				'13' => array('name' => '兴业银行', 'code' => 'CIB'),
				'14' => array('name' => '华夏银行', 'code' => 'HXB'),
				'15' => array('name' => '平安银行', 'code' => 'PAB'),
				//'17' => array('name' => '广州银行', 'code' => 'GZCB'),
				// '18' => array('name' => '南京银行', 'code' => 'NJCB'),
				'20' => array('name' => '光大银行', 'code' => 'CEB'),
				'24' => array('name' => '浦发银行', 'code' => 'SPDB'),
			);
			$this->utils->debug_log("=======================getting ffpay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function checkWithdrawStatus($transId) {

		# ---- First add bank card entry ----
        $params = array();
		$params['appid'] = $this->getSystemInfo("account"); 
		$params['outTradeNo'] = $transId;
		$params['randomStr'] = $this->uuid();
		$params['sign'] = $this->sign($params);

		$url = $this->getSystemInfo('check_Status_url');
		$response = $this->submitPostForm($url, $params,false, $transId);
		$decodedResult = $this->decodeResult($response, true);

		$this->CI->utils->debug_log('======================================ffpay checkWithdrawStatus params: ', $params);
		$this->CI->utils->debug_log('======================================ffpay checkWithdrawStatus url: ', $url );
		$this->CI->utils->debug_log('======================================ffpay checkWithdrawStatus result: ', $response );
		$this->CI->utils->debug_log('======================================ffpay checkWithdrawStatus decoded Result', $decodedResult);

		return $decodedResult;
    }
    
	public function callbackFromServer($transId, $params) {
		if(empty($params) || is_null($params)){
			$raw_post_data = file_get_contents('php://input', 'r');
        	$params = json_decode($raw_post_data, true);
		}
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->utils->debug_log('=========================ffpay process withdrawalResult order id', $transId);
       
        $result = $params;

        $this->utils->debug_log("=========================ffpay checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($result['remitStatus'] == self::CALLBACK_SUCCESS) {
            $this->utils->debug_log('=========================ffpay withdrawal was successful: trade ID [%s]', $params['outTradeNo']);
            
            $msg ='OK';
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = $msg;
            $result['success'] = true;
       }else {
           $realStateDesc = $result['withdrawId'];
            $this->errMsg = '['.$realStateDesc.']';
            $msg = sprintf('======================ffpay withdrawal was not successful: '.$this->errMsg);
            $this->writePaymentErrorLog($msg, $params);
       
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array('MerchantCode', 'OrderId', 'Amount', 'Fee', 'OutTradeNo', 'Time', 'Status', 'Sign', 'merchantCode', 'date', 'sign');

        foreach ($requiredFields as $f) {
        	if (!array_key_exists($f, $fields)) {
        		$this->writePaymentErrorLog("======================ffpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
        		return false;
        	}
        }

        if ($fields['OrderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================ffpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

    	if ($fields['Status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================ffpay withdrawal checkCallbackOrder  status is not success", $fields);
            return false;
        }

        if ($fields['Amount']  != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================ffpay withdrawal checkCallbackOrder amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields["sign"]!=$this->validateSign($fields)) {
        	$this->writePaymentErrorLog('=========================ffpay withdrawal checkCallback signature Error', $validateSign);
        	return false;
        }

        # everything checked ok
        return true;
    }

    public function validateSign($data) {
	    $callback_sign = $data['sign'];
	    $data_keys = array('Amount', 'Fee', 'MerchantCode', 'OrderId', 'OutTradeNo' ,'Status' ,'Time');
        $signStr =  $this->createSignStr($data,$data_keys);
        $sign=md5($signStr);
    
		return $sign;
	}
	
	# -- signing --
	public function sign($params) {
		$params_keys = array('Amount', 'BankCardName', 'BankCardNum', 'BankCode', 'Branch', 'MerchantCode', 'OrderDate', 'OrderId');
	    $signStr = $this->createSignStr($params,$params_keys);
        $sign=md5($signStr);
 	
		return $sign;
	}

	private function createSignStr($params,$keys) {
		$signStr = '';
		foreach($keys as $key) {
			if (array_key_exists($key, $params)) {
				$signStr .= $key."=".$params[$key]."&";
			}
		}
		$signStr .= 'Key='. $this->getSystemInfo('key');		
		return $signStr;
	}
}
