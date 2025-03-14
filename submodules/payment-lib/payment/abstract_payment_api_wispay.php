<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * WISPAY
 *
 * * 'WISPAY_QUICKPAY_PAYMENT_API', ID 5335
 * * 'WISPAY_ALIPAY_PAYMENT_API', ID 5635
 * * 'WISPAY_ALIPAY_H5_PAYMENT_API', ID 5636
 * * 'WISPAY_WEIXIN_PAYMENT_API', ID 5637
 * * 'WISPAY_WEIXIN_H5_PAYMENT_API', ID 5638
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_wispay extends Abstract_payment_api {

    const PAYTYPE_QUICKPAY= '01';
    const PAYTYPE_ALIPAY= '11';
	const PAYTYPE_WEIXIN= '10';

    const CHANNEL_PC= '01';
	const CHANNEL_MOBILE= '02';

	const RETURN_SUCCESS_CODE = '0000';
    const CALLBACK_SUCCESS_CODE = '0000';
    const RETURN_SUCCESS = '{"code":"SUCCESS","msg":"ok"}';
    const RETURN_FAILED = '{"code":"ERROR","msg":"faild"}';


	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function getSecretInfoList() {
		$secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'webwt_server_pub_key', 'webwt_pri_key');
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
        $params['version'] = "20180221";
        $params['merId'] = $this->getSystemInfo("account");
        $params['transDate'] = date("Ymd");
        $params['seqId'] = $order->secure_id;
        $params['transTime'] = date("His");
        $params['amount'] = $this->convertAmountToCurrency($amount); //分
        $params['notifyUrl'] = $this->getNotifyUrl($orderId);
		$params['returnUrl'] = $this->getReturnUrl($orderId);
		$params['subject'] = "Deposit";
		$params['body'] = "Deposit";
		$params['cardType'] = '01';
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign'] = $this->sign($params);


		$this->CI->utils->debug_log("=====================wispay generatePaymentUrlForm", $params);
		return $this->processPaymentUrlForm($params);
    }


	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
	    $url = $this->getSystemInfo('url');
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $url,
			'params' => $params,
			'post' => true,
		);
	}

	# Submit URL form
	protected function processPaymentUrlFormURL($params) {

    	$url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, false, $params['seqId']);
        $decode_data = json_decode($response, true);
        $this->CI->utils->debug_log('=====================wispay processPaymentUrlFormURL response json to array', $decode_data);
        $msg = lang('Invalidate API response');

		if(!empty($decode_data['code']) && ($decode_data['code'] == self::RETURN_SUCCESS_CODE)) {
			return array(
	                'success' => true,
	                'type' => self::REDIRECT_TYPE_URL,
	                'url' => $decode_data['payResult'],
            	);
        }else {
            if( $decode_data['code'] != self::RETURN_SUCCESS_CODE && !empty($decode_data['msg'])) {
                $msg = $decode_data['msg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
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
            $this->CI->utils->debug_log('=======================wispay callbackFromServer server callbackFrom', $params);
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
			$this->CI->sale_order->updateExternalInfo($order->id, null, null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS;
        } else {
            $result['return_error'] = self::RETURN_FAIL;
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
		$requiredFields = array('merId','stat','amount','version','seqId');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================wispay missing parameter: [$f]", $fields);
				return false;
			}
		}
		# is signature authentic?
		if (!$this->validateSign($fields,$fields['sign'])) {
			$this->writePaymentErrorLog('=====================wispay checkCallbackOrder Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['stat'] != self::CALLBACK_SUCCESS_CODE) {
			$payStatus = $fields['stat'];
			$this->writePaymentErrorLog("=====================wispay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================wispay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['seqId'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================wispay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
    }


	# -- amount --
	protected function convertAmountToCurrency($amount) {
		return number_format($amount * 100, 0, '.', '');
	}

	# -- notifyURL --
	public function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

    # -- returnURL --
	public function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}


    # -- public key --
	public function getServerPubKeyStr() {
        $webwt_pub_key = $this->getSystemInfo('webwt_server_pub_key');
		$pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($webwt_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
		return $pub_key;
    }


	# -- private key --
	public function getPrivKeyStr() {
		$webwt_pri_key = $this->getSystemInfo('webwt_pri_key');
		$prikey = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($webwt_pri_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
		return $prikey;
	}


   # -- signatures --
    private function sign($params) {
		$signStr = $this->createSignStr($params);
		openssl_sign($signStr, $sign_info, openssl_get_privatekey($this->getPrivKeyStr()), OPENSSL_ALGO_SHA1);

		$sign = strtoupper(bin2hex($sign_info));
		return $sign;
	}

    private function createSignStr($params) {
        ksort($params);
		$signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign'){
                continue;
            }
			$signStr.=$key."=".$value."&";
        }
        $signStr = rtrim($signStr, '&');
		return $signStr;
    }

	# -- 驗簽 --
    public function validateSign($data, $sign) {
        $signStr = $this->createSignStr($data);

		return (bool)openssl_verify($signStr, pack("H*",$sign), openssl_get_publickey($this->getServerPubKeyStr()), OPENSSL_ALGO_SHA1);
	}
}


