<?php
require_once dirname(__FILE__) . '/abstract_payment_api_flightpaying.php';

/**
 * FLIGHTPAYING 聚聯 取款
 *
 * * FLIGHTPAYING_WITHDRAWAL_PAYMENT_API, ID: 882
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://flightpaying.com/trade/api/
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_flightpaying_withdrawal extends Abstract_payment_api_flightpaying {
	const CALLBACK_STATUS_SUCCESS = 1;

	public function getPlatformCode() {
		return FLIGHTPAYING_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'flightpaying_withdrawal';
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
		$this->utils->debug_log("===============================flightpaying Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
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
			$this->utils->error_log("========================flightpaying withdrawal bank whose bankTypeId=[$bank] is not supported by flightpaying");
			return array('success' => false, 'message' => 'Bank not supported by flightpaying');
			$bank = '无';
		}
        $bankno = $bankInfo[$bank]['code'];	//开户行名称
        $bank = $bankInfo[$bank]['name'];	//开户行名称
        

		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("=========================flightpaying withdrawal get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

		if(!empty($playerBankDetails)){
			$bankBranch = $playerBankDetails['branch'];
			$bankSubBranch = $playerBankDetails['branch'];
			$province = $playerBankDetails['province'];	//开户省  卡号的开户省
			$city = $playerBankDetails['city'];	//开户市 卡号的开户市
			
		}

		$params = array();
		$params['service'] = 'applyAgentPay';
		$params['signType'] = 'MD5';
		$params['inputCharset'] = 'UTF-8';
        $params['sysMerchNo'] = $this->getSystemInfo("account");   
        $params['outOrderNo'] = $transId;
		$params['orderTime'] = date('Ymdhis');
		$params['finaCode'] = $bankno;
		$params['payeeAcct'] = $accNum;
		$params['payeeName'] = $name;
		$params['bankName'] = $bank;
        $params['applyAmt'] = $this->convertAmountToCurrency($amount);
        $params['payeeAcctAttr'] = 'PRIVATE';
		// $params['drawBankBranchName'] = $bankSubBranch;
		// $params['noticeUrl'] = $this->getNotifyUrl($transId); 
        $params['bankProvince'] = $province;
		$params['bankCity'] = $city;
		
        $params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log('=========================flightpaying withdrawal paramStr before sign', $params);

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
		
		$this->CI->utils->debug_log('======================================flightpaying submitWithdrawRequest params: ', $params);

		$url = $this->getSystemInfo('url');	
		$interface_name = $params['service'];
		$new_url = $url.$interface_name;
			
		$this->CI->utils->debug_log('======================================flightpaying withdrawal url: ', $new_url );

		$postString = http_build_query($params);
		$curlConn = curl_init($new_url);
		curl_setopt($curlConn, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curlConn, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlConn, CURLOPT_POSTFIELDS, $postString);

		$this->setCurlProxyOptions($curlConn);

		$result['result'] = curl_exec($curlConn);
		$result['success'] = (curl_errno($curlConn) == 0);
		$result['message'] = curl_error($curlConn);
		$this->utils->debug_log("===============================flightpaying withdrawal postString", $postString, "curl result", $result);
		curl_close($curlConn);
		

		$decodedResult = $this->decodeResult($result['result']);
		$this->utils->debug_log("===============================flightpaying withdrawal decoded Result", $decodedResult);
		return $decodedResult;

	}

	public function decodeResult($resultString, $queryAPI = false) {
        $result_array = json_decode($resultString, true);
        // $result_array = $result;
        $this->CI->utils->debug_log('==============flightpaying submitWithdrawRequest decodeResult json decoded', $result_array);
		if($queryAPI) {
			if(array_key_exists("retCode",$result_array)) {
				$returnCode = $result_array['orderStatus'];
				$returnDesc = $this->getMappingErrorMsg($result_array['orderStatus']);
				if ($returnCode == '05'){
					$message = 'flightpaying payment response successful, result Code:'.$returnCode.", Desc: ".$returnDesc;
					return array('success' => true, 'message' => $message);
				}else if($returnCode == '06'){
					$msg = "flightpaying payment failed for Code:".$returnCode.", Desc: ".$returnDesc;
					$transId = $result_array['outOrderNo'];
					$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
					return array('success' => false, 'message' => $msg);
				}else{
					$message = "flightpaying payment failed for Code:".$returnCode.", Desc: ".$returnDesc;;
					return array('success' => false, 'message' => $message);
				}
			} 
			else{
				$message = $this->errMsg = 'flightpaying payment failed retCode is not exists';
				return array('success' => false, 'message' => $message);
			} 
		$this->utils->debug_log("=========================flightpaying checkWithdrawStatus decoded result queryAPI", $result_array);
		return array('success' => false, 'message' => $this->errMsg);
		}
		else {
			if(array_key_exists("retCode",$result_array)) {
				$returnCode = $result_array['retCode'];
				$returnDesc = $result_array['retMsg'];
				
				if ($returnCode == '0000'){
					$message = 'flightpaying payment response successful, result Code:'.$returnCode.", Desc: ".$returnDesc;;
					return array('success' => true, 'message' => $message);

				}else{
					$message = "flightpaying payment failed for Code:".$returnCode.", Desc: ".$returnDesc;
					return array('success' => false, 'message' => $message);
				} 
				
			} 
			else{
				$message = $this->errMsg = 'flightpaying payment failed retCode is not exists';
				return array('success' => false, 'message' => $message);
			} 
		$this->utils->debug_log("=========================flightpaying withdrawal decoded result string", $result_array);
		return array('success' => false, 'message' => $this->errMsg);
				
		}
	}	

	private function getMappingErrorMsg($state) {
		$msg = "";
		switch ($state) {
			case '00':
				$msg = "提交申请";
				break;

			case '01':
				$msg = "审核通过";
				break;

			case '02':
				$msg = "申请被拒绝";
				break;			

			case '03':
				$msg = "已打批次";
				break;

			case '04':
				$msg = "提交到渠道";
				break;

			case '05':
				$msg = "代付成功";
				break;

			case '06':
				$msg = "代付失败";
				break;

			default:
				$msg = "";
				break;
		}
		return $msg;
	}

	public function getHuidpayBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("flightpaying_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1], 'code' => $bankInfoItem[2]);
			}
			$this->utils->debug_log("==================getting flightpaying bank info from extra_info: ", $bankInfo);
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
			$this->utils->debug_log("=======================getting flightpaying bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function checkWithdrawStatus($transId) {
		// $this->CI->load->model(array('wallet_model'));
		// $walletaccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		// $dateTimeString = $walletaccount['dwDateTime'];
		// $datetime = new DateTime($dateTimeString);

		# ---- First add bank card entry ----
        $params = array();
		$params['service'] = 'queryAgentPay';
		$params['signType'] = 'MD5';
		$params['inputCharset'] = 'UTF-8';
		$params['sysMerchNo'] = $this->getSystemInfo("account"); 
		$params['outOrderNo'] = $transId;
		$params['sign'] = $this->sign($params);

		$url = $this->getSystemInfo('url');
		$interface_name = $params['service'];
		$new_url = $url.$interface_name;
		$response = $this->submitPostForm($new_url, $params,false, $transId);
		$decodedResult = $this->decodeResult($response, true);

		$this->CI->utils->debug_log('======================================flightpaying checkWithdrawStatus params: ', $params);
		$this->CI->utils->debug_log('======================================flightpaying checkWithdrawStatus url: ', $new_url );
		$this->CI->utils->debug_log('======================================flightpaying checkWithdrawStatus result: ', $response );
		$this->CI->utils->debug_log('======================================flightpaying checkWithdrawStatus decoded Result', $decodedResult);

		return $decodedResult;
    }
    
	public function callbackFromServer($transId, $params) {
        $result = array('success' => false, 'message' => 'Payment failed');


        $this->utils->debug_log('=========================flightpaying process withdrawalResult order id', $transId);
       
        $result = $params;

        $this->utils->debug_log("=========================flightpaying checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($result['retCode'] == '0000') {
            $this->utils->debug_log('=========================flightpaying withdrawal payment was successful: trade ID [%s]', $params['withdrawId']);
            
            $msg ='SUCCESS';
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = $msg;
            $result['success'] = true;
       }else {
           $realStateDesc = $result['withdrawId'];
            $this->errMsg = '['.$realStateDesc.']';
            $msg = sprintf('======================flightpaying withdrawal payment was not successful: '.$this->errMsg);
            $this->writePaymentErrorLog($msg, $params);
       
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array('service', 'signType', 'sign', 'inputCharset', 'sysMerchNo');

        foreach ($requiredFields as $f) {
        	if (!array_key_exists($f, $fields)) {
        		$this->writePaymentErrorLog("======================bojimart withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
        		return false;
        	}
        }

        if ($fields['sysMerchNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("========================dorapay checkCallbackOrder type2 order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        if ($fields["sign"]!=$this->validateSign($fields)) {
        	$this->writePaymentErrorLog('=========================bojimart withdrawal checkCallback signature Error', $fields);
        	return false;
        }

        # everything checked ok
        return true;
    }

    public function validateSign($params) {
    	$callback_sign = $params['sign'];
        unset($params['sign']);
       	unset($params['signType']);
        ksort($params);       
        $signStr = '';
		$params_len = count($params);
		$counter = 0;
		foreach($params as $key => $value) {
			++$counter;
			if($counter == $params_len){
				$signStr .= $key."=".$value;
				continue;
			}			
			$signStr .= $key.'='.$value.'&';
		}
		$signStr .= $this->getSystemInfo('key');
        $sign=md5($signStr);
        
		return $sign;
	}
	
	# -- signing --
	public function sign($data) {
	    $paramsKeys = $data;
        ksort($paramsKeys);
        unset($paramsKeys['signType']);
        $signStr = '';
		$params_len = count($paramsKeys);
		$counter = 0;
		foreach($paramsKeys as $key => $value) {
			++$counter;
			if($counter == $params_len){
				$signStr .= $key."=".$value;
				continue;
			}			
			$signStr .= $key.'='.$value.'&';
		}
		$signStr .= $this->getSystemInfo('key');
        $sign=md5($signStr);

	
		return $sign;
	}
}
