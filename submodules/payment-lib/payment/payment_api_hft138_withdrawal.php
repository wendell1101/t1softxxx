<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hft138.php';

/**
 * HFT138 浩付通
 *
 * * HFT138_WITHDRAWAL_PAYMENT_API, ID: 5346
 *
 * Required Fields:
 * * URL:http://apisx.hft138.com:3361/api2_index_adddf
 * * Account
 * * Key
 * * Secret
 *
 * Field Values:
 * * URL:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hft138_withdrawal extends Abstract_payment_api_hft138 {

    const RETURN_SUCCESS_CODE = "1";
    const RETURN_SUCCESS = 'ok';

    public function getPlatformCode() {
        return HFT138_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'hft138_withdrawal';
    }


    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $province    = empty($playerBankDetails['province'])    ? "无" : $playerBankDetails['province'];
            $city        = empty($playerBankDetails['city'])        ? "无" : $playerBankDetails['city'];
            $bankBranch  = empty($playerBankDetails['branch'])      ? "无" : $playerBankDetails['branch'];
            $bankAddress = empty($playerBankDetails['bankAddress']) ? "无" : $playerBankDetails['bankAddress'];
        } else {
            $bankBranch  = '无';
            $province    = '无';
            $city        = '无';
            $bankAddress = '无';
        }
        # look up bank code
        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================hft138 withdrawal bank whose bankTypeId=[$bank] is not supported by hft138");
            return array('success' => false, 'message' => 'Bank not supported by hft138');
        }

        $bizContext = array(
            "bankname"     => $bankInfo[$bank]['name'],
            "bankzhiname"  => $bankBranch, //開戶支行 //開戶分行
            "banknumber"   => $accNum,  //銀行卡卡號
            "bankfullname" => $name, //收款人
            "sheng"        => $province,
            "shi"          => $city,
            'tkmoney'      => $this->convertAmountToCurrencyPlusfee($amount), //元 加手續費
            "outtradeno"   => $transId,
        );
        $this->CI->utils->debug_log('=========================hft138 getWithdrawParams bizContext', $bizContext);
        $bizContextJson = json_encode($bizContext,JSON_UNESCAPED_UNICODE);

        $params = array();
        $params['bizcontext'] = $this->AESEncrypt($bizContextJson);
        $params['userid'] = $this->getSystemInfo("account");
		$params['charset'] = "utf-8";
        $params['t'] = time();
        $params['sign'] = $this->sign($params);
        $params['notify_url'] = $this->getNotifyUrl($transId);



        $this->CI->utils->debug_log('=========================hft138 getWithdrawParams params', $params);
        return $params;
    }



	public function getOrderIdFromParameters($params) {
		$this->utils->debug_log('====================================hft138 callbackOrder params', $params);
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
            	$this->utils->debug_log('====================================hft138 callbackOrder transId is empty when getOrderIdFromParameters', $params);
            }
		}
		else {
			$this->utils->debug_log('====================================hft138 callbackOrder cannot get any transId when getOrderIdFromParameters', $params);
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
		$this->CI->utils->debug_log('======================================hft138 submitWithdrawRequest params:', $params);
        $url = $this->getSystemInfo('url');

        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('=========================hft138 submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;

	}

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $this->utils->debug_log("=========================hft138 decodeResult resultString", $resultString);

		$result = json_decode($resultString, true);
		$this->utils->debug_log("=========================hft138 json_decode result", $result);

		$respCode = $result['code'];
		$resultMsg = $result['msg'];
		$this->utils->debug_log("=========================hft138 withdrawal resultMsg", $resultMsg);

		if($queryAPI){
			if($respCode == self::RETURN_SUCCESS_CODE) {
				if($result['code'] == self::RETURN_SUCCESS_CODE){
					$message = 'HFT138 payment response successful, result Code:'.$respCode.", Msg: ".$resultMsg;
					return array('success' => true, 'message' => $message);
				}else{
					$message = "HFT138 payment failed for Code:".$respCode.", Msg: ".$resultMsg;
					$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $message);
					return array('success' => false, 'message' => $message);
				}
			}else{
				$message = "HFT138 payment  result_code is Query failed ".$resultMsg;
				return array('success' => false, 'message' => $message);
			}
		}
		else{
			if($respCode == self::RETURN_SUCCESS_CODE) {
	            $message = "HFT138 request successful. [".$respCode."]: ".$resultMsg;
	            return array('success' => true, 'message' => $message);
            }
            else {
				if($resultMsg == '' || $resultMsg == false) {
						$this->utils->error_log("========================hft138 return UNKNOWN ERROR!");
						$resultMsg = "未知错误";
				}

				$message = "HFT138 withdrawal response, Code: ".$respCode.", Msg: ".$resultMsg;
				return array('success' => false, 'message' => $message);
			}
		}

	}

	public function checkWithdrawStatus($transId) {
        $bizContext = array("outtradeno" => $transId);

        $params = array();
        $params['bizcontext'] = $this->AESEncrypt(json_encode($bizContext));
        $this->CI->utils->debug_log('======================================66666666666666666666 bizContext ', $bizContext);
        $this->CI->utils->debug_log('======================================77777777777777777777 params[bizcontext] ', $params['bizcontext']);

        $params['userid'] = $this->getSystemInfo("account");
		$params['charset'] = "utf-8";
        $params['t'] = time();
        $params['sign'] = $this->sign($params);


        $url = $this->getSystemInfo('check_withdraw_status_url', 'http://apisx.hft138.com:3361/api2_index_querydf');

		$response = $this->submitPostForm($url, $params, false, $transId);
		$decodedResult = $this->decodeResult($response, true);

		$this->CI->utils->debug_log('======================================hft138 checkWithdrawStatus params: ', $params);
		$this->CI->utils->debug_log('======================================hft138 checkWithdrawStatus url: ', $url );
		$this->CI->utils->debug_log('======================================hft138 checkWithdrawStatus result: ', $response );
		$this->CI->utils->debug_log('======================================hft138 checkWithdrawStatus decoded Result', $decodedResult);

		return $decodedResult;
    }

    public function callbackFromServer($transId, $params) {
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->utils->debug_log('==========================hft138 process withdrawalResult order id', $transId);

        $result = $params;

        $this->utils->debug_log("==========================hft138 checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['code'] == self::RETURN_SUCCESS_CODE) {
            $this->utils->debug_log('==========================hft138 withdrawal payment was successful: trade ID [%s]', $params['outtradeno']);

            $msg = sprintf('=hft138 withdrawal was successful: trade ID [%s]',$params['outtradeno']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS;
            $result['success'] = true;
       }else {
            $realStateDesc = $params['msg'];
            $this->errMsg = '['.$realStateDesc.']';
            $msg = sprintf('=hft138 withdrawal payment was not successful: '.$this->errMsg);
            $this->writePaymentErrorLog($msg, $params);

            $result['message'] = $msg;
        }

        return $result;
    }


    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array('code', 'msg', 'data');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================hft138 withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['sign']!=$this->validateSign($fields)) {
            $this->writePaymentErrorLog('==========================hft138 withdrawal checkCallback signature Error',$fields);
            return false;
        }

        if ($fields['code'] != self::RETURN_SUCCESS_CODE) {
            $this->writePaymentErrorLog("=======================hft138 checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        $fields = $this->AESDecrypt($fields,$this->getSystemInfo("aesKey"));
		if ($fields['tkmoney'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================hft138 withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
			return false;
		}

        if ($fields['outtradeno'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================hft138 withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }


    # -- signatures --
	public function sign($params) {
        $signStr =  $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
    
        return $sign;
    }

    public function createSignStr($params) {
    	ksort($params);
        $signStr = '';
        foreach ($params as $key => $value) {

            $signStr .= $key."=".$value."&";
        }
        $signStr .= 'key='. $this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $sing_param = $params;
        unset($sing_param["sign"]);

        $signStr =  $this->createSignStr($sing_param);
        $sign = strtoupper(md5($signStr));

        if($params['sign'] == $sign){
            return true;
        }
        else{
           
            return false;
        }
    }


    # -- bankinfo --
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("hft138_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting hft138 bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
				'1' => array('name' => '工商银行', 'code' => 'ICBC'),
				'2' => array('name' => '招商银行', 'code' => 'CMBCHINA'),
				'3' => array('name' => '建设银行', 'code' => 'CCB'),
				'4' => array('name' => '农业银行', 'code' => 'ABC'),
				// '5' => array('name' => '交通银行', 'code' => 'COMM'),
				'6' => array('name' => '中国银行', 'code' => 'BOC'),
				// '7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
				// '8' => array('name' => '广东发展银行', 'code' => 'GDB'),
				// '9' => array('name' => '东莞农商银行', 'code' => 'DRCBANK'),
				'10' => array('name' => '中信银行', 'code' => 'ECITIC'),
				'11' => array('name' => '民生银行', 'code' => 'CMBC'),
				'12' => array('name' => '中国邮政储蓄银行', 'code' => 'POST'),
				'13' => array('name' => '兴业银行', 'code' => 'CIB'),
				'14' => array('name' => '华夏银行', 'code' => 'HXB'),
				'15' => array('name' => '平安银行', 'code' => 'PINGAN'),
				// '16' => array('name' => '广西农村信用社', 'code' => 'GX966888'),
				// '17' => array('name' => '广州银行', 'code' => 'GZCB'),
				'18' => array('name' => '南京银行', 'code' => 'NJCB'),
                // '19' => array('name' => '广州农商银行', 'code' => 'GRCB'),
				'20' => array('name' => '光大银行', 'code' => 'CEB'),
				'88' => array('name' => '北京银行', 'code' => 'BCCB'),
            );
            $this->utils->debug_log("=======================getting hft138 bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }


	private function convertAmountToCurrencyPlusfee($amount) {
        $fee = $this->getSystemInfo("fee","2");
		return (float)number_format($amount, 2, '.', '') + $fee;
	}


 # -- DEMO CODE------------------------------------------------------------------------------------------------------------ --

    # -- AES加密 --
    public function AESEncrypt($preEncryptString) {
        //1.对 $preEncryptString 进行补码 得到 $preEncryptString
        $size             = @mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $preEncryptString = $this->pkcs5_pad($preEncryptString, $size);

        //2.对 $preEncryptString 运用AES ECB128 位加密 得到 $encryptData
        $td               = @mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv               = @mcrypt_create_iv(@mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        @mcrypt_generic_init($td, $this->getSystemInfo("aesKey"), $iv);

        $encryptData = @mcrypt_generic($td, $preEncryptString);
        @mcrypt_generic_deinit($td);
        @mcrypt_module_close($td);

        //3.对$encryptData进行 base64加密 得到 $encryptData
        $encryptData = base64_encode($encryptData);

        return $encryptData;
    }

    private function pkcs5_pad($text, $blocksize) {
        $pad = $blocksize-(strlen($text)%$blocksize);
        return $text.str_repeat(chr($pad), $pad);
    }


    # -- AES解密 --
	public static function AESDecrypt($encrypted, $aesKey) {
		//1.对$encrypted进行64位解密，得到$encrypted
		$encrypted = base64_decode($encrypted);

		//2.对 $encrypted 进行 ECB128 位解密 得到 $decrypted
		$decrypted = @mcrypt_decrypt(
			MCRYPT_RIJNDAEL_128,
			$aesKey,
			$encrypted,
			MCRYPT_MODE_ECB
		);

		//3.对 $decrypted 去掉补码，得到 $decrypted
		$decrypted = self::pkcs5_unpad($decrypted);

		return $decrypted;
	}

	private static function pkcs5_unpad($decrypted) {
		$len       = strlen($decrypted);
		$padding   = ord($decrypted[$len-1]);
		$decrypted = substr($decrypted, 0, -$padding);
		return $decrypted;
    }

}

