<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * WEBWT 瀚银
 * *
 * * 'WEBWT_QUICKPAY_PAYMENT_API', ID: 5319
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

abstract class Abstract_payment_api_webwt extends Abstract_payment_api {

	const RETURN_SUCCESS_CODE = '01';
	const RETURN_FAILED_CODE = '02';
	const RETURN_SUCCESS = 'SUCCESS';
	const RETURN_FAILED = 'FAIL';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function getSecretInfoList() {
		$secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'webwt_pri_key');
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
		$params['agtId'] = $this->getSystemInfo("agtid");
		$params['merId'] = $this->getSystemInfo("account");
        $params['orderAmt'] = $this->convertAmountToCurrency($amount); //分
        $params['memberId'] = $playerId;
		$params['orderId'] = $order->secure_id;
		$params['orderTime'] = date("Ymd");
        $params['notifyUrl'] = $this->getNotifyUrl($orderId);
		$params['pageReturnUrl'] = $this->getReturnUrl($orderId);
		$params['goodsName'] = 'Deposit';

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("==========================webwt generatePaymentUrlForm", $params);
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
            $this->CI->utils->debug_log('=======================webwt callbackFromServer server callbackFrom', $params);
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

		$requiredFields = array('orderState', 'orderId','orderAmt');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================webwt missing parameter: [$f]", $fields);
				return false;
			}
		}
        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=======================webwt checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['orderState'] != self::RETURN_SUCCESS_CODE) {
			$payStatus = $fields['orderState'];
			$this->writePaymentErrorLog("=====================webwt Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($fields['orderAmt'] != $this->convertAmountToCurrency($order->amount)) {
			$this->writePaymentErrorLog("=====================webwt Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['orderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================webwt checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
		return number_format($amount*100, 0, '.', '');
	}

	# -- bankInfo --
	protected function getBankListInfoFallback() {
		return array(
			array('label' => '工商银行', 'value' => 'ICBC'),
			array('label' => '建设银行', 'value' => 'CCB'),
			array('label' => '农业银行', 'value' => 'ABC'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '中信银行', 'value' => 'CITIC'),
			array('label' => '民生银行', 'value' => 'CMBC'),
			array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
		);
	}

    # -- signatures --
    private function sign($params) {
        $signStr = $this->createSignStr($params);
		$sign = strtoupper(md5($signStr));
		$signRSA = $this->RSAsign($sign);

        return $signRSA;
	}

    private function createSignStr($params) {
        ksort($params);
		$signStr = '';
        foreach($params as $key => $value) {
			$signStr.=$key."=".$value."&";
        }
        $signStr = $signStr."key=".$this->getSystemInfo('key');
		return $signStr;
    }

    private function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
			if( $key == 'sign'){
				continue;
			}
			$signStr.=$key."=".$value."&";
		}

        $signStr = $signStr."key=".$this->getSystemInfo('key');
		$sign = strtoupper(md5($signStr));
		if($sign == $sign){
			return true;
		}
		else{

			return false;
		}
    }

	# -- private key --
	public function getPrivKeyStr() {
		$webwt_pri_key = $this->getSystemInfo('webwt_pri_key');
		$prikey = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($webwt_pri_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
		return $prikey;
	}


	# -- RSA --
    public function RSAsign($signStr){
		$prikey = openssl_get_privatekey($this->getPrivKeyStr());

        openssl_sign($signStr, $sign_info, $prikey, OPENSSL_ALGO_SHA256);
        $RSASign = base64_encode($sign_info);

        return $RSASign;
    }
}
