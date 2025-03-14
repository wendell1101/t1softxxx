<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * CSSF 彩世商付
 *
 * * CSSF_PAYMENT_API, ID: 340
 * * CSSF_ALIPAY_PAYMENT_API, ID: 341
 * * CSSF_WEIXIN_PAYMENT_API, ID: 342
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
abstract class Abstract_payment_api_cssf extends Abstract_payment_api {
	const PAYTYPE_ALIPAY = '11';
	const PAYTYPE_WEIXIN = '10';
	const QRCODE_REPONSE_CODE_SUCCESS = '000000';
	const ORDER_STATUS_SUCCESS = '2';
	const RETURN_SUCCESS_CODE = 'OK';
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

		$params = array();
		$params['outOid'] = $order->secure_id;
		$params['merchantCode'] = $this->getSystemInfo("account");
		$params['mgroupCode'] = $this->getSystemInfo("mgroupCode");
		$params['transAmount'] = $this->convertAmountToCurrency($amount);
		$params['goodsName'] = 'Deposit';
		$params['goodsDesc'] = 'Deposit';
		$params['terminalType'] = '1';	//1：PC  2：APP
		$params['pageNotifyUrl'] = $this->getReturnUrl($orderId);
		$params['tradeNotifyUrl'] = $this->getNotifyUrl($orderId);
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================cssf generatePaymentUrlForm", $params);
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
		$this->CI->utils->debug_log('=====================cssf scan url', $this->getSystemInfo('url'));
		$response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['outOid']);
		$response = json_decode($response, true);

		$this->CI->utils->debug_log('=====================cssf response', $response);

		$msg = lang('Invalidate API response');

		if($response['code'] == self::QRCODE_REPONSE_CODE_SUCCESS) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $response['value']['qrcodeUrl']
			);
		}
		else {
			if($response['msg']) {
				$msg = $response['msg'];
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

		$raw_post_data = file_get_contents('php://input', 'r');
		$flds = json_decode($raw_post_data, true);
		$params = array_merge( $params, $flds );

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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['platformOid'], null, null, null, $response_result_id);
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
			'outOid', 'merchantCode', 'mgroupCode', 'payAmount', 'orderStatus', 'platformOid', 'timestamp'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================cssf missing parameter: [$f]", $fields);
				return false;
			}
		}

		$callbackSign = $this->sign($fields, false, false);

		# is signature authentic?
		if ($fields['sign'] != $callbackSign) {
			$this->writePaymentErrorLog("=====================cssf check callback sign error, signature is [$callbackSign], match? ", $fields['sign']);
			return false;
		}

		if ($fields['orderStatus'] != self::ORDER_STATUS_SUCCESS) {
			$payStatus = $fields['orderStatus'];
			$this->writePaymentErrorLog("=====================cssf Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if (
			($this->convertAmountToCurrency($order->amount) != floatval( $fields['tranAmount'] )) &&
			($this->convertAmountToCurrency($order->amount) != floatval( $fields['transAmount'] ))
		) {
			$this->writePaymentErrorLog("=====================cssf Payment amounts do not match, expected [$order->amount]", $fields);
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
			array('label' => '建设银行', 'value' => '1004'),
			array('label' => '农业银行', 'value' => '1002'),
			array('label' => '工商银行', 'value' => '1001'),
			array('label' => '中国银行', 'value' => '1003'),
			array('label' => '浦发银行', 'value' => '1014'),
			array('label' => '光大银行', 'value' => '1008'),
			array('label' => '平安银行', 'value' => '1011'),
			array('label' => '兴业银行', 'value' => '1013'),
			array('label' => '邮政储蓄银行', 'value' => '1006'),
			array('label' => '中信银行', 'value' => '1007'),
			array('label' => '华夏银行', 'value' => '1009'),
			array('label' => '招商银行', 'value' => '1012'),
			array('label' => '广发银行', 'value' => '1017'),
			array('label' => '北京银行', 'value' => '1016'),
			array('label' => '上海银行', 'value' => '1025'),
			array('label' => '民生银行', 'value' => '1010'),
			array('label' => '交通银行', 'value' => '1005'),
			array('label' => '北京农村商业银行', 'value' => '1103')
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
		return number_format($amount * 100, 2, '.', '');
	}

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
		$md5key = "key=".$this->getSystemInfo('key');

		if($params['bankCode'] || $params['payCardType'] ) {
			$data = array(
				"outOid", "merchantCode", "mgroupCode", "transAmount", "goodsName", "goodsDesc", "terminalType", "bankCode", "userType", "cardType",
				"payCardType", "payAmount", "curType", "tranAmount", "orderStatus", "platformOid", "timestamp"	//callback params
			);
		}
		else {
			$data = array(
				"busType", "goodDesc", "goodName", "goodNum", "merchantCode", "mgroupCode", "outOid", "payAmount", "payType",
				"tranAmount", "orderStatus", "platformOid", "timestamp", "extend1", "extend2", "extend3"	//callback params
			);
		}

	    sort($data);

	    $arr = array();
	    for($i = 0; $i< count($data); $i++){
			if (array_key_exists($data[$i], $params)) {
				$arr[$i] = $data[$i].'='.$params[$data[$i]];
			}
	    }
	    $signStr = implode('&', $arr);
	    $signStr .= '&'.$md5key;

	    $sign = strtoupper(md5($signStr));
		return $sign;
	}
}
