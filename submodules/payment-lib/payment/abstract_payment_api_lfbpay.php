<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * LFBPAY 乐付宝
 *
 * * LFBPAY_PAYMENT_API, ID: 388
 * * LFBPAY_ALIPAY_PAYMENT_API, ID: 389
 * * LFBPAY_WEIXIN_PAYMENT_API, ID: 390
 * * LFBPAY_QQPAY_PAYMENT_API, ID: 391
 * * LFBPAY_JDPAY_PAYMENT_API, ID: 417
 *
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
abstract class Abstract_payment_api_lfbpay extends Abstract_payment_api {
	const SERVICE_B2C = 'TRADE.B2C';
	const SERVICE_SCANPAY = 'TRADE.SCANPAY';
	const SERVICE_H5PAY = 'TRADE.H5PAY';
	const SERVICE_CALLBACK = 'TRADE.NOTIFY';
	const PAYTYPE_ALIPAY = '1';
	const PAYTYPE_WEIXIN = '2';
	const PAYTYPE_QQPAY = '3';
	const PAYTYPE_JDPAY = '5';
	const QRCODE_REPONSE_CODE_SUCCESS = '00';
	const ORDER_STATUS_SUCCESS = '1';
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const RETURN_FAILED_CODE = 'faile';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	/**
	 * detail: Constructs an URL so that the caller can redirect / invoke it to make payment through this API, See controllers/redirect.php for detail.
	 *
	 * @param int $orderId order id
	 * @param int $playerId player id
	 * @param float $amount amount
	 * @param string $orderDateTime
	 * @param int $playerPromoId
	 * @param string $enabledSecondUrl
	 * @param int $bankId
	 * @return array
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params['version'] = '1.0.0.0';
		$params['merId'] = $this->getSystemInfo("account");
		$params['tradeNo'] = $order->secure_id;
		$params['tradeDate'] = date('Ymd');
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['notifyUrl'] = $this->getNotifyUrl($orderId);
		$params['extra'] = 'Deposit';
		$params['summary'] = 'Deposit';
		$params['expireTime'] = '60';
		$params['clientIp'] = $this->getClientIP();

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================lfbpay generatePaymentUrlForm", $params);

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
		$response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['tradeNo']);
		$response = $this->parseResultXML($response);

		$msg = lang('Invalidate API response');
		if($response['detail']['code'] == self::QRCODE_REPONSE_CODE_SUCCESS) {

			if($this->CI->utils->is_mobile()) {
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_URL,
					'url' => base64_decode($response['detail']['qrCode'])
				);
			}

			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => base64_decode($response['detail']['qrCode'])
			);
		}
		else {
			if($response['detail']['desc']) {
				$msg = $response['detail']['desc'];
			}

			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $msg
			);
		}
	}

	public function parseResultXML($resultXml) {
		$result = NULL;
		$obj = simplexml_load_string($resultXml);
		$arr = $this->CI->utils->xmlToArray($obj);
		$result = $arr;
		return $result;
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

		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			return $result;
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['opeNo'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
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
		$requiredFields = array(
			'service', 'merId', 'tradeNo', 'tradeDate', 'opeNo', 'opeDate', 'amount', 'status', 'extra', 'payTime', 'notifyType'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================lfbpay missing parameter: [$f]", $fields);
				return false;
			}
		}

		$callbackSign = strtoupper($this->sign($fields));

		# is signature authentic?
		if ($fields['sign'] != $callbackSign) {
			$this->writePaymentErrorLog("=====================lfbpay check callback sign error, signature is [$callbackSign], match? ", $fields);
			return false;
		}

		if ($fields['status'] != self::ORDER_STATUS_SUCCESS) {
			$payStatus = $fields['status'];
			$this->writePaymentErrorLog("=====================lfbpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['amount'] )
		) {
			$this->writePaymentErrorLog("=====================lfbpay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	public function getBankListInfoFallback() {
		return array(
			array('label' => '中国农业银行', 'value' => 'ABC'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '渤海银行', 'value' => 'CBHB'),
			array('label' => '中国建设银行', 'value' => 'CCB'),
			array('label' => '光大银行', 'value' => 'CEB'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '民生银行', 'value' => 'CMBC'),
			array('label' => '中信银行', 'value' => 'CNCB'),
			array('label' => '交通银行', 'value' => 'COMM'),
			array('label' => '广发银行', 'value' => 'GDB'),
			array('label' => '华夏银行', 'value' => 'HXB'),
			array('label' => '工商银行', 'value' => 'ICBC'),
			array('label' => '平安银行', 'value' => 'PAB'),
			array('label' => '中国邮政', 'value' => 'PSBC'),
			array('label' => '浦发银行', 'value' => 'SPDB')
		);
	}

	# -- Private functions --
	/**
	 * detail: After payment is complete, the gateway will invoke this URL asynchronously
	 *
	 * @param int $orderId
	 * @return void
	 */
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: After payment is complete, the gateway will send redirect back to this URL
	 *
	 * @param int $orderId
	 * @return void
	 */
	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: Format the amount value for the API
	 *
	 * @param float $amount
	 * @return float
	 */
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
		$signStr = '';
		//1网银支付
		if($params['service'] == self::SERVICE_B2C) {
			$signStr = sprintf(
				"service=%s&version=%s&merId=%s&tradeNo=%s&tradeDate=%s&amount=%s&notifyUrl=%s&extra=%s&summary=%s&expireTime=%s&clientIp=%s&bankId=%s",
					$params['service'],
					$params['version'],
					$params['merId'],
					$params['tradeNo'],
					$params['tradeDate'],
					$params['amount'],
					$params['notifyUrl'],
					$params['extra'],
					$params['summary'],
					$params['expireTime'],
					$params['clientIp'],
					$params['bankId']
			);

	    }
	    //2扫码支付
	    else if($params['service'] == self::SERVICE_SCANPAY || $params['service'] == self::SERVICE_H5PAY ){
			$signStr = sprintf(
					"service=%s&version=%s&merId=%s&typeId=%s&tradeNo=%s&tradeDate=%s&amount=%s&notifyUrl=%s&extra=%s&summary=%s&expireTime=%s&clientIp=%s",
					$params['service'],
					$params['version'],
					$params['merId'],
					$params['typeId'],
					$params['tradeNo'],
					$params['tradeDate'],
					$params['amount'],
					$params['notifyUrl'],
					$params['extra'],
					$params['summary'],
					$params['expireTime'],
					$params['clientIp']
			);
		}
		//7回调
		else if($params['service'] == self::SERVICE_CALLBACK){
			$signStr = sprintf(
					"service=%s&merId=%s&tradeNo=%s&tradeDate=%s&opeNo=%s&opeDate=%s&amount=%s&status=%s&extra=%s&payTime=%s",
					$params['service'],
					$params['merId'],
					$params['tradeNo'],
					$params['tradeDate'],
					$params['opeNo'],
					$params['opeDate'],
					$params['amount'],
					$params['status'],
					$params['extra'],
					$params['payTime']
			);
		}

		$signStr .= $this->getSystemInfo('key');
	    $sign = md5($signStr);
		return $sign;
	}
}
