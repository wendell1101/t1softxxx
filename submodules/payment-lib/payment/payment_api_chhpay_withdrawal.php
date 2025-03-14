<?php
require_once dirname(__FILE__) . '/abstract_payment_api_chhpay.php';

/**
 * CHHPAY
 *
 * * CHHPAY_WITHDRAWAL_PAYMENT_API, ID: 600
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://client.chhpay.com/
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_chhpay_withdrawal extends Abstract_payment_api_chhpay {
	const CALLBACK_STATUS_SUCCESS = 1;

	public function getPlatformCode() {
		return CHHPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'chhpay_withdrawal';
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

	public function getSecretInfoList() {
		$secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'chhpay_pub_key', 'chhpay_priv_key');
		return $secretsInfo;
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
		$params['hmac'] = $this->sign($params);
		$this->CI->utils->debug_log('======================================chhpay submitWithdrawRequest params: ', $params);

		$url = $this->getSystemInfo('url');
		$this->CI->utils->debug_log('======================================chhpay withdrawal url: ', $url );

		$postString = is_array($params) ? http_build_query($params) : $params;
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
		$this->utils->debug_log("===============================chhpay withdrawal postString", $postString, "curl result", $result);
		curl_close($curlConn);

		$result['result'] = preg_split("/\s+/",$result['result']);
		foreach ($result['result'] as $v){
		   $decode_data[substr($v,0,strpos($v,'='))]=substr($v,strpos($v,'=')+1);
		}

		$decodedResult = $this->decodeResult($decode_data);
		$this->utils->debug_log("===============================chhpay withdrawal decoded Result", $decodedResult);

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
		$params = array();
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));


		# look up bank code
		$bankInfo = $this->getChhpayBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================chhpay withdrawal bank whose bankTypeId=[$bank] is not supported by chhpay");
			return array('success' => false, 'message' => 'Bank not supported by chhpay');
			$bank = '无';
		}
        $bankName = $bankInfo[$bank];	//銀行名稱

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
        $params['p0_Cmd'] = "TransPay";
        $params['p1_MerId'] = $this->getSystemInfo("account");
        $params['p2_Order'] = $transId;
		$params['p3_CardNo'] = $accNum;
		$params['p4_BankName'] = $bankName;
		$params['p5_AtName'] = $name;
        $params['p6_Amt'] = $this->convertAmountToCurrency($amount);
        $params['pc_NewType'] = "PRIVATE";
        $params['pd_BranchBankName'] = $bankBranch;
        $params['pe_Province'] = $province;
		$params['pf_City'] = $city;

		return $params;
	}

	## This function takes in the return value of the URL and translate it to the following structure
	## array('success' => false, 'message' => 'Error message')
	public function decodeResult($resultString, $queryAPI = false) {
		#different return type
		if($queryAPI) {
			$result = json_decode($resultString, true);
			$this->utils->debug_log("=========================chhpay checkWithdrawStatus decoded result string", $result);
		}
		else {
			$result = $resultString;
			$this->utils->debug_log("=========================chhpay withdrawal decoded result string", $result);
		}
		$returnCode = $result['r1_Code'];
		$returnDesc = $result['r7_Desc'];
		$this->utils->debug_log("=========================chhpay withdrawal returnDesc", $returnDesc);
		#when success
		if($returnCode == '0000') {
			$message = "Chhpay withdrawal response successful, r2_TrxId: ". $result['r2_TrxId'];
			if($queryAPI) {
				$message = "Chhpay withdrawal success! r2_TrxId: ". $result['r2_TrxId'];
			}
			return array('success' => true, 'message' => $message);
		} else {
			if($returnDesc == '' || $returnDesc == false) {
				$this->utils->debug_log("=========================chhpay withdrawal no error description");
				# look up return code
				$returnInfo = $this->getChhpayReturnInfo();
				if(!array_key_exists($returnCode, $returnInfo)) {
					$this->utils->error_log("========================chhpay return UNKNOWN ERROR!");
					$returnDesc = "未知错误";
				}
				else {
					$returnDesc = $returnInfo[$returnCode];	//开户行名称
				}
			}

			if($queryAPI) {
				if($returnCode == "3002"){
					$sucess = false;
					$message = "Chhpay withdrawal failed, Code: ".$returnCode.", Desc: ".$returnDesc;
					$this->CI->wallet_model->withdrawalAPIReturnFailure($message);
					return array('success' => $sucess, 'message' => $message);
				}
				else {
					$sucess = false;
					$message = "Chhpay withdrawal response status, Code: ".$returnCode.", Desc: ".$returnDesc;
					return array('success' => $sucess, 'message' => $message);
				}
			}

			$message = "Chhpay withdrawal response failed, Code: ".$returnCode.", Desc: ".$returnDesc;
			return array('success' => false, 'message' => $message);
		}

		return array('success' => false, 'message' => "Decode failed");

	}

	## This function provides a way to manually check withdraw status. Useful when API does not provide a callback.
	## Returns array('success' => false, 'payment_fail' => false, 'message' => 'Error message')
	## 'success' means whether payment is successful, 'payment_fail' means if payment is not successful, shall we mark it as failed or shall we wait
	public function checkWithdrawStatus($transId) {
		$this->CI->load->model(array('wallet_model'));
		$walletaccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		$dateTimeString = $walletaccount['dwDateTime'];
		$datetime = new DateTime($dateTimeString);

		# ---- First add bank card entry ----
        $param = array();
        $param['p0_Cmd'] = 'TransQuery';
		$param['p1_MerId'] = $this->getSystemInfo("account");
		$param['p2_Order'] = $transId;

		$param['hmac'] = $this->sign($param);

		$this->CI->utils->debug_log('======================================chhpay checkWithdrawStatus params: ', $param);

		$url = $this->getSystemInfo('url');
		$this->CI->utils->debug_log('======================================chhpay checkWithdrawStatus url: ', $url );

		$response = $this->submitGetForm($url, $param);

		$this->CI->utils->debug_log('======================================chhpay checkWithdrawStatus result: ', $response );

		$decodedResult = $this->decodeResult($response, true);

		return $decodedResult;
    }

   # -- signing --
	public function sign($params) {
		$signStr = $this->createSignStr($params);
		$sign_info = '';
		openssl_sign($signStr, $sign_info, $this->getPrivKey());
		$sign = base64_encode($sign_info);
		
		return $sign;
	}

	public function createSignStr($params) {
		ksort($params);
		
		$signStr = '';
		foreach($params as $key => $value) {
			if(empty($value) || $key == 'hmac' ) {
				continue;
			}
			$signStr .= $value;
		}
		return $signStr;
	}

	# Returns public key given by gateway
	private function getPubKey() {
		$chhpay_pub_key = $this->getSystemInfo('chhpay_pub_key');

		$pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($chhpay_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
		return openssl_get_publickey($pub_key);
	}

	# Returns the private key generated by merchant
	private function getPrivKey() {
		$chhpay_priv_key = $this->getSystemInfo('chhpay_priv_key');

		$priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($chhpay_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
		return openssl_get_privatekey($priv_key);
	}

	/*Customized functions*/
	public function getChhpayBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("chhpay_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
			}
			$this->utils->debug_log("==================getting chhpay bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				'1' => '中国工商银行',
				'2' => '招商银行',
				'3' => '中国建设银行',
				'4' => '中国农业银行',
				'5' => '交通银行',
				'6' => '中国银行',
				'8' => '广东发展银行',
				'10' => '中信银行',
				'11' => '民生银行',
				'12' => '中国邮政储蓄',
				'13' => '兴业银行',
				'14' => '华夏银行',
				'15' => '平安银行',
				'20' => '光大银行'
			);
			$this->utils->debug_log("=======================getting chhpay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function getChhpayReturnInfo() {
		$returnInfo = array(
			'1001' => '扣减金额大于可提现金额',
			'1002' => '可结算金额不足',
			'2001' => 'Ip 白名单不存在',
			'2002' => '参数为空',
			'2003' => '签名错误',
			'2004' => '商户不存在',
			'2005' => '商户账户不存在',
			'2006' => '账户被冻结',
			'2007' => '订单重复',
			'2009' => '业务未开通',
			'2010' => '银行卡未设置白名单',
			'2012' => '金额超限',
			'2013' => '不支持的银行',
			'3001' => '订单不存在',
			'3002' => '订单失败',
			'3003' => '订单处理中',
			'3004' => '订单待处理',
			'9999' => '未知错误'
		);
		return $returnInfo;
	}

}
