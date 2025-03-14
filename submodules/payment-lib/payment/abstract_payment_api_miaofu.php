<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 *
 * Miaofu 秒付
 *
 * MIAOFU_PAYMENT_API, ID: 127
 * MIAOFU_ALIPAY_PAYMENT_API, ID: 128
 * MIAOFU_WECHAT_PAYMENT_API, ID: 129
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra Info
 *
 * Field Values:
 * * URL: http://pay.miaofupay.com/gateway
 * * Account: ## Merchant ID ##
 * * Key: ## MD5 Key ##
 * * Extra Info:
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_miaofu extends Abstract_payment_api {

	const INPUT_CHARSET = 'UTF-8';
	const PAY_TYPE_EBANKING = 1;
	const RETURN_SUCCESS_CODE = 'success';
	const TRADE_STATUS_SUCCESS = 'success';
	const TRADE_STATUS_FAILED = 'failed';
	const TRADE_STATUS_PAYING = 'paying';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	public abstract function getBankType($direct_pay_extra_info);

	# -- override common API functions --
	## Constructs an URL so that the caller can redirect / invoke it to make payment through this API
	## See controllers/redirect.php for detail.
	##
	## Retuns a hash containing these fields:
	## array(
	##	'success' => true,
	##	'type' => self::REDIRECT_TYPE_FORM,  ## constants defined in abstract_payment_api.php
	##	'url' => $info['url'],
	##	'params' => $params,
	##	'post' => true
	## );
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		# Reference: Documentation section 3.2.2
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$params = array();

		$params['input_charset'] = self::INPUT_CHARSET;
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['return_url'] = $this->getReturnUrl($orderId);
		$params['pay_type'] = self::PAY_TYPE_EBANKING;

		$params['bank_code'] = $this->getBankType($order->direct_pay_extra_info);
		$params['merchant_code'] = $this->getSystemInfo('account');
		$params['order_no'] = $order->secure_id;
		$params['order_amount'] = $this->convertAmountToCurrency($amount);
		$params['order_time'] = date('Y-m-d H:i:s'); # e.g. 2015-01-01 12:45:52

		$params['req_referer'] = $this->CI->utils->site_url_with_host();
		$params['customer_ip'] = $this->getClientIP();

		$params['sign'] = $this->sign($params);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true,
		);
	}

	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	## Reference: sample code, callback.php
	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	## This will be called when user redirects back to our page from payment API
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
		$success = true;

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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['trade_no'], $params['trade_time'], null, null, $response_result_id);
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
			$result['return_error'] = 'Error';
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	## Validates whether the callback from API contains valid info and matches with the order
	## Reference: Documentation section 3.3.2
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array(
			"merchant_code", "sign", "order_no", "order_amount", "trade_status"
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->verifySignature($fields)) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}

		if ($order->secure_id != $fields['order_no']) {
			$this->writePaymentErrorLog("Order numbers do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['order_amount']))
		) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['trade_status'] != self::TRADE_STATUS_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# Config in extra_info will overwrite this one
	public function getBankListInfoFallback() {
		return array(
			array('label' => '中国农业银行', 'value' => 'ABC'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '交通银行', 'value' => 'BOCOM'),
			array('label' => '中国建设银行', 'value' => 'CCB'),
			array('label' => '中国工商银行', 'value' => 'ICBC'),
			array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
			array('label' => '招商银行', 'value' => 'CMBC'),
			array('label' => '浦发银行', 'value' => 'SPDB'),
			array('label' => '中国光大银行', 'value' => 'CEBBANK'),
			array('label' => '中信银行', 'value' => 'ECITIC'),
			array('label' => '平安银行', 'value' => 'PINGAN'),
			array('label' => '中国民生银行', 'value' => 'CMBCS'),
			array('label' => '华夏银行', 'value' => 'HXB'),
			array('label' => '广发银行', 'value' => 'CGB'),
			array('label' => '北京银行', 'value' => 'BCCB'),
			array('label' => '上海银行', 'value' => 'BOS'),
			array('label' => '北京农商银行', 'value' => 'BRCB'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '上海农商银行', 'value' => 'SRCB'),
			array('label' => '支付宝扫码', 'value' => 'ZHIFUBAO'),
			array('label' => '微信扫码', 'value' => 'WEIXIN'),
		);
	}

	# -- Private functions --
	# After payment is complete, the gateway will invoke this URL asynchronously
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## After payment is complete, the gateway will send redirect back to this URL
	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## Format the amount value for the API
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	public function sign($data) {
		$strb = "";
		ksort($data);
		$this->utils->debug_log();
		foreach ($data as $key => $val) {
			if ($key == 'key' || $key == 'sign' || is_null($val) || $val == '') {
				continue;
			}
			$this->appendParam($strb, $key, $val);
		}

		$this->appendParam($strb, 'key', $this->getSystemInfo('key'));
		$strb = substr($strb, 1);
		return md5($strb);
	}

	public function verifySignature($data) {
		$mySign = $this->sign($data);
		if (strcasecmp($mySign, $data['sign']) === 0) {
			return true;
		} else {
			return false;
		}
	}

	private function appendParam(&$sb, $name, $val, $and = true, $charset = null) {
		if ($and) {
			$sb .= "&";
		} else {
			$sb .= "?";
		}

		$sb .= $name;
		$sb .= "=";
		if (is_null($val)) {
			$val = "";
		}
		if (is_null($charset)) {
			$sb .= $val;
		} else {
			$sb .= urlencode($val);
		}
	}

}
