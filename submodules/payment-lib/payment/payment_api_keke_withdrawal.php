<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * KEKE 可可支付
 *
 * * KEKE_WITHDRAWAL_PAYMENT_API, ID: 5512
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Secret
 *
 * Field Values:
 * * URL: http://www.keke1688.com/payment_Api.html
 * * Account: ## MerId ##
 * * Key: ## APIKEY ##
 * * Secret: ## TerId ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_keke_withdrawal extends Abstract_payment_api {

    const RESULT_STATUS_SUCCESS = "00000";

    const ORDER_STATUS_SUCCESS  = '01';
    const ORDER_STATUS_PROCESSING = '00';
    const ORDER_STATUS_FAILED = '02';

    const CALLBACK_MSG_SUCCESS = 'SUCCESS';

    public function getPlatformCode() {
        return KEKE_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'keke_withdrawal';
    }

    # Implement abstract function but do nothing
    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {}
    public function directPay($order = null) {}

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'keke_pub_key', 'keke_priv_key');
        return $secretsInfo;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        # look up bank code
		$bankInfo = $this->getBankInfo();
        $bankName = $bankInfo[$bank]['name'];

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

        $data = [
            'trade_code' => 'T001',
            'member_id' => $this->getSystemInfo('account'),
            'amount' => $this->convertAmountToCurrency($amount),
            'account_no' => $accNum,
            'bankname' => $bankName,
            'subbranch' => $bankBranch,
            'province' => $province,
            'city' => $city,
            'account_name' => $name,
            'order_no' => $transId.'000', //订单号位数不能小于16位
            'notify_url' => $this->getNotifyUrl($transId),
            'nonce_str' => 'withdrawal',
            'req_time' => date('YmdHis'),
        ];

        $data['sign'] = $this->sign($data);
        $this->CI->utils->debug_log('=========================keke getWithdrawParams data', $data);

        $cipher_rsa = $this->publicEncrypt(json_encode($data));
        $this->CI->utils->debug_log('=========================keke getWithdrawParams cipher_rsa', $cipher_rsa);

        $req_data = [
            'member_id' => $this->getSystemInfo('account'),
            'cipher_rsa' => $cipher_rsa
        ];
        return $req_data;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
        }

        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================keke withdrawal bank whose bankTypeId=[$bank] is not supported by keke");
            return array('success' => false, 'message' => 'Bank not supported by keke');
        }

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $this->CI->utils->debug_log('=========================keke submitWithdrawRequest params', $params);

        $url = $this->getSystemInfo('url');

        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $this->CI->utils->debug_log('=========================keke submitWithdrawRequest decoded Result', $decodedResult);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;

    }


    public function decodeResult($resultString, $queryAPI = false) {
		$result = json_decode($resultString, true);
		$this->utils->debug_log("=========================keke json_decode result", $result);

        $respCode = $result['code'];
		$resultMsg = $result['msg'];
		$this->utils->debug_log("=========================keke withdrawal resultMsg", $resultMsg);

		if($queryAPI){
			if($respCode == self::RESULT_STATUS_SUCCESS) {
                $message = 'keke payment response successful!';
                return array('success' => true, 'message' => $message);
			}else{
				$message = "keke payment result_code is [ ".$respCode. " ] , Query failed msg: ".$resultMsg;
				return array('success' => false, 'message' => $message);
			}
		}
		else{
			if($respCode == self::RESULT_STATUS_SUCCESS) {
	            $message = "keke request successful.";
	            return array('success' => true, 'message' => $message);
            }
            else {
				if($resultMsg == '' || $resultMsg == false) {
						$this->utils->error_log("========================keke return UNKNOWN ERROR!");
						$resultMsg = "未知错误";
				}

				$message = "keke withdrawal response, Code: [ ".$respCode." ] , Msg: ".$resultMsg;
				return array('success' => false, 'message' => $message);
			}
		}

	}

    public function checkWithdrawStatus($transId) {
        $params = array();
        $params['trade_code'] = "T002";
        $params['member_id'] = $this->getSystemInfo('account');
        $params['order_no'] = $transId.'000';
        $params['nonce_str'] = 'withdrawal';
        $params['req_time'] = date('YmdHis');
        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log('=========================keke checkWithdrawStatus params: ', $params);

        $url = $this->getSystemInfo('check_status_url', 'https://gateway.keke.net/supApi/queryTransferResult');
        $response = $this->submitPostForm($url, $params, false, $transId);
        $decodedResult = $this->decodeResult($response, true);

        $this->CI->utils->debug_log('=========================keke checkWithdrawStatus result: ', $response);
        return $decodedResult;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);

        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->utils->debug_log("==========================keke checkCallback params", $params);


        $params = $this->privateDecrypt($params['cipher_rsa']);
        $params = json_decode($params, true);
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['trade_state'] == self::ORDER_STATUS_SUCCESS) {
            $this->utils->debug_log('=========================keke withdrawal payment was successful: trade ID [%s]', rtrim($params['order_no'],'000'));

            $msg = sprintf('keke withdrawal was successful: trade ID [%s]',rtrim($params['order_no'],'000'));
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::CALLBACK_MSG_SUCCESS;
            $result['success'] = true;

        }elseif($params['trade_state'] == self::ORDER_STATUS_FAILED){
            $this->utils->debug_log('==========================keke withdrawal payment was failed: trade ID [%s]', rtrim($params['order_no'],'000'));

            $msg = sprintf('keke withdrawal was failed: trade ID [%s]',rtrim($params['order_no'],'000'));
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);

            $result['message'] = self::CALLBACK_MSG_SUCCESS;
            $result['success'] = true;

        }else{
            $msg = sprintf('keke withdrawal payment was not successful: trade ID [%s] ',rtrim($params['order_no'],'000'));

            if($params['trade_state'] == self::ORDER_STATUS_PROCESSING){
                $msg = sprintf('keke withdrawal is processing: trade ID [%s]',rtrim($params['order_no'],'000'));
            }

            $this->debug_log($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
        $this->utils->debug_log('==========================keke withdrawal checkCallbackOrder fields', $fields);

        $requiredFields = array('trade_state', 'amount', 'order_no', 'sign');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================keke withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['sign'] != $this->validateSign($fields)) {
            $this->writePaymentErrorLog('==========================keke withdrawal checkCallback signature Error',$fields);
            return false;
        }

		if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================keke withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
			return false;
		}

        if (rtrim($fields['order_no'],'000') != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================keke withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

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
            $this->utils->debug_log("=========================keke bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => array('name' => '工商银行', 'code' => 'ICBC'),
                '2' => array('name' => '招商银行', 'code' => 'CMB'),
                '3' => array('name' => '建设银行', 'code' => 'CCB'),
                '4' => array('name' => '农业银行', 'code' => 'ABC'),
                '5' => array('name' => '交通银行', 'code' => 'BCOM'),
                '6' => array('name' => '中国银行', 'code' => 'BOC'),
                '7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
                '8' => array('name' => '广东发展银行', 'code' => 'GDB'),
                '10' => array('name' => '中信银行', 'code' => 'CITIC'),
                '11' => array('name' => '民生银行', 'code' => 'CMBC'),
                '12' => array('name' => '邮储银行', 'code' => 'PSBC'),
                '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                '14' => array('name' => '华夏银行', 'code' => 'HXB'),
                '15' => array('name' => '平安银行', 'code' => 'PABC'),
                '18' => array('name' => '南京银行', 'code' => 'BON'),
                '20' => array('name' => '光大银行', 'code' => 'CEB'),
                '24' => array('name' => '上海浦东发展银行', 'code' => 'SPDB'),
                '26' => array('name' => '广东发展银行', 'code' => 'GDB'),
                '27' => array('name' => '上海浦东发展银行', 'code' => 'SPDB'),
                '29' => array('name' => '北京银行', 'code' => 'BOB'),
                '31' => array('name' => '上海银行', 'code' => 'SHB'),
                '33' => array('name' => '北京农商', 'code' => 'BJRCB'),
            );
            $this->utils->debug_log("=========================keke bank info from code: ", $bankInfo);

        }
        return $bankInfo;
    }

    # -- signatures --
    protected function sign($params) {
        ksort($params);
        $signStr = "";
        foreach($params as $key => $value) {
            if($key == 'sign'){
                continue;
            }
            $signStr.=$key."=".$value."&";
        }
        $signStr = $signStr."key=".$this->getSystemInfo('key');
        $sign = strtoupper(md5($signStr));
       
        return $sign;
    }

    protected function validateSign($params) {
        $sign = $this->sign($params);
		if($params['sign'] == $sign){
			return true;
		}
		else{
		
			return false;
		}
    }

    # -- 公钥加密 --
    protected function publicEncrypt($data) {
        $encrypt = '';
        foreach(str_split($data, 117) as $chunk){
            openssl_public_encrypt($chunk, $encrypted, $this->getPubKey());
            $encrypt.= $encrypted;
        }
       
        return base64_encode($encrypt);
    }

    # -- 私钥解密 --
    protected function privateDecrypt($data) {
        $decrypt = '';
        $encrypted=base64_decode($data);
        foreach(str_split($encrypted, 128) as $chunk){
            openssl_private_decrypt($chunk, $decrypted, $this->getPrivKey());
            $decrypt.= $decrypted;
        }
        return $decrypt;
    }

    private function getPubKey() {
        $keke_pub_key = $this->getSystemInfo('keke_pub_key');
        $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($keke_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($pub_key);
    }

    private function getPrivKey() {
        $keke_priv_key = $this->getSystemInfo('keke_priv_key');
        $priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($keke_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($priv_key);
    }
}