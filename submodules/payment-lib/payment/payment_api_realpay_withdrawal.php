<?php
require_once dirname(__FILE__) . '/abstract_payment_api_realpay.php';

/**
 * realpay
 *
 * * REALPAY_WITHDRAWAL_PAYMENT_API, ID: 6166
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://realpay168.com/transfer/apply
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_realpay_withdrawal extends Abstract_payment_api_realpay {
	public function getPlatformCode() {
		return REALPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'realpay_withdrawal';
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

	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);

        $this->utils->debug_log("==================realpay withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $pix_number  = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
        }

        $aes_key = $this->getRandStr();
        //detail data
        $body_detail_params['seq']          = '1';
        $body_detail_params['amount']       = $this->convertAmountToCurrency($amount); //分
        $body_detail_params['accType']      = '0';
        $body_detail_params['certType']     = '0';
        $body_detail_params['certId']       = $pix_number;
        $body_detail_params['bankCardNo']   = $pix_number;
        $body_detail_params['bankCardName'] = $name;

        //bodydata
        $body_params['batchOrderNo'] = $transId;
        $body_params['totalNum']     = 1;
        $body_params['totalAmount']  = $this->convertAmountToCurrency($amount); //分
        $body_params['notifyUrl']    = $this->getNotifyUrl($transId);
        $body_params['detail']       = '['.json_encode($body_detail_params).']';
        $body_params['currencyType'] = $this->getSystemInfo('currencyType');
        $params = array();
        $params['head']['mchtId']  = $this->getSystemInfo('account');
        $params['head']['version'] = '20';
        $params['head']['biz']     = $this->getSystemInfo('biz');
        $params['body']            = urlencode($this->aesEncrypt(json_encode($body_params,JSON_UNESCAPED_SLASHES), $aes_key));
        $params['sign']            = $this->makeSign($body_params);
        $params['encryptKey']      = urlencode($this->rsaPublicEncrypt($aes_key));
		$this->CI->utils->debug_log('=========================realpay withdrawal paramStr before sign', $params);
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
        $url = $this->getWithdrawUrl();

        list($content, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================realpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================realpay submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================realpay submitWithdrawRequest response ', $response_result);
        $this->CI->utils->debug_log('======================================realpay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $aes_key = $this->rsaPrivateDecrypt(urldecode($result['encryptKey']));
        $body = json_decode($this->aesDecrypt(urldecode($result['body']), $aes_key), true);
        $this->utils->debug_log("=========================realpay json_decode result", $result);

        if(isset($result['head']['respCode']) && $result['head']['respCode'] == self::REQUEST_SUCCESS) {
            if(isset($body['status']) && $body['status'] == self::CALLBACK_SUCCESS) {
                $message = "realpay withdrawal response successful, code:[".$result['head']['respCode']."]: ".$body['status'];
                return array('success' => true, 'message' => $message);
            }
            $message = "realpay withdrawal response failed. [".$result['head']['respCode']."]: ".$result['body']['status'];
            return array('success' => false, 'message' => $message);

        }
        elseif(!empty($result['head']['respMsg'])){
            $message = 'realpay withdrawal response: '.$result['head']['respMsg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "realpay decoded fail.");
    }

	private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount*100, 0, '.', '');
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================realpay process withdrawalResult order id', $transId);
        $this->CI->utils->debug_log("=========================realpay checkCallback params", $params);


        if(!empty($params['encryptKey']) && !empty($params['body']) && !empty($params['sign'])){
            $aes_key         = $this->rsaPrivateDecrypt(urldecode($params['encryptKey']));
            $body            = json_decode($this->aesDecrypt(urldecode($params['body']), $aes_key), true);
            $validation_sign = $this->makeSign($body);
            $detail          = json_decode($body['detail'],true);
            $body['detail']  = $detail[0];
            $body['validation_sign'] = $validation_sign;
            $body['sign']    = $params['sign'];
            $this->CI->utils->debug_log("=========================realpay checkCallback params", $body);
            if(isset($body['detail']) && isset($body['batchOrderNo'])){
                $params = $body;
            }
        }

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['detail']['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('realpay withdrawal was successful: trade ID [%s]', $params['batchOrderNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('realpay withdrawal was not success: [%s]', $params['detail']['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = array('batchOrderNo', 'amount', 'status');

        $this->CI->utils->debug_log("=========================realpay checkCallback detailData", $fields);

        foreach ($requiredFields as $f) {
            $validate_array = $fields['detail'];
            $validate_array['batchOrderNo'] =  $fields['batchOrderNo'];
           if (!array_key_exists($f, $validate_array)) {
                $this->writePaymentErrorLog("=======================realpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if($fields['validation_sign'] != $fields['sign']) {
            $this->writePaymentErrorLog('=====================realpay withdrawal checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['batchOrderNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================realpay withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
            return false;
        }

        if ($fields['detail']['amount']  != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog("======================realpay withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
            return false;
        }

        return true;
    }

    private function getPubKey() {
        $realpay_pub_key = $this->getSystemInfo('realpay_pub_key');
        $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($realpay_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($pub_key);
    }

    private function getPrivKey() {
        $realpay_priv_key = $this->getSystemInfo('realpay_priv_key');
        $priv_key = '-----BEGIN PRIVATE KEY-----' . PHP_EOL . chunk_split($realpay_priv_key, 64, PHP_EOL) . '-----END PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($priv_key);
    }

    public function rsaPublicEncrypt($data)
    {
        $encrypted = '';
        $pu_key = $this->getPubKey();
        $plainData = str_split($data, 128);

        foreach ($plainData as $chunk) {
            $partialEncrypted = '';
            //公钥加密
            $encryptionOk = openssl_public_encrypt($chunk, $partialEncrypted, $pu_key);

            if ($encryptionOk === false) {
                return false;
            }

            $encrypted .= $partialEncrypted;
        }

        $encrypted = base64_encode($encrypted);

        return $encrypted;
    }

    /**
     * RSA私钥解密
     * @param string $private_key 私钥
     * @param string $data 公钥加密后的字符串
     * @return string $decrypted 返回解密后的字符串
     */
    public function rsaPrivateDecrypt($data)
    {
        $decrypted = '';
        $pi_key = $this->getPrivKey();
        $plainData = str_split(base64_decode($data), 184);
        foreach ($plainData as $chunk) {
            $str = '';
            //私钥解密
            $decryptionOk = openssl_private_decrypt($chunk, $str, $pi_key);
            if ($decryptionOk === false) {
                return false;
            }
            $decrypted .= $str;
        }

        return $decrypted;
    }

    /**
     * 获取16位随机字符串
     * @param int $length
     * @return string
     */
    public function getRandStr($length = 16)
    {
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

    /**
     * 生成签名
     * @param array $data
     * @param string $sign
     * @return string $result
     */
    public function makeSign($data)
    {
        ksort($data);
        $string = $this->ToUrlParams($data);
        $string = $string . "&key=".$this->getSystemInfo('key');
        $result = md5($string);
        return $result;
    }

    /**
     * 格式化参数
     */
    public function toUrlParams($data)
    {
        $temp = "";
        foreach ($data as $k => $v)
        {
            if($v != "" && !is_array($v)){
                $temp .= $k . "=" . $v . "&";
            }
        }
        $temp = trim($temp, "&");
        return $temp;
    }

    /**
     * AES 加密
     * @param $data
     * @param $key
     * @return string
     */
    public static function aesEncrypt($data, $key)
    {
        $iv_size = openssl_cipher_iv_length('AES-128-ECB');

        $iv = openssl_random_pseudo_bytes($iv_size);

        $encryptedMessage = openssl_encrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encryptedMessage);
    }

    /**
     * AES 解密
     * @param $data
     * @param $key
     * @return string
     */
    public static function aesDecrypt($data, $key)
    {
        $iv_size = openssl_cipher_iv_length('AES-128-ECB');
        $iv = openssl_random_pseudo_bytes($iv_size);
        $decrypted = openssl_decrypt( base64_decode($data),'AES-128-ECB', $key, OPENSSL_RAW_DATA,$iv);

        return $decrypted;
    }
}
