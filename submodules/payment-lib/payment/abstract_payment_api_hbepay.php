<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * HBEPAY 汇宝
 * *
 * * 'HBEPAY_PAYMENT_API', ID 5306
 * * 'HBEPAY_QUICKPAY_PAYMENT_API', ID: 5307
 * * 'HBEPAY_UNIONPAY_PAYMENT_API', ID: 5308
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

abstract class Abstract_payment_api_hbepay extends Abstract_payment_api {

    const PAYTYPE_WEIXIN = 'weixin';
    const PAYTYPE_WEIXIN_H5 = 'wxwap';

	const RETURN_SUCCESS_CODE = 'T';
	const RETURN_FAILED_CODE = 'F';
	const RETURN_SUCCESS = 'success';
	const RETURN_FAILED = 'fail';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'hbepay_server_pub_key', 'hbepay_pri_key');
        return $secretsInfo;
    }

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params = array();
		$params['partner'] = $this->getSystemInfo("account");
        $params['input_charset'] = 'UTF-8';
		$params['request_time'] = date('YmdHis'); # 时间格式：yyyy-MM-dd HH:mm:ss
        $params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['return_url'] = $this->getReturnUrl($orderId);
		$params['out_trade_no'] = $order->secure_id;
		$params['amount'] = $this->convertAmountToCurrency($amount); //元
		$params['tran_ip'] = $this->getClientIP();
		$this->configParams($params, $order->direct_pay_extra_info);
		$this->CI->utils->debug_log("=====================hbepay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}


	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
		return array(
			'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true,
		);
	}

	# Display QRCode get from curl
	protected function processPaymentUrlFormQRCode($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['out_trade_no']);
		$response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================hbepay processPaymentUrlFormPost response json to array', $response);

        $msg = lang('Invalidate API response');
    	if(!empty($response['is_succ'] == self::RETURN_SUCCESS_CODE)){
			$decryresponse = $this->decrypt($response['response']);
			$decryresponse = json_decode($decryresponse, true);
			$this->CI->utils->debug_log('===================================json decode decryresponse', $decryresponse);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'base64' => $decryresponse['base64QRCode'],
            );
        }else {
            if(!empty($response['is_succ'] == self::RETURN_FAILED_CODE && $response['fault_reason'])) {
                $msg = $response['fault_reason'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $msg
            );
        }
    }

	/**
	 * detail: This will be called when the payment is async, API server calls our callback page,
	 * When that happens, we perform verifications and necessary database updates to mark the payment as successful
	 *
	 * @param int $orderId order id
	 * @param array $params
	 * @return array
	 */
	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	/**
	 * detail: This will be called when user redirects back to our page from payment API
	 *
	 * @param int $orderId order id
	 * @param array $params
	 * @return array
	 */
	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================hbepay callbackFromServer server callbackFrom', $params);
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

		# Update order payment status and balance
		$success=true;

		# Update player balance based on order status
		# if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
		$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
		if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
			$this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
			if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
				$this->CI->sale_order->setStatusToSettled($orderId);
			}
		} else {
			# update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $params['order_id'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS;
		} else {
			$result['message'] = $processed ? self::RETURN_SUCCESS : self::RETURN_FAILED;
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	/**
	 * detail: Validates whether the callback from API contains valid info and matches with the order
	 *
	 * @return boolean
	 */

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array('out_trade_no', 'request_time','sign','sign_type','content');
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("======================hbepay check callback Missing parameter: [$f]", $fields);
				return false;
			}
		}

		$decryptCallbackContent = $this->decrypt($fields['content']);
		$decryptResult = json_decode($decryptCallbackContent,true);
		$this->CI->utils->debug_log("===================== hbepay decryptResult", $decryptResult);

		$decryptResultrequiredFields = array('trade_id', 'out_trade_no', 'amount_str', 'amount_fee', 'status', 'business_type', 'create_time');
		foreach ($decryptResultrequiredFields as $f) {
			if (!array_key_exists($f, $decryptResult)) {
				$this->writePaymentErrorLog("======================hbepay check callback decryptResult Missing parameter: [$f]", $decryptResult);
				return false;
			}
		}

        # is signature authentic?
        if (!$this->validateSign($decryptCallbackContent, $fields['sign'])) {
            $this->writePaymentErrorLog('=======================hbepay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['status'] == 2) {
			$this->writePaymentErrorLog('========================hbepay Payment was not successful', $fields);
			return false;
		}

		if ($decryptResult['amount_str'] != $this->convertAmountToCurrency($order->amount)) {
			$this->writePaymentErrorLog("=====================hbepay Payment amounts do not match, expected [$order->amount]", $decryptResult);
			return false;
		}

        if ($decryptResult['out_trade_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================hbepay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $decryptResult);
            return false;
        }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- notifyURL --
	public function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

    # -- returnURL --
	public function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

    # -- amount --
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- bankInfo --
	protected function getBankListInfoFallback() {
		return array(
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '建设银行', 'value' => 'CCB'),
			array('label' => '农业银行', 'value' => 'ABC'),
			array('label' => '民生银行', 'value' => 'CMBC'),
			array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
			array('label' => '光大银行', 'value' => 'CEB'),
		);
	}

	# -- MD5sign --
	public function MD5sign($params) {
       	$signStr = $this->createSignStr($params);
        $sign = strtolower(md5($signStr));
		return $sign;
	}

    private function createSignStr($params) {
		$params = array('partner'=>$params['partner'],'service'=>$params['service'],'out_trade_no'=>$params['out_trade_no'],'amount'=>$params['amount'],'tran_ip'=>$params['tran_ip'],'subject'=>$params['subject'],'request_time'=>$params['request_time'],'notify_url'=>$params['notify_url']);
        ksort($params);
		$signStr = '';
		foreach ($params as $key => $value) {
            if(empty($value)){
                $signStr .= $key."=".$value."&";
            }
		}
		$signStr .= 'verfication_code='. $this->getSystemInfo('key');
		return $signStr;
	}

	# -- RSAsign 商戶私鑰簽名 --
    public function RSAsign($params){
		ksort($params);
        $signStr = '';
        foreach ($params as $key => $value) {
            if(""!=$value && $key != 'sign'){
                $signStr .= $key."=".$value."&";
            }
        }
        $signStr = rtrim($signStr, '&');


		$prikey = openssl_get_privatekey($this->getPrivKeyStr());
        openssl_sign($signStr, $sign_info, $prikey);
        $RSASign = base64_encode($sign_info);
        return $RSASign;
    }

    # -- RSAencrypt 平台公鑰加密--
    public function encrypt($params){
		ksort($params);
        $str = '';
        foreach ($params as $key => $value) {
            if(""!=$value){
                $str .= $key."=".$value."&";
            }
        }
        $str = rtrim($str, '&');

        $split = str_split($str, 64);
        $encParam_encrypted = '';
		$pubkey = openssl_get_publickey($this->getServerPubKeyStr());
        foreach($split as $part) {
            openssl_public_encrypt($part,$partialData,$pubkey);
            $encParam_encrypted .= $partialData;
        }

        $encrypt = base64_encode(($encParam_encrypted));
        return $encrypt;
    }

    # -- RSA解密 --
    public function decrypt($data){
		$prikey = openssl_get_privatekey($this->getPrivKeyStr());
		$data=base64_decode($data);
		$Split = str_split($data, 128);
		$decData_decrypted='';
		foreach($Split as $k=>$v){
			openssl_private_decrypt($v, $decrypted, $prikey);
			$decData_decrypted.= $decrypted;
		}

		return $decData_decrypted;
	}

	# -- 驗簽 --
    public function validateSign($data, $sign) {
        $pubkey = openssl_get_publickey($this->getServerPubKeyStr());
		return (bool)openssl_verify($data, base64_decode($sign), $pubkey);
	}


    # -- public key --
    public function getServerPubKeyStr() {
        $hbepay_pub_key = $this->getSystemInfo('hbepay_server_pub_key');
        $pubkey = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($hbepay_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return $pubkey;
    }

    # -- private key --
    public function getPrivKeyStr() {
        $hbepay_pri_key = $this->getSystemInfo('hbepay_pri_key');
        $prikey = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($hbepay_pri_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        return $prikey;
    }
}
