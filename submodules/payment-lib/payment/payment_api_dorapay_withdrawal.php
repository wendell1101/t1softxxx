<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dorapay.php';

/**
 * DORAPAY  取款
 *
 * * DORAPAY_WITHDRAWAL_PAYMENT_API, ID: 817 
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://client.dorapay.com/
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dorapay_withdrawal extends Abstract_payment_api_dorapay {

	const PAY_RESULT_SUCCESS = '0';

	public function getPlatformCode() {
		return DORAPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dorapay_withdrawal';
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

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## This function returns the params to be submitted to the withdraw URL
	## Note that $bank param is the bank_type ID in database, we compare it with the supported bank_codes by this API
	private $errMsg = 'Payment failed'; # This variable is used to store error message that's available upon submit
	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$params = array();
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

		
       
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("===============================Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
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
			$this->utils->error_log("========================dorapay withdrawal bank whose bankTypeId=[$bank] is not supported by dorapay");
			return array('success' => false, 'message' => 'Bank not supported by dorapay');
			$bank = '无';
		}
        $bankno = $bankInfo[$bank]['code'];	//开户行名称
        $bank = $bankInfo[$bank]['name'];	//开户行名称
        

		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("=========================dorapay withdrawal get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

		if(!empty($playerBankDetails)){
			$bankBranch = $playerBankDetails['branch'];
			$bankSubBranch = $playerBankDetails['branch'];
			$province = $playerBankDetails['province'];	//开户省  卡号的开户省
			$city = $playerBankDetails['city'];	//开户市 卡号的开户市
			
		}
		$playeruid= $this->uuid();
		$params = array();
		$params['app_id'] = null;
		$params['format'] = null;
        $params['timestamp'] = date('Ymdhis');
        $params['charset'] = 'UTF-8';
        $params['api_version'] = '1.5';
        $params['client_ip'] = $this->getClientIp();
        $biz_content_arr = array(
        	'company_id' => $this->getSystemInfo("account"),
        	'company_order_no' => $transId,
        	'player_id' => $playeruid,
        	'terminal' => $this->utils->is_mobile()?'1':'2',
        	'notify_url' => $this->getNotifyUrl($transId),
        	'amount_money' => $this->convertAmountToCurrency($amount),
        	'extra_param' => null, 
        	'card_no' => $accNum,
        	'name' => $name,
        	'nick_name' => null,
        	'qrcode_url' => null,
        	'channel_code' => self::PAYTYPE_EB_BANKCARD,
        	'bank_addr' => $bankno,
        		);
        $params["biz_content"] = $biz_content_arr;
		//$this->configParams($params, $order->direct_pay_extra_info);

		$sign_arr = $this->create_sign_arr($params);
		$params["biz_content"] = json_encode($params["biz_content"]);
		$params['sign'] = $this->sign($sign_arr);

		$this->CI->utils->debug_log('=========================dorapay withdrawal getWithdrawParams', $params);

		return $params;
	}

	public function uuid(){
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); 
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); 
        return vsprintf('%s%s-%s-%s', str_split(bin2hex($data), 4));
	}

	protected function create_sign_arr($params){
		$result = array(
			'company_id' => $params['biz_content']['company_id'],
			'company_order_no' => $params['biz_content']['company_order_no'],
			'player_id' => $params['biz_content']['player_id'],
			'amount_money' => $params['biz_content']['amount_money'],
			'client_ip' => $params['client_ip'] ,
			'api_version' => $params['api_version'],
			'channel_code' => $params['biz_content']['channel_code']
		);
		return $result;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false, 'message' => 'payment failed');		
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
		
		$this->CI->utils->debug_log('======================================dorapay submitWithdrawRequest params: ', $params);

		$url = $this->getSystemInfo('url');		
		$this->CI->utils->debug_log('======================================dorapay withdrawal url: ', $url );

		$postString = http_build_query($params);
		$curlConn = curl_init($url);
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
		$this->utils->debug_log("===============================dorapay withdrawal postString", $postString, "curl result", $result);
		curl_close($curlConn);
		

		$decodedResult = $this->decodeResult($result['result']);
		$this->utils->debug_log("===============================dorapay withdrawal decoded Result", $decodedResult);
		
		return $decodedResult;

	}

	public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $result_array = $result;
		if($queryAPI) {
			$this->utils->debug_log("=========================dorapay checkWithdrawStatus decoded result string", $result_array);
		}
		else {
			$this->utils->debug_log("=========================dorapay withdrawal decoded result string", $result_array);
		}
		
		if ($result_array['status'] == '200'){

			if(array_key_exists("status",$result['data'])){
				if($result_array['data']['status'] == '0') {
					$message = 'dorapay payment response successful, result: '.$result_array['data']['status'].$result_array['msg'];
					return array('success' => true, 'message' => $message);
				} else {
					$message = $this->errMsg = 'dorapay payment failed for ['.$result_array['data']['error_msg'].']'.$result_array['message'].'status is not 0';
					return array('success' => false, 'message' => $message);
				}
			}else {
				$this->utils->debug_log("====================dorapay array_key_exists is not exists status: ", $result);
			}
		}else{
			$message = $this->errMsg = 'dorapay payment status is not 200 failed for ['.$result_array['msg'].']'.$result_array['message'];
			$this->utils->debug_log("====================dorapay status is not 200: ", $result);
			return array('success' => false, 'message' => $message);
			
		}
	}	

	public function getHuidpayBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("dorapay_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1], 'code' => $bankInfoItem[2]);
			}
			$this->utils->debug_log("==================getting dorapay bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				'1' => array('name' => '中国工商银行', 'code' => 'ICBC'),
				'2' => array('name' => '招商银行', 'code' => 'CMBCHINA'),	
				'3' => array('name' => '中国建设银行', 'code' => 'CCB'),
				'4' => array('name' => '中国农业银行', 'code' => 'ABC'),
				'5' => array('name' => '交通银行', 'code' => 'BOCO'),
				'6' => array('name' => '中国银行', 'code' => 'BOC'),
				'7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
				'8' => array('name' => '广东发展银行', 'code' => 'GDB'),
				'10' => array('name' => '中信银行', 'code' => 'ECITIC'),
				'11' => array('name' => '民生银行', 'code' => 'CMBC'),
				'12' => array('name' => '中国邮政储蓄', 'code' => 'POST'),
				'13' => array('name' => '兴业银行', 'code' => 'CIB'),
				'14' => array('name' => '华夏银行', 'code' => 'HXB'),
				'15' => array('name' => '平安银行', 'code' => 'SZCB'),
				'17' => array('name' => '广州银行', 'code' => 'GZCB'),
				'18' => array('name' => '南京银行', 'code' => 'NJCB'),
				'20' => array('name' => '光大银行', 'code' => 'CEB'),
				'24' => array('name' => '浦发银行', 'code' => 'SPDB'),
			);
			$this->utils->debug_log("=======================getting dorapay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function checkWithdrawStatus($transId) {
		$this->CI->load->model(array('wallet_model'));
		$walletaccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		$dateTimeString = $walletaccount['dwDateTime'];
		$datetime = new DateTime($dateTimeString);

		# ---- First add bank card entry ----
        
		$param['merchant'] = $this->getSystemInfo("account");
		$params['withdrawId'] = $transId;
		$sign_key = array(
			'merchant', 'withdrawId'
		);
		$param['sign'] = $this->sign($param,$sign_key);
		
		$this->CI->utils->debug_log('======================================dorapay checkWithdrawStatus params: ', $param);

		$url = $this->getSystemInfo('url');	
		$this->CI->utils->debug_log('======================================dorapay checkWithdrawStatus url: ', $url );

		$response = $this->submitGetForm($url, $param);

		$this->CI->utils->debug_log('======================================dorapay checkWithdrawStatus result: ', $response );
		
		$decodedResult = $this->decodeResult($response, true);

		return $decodedResult;
    }
    
	public function callbackFromServer($transId, $params) {


        $this->utils->debug_log("=========================dorapay callbackFromServer params", $params);

        $decode_biz_content = json_decode($params['biz_content'],true);
        $this->utils->debug_log("=========================dorapay callbackFromServer params biz_content", $decode_biz_content);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if($params['type']=='2'){

            $this->CI->utils->debug_log('======================================dorapay process type=2 order id', $transId);
        	

            $verifyResult=$this->checkCallbackOrder_type2($order,$params);

            $success=true;
            $result['success'] = $success;

			if ($verifyResult == $success) {
				$resultContent=[
	                    'company_order_no'=>$decode_biz_content['company_order_no'],
	                    'trade_no'=>$decode_biz_content['trade_no'],
	                    'status'=>0,
	                ];

	            $result['message'] = json_encode($resultContent);
			} else {
				$resultContent=[
						'error_msg'=>'handle_callback_error',
	                    'company_order_no'=>$decode_biz_content['company_order_no'],
	                    'trade_no'=>$decode_biz_content['trade_no'],
	                    'status'=>1,
	                ];

	            $result['message'] = json_encode($resultContent);
			}

            return $result;

        }elseif($params['type']=='3'){

           $this->CI->utils->debug_log('======================================dorapay process type=3 order id', $transId);

            $verifyResult=$this->checkCallbackOrder($order, $params);

            $success=true;
            $result['success'] = $success;

			if ($verifyResult == $success) {
				$resultContent=[
	                    'company_order_no'=>$decode_biz_content['company_order_no'],
	                    'trade_no'=>$decode_biz_content['trade_no'],
	                    'status'=>0,
	                ];
	            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId);
	            $result['message'] = json_encode($resultContent);
			} else {
				$resultContent=[
						'error_msg'=>'handle_callback_error',
	                    'company_order_no'=>$decode_biz_content['company_order_no'],
	                    'trade_no'=>$decode_biz_content['trade_no'],
	                    'status'=>1,
	                ];
	            
	            $result['message'] = json_encode($resultContent);
			}

            return $result;
        }
	
    }

    public function checkCallbackOrder_type2($order,$fields) {
        # does all required fields exist in the header?
        $decode_biz_content = json_decode($fields['biz_content'], true);
        $array_merge = array_merge($fields,$decode_biz_content);

        $requiredFields = array(

        	'timestamp','charset','api_version','sign','type','company_id', 'player_id','company_order_no','trade_no','amount_money','channel_code'
        );
        foreach ($requiredFields as $f) {
        	if (!array_key_exists($f, $array_merge)) {
        		$this->writePaymentErrorLog("======================dorapay withdrawal checkCallbackOrder_type2 missing parameter: [$f]", $array_merge);
        		return false;
        	}
        }

        $check_amount = $order['amount'];
		if ( $array_merge['amount_money'] != $check_amount )
		{
			$this->writePaymentErrorLog("=====================dorapay Payment type2 amounts do not match, expected [$order->amount]", $array_merge);
			return false;
		}

        if ($array_merge['company_order_no'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("========================dorapay checkCallbackOrder type2 order IDs do not match, expected [$order->secure_id]", $array_merge);
            return false;
        }


        // if ($array_merge["sign"]!=$this->validateSign_type2($fields)) {
        // 	$this->writePaymentErrorLog('=========================dorapay withdrawal checkCallback type2 signature Error', $fields);
        // 	return false;
        // }

        # is signature authentic?
		$chk_sign = true;
		if (!$this->validateSign_type2($fields, $array_merge['sign'])) {
			$this->CI->utils->debug_log('====================dorapay checkCallbackOrder type2 Signature Error', $fields);
			$this->CI->utils->debug_log('====================dorapay checkCallbackOrder type2 Signature Recheck');
			if(!$this->validateSign_type2($fields, $array_merge['sign'], true)){
				$this->writePaymentErrorLog('====================dorapay checkCallbackOrder type2 Signature Recheck Error', $fields);
				$chk_sign = false;
			}
		}

        # everything checked ok
        return true;
    }

    public function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
    	$decode_biz_content = json_decode($fields['biz_content'], true);
        $array_merge = array_merge($fields,$decode_biz_content);

        $requiredFields = array(

        	'timestamp','charset','api_version','sign','type','company_id', 'player_id','company_order_no','trade_no','actual_amount','status','apply_time','operating_time','api_version'

        );
        foreach ($requiredFields as $f) {
        	if (!array_key_exists($f, $array_merge)) {
        		$this->writePaymentErrorLog("======================dorapay withdrawal type3 checkCallbackOrder type3 missing parameter: [$f]", $array_merge);
        		return false;
        	}
        }

        if ($array_merge['status'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['status'];
			$this->writePaymentErrorLog("=====================dorapay Payment type3 was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		$check_amount = $order['amount'];
		if ( $array_merge['actual_amount'] != $check_amount )
		{
			$this->writePaymentErrorLog("=====================dorapay Payment type3 amounts do not match, expected [$check_amount]", $array_merge);
			return false;
		}

        if ($array_merge['company_order_no'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("========================dorapay checkCallbackOrder type3 order IDs do not match, expected [$order->secure_id]", $array_merge);
            return false;
        }

        // if ($array_merge["sign"]!=$this->validateSign($fields)) {
        // 	$this->writePaymentErrorLog('=========================dorapay withdrawal checkCallback type3 signature Error', $fields);
        // 	return false;
        // }
        # is signature authentic?
		$chk_sign = true;
		if (!$this->validateSign_type3($fields, $array_merge['sign'])) {
			$this->CI->utils->debug_log('====================dorapay checkCallbackOrder type3 Signature Error', $fields);
			$this->CI->utils->debug_log('====================dorapay checkCallbackOrder type3 Signature Recheck');
			if(!$this->validateSign_type3($fields, $array_merge['sign'], true)){
				$this->writePaymentErrorLog('====================dorapay checkCallbackOrder type3 Signature Recheck Error', $fields);
				$chk_sign = false;
			}
		}

        # everything checked ok
        return true;
    }

    public function validateSign_type2($params,$signature, $recheck = false) {
    	// $params['key']=$this->getSystemInfo('key');
    	$temp = explode('"amount_money":', $params['biz_content']);
    	$temp2 = explode(',', $temp[1]);
    	$amount = $temp2[0];
		$amount = str_replace('"', '', $amount);
		$decode_biz_content= json_decode($params['biz_content'], true);

		$signStr = $this->createSignStr_type2($params,$decode_biz_content,$amount);
		

		if ( $signature == $signStr ) {
			return true;

		} else {
			if($recheck){ #the broken sign would contain a space
			
				$amount_money = $decode_biz_content['amount_money'];
				if(is_int(strpos($amount_money,','))){

					$amount = str_replace( ',', '', $amount_money );

					$signStr = $this->createSignStr_type2($params,$decode_biz_content,$amount);
					if ($signature == $signStr){
						return true;
					}
					
				}else{
					$amount = $this->convertAmountToCurrency($amount_money);
					$signStr = $this->createSignStr_type2($params,$decode_biz_content,$amount);

					if ($signature == $signStr){
						return true;
					}

				}
			}
			return false;
		}

	}

	private function createSignStr_type2($params,$decode_biz_content,$amount) {
		
		$params_keys = array(

			'company_id','company_order_no','player_id','amount_money','name','nick_name','channel_code' 
		);
		$signStr = '';
		$params_len = count($params_keys);
		$counter = 0;

		foreach($params_keys as $key => $value) {
			++$counter;

			if($counter == $params_len){
				$signStr .= $value .'='. $decode_biz_content[$value];
				continue;
				}
				if ($value== 'amount_money'){

					$signStr .= $value .'='. $amount.'&';
				}else{
					$signStr .= $value .'='. $decode_biz_content[$value].'&';
				}		
		}
		$signStr .= $this->getSystemInfo('key');
		$sign=md5($signStr);
		
		return $sign;
	}

    public function validateSign_type3($params,$signature, $recheck = false) {

		$decode_biz_content= json_decode($params['biz_content'], true);

		$signStr = $this->createSignStr_type3($params,$decode_biz_content,null);
		

		if ( $signature == $signStr ) {
			return true;

		} else {
			if($recheck){ #the broken sign would contain a space
			
				$amount_money = $decode_biz_content['amount_money'];
				if(is_int(strpos($amount_money,','))){

					$amount = str_replace( ',', '', $amount_money );

				
					$signStr = $this->createSignStr_type3($params,$decode_biz_content,$amount);
					if ($signature == $signStr){
						return true;
					}
					
				}else{
					$amount = $this->convertAmountToCurrency($amount_money);
					$signStr = $this->createSignStr_type3($params,$decode_biz_content,$amount);
				
					if ($signature == $signStr){
						return true;
					}

				}
			}
			return false;
		}
		
	}

	private function createSignStr_type3($params,$decode_biz_content,$amount) {
		$params_keys = array(

			'company_id','company_order_no','player_id','actual_amount','apply_time','operating_time','api_version'

		);
		$signStr = '';
		$params_len = count($params_keys);
		$counter = 0;

		foreach($params_keys as $key => $value) {
			++$counter;

			if($counter == $params_len){
					$signStr .= $value .'='. $decode_biz_content[$value];
					continue;
				}
				if ($value== 'actual_amount'){

					$signStr .= $value .'='. $amount.'&';
				}else{
					$signStr .= $value .'='. $decode_biz_content[$value].'&';
				}			
		}
		$signStr .= $this->getSystemInfo('key');
		$sign=md5($signStr);

		return $sign;
	}
	
	# -- signing --
	public function sign($data) {

		$signStr = '';
		$params_len = count($data);
		$counter = 0;

		foreach($data as $key => $value) {
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

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', ',');
	}

}
