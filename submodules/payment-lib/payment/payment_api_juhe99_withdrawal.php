<?php
require_once dirname(__FILE__) . '/abstract_payment_api_juhe99.php';

/**
 *   JUHE99  聚合99 取款
 *
 * * JUHE99_WITHDRAWAL_PAYMENT_API, ID: 882
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://juhe99.com/trade/api/
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_juhe99_withdrawal extends Abstract_payment_api_juhe99 {
	const CALLBACK_STATUS_SUCCESS = 1;

	public function getPlatformCode() {
		return JUHE99_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'juhe99_withdrawal';
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

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'juhe99_pub_key', 'juhe99_priv_key');
        return $secretsInfo;
    }

	## This function returns the params to be submitted to the withdraw URL
	## Note that $bank param is the bank_type ID in database, we compare it with the supported bank_codes by this API
	private $errMsg = 'Payment failed'; # This variable is used to store error message that's available upon submit
	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$params = array();
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("===============================juhe99 Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
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
			$this->utils->error_log("========================juhe99 withdrawal bank whose bankTypeId=[$bank] is not supported by juhe99");
			return array('success' => false, 'message' => 'Bank not supported by juhe99');
			$bank = '无';
		}
        $bankno = $bankInfo[$bank]['code'];	//开户行名称
        $bank = $bankInfo[$bank]['name'];	//开户行名称


		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("=========================juhe99 withdrawal get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

		if(!empty($playerBankDetails)){
			$bankBranch = $playerBankDetails['branch'];
			$bankSubBranch = $playerBankDetails['branch'];
			$province = $playerBankDetails['province'];	//开户省  卡号的开户省
			$city = $playerBankDetails['city'];	//开户市 卡号的开户市

		}

		$params = array();
		$params['trxType'] = 'TransferSingle';
		$params['r1_merchantNo'] = $this->getSystemInfo("account");
		$params['r2_batchNo'] = $transId;
		$params['r3_orderNumber'] = $transId;
        $params['r4_amount'] = $this->convertAmountToCurrency($amount);
        $params['r5_bankId'] = $bankno;
        $params['r6_accountNo'] = $accNum;
        $params['r7_accountName'] = $name;
        $params['r8_business'] = 'B2C';
		// $params['r9_cnaps'] = $bankno;
        $params['r10_belongsType'] = 'PAYER';
        $params['r11_urgency'] = 'true';
		// $params['drawBankBranchName'] = $bankSubBranch;
		// $params['noticeUrl'] = $this->getNotifyUrl($transId);
  //       $params['bankProvince'] = $province;
		// $params['bankCity'] = $city;
        $params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log('=========================juhe99 withdrawal paramStr before sign', $params);

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

		$this->CI->utils->debug_log('======================================juhe99 submitWithdrawRequest params: ', $params);

		$url = $this->getSystemInfo('url');
		$this->CI->utils->debug_log('======================================juhe99 withdrawal url: ', $url );

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
		$this->utils->debug_log("===============================juhe99 withdrawal postString", $postString, "curl result", $result);
		curl_close($curlConn);


		$decodedResult = $this->decodeResult($result['result']);
		$this->utils->debug_log("===============================juhe99 withdrawal decoded Result", $decodedResult);
		return $decodedResult;

	}

	public function decodeResult($resultString, $queryAPI = false) {
        $result_array = json_decode($resultString, true);
        // $result_array = $result;
        $this->CI->utils->debug_log('==============juhe99 submitWithdrawRequest decodeResult json decoded', $result_array);
		if($queryAPI) {
			if(is_array($result_array)) {
				$returnCode = $result_array['r5_orderStatus'];
				$returnDesc = $this->getStatusErrorMsg($result_array['r5_orderStatus']);
				if ($returnCode == 'SUCCESS'){
					$message = 'juhe99 payment response successful, result Code:'.$returnCode.", Desc: ".$returnDesc;
					return array('success' => true, 'message' => $message);
				}else if($returnCode == 'FAIL'){
					$msg = "juhe99 payment failed for Code:".$returnCode.", Desc: ".$returnDesc;
					$transId = $result_array['outOrderNo'];
					$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
					return array('success' => false, 'message' => $msg);
				}else{
					$message = "juhe99 payment failed for Code:".$returnCode.", Desc: ".$returnDesc;
					return array('success' => false, 'message' => $message);
				}
			}
			else{
				$message = $this->errMsg = 'juhe99 payment failed retCode is not exists Desc: '.$resultString;
				return array('success' => false, 'message' => $message);
			}
		$this->utils->debug_log("=========================juhe99 checkWithdrawStatus decoded result queryAPI", $result_array);
		return array('success' => false, 'message' => $this->errMsg);
		}
		else {
			if(is_array($result_array)) {
				$returnCode = $result_array['retCode'];
				$returnDesc = $result_array['retMsg'];

				if ($returnCode == '0000'){
					$message = 'juhe99 payment response successful, result Code:'.$returnCode.", Desc: ".$returnDesc;;
					return array('success' => true, 'message' => $message);

				}else{
					$message = "juhe99 payment failed for Code:".$returnCode.", Desc: ".$returnDesc;
					return array('success' => false, 'message' => $message);
				}

			}
			else{
				$message = $this->errMsg = 'juhe99 payment failed retCode is not exists  Desc: '.$resultString;
				return array('success' => false, 'message' => $message);
			}
		$this->utils->debug_log("=========================juhe99 withdrawal decoded result string", $result_array);
		return array('success' => false, 'message' => $this->errMsg);

		}
	}

	private function getMappingErrorMsg($state) {
		$msg = "";
		switch ($state) {
			case '0000':
				$msg = "接收成功";
				break;

			case '0001':
				$msg = "订单已存在";
				break;

			case '0002':
				$msg = "接收失败";
				break;

			case '0003':
				$msg = "records格式错误";
				break;

			case '0004':
				$msg = "订单号为ORDERNUM 的记录，参数不合法";
				break;

			case '0005':
				$msg = "批量代付汇总金额或笔数与明细不符";
				break;

			case '0007':
				$msg = "账户状态异常";
				break;

			case '0008':
				$msg = "records记录数超过最大代付数量";
				break;

			case '0009':
				$msg = "重复打款";
				break;

			case '0010':
				$msg = "订单不存在";
				break;

			case '0011':
				$msg = "打款超过限额";
				break;

			default:
				$msg = "";
				break;
		}
		return $msg;
	}

	private function getStatusErrorMsg($state) {
		$msg = "";
		switch ($state) {
			case 'INIT':
				$msg = "未处理";
				break;

			case 'DOING':
				$msg = "处理中";
				break;

			case 'SUCCESS':
				$msg = "成功";
				break;

			case 'FAIL':
				$msg = "失败";
				break;

			default:
				$msg = "";
				break;
		}
		return $msg;
	}


	public function getHuidpayBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("juhe99_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1], 'code' => $bankInfoItem[2]);
			}
			$this->utils->debug_log("==================getting juhe99 bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				'1' => array('name' => '工商银行', 'code' => 'ICBC'),
				'2' => array('name' => '招商银行', 'code' => 'CMBCHINA'),
				'3' => array('name' => '建设银行', 'code' => 'CCB'),
				'4' => array('name' => '中国农业银行', 'code' => 'ABC'),
				'5' => array('name' => '交通银行', 'code' => 'BOCO'),
				'6' => array('name' => '中国银行', 'code' => 'BOC'),
				// '7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
				'8' => array('name' => '广发银行', 'code' => 'CGB'),
				'10' => array('name' => '中信银行', 'code' => 'ECITIC'),
				'11' => array('name' => '中国民生银行', 'code' => 'CMBC'),
				'12' => array('name' => '中国邮政银行', 'code' => 'POST'),
				'13' => array('name' => '兴业银行', 'code' => 'CIB'),
				'14' => array('name' => '华夏银行', 'code' => 'HXB'),
				'15' => array('name' => '平安银行', 'code' => 'PINGAN'),
				//'17' => array('name' => '广州银行', 'code' => 'GZCB'),
				//'18' => array('name' => '南京银行', 'code' => 'NJCB'),
				'20' => array('name' => '光大银行', 'code' => 'CEB'),
				'24' => array('name' => '上海浦东发展银行', 'code' => 'SPDB'),
			);
			$this->utils->debug_log("=======================getting juhe99 bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function checkWithdrawStatus($transId) {

		# ---- First add bank card entry ----
        $params = array();
		$params['trxType'] = 'TransferSingleQuery';
		$params['r1_merchantNo'] = $this->getSystemInfo("account");
		$params['r2_orderNumber'] = $transId;
		$params['sign'] = $this->querysign($params);

		$url = $this->getSystemInfo('url');
		$response = $this->submitPostForm($url, $params,false, $transId);
		$decodedResult = $this->decodeResult($response, true);

		$this->CI->utils->debug_log('======================================juhe99 checkWithdrawStatus params: ', $params);
		$this->CI->utils->debug_log('======================================juhe99 checkWithdrawStatus url: ', $url );
		$this->CI->utils->debug_log('======================================juhe99 checkWithdrawStatus result: ', $response );
		$this->CI->utils->debug_log('======================================juhe99 checkWithdrawStatus decoded Result', $decodedResult);

		return $decodedResult;
    }

	public function callbackFromServer($transId, $params) {
        $result = array('success' => false, 'message' => 'Payment failed');


        $this->utils->debug_log('=========================juhe99 process withdrawalResult order id', $transId);

        $result = $params;

        $this->utils->debug_log("=========================juhe99 checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($result['retCode'] == '0000') {
            $this->utils->debug_log('=========================juhe99 withdrawal payment was successful: trade ID [%s]', $params['withdrawId']);

            $msg ='SUCCESS';
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = $msg;
            $result['success'] = true;
       }else {
           $realStateDesc = $result['withdrawId'];
            $this->errMsg = '['.$realStateDesc.']';
            $msg = sprintf('======================juhe99 withdrawal payment was not successful: '.$this->errMsg);
            $this->writePaymentErrorLog($msg, $params);

            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array('trxType', 'retCode', 'retMsg', 'r1_merchantNo', 'r2_batchNo','r3_orderNumber','r4_serialNumber','sign');

        foreach ($requiredFields as $f) {
        	if (!array_key_exists($f, $fields)) {
        		$this->writePaymentErrorLog("======================juhe99 withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
        		return false;
        	}
        }

        if ($fields['r1_merchantNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("========================juhe99 checkCallbackOrder type2 order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        if ($fields["sign"]!=$this->validateSign($fields)) {
        	$this->writePaymentErrorLog('=========================juhe99 withdrawal checkCallback signature Error', $fields);
        	return false;
        }

        # everything checked ok
        return true;
    }

    public function validateSign($params) {
    	$callback_sign = $params['sign'];
       	$keys = array('trxType', 'retCode', 'r1_merchantNo', 'r2_ batchNo', 'r3_orderNumber', 'r4_serialNumber');
        $signStr = "";
        foreach($keys as $key) {
            $signStr .= '#'.$params[$key];
        }
        $signStr .= '#'.$this->getSystemInfo('key');
        $sign = md5($signStr);
       
        return $sign;
	}

	# -- signing --
	public function sign($data) {
	    $keys = array('trxType', 'r1_merchantNo', 'r2_batchNo', 'r3_orderNumber', 'r4_amount', 'r5_bankId', 'r6_accountNo', 'r7_accountName','r8_business','r10_belongsType','r11_urgency');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $data)) {
                $signStr .= '#'.$data[$key];
            }
        }
        openssl_sign($signStr, $sign_info, $this->getPrivKey(), OPENSSL_ALGO_MD5);
        $sign = base64_encode($sign_info);

       
        return $sign;
	}

	public function querysign($data) {
	    $keys = array('trxType', 'r1_merchantNo', 'r2_orderNumber');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $data)) {
                $signStr .= '#'.$data[$key];
            }
        }
        openssl_sign($signStr, $sign_info, $this->getPrivKey(), OPENSSL_ALGO_MD5);
        $sign = base64_encode($sign_info);

       
        return $sign;
	}

	private function getPubKey() {
		$juhe99_pub_key = $this->getSystemInfo('juhe99_pub_key');

		$pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($juhe99_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
		return openssl_get_publickey($pub_key);
	}

	private function getPrivKey() {
		$juhe99_priv_key = $this->getSystemInfo('juhe99_priv_key');

		$priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($juhe99_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
		return openssl_get_privatekey($priv_key);
	}
}
