<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * JIEEPAY捷付通
 * http://www.jieepay.com
 *
 * * JIEEPAY_PAYMENT_API, ID: 136
 * * JIEEPAY_ALIPAY_PAYMENT_API, ID: 137
 * * JIEEPAY_WECHAT_PAYMENT_API, ID: 138
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: http://cashier.chinapay360.com/payment/
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_jieepay extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'success|9999';
	const RESULT_CODE_SUCCESS = 'success'; # 'Success001'; In actual testing, the successful wechat payment returns success002

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

		$params['MerId'] = $this->getSystemInfo("account");
		$params['OrdId'] = $order->secure_id;
		$params['OrdAmt'] = $this->convertAmountToCurrency($amount);
		$params['BankCode'] = $this->getBankCode($order->direct_pay_extra_info);

		# Fixed values
		$params['PayType'] = 'DTDP';
		$params['CurCode'] = 'RMB';
		$params['ProductInfo'] = 'Topup';
		$params['Remark'] = 'Topup';

		$params['ReturnURL'] = $this->getReturnUrl($orderId);
		$params['NotifyURL'] = $this->getNotifyUrl($orderId);

		$params['SignType'] = 'MD5';
		$params['SignInfo'] = $this->sign($params);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true
		);
	}

	public abstract function getBankCode($direct_pay_extra_info);

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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['OrdNo'], null, null, null, $response_result_id);
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
		# does all required fields exist?
		$requiredFields = array(
			'OrdId', 'OrdAmt', 'ResultCode', 'SignInfo'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->verify($fields, $fields['SignInfo'])) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		# is payment successful?
		if (strpos(strtolower($fields['ResultCode']), self::RESULT_CODE_SUCCESS) === false) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		# does amount match?
		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['OrdAmt']))
		) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	/**
	 *
	 * detail: a static bank list information
	 *
	 * @return array
	 */
	public function getBankListInfoFallback() {
		return array(
			array('label' => '农业银行', 'value' => 'ABC'),
			array('label' => '工商银行', 'value' => 'ICBC'),
			array('label' => '建设银行', 'value' => 'CCB'),
			array('label' => '交通银行', 'value' => 'BCOM'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '民生银行', 'value' => 'CMBC'),
			array('label' => '光大银行', 'value' => 'CEBB'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '中国邮政', 'value' => 'PSBC'),
			array('label' => '平安银行', 'value' => 'SPABANK'),
			array('label' => '中信银行', 'value' => 'ECITIC'),
			array('label' => '广东发展银行', 'value' => 'GDB'),
			array('label' => '华夏银行', 'value' => 'HXB'),
			array('label' => '浦发银行', 'value' => 'SPDB'),
			array('label' => '东亚银行', 'value' => 'BEA'),
			array('label' => '微信扫码', 'value' => 'WECHATQR'),
			array('label' => '支付宝扫码', 'value' => 'ALIPAYQR'),
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

	private function prepareSign($data) {
		$array = array();
		$keys = array('MerId', 'OrdId', 'OrdAmt', 'PayType', 'CurCode', 'BankCode', 'ProductInfo', 'Remark', 'ReturnURL', 'NotifyURL', 'SignType');
		foreach ($keys as $key) {
			array_push($array, $key . '=' . $data[$key]);
		}
		array_push($array, 'MerKey='.$this->getSystemInfo('key'));
		return implode($array, '&');
	}

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($data) {
		$dataStr = $this->prepareSign($data);
		$signature = MD5($dataStr);
		return $signature;
	}

	public function prepareVerify($data) {
		$array = array();
		$keys = array('MerId', 'OrdId', 'OrdAmt', 'OrdNo', 'ResultCode', 'Remark', 'SignType');
		foreach ($keys as $key) {
			array_push($array, $key . '=' . $data[$key]);
		}
		return implode($array, '&');
	}

	/**
	 * detail: verify signature
	 *
	 * @param array $data
	 * @param string $signature
	 * @return	string
	 */
	public function verify($data, $signature) {
		$dataStr = $this->prepareVerify($data);
		$signInfo = md5(md5($dataStr).$this->getSystemInfo('key'));
		if ($signInfo == $signature) {
			return true;
		} else {
			return false;
		}
	}

}
