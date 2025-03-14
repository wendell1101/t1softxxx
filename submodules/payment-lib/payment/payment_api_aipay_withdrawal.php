<?php
require_once dirname(__FILE__) . '/abstract_payment_api_aipay.php';

/**
 * aipay 艾付 取款
 *
 * * AIPAY_WITHDRAWAL_PAYMENT_API, ID: 5043
 * * AIPAY_2_WITHDRAWAL_PAYMENT_API, ID: 5318
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.goodatpay.com/withdraw/singleWithdraw
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_aipay_withdrawal extends Abstract_payment_api_aipay {
	const CALLBACK_SUCCESS = 'S';
	const CALLBACK_MSG_SUCCESS = 'SUCCESS';

	public function getPlatformCode() {
		return AIPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'aipay_withdrawal';
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
		$this->utils->debug_log("===============================aipay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
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
			$this->utils->error_log("========================aipay withdrawal bank whose bankTypeId=[$bank] is not supported by aipay");
			return array('success' => false, 'message' => 'Bank not supported by aipay');
			$bank = '无';
		}
        $bankno = $bankInfo[$bank]['code'];	//开户行名称
        $bank = $bankInfo[$bank]['name'];	//开户行名称
        

		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("=========================aipay withdrawal get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

		if(!empty($playerBankDetails)){
			$bankBranch = $playerBankDetails['branch'];
			$bankSubBranch = $playerBankDetails['branch'];
			$province = $playerBankDetails['province'];	//开户省  卡号的开户省
			$city = $playerBankDetails['city'];	//开户市 卡号的开户市
			
		}

		$params = array();
		$params['merchant_no']  = $this->getSystemInfo("account");
		$params['order_no']     = $transId;  
		$params['card_no']      = $accNum;
		$params['account_name'] = base64_encode($name);
		$params['bank_branch']  = base64_encode($bankBranch);  //对公账户需填写
		$params['cnaps_no']     = ""; //对公账户需填写
		$params['bank_code']    = $bankno;
		$params['bank_name']    = base64_encode($bank);
		$params['amount']       = $this->convertAmountToCurrency($amount);
        $params['sign']         = $this->sign($params);
		$this->CI->utils->debug_log('=========================aipay withdrawal paramStr before sign', $params);
		return $params;
	}

	    # Callback URI: /callback/fixed_process/<payment_id>
	public function getOrderIdFromParameters($params) {
		$this->utils->debug_log('====================================aipay callbackOrder params', $params);
		if(empty($params) || is_null($params)){
			$raw_post_data = file_get_contents('php://input', 'r');
        	$params = json_decode($raw_post_data, true);
		}
		
		$transId = null;
		//for fixed return url on browser
		if (isset($params['orders'][0]['mer_order_no'])) {
			$trans_id = $params['orders'][0]['mer_order_no'];

			$this->CI->load->model(array('wallet_model'));
	        $walletAccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($trans_id);

			if(!empty($walletAccount)){
               	$transId = $walletAccount['transactionCode'];
            }else{
            	$this->utils->debug_log('====================================aipay callbackOrder transId is empty when getOrderIdFromParameters', $params);
            }
		}
		else {
			$this->utils->debug_log('====================================aipay callbackOrder cannot get any transId when getOrderIdFromParameters', $params);
		}
		return $transId;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false, 'message' => 'payment failed');		
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

		$this->CI->utils->debug_log('======================================aipay submitWithdrawRequest params: ', $params);

		$url = $this->getSystemInfo('url');	

		list($content, $response_result) = $this->submitGetForm($url, $params, false, $transId, true);
        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('======================================aipay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================aipay submitWithdrawRequest decoded Result', $decodedResult);

		return $decodedResult;

	}

	public function decodeResult($resultString, $queryAPI = false) {
		if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result_array = json_decode($resultString, true);
        $this->CI->utils->debug_log('==============aipay submitWithdrawRequest decodeResult json decoded', $result_array);
		if($queryAPI) {
			if($result_array['result_code']=='000000') {
				$returnCode = $result_array['result'];
				$returnDesc = $this->getMappingErrorMsg($result_array['result']);

				if ($returnCode == 'S'){
					$message = 'aipay payment response successful, result Code:'.$returnCode.", Desc: ".$returnDesc;
					return array('success' => true, 'message' => $message);
				}else if($returnCode == 'F'){
					$message = "aipay payment failed for Code:".$returnCode.", Desc: ".$returnDesc;
					$transId = $result_array['mer_order_no'];
					$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $message);
					return array('success' => false, 'message' => $message);
				}else{
					$message = "aipay payment failed for Code:".$returnCode.", Desc: ".$returnDesc;;
					return array('success' => false, 'message' => $message);
				}
			} 
			else{
				$message = $this->errMsg = 'aipay payment failed result_code is Query failed'.$result_array;
				return array('success' => false, 'message' => $message);
			} 
		}
		else {
			if($result_array['result_code']=='000000') {
				$returnCode = $result_array['result'];
				$returnDesc = $this->getMappingErrorMsg($result_array['result']);

				if ($returnCode == 'S'){
					$message = 'aipay payment response successful, result Code:'.$returnCode.", Desc: ".$returnDesc;
					return array('success' => true, 'message' => $message);
				}else if($returnCode == 'F'){
					$message = "aipay payment failed for Code:".$returnCode.", Desc: ".$returnDesc;
					$transId = $result_array['mer_order_no'];
					$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $message);
					return array('success' => false, 'message' => $message);
				}else{
					$message = "aipay payment success for Code:".$returnCode.", Desc: ".$returnDesc;;
					return array('success' => true, 'message' => $message);
				}
			} 
			else{
				$erroe_code = $result_array['result_code'];
				$error_msg = $result_array['result_msg'];
				$message = $this->errMsg = 'aipay payment failed result_code is Query failed Code:'.$erroe_code.", Desc: ".$error_msg;
				return array('success' => false, 'message' => $message);
			} 		
		}
	}	

	private function getMappingErrorMsg($state) {
		$msg = "";
		switch ($state) {
			case 'S':
				$msg = "成功";
				break;

			case 'F':
				$msg = "失败";
				break;

			case 'H':
				$msg = "处理中";
				break;			

			default:
				$msg = "";
				break;
		}
		return $msg;
	}

	public function getHuidpayBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("aipay_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
				$bankInfo[$system_bank_type_id] = array('name' => $bankInfoItem['name'], 'code' => $bankInfoItem['code']);
			}
			$this->utils->debug_log("==================getting aipay bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				'1' => array('name' => '中国工商银行', 'code' => 'ICBC'),
				'2' => array('name' => '招商银行', 'code' => 'CMB'),	
				'3' => array('name' => '中国建设银行', 'code' => 'CCB'),
				'4' => array('name' => '中国农业银行', 'code' => 'ABC'),
				'5' => array('name' => '交通银行', 'code' => 'COMM'),
				'6' => array('name' => '中国银行', 'code' => 'BOC'),
				// '7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
				'8' => array('name' => '广发银行', 'code' => 'GDB'),
				'10' => array('name' => '中信银行', 'code' => 'CITIC'),
				'11' => array('name' => '中国民生银行', 'code' => 'CMBC'),
				'12' => array('name' => '中国邮政储蓄银行', 'code' => 'PSBC'),
				'13' => array('name' => '兴业银行', 'code' => 'CIB'),
				// '14' => array('name' => '华夏银行', 'code' => 'hxb'),
				'15' => array('name' => '平安银行', 'code' => 'SPABANK'),
				//'17' => array('name' => '广州银行', 'code' => 'GZCB'),
				//'18' => array('name' => '南京银行', 'code' => 'NJCB'),
				'20' => array('name' => '中国光大银行', 'code' => 'CEB'),
				'24' => array('name' => '浦发银行', 'code' => 'SPDB'),
			);
			$this->utils->debug_log("=======================getting aipay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function checkWithdrawStatus($transId) {

		# ---- First add bank card entry ----
        $params = array();
		$params['merchant_no'] = $this->getSystemInfo("account"); 
		$params['order_no'] = $transId;
		$params['sign'] = $this->sign($params);

		$url = $this->getSystemInfo('url');
		$response = $this->submitGetForm($url, $params,false, $transId);
		$decodedResult = $this->decodeResult($response, true);

		$this->CI->utils->debug_log('======================================aipay checkWithdrawStatus params: ', $params);
		$this->CI->utils->debug_log('======================================aipay checkWithdrawStatus url: ', $url );
		$this->CI->utils->debug_log('======================================aipay checkWithdrawStatus result: ', $response );
		$this->CI->utils->debug_log('======================================aipay checkWithdrawStatus decoded Result', $decodedResult);

		return $decodedResult;
    }
    
	public function callbackFromServer($transId, $params) {
		$response_result_id = parent::callbackFromServer($transId, $params);
		$result = array('success' => false, 'message' => 'Payment failed');
		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		if(empty($params) || is_null($params)){
			$raw_post_data = file_get_contents('php://input', 'r');
        	$this->CI->utils->debug_log("=====================aipay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("=====================aipay json_decode params", $params);
		}
        
        $this->utils->debug_log("=========================aipay callbackFromServer params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        $returnCallbackOrderId = $params['orders'][0]['mer_order_no'];
        $statusCode = $params['orders'][0]['result'];
        if($statusCode == self::CALLBACK_SUCCESS) {
            $msg = sprintf('aipay withdrawal was successful: trade ID [%s]',$returnCallbackOrderId);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::CALLBACK_MSG_SUCCESS;
            $result['success'] = true;
       }else {
            $msg = sprintf('aipay withdrawal payment was not successful  trade ID [%s] ',$returnCallbackOrderId);
            $this->writePaymentErrorLog($msg, $params);
			$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
    	$validateSign = $fields;
    	$params = $fields['orders'][0];
    	unset($fields['orders'][0]);
    	$fields = array_merge($fields,$params);
        # does all required fields exist in the header?
        $requiredFields = array('merchant_no', 'order_no', 'mer_order_no', 'result', 'amount','withdraw_fee','sign');

        foreach ($requiredFields as $f) {
        	if (!array_key_exists($f, $fields)) {
        		$this->writePaymentErrorLog("======================aipay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
        		return false;
        	}
        }

        if ($fields['mer_order_no'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("========================aipay checkCallbackOrder type2 order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

    	if ($fields['result'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================aipay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        $newAmount = ($fields['amount'] - $fields['withdraw_fee']) ; 
        if ($newAmount != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================aipay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $newAmount, $fields);
            return false;
        }

        if ($fields["sign"]!=$this->validateSign($validateSign)) {
        	$this->writePaymentErrorLog('=========================aipay withdrawal checkCallback signature Error', $validateSign);
        	return false;
        }

        # everything checked ok
        return true;
    }

    public function validateSign($data) {
        $post=array(
		"merchant_no"=>$data['merchant_no'],
		"orders"=>$data['orders']
		);
    	$src = json_encode($post);
        $signStr =  $src .$this->getSystemInfo('key');
        $sign=strtolower(md5($signStr));
 
		return $sign;
	}
	
	# -- signing --
	public function sign($params) {
	    $signStr =  $this->createSignStr($params);
        $sign=strtolower(md5($signStr));
 	
		return $sign;
	}

	public function checkWithdrawStatusSign($params) {
	    $signStr =  $this->createSignStr($params,false);
        $sign=strtolower(md5($signStr));
 		
		return $sign;
	}

	private function createSignStr($params,$password=true) {
		$signStr = '';
		foreach ($params as $key => $value) {

			$signStr .= $key."=".$value."&";
		}
		if($password){
			$signStr .= 'pay_pwd='. $this->getSystemInfo('pay_pwd').'&key='. $this->getSystemInfo('key');
		}else{
			$signStr .= 'key='. $this->getSystemInfo('key');
		}
		
		return $signStr;
	}
}
