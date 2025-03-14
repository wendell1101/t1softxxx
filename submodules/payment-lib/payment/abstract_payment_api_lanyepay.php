<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * LANYEPAY 蓝叶支付
 *
 * * LANYEPAY_PAYMENT_API, ID: 412
 * * LANYEPAY_ALIPAY_PAYMENT_API, ID: 413
 * * LANYEPAY_WEIXIN_PAYMENT_API, ID: 414
 * * LANYEPAY_TENPAY_PAYMENT_API, ID: 415
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://zf.chemstar.net.cn/apisubmit
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_lanyepay extends Abstract_payment_api {
	const PAYTYPE_BANK       = 'bank';
	const PAYTYPE_ALIPAY     = 'alipay';
	const PAYTYPE_ALIPAY_WAP = 'aliwap';
	const PAYTYPE_WEIXIN     = 'weixin';
	const PAYTYPE_WEIXIN_WAP = 'wxh5';
	const PAYTYPE_QQPAY      = 'qqrcode';
	const PAYTYPE_QQPAY_WAP  = 'qqwallet';
	const PAYTYPE_JDPAY      = 'jd';
	const PAYTYPE_QUICKPAY   = 'kuaijie';
	const PAYTYPE_UNIONPAY   = 'yl';
	const PAYTYPE_TENPAY     = 'tenpay';

	const QRCODE_REPONSE_CODE_SUCCESS = '000000';
	const ORDER_STATUS_SUCCESS = '1';
	const RETURN_SUCCESS_CODE = 'success';
	const RETURN_FAILED_CODE = 'failed';

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

		$params['version'] = '1.0';
		$params['customerid'] = $this->getSystemInfo("account");
		$params['sdorderno'] = $order->secure_id;
		$params['total_fee'] = $this->convertAmountToCurrency($amount);
		$params['returnurl'] = $this->getReturnUrl($orderId);
		$params['notifyurl'] = $this->getNotifyUrl($orderId);
		$this->configParams($params, $order->direct_pay_extra_info);

		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================lanyepay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	protected function processPaymentUrlFormPost($params) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true,
		);
	}

	protected function processPaymentUrlFormQRCode($params) {}

	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		$this->CI->utils->debug_log("=====================lanyepay callbackFrom $source params", $params);

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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['sdpayno'], null, null, null, $response_result_id);
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

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array(
			'status', 'customerid', 'sdpayno', 'sdorderno', 'total_fee', 'paytype'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================lanyepay missing parameter: [$f]", $fields);
				return false;
			}
		}

		$callbackSign = $this->sign($fields, false, false);

		# is signature authentic?
		if ($fields['sign'] != $callbackSign) {
			$this->writePaymentErrorLog("=====================lanyepay check callback sign error, signature is [$callbackSign], match? ", $fields['sign']);
			return false;
		}

		if ($fields['status'] != self::ORDER_STATUS_SUCCESS) {
			$status = $fields['status'];
			$this->writePaymentErrorLog("=====================lanyepay Payment was not successful, status is [$status]", $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != floatval( $fields['total_fee']) ) {
			$this->writePaymentErrorLog("=====================lanyepay Payment amounts do not match, expected [$order->amount]", $fields);
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
			array('label' => '工商银行', 'value' => 'ICBC'),
			array('label' => '农业银行', 'value' => 'ABC'),
			array('label' => '中国银行', 'value' => 'BOCSH'),
			array('label' => '建设银行', 'value' => 'CCB'),
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '浦发银行', 'value' => 'SPDB'),
			array('label' => '广发银行', 'value' => 'GDB'),
			array('label' => '交通银行', 'value' => 'BOCOM'),
			array('label' => '中信银行', 'value' => 'CNCB'),
			array('label' => '民生银行', 'value' => 'CMBC'),
			array('label' => '光大银行', 'value' => 'CEB'),
			array('label' => '华夏银行', 'value' => 'HXB'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '上海银行', 'value' => 'BOS'),
			array('label' => '上海农商', 'value' => 'SRCB'),
			array('label' => '平安银行', 'value' => 'PAB'),
			array('label' => '北京银行', 'value' => 'BCCB'),
			array('label' => '邮政储蓄银行', 'value' => 'PSBC')
		);
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- private helper functions --
	public function sign($params) {
		$md5key = $this->getSystemInfo('key');

		if(isset($params['status'])) {	//callback params
			$data = array(
				"customerid", "status", "sdpayno", "sdorderno", "total_fee", "paytype"
			);
		}
		else {
			$data = array(
				"version", "customerid", "total_fee", "sdorderno", "notifyurl", "returnurl"
			);
		}

	    $arr = array();
	    for($i = 0; $i< count($data); $i++){
			if (array_key_exists($data[$i], $params)) {
				$arr[$i] = $data[$i].'='.$params[$data[$i]];
			}
	    }
	    $signStr = implode('&', $arr);
	    $signStr .= '&'.$md5key;

	    $sign = md5($signStr);
		return $sign;
	}
}
