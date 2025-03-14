<?php
require_once dirname(__FILE__) . '/abstract_payment_api_easypay.php';

/**
 * EASYPAY 
 *
 * * EASYPAY_WITHDRAWAL_PAYMENT_API, ID: 657
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://client.easypay.com/
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_easypay_withdrawal extends Abstract_payment_api_easypay {
	const CALLBACK_STATUS_SUCCESS = 1;

	public function getPlatformCode() {
		return EASYPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'easypay_withdrawal';
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

		$params = array();
		$datestring=date('Ymd');
        $params['v_pagecode'] = "1005";
        $params['v_mid'] = $this->getSystemInfo("account");
        $params['v_oid'] = $datestring.'-'.$this->getSystemInfo("account").'-'.ltrim($transId, 'W');  //日期-商户编号-商户流水
        $params['v_amount'] = $this->convertAmountToCurrency($amount);

		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
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
		$accountType = '0';
		$currency = 'CNY';
		//$mobile = $this->getBankMobileNo();
		$idType = '身份证';
		//$idNo = $this->getIdNo();
		$idNo = "123456789123456789";
		$licenseNo = '123456';
		$merchantOrderNo = $transId;
        $remark = "Withdrawal";
        

		# look up bank code
		$bankInfo = $this->getHuidpayBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================easypay withdrawal bank whose bankTypeId=[$bank] is not supported by easypay");
			return array('success' => false, 'message' => 'Bank not supported by easypay');
			$bank = '无';
		}
        $bankno = $bankInfo[$bank]['code'];	//开户行名称
        $bank = $bankInfo[$bank]['name'];	//开户行名称
        

		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("=========================easypay withdrawal get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

		if(!empty($playerBankDetails)){
			$bankBranch = $playerBankDetails['branch'];
			$bankSubBranch = $playerBankDetails['branch'];
			$province = $playerBankDetails['province'];	//开户省  卡号的开户省
			$city = $playerBankDetails['city'];	//开户市 卡号的开户市
			
		}

		
        $params['v_payeename'] = $name;
        $params['v_payeecard'] = $accNum;
        $params['v_accountprovince'] = $province;
        $params['v_accountcity'] = $city;
        $params['v_bankname'] = $bank;
        $params['v_bankno'] = $bankno;
        $params['v_ymd'] = date('Ymd');
        $params['v_url'] = $this->getNotifyUrl($transId); 
        //$params['v_sign'] = $this->sign($params);

	

		return $params;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false, 'message' => 'payment failed');
		$success = false;
		$message = 'payment failed';
		
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
		
		//$queryStr = $this->createSignStr($params);
		$params['v_sign'] = $this->sign($params);
		
		$this->CI->utils->debug_log('======================================easypay submitWithdrawRequest params: ', $params);

		$url = $this->getSystemInfo('url');		
		$this->CI->utils->debug_log('======================================easypay withdrawal url: ', $url );

        $postString='['.json_encode($params).']';
        $curlConn = curl_init($url);
        curl_setopt ($curlConn, CURLOPT_POST, 1);
        curl_setopt($curlConn, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curlConn, CURLOPT_HEADER, false);
		curl_setopt($curlConn, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlConn, CURLOPT_POSTFIELDS, $postString);

		$this->setCurlProxyOptions($curlConn);

		$result['result'] = curl_exec($curlConn);
		$result['success'] = (curl_errno($curlConn) == 0);
		$result['message'] = curl_error($curlConn);
		$this->utils->debug_log("===============================easypay withdrawal postString", $postString, "curl result", $result);
		curl_close($curlConn);

		$decodedResult = $this->decodeResult($result['result']);
		$this->utils->debug_log("===============================easypay withdrawal decoded Result", $decodedResult);
		
		return $decodedResult;
	}

	public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $result_array = $result[0];
		if($queryAPI) {
			$this->utils->debug_log("=========================easypay checkWithdrawStatus decoded result string", $result_array);
		}
		else {
			$this->utils->debug_log("=========================easypay withdrawal decoded result string", $result_array);
		}
		
		if($result_array['result'] == '提交成功') {
			$message = 'easypay payment response successful, result: '.$result_array['result'];
            
			return array('success' => true, 'message' => $message);
		} 
		else {
			$this->errMsg = 'easypay payment failed for ['.$result_array['ResCode'].']'.$result_array['ResDesc'];
		} 

		return array('success' => false, 'message' => $this->errMsg);
	}	

	public function getHuidpayBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("easypay_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1], 'code' => $bankInfoItem[2]);
			}
			$this->utils->debug_log("==================getting easypay bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				'1' => array('name' => '中国工商银行', 'code' => '102100099996'),
				'2' => array('name' => '招商银行', 'code' => '308584000013'),	
				'3' => array('name' => '中国建设银行', 'code' => '105100000017'),
				'4' => array('name' => '中国农业银行', 'code' => '103100000026'),
				'5' => array('name' => '交通银行', 'code' => '301290000007'),
				'6' => array('name' => '中国银行', 'code' => '104100000004'),
				'8' => array('name' => '广东发展银行', 'code' => '306581000003'),
				'10' => array('name' => '中信银行', 'code' => '302100011000'),
				//'11' => array('name' => '民生银行', 'code' => 'CMBC'),
				'12' => array('name' => '中国邮政储蓄', 'code' => '403100000004'),
				'13' => array('name' => '兴业银行', 'code' => '309391000011'),
				'14' => array('name' => '华夏银行', 'code' => '304100040000'),
				'15' => array('name' => '平安银行', 'code' => '313584099990'),
				'20' => array('name' => '光大银行', 'code' => '303100000006'),
			);
			$this->utils->debug_log("=======================getting easypay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function checkWithdrawStatus($transId) {
		$this->CI->load->model(array('wallet_model'));
		$walletaccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		$dateTimeString = $walletaccount['dwDateTime'];
		$datetime = new DateTime($dateTimeString);

		# ---- First add bank card entry ----
        $param = array();
        $datestring=date('Ymd');
        $param['v_pagecode'] = '1006';
		$param['v_mid'] = $this->getSystemInfo("account");
		$param['v_tpoid'] = $datestring.'-'.$this->getSystemInfo("account").'-'.ltrim($transId, 'W');;
		$param['v_type'] = "1";
		
		$param['v_sign'] = $this->sign($param);
		
		$this->CI->utils->debug_log('======================================easypay checkWithdrawStatus params: ', $param);

		$url = $this->getSystemInfo('url');	
		$this->CI->utils->debug_log('======================================easypay checkWithdrawStatus url: ', $url );

		$response = $this->submitGetForm($url, $param);

		$this->CI->utils->debug_log('======================================easypay checkWithdrawStatus result: ', $response );
		
		$decodedResult = $this->decodeResult($response, true);

		return $decodedResult;
    }
    
	public function callbackFromServer($transId, $params) {
        $result = array('success' => false, 'message' => 'Payment failed');


        $this->utils->debug_log('=========================easypay process withdrawalResult order id', $transId);
       
        $result = $params;

        $this->utils->debug_log("=========================easypay checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($result['v_status'] == '2000') {
            $this->utils->debug_log('=========================easypay withdrawal payment was successful: trade ID [%s]', $params['v_oid']);
            //$msg = sprintf('easypay withdrawal payment was successful: trade ID [%s]', $params['v_oid']);
            $data['result']="ok";
            $msg ='['.json_encode($data).']';
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = $msg;
            $result['success'] = true;
       }
       else if($result['v_status'] == '2001') {
           $result['success'] = false;
           $result['message'] = 'Status Code: ['.$result['v_status'].']: rejected';
           $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
       }	
        else {
           $realStateDesc = $result['info'];
            $this->errMsg = '['.$realStateDesc.']';
            $msg = sprintf('======================easypay withdrawal payment was not successful: '.$this->errMsg);
            $this->writePaymentErrorLog($msg, $params);
       
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array('v_pagecode', 'v_mid', 'v_oid', 'v_orderid', 'v_status', 'v_sign');
        foreach ($requiredFields as $f) {
        	if (!array_key_exists($f, $fields)) {
        		$this->writePaymentErrorLog("======================bojimart withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
        		return false;
        	}
        }

        if ($fields["v_sign"]!=$this->validateSign($fields)) {
        	$this->writePaymentErrorLog('=========================bojimart withdrawal checkCallback signature Error', $fields);
        	return false;
        }

       
        # everything checked ok
        return true;
    }

    private function validateSign($params) {
		$params_keys = array(
			'v_pagecode', 'v_mid', 'v_oid', 'v_orderid', 'v_amount', 'v_status'
		);
		$signStr = $this->createSignStr($params,$params_keys);


		//$valid = openssl_verify($signStr, base64_decode($params['sign']), $this->getPubKey(), OPENSSL_ALGO_MD5);
		$sign=strtoupper(hash('sha1', $signStr));

		return $sign;
	}
}
