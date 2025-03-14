<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * YiFuBao payment API implementation
extra_info: {
	"yifubao_merchant_code" : ""
}
 */
class Payment_api_yifubao extends Abstract_payment_api {
	const INPUT_CHARSET = 'UTF-8';
	const PAY_TYPE = 1; # 1为网银支付,目前暂时只支持网银支付
	const RETURN_SUCCESS_CODE = 'success'; # 商户系统在收到通知并处理完成后必须打印输出包含“success”这个字符串

	private $info;

	public function __construct($params = null) {
		parent::__construct($params);

		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->info = $this->getInfoByEnv();
	}

	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return YIFUBAO_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yifubao';
	}

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

		# Setup parameters. Reference: Documentation section 3.2.2

		# read some parameters from config
		$paramNames = array('merchant_code');
		$params = array();
		foreach ($paramNames as $p) {
			$params[$p] = $this->getSystemInfo("yifubao_$p");
		}

		# some parameters are constant
		$params['input_charset'] = self::INPUT_CHARSET;
		$params['pay_type'] = self::PAY_TYPE;
		$params['product_name'] = lang('pay.deposit'); # this will be displayed on the payment page
		$params['product_num'] = 1; # this will be displayed on the payment page

		# order-related params
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$params['order_no'] = $order->secure_id;
		$params['order_time'] = $orderDateTime->format('Y-m-d H:i:s');
		$params['order_amount'] = $this->convertAmountToCurrency($amount);
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['return_url'] = $this->getReturnUrl($orderId);

		# anti-phishing
		$params['req_referer'] = $_SERVER['HTTP_HOST'];
		$params['customer_ip'] = $this->getClientIP();

		$direct_pay_extra_info = $order->direct_pay_extra_info;
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['bank_code'] = $extraInfo['banktype'];
			}
		}

		# sign param
		$params['sign'] = $this->sign($params);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->info['url'],
			'params' => $params,
			'post' => true,
		);
	}

	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);

		//check browser or server
		if(isset($params['notify_type']) && $params['notify_type']=='page_notify') {
			//browser
			return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
		} else {
			//server
			return $this->callbackFrom('server', $orderId, $params, $response_result_id);
		}
	}

	## This will be called when user redirects back to our page from payment API
	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}

	## $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			return $result;
		}

		# Update order payment status and balance
		$success=true;
		// $this->CI->sale_order->startTrans();

		# Update player balance based on order status
		# if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
		$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
		if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
			$this->CI->utils->debug_log('CallbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
			if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
				$this->CI->sale_order->setStatusToSettled($orderId);
			}
		} else {
			# update player balance
			$this->CI->sale_order->updateExternalInfo($order->id,
				$params['trade_no'], '', # $externalOrderId, $bankOrderId. Bank order ID was not provided.
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->CI->sale_order->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}
		// $success = $this->CI->sale_order->endTransWithSucc();

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

	## Validates whether the callback from API contains valid info and matches with the order
	## Reference: code sample, callback.php
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		# does all required fields exist?
		$requiredFields = array(
			'merchant_code', 'order_no', 'order_amount', 'order_time', 'trade_no', 'trade_time', 'trade_status', 'sign'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->verify($fields, $fields['sign'])) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		# is payment successful?
		if ($fields['trade_status'] != 'success') {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		# does amount match?
		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['order_amount']))
		) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# does merchNo match?
		if ($fields['merchant_code'] != $this->getSystemInfo('yifubao_merchant_code')) {
			$this->writePaymentErrorLog("Merchant codes do not match, expected [" . $this->getSystemInfo('yifubao_merchant_code') . "]", $fields);
			return false;
		}

		# does order_no match?
		if ($fields['order_no'] != $order->secure_id) {
			$this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- Functions to display bank dropdown --
	## Reference: Documentation, section 4.2
	public function getBankListInfo() {
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
		);
	}
	/*
	"bank_list" : {
		"ABC" : "_json: { \"1\":\"Agricultural Bank of China\", \"2\":\"中国农业银行\"}",
		"BOC" : "_json: { \"1\":\"Bank of China\", \"2\":\"中国银行\"}",
		"BOCOM" : "_json: { \"1\":\"Bank of Communications\", \"2\":\"交通银行\"}",
		"CCB" : "_json: { \"1\":\"China Construction Bank\", \"2\":\"中国建设银行\"}",
		"ICBC" : "_json: { \"1\":\"Commercial Bank of China\", \"2\":\"中国工商银行\"}",
		"PSBC" : "_json: { \"1\":\"China Postal Savings Bank\", \"2\":\"中国邮政储蓄银行\"}",
		"CMBC" : "_json: { \"1\":\"China Merchants Bank\", \"2\":\"招商银行\"}",
		"SPDB" : "_json: { \"1\":\"Shanghai Pudong Development Bank\", \"2\":\"浦发银行\"}",
		"CEBBANK" : "_json: { \"1\":\"China Everbright Bank\", \"2\":\"中国光大银行\"}",
		"ECITIC" : "_json: { \"1\":\"CITIC Bank\", \"2\":\"中信银行\"}",
		"PINGAN" : "_json: { \"1\":\"Ping An Bank\", \"2\":\"平安银行\"}",
		"CMBCS" : "_json: { \"1\":\"China Minsheng Bank\", \"2\":\"中国民生银行\"}",
		"HXB" : "_json: { \"1\":\"HSBC Bank\", \"2\":\"华夏银行\"}",
		"CGB" : "_json: { \"1\":\"Guangdong Development Bank\", \"2\":\"广发银行\"}",
		"BCCB" : "_json: { \"1\":\"Bank of Beijing\", \"2\":\"北京银行\"}",
		"BOS" : "_json: { \"1\":\"Shanghai Bank\", \"2\":\"上海银行\"}",
		"BRCB" : "_json: { \"1\":\"Beijing Rural Commercial Bank\", \"2\":\"北京农商银行\"}",
		"CIB" : "_json: { \"1\":\"Industrial Bank\", \"2\":\"兴业银行\"}",
		"SRCB" : "_json: { \"1\":\"Shanghai Rural Commercial Bank\", \"2\":\"上海农商银行\"}"
	}
	*/

	# -- Private functions --
	## After payment is complete, the gateway will invoke this URL asynchronously
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

	# -- private helper functions --
	# Reference: Sample code, PHP5.2/helper.php
	public function sign($params) {
		$strb = "";
		ksort($params);
		foreach ($params as $key => $val) {
			if ($key == 'sign') {
				continue;
			}
			if (empty($val)) {
				continue;
			}
			$this->appendParam($strb, $key, $val);
		}
		$this->appendParam($strb, 'key', $this->getSystemInfo('key'));
		$strb = substr($strb, 1, strlen($strb) - 1);
		return md5($strb);
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

	private function verify($params, $sign) {
		return sign($params) == $sign;
	}

	// public function getClientIp() {
	// 	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
	// 		$ip = $_SERVER['HTTP_CLIENT_IP'];
	// 	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	// 		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	// 	} else {
	// 		$ip = $_SERVER['REMOTE_ADDR'];
	// 	}
	// 	if (REQ_CUSTOMER_ID != null)
	// 		$ip = REQ_CUSTOMER_ID;
	// 	return $ip;
	// }

}
