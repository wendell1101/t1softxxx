<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * YOMPAY 优付支付
 * https://www.yompay.com/
 *
 * YOMPAY_PAYMENT_API, ID: 52
 *
 * Required Fields:
 *
 * * URL
 * * Key – Signing key
 * * Extra Info
 *
 * Field Values:
 *
 * * URL: https://www.yompay.com/Payapi/
 * * Extra Info:
 * > {
 * >     "yompay_MER_NO": "##merchant number##",
 * >     "bank_list": {
 * >         "ABC": "_json:{\"1\": \"ABC\", \"2\":\"中国农业银行\"}",
 * >         "BOC": "_json:{\"1\": \"BOC\", \"2\":\"中国银行\"}",
 * >         "BOCOM": "_json:{\"1\": \"BOCOM\", \"2\":\"交通银行\"}",
 * >         "CCB": "_json:{\"1\": \"CCB\", \"2\":\"中国建设银行\"}",
 * >         "ICBC": "_json:{\"1\": \"ICBC\", \"2\":\"中国工商银行\"}",
 * >         "PSBC": "_json:{\"1\": \"PSBC\", \"2\":\"中国邮政储蓄银行\"}",
 * >         "CMBC": "_json:{\"1\": \"CMBC\", \"2\":\"招商银行\"}",
 * >         "SPDB": "_json:{\"1\": \"SPDB\", \"2\":\"浦发银行\"}",
 * >         "CEBBANK": "_json:{\"1\": \"CEBBANK\", \"2\":\"中国光大银行\"}",
 * >         "ECITIC": "_json:{\"1\": \"ECITIC\", \"2\":\"中信银行\"}",
 * >         "PINGAN": "_json:{\"1\": \"PINGAN\", \"2\":\"平安银行\"}",
 * >         "CMBCS": "_json:{\"1\": \"CMBCS\", \"2\":\"中国民生银行\"}",
 * >         "HXB": "_json:{\"1\": \"HXB\", \"2\":\"华夏银行\"}",
 * >         "CGB": "_json:{\"1\": \"CGB\", \"2\":\"广发银行\"}",
 * >         "BCCB": "_json:{\"1\": \"BCCB\", \"2\":\"北京银行\"}",
 * >         "BOS": "_json:{\"1\": \"BOS\", \"2\":\"上海银行\"}",
 * >         "BRCB": "_json:{\"1\": \"BRCB\", \"2\":\"北京农商银行\"}",
 * >         "CIB": "_json:{\"1\": \"CIB\", \"2\":\"兴业银行\"}",
 * >         "SRCB": "_json:{\"1\": \"SRCB\", \"2\":\"上海农商银行\"}",
 * >         "WEIXIN": "_json:{\"1\": \"WEIXIN\", \"2\":\"微信扫码支付\"}"
 * >     }
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yompay extends Abstract_payment_api {
	const YOMPAY_INPUT_CHARSET = "UTF-8";
	const RETURN_SUCCESS_CODE = 'success';

	private $info;

	public function __construct($params = null) {
		parent::__construct($params);

		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->info = $this->getInfoByEnv();
	}

	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return YOMPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yompay';
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

		# Reference: apiphp.doc, section 2.2.2
		# read some parameters from config
		$paramNames = array('MER_NO');
		$params = array();
		foreach ($paramNames as $p) {
			$params[$p] = $this->getSystemInfo("yompay_$p");
		}

		# constant parameters
		$params['INPUT_CHARSET'] = self::YOMPAY_INPUT_CHARSET;
		$params['RETURN_URL'] = $this->getReturnUrl($orderId);
		$params['NOTIFY_URL'] = $this->getNotifyUrl($orderId);

		# order-related params
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$params['ORDER_NO'] = $order->secure_id;
		$params['ORDER_AMOUNT'] = $this->convertAmountToCurrency($amount);
		$params['RETURN_PARAMS'] = 'NA'; # No parameter needed
		$params['PRODUCT_NAME'] = 'Payment'; #lang('pay.deposit');
		$params['PRODUCT_NUM'] = 1;

		# anti-phising params
		$params['REFERER'] = $_SERVER['HTTP_HOST'];
		$params['CUSTOMER_IP'] = parent::getClientIP();
		$params['CUSTOMER_PHONE'] = 'NA';
		$params['RECEIVE_ADDRESS'] = 'NA';

		$direct_pay_extra_info = $order->direct_pay_extra_info;
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['BANK_CODE'] = $extraInfo['banktype'];
			}
		}

		if(!isset($params['BANK_CODE'])) {
			$params['BANK_CODE'] = '';
		}

		# sign param
		$params['SIGN'] = $this->sign($params);

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
		$success=true;
		// $this->CI->sale_order->startTrans();

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
			$this->CI->sale_order->updateExternalInfo($order->id,
				$params['trade_no'], $params['trade_time'], # Use trade_time as bank order ID
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
			'mer_no', 'order_no', 'order_amount', 'return_params', 'trade_no',
			'trade_time', 'trade_status', 'sign',
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

		$processed = true; # processed is set to true once the signature verification pass

		# check parameter values: orderStatus, tradeAmt, orderNo, merchNo
		# is payment successful?
		if ($fields['trade_status'] != '1') {
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
		if ($fields['mer_no'] != $this->getSystemInfo('yompay_MER_NO')) {
			$this->writePaymentErrorLog("Merchant codes do not match, expected [" . $this->getSystemInfo('yompay_MER_NO') . "]", $fields);
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

	## functions to display banks etc on the cashier page
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'banktype', 'type' => 'list', 'label_lang' => 'pay.bank',
				'list' => $this->getBankList(), 'list_tree' => $this->getBankListTree()),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	public function getBankList() {
		//create from list tree
		$list = array();
		$bankListInfo = $this->getBankListInfo();
		foreach ($bankListInfo as $bankInfo) {
			$list[$bankInfo['value']] = $bankInfo['label'];
		}
		return $list;
	}

	# The data here is for reference only. Actual data in extra_info->bank_list
	public function getBankListInfo() {
		return array(
			array('label' => '微信扫码支付', 'value' => 'WEIXIN'),
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
		/* == extra_info config ==
			"bank_list": {
				"ABC" : "_json:{\"1\": \"ABC\", \"2\":\"中国农业银行\"}",
				"BOC" : "_json:{\"1\": \"BOC\", \"2\":\"中国银行\"}",
				"BOCOM" : "_json:{\"1\": \"BOCOM\", \"2\":\"交通银行\"}",
				"CCB" : "_json:{\"1\": \"CCB\", \"2\":\"中国建设银行\"}",
				"ICBC" : "_json:{\"1\": \"ICBC\", \"2\":\"中国工商银行\"}",
				"PSBC" : "_json:{\"1\": \"PSBC\", \"2\":\"中国邮政储蓄银行\"}",
				"CMBC" : "_json:{\"1\": \"CMBC\", \"2\":\"招商银行\"}",
				"SPDB" : "_json:{\"1\": \"SPDB\", \"2\":\"浦发银行\"}",
				"CEBBANK" : "_json:{\"1\": \"CEBBANK\", \"2\":\"中国光大银行\"}",
				"ECITIC" : "_json:{\"1\": \"ECITIC\", \"2\":\"中信银行\"}",
				"PINGAN" : "_json:{\"1\": \"PINGAN\", \"2\":\"平安银行\"}",
				"CMBCS" : "_json:{\"1\": \"CMBCS\", \"2\":\"中国民生银行\"}",
				"HXB" : "_json:{\"1\": \"HXB\", \"2\":\"华夏银行\"}",
				"CGB" : "_json:{\"1\": \"CGB\", \"2\":\"广发银行\"}",
				"BCCB" : "_json:{\"1\": \"BCCB\", \"2\":\"北京银行\"}",
				"BOS" : "_json:{\"1\": \"BOS\", \"2\":\"上海银行\"}",
				"BRCB" : "_json:{\"1\": \"BRCB\", \"2\":\"北京农商银行\"}",
				"CIB" : "_json:{\"1\": \"CIB\", \"2\":\"兴业银行\"}",
				"SRCB" : "_json:{\"1\": \"SRCB\", \"2\":\"上海农商银行\"}",
				"WEIXIN" : "_json:{\"1\": \"WEIXIN\", \"2\":\"微信扫码支付\"}"
			}
		*/
	}

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
	# Sign the data for submitting to payment API
	# Reference: apiphp.doc, section 2.2.2
	public function sign($data) {
		$paramNames = array(
			'INPUT_CHARSET', 'RETURN_URL', 'NOTIFY_URL', 'BANK_CODE', 'ORDER_NO',
			'ORDER_AMOUNT', 'PRODUCT_NAME', 'PRODUCT_NUM', 'REFERER', 'CUSTOMER_IP',
			'CUSTOMER_PHONE', 'RECEIVE_ADDRESS', 'RETURN_PARAMS'
			);
		$dataStr = '';
		foreach($paramNames as $key) {
			$dataStr .= $data[$key];
		}
		return MD5($dataStr . $this->info['key']);
	}

	public function signCallback($data) {
		$paramNames = array(
			'mer_no', 'order_no', 'order_amount', 'return_params', 'trade_no',
			'trade_time', 'trade_status'
			);
		$dataStr = '';
		foreach($paramNames as $key) {
			$dataStr .= $data[$key];
		}

		return MD5($dataStr . $this->info['key']);
	}

	# Verify the signature for payment API callback
	# Reference: apiphp.doc, section 2.4.2
	public function verifySignature($data) {
		$mySign = $this->signCallback($data);
		if (strcasecmp($mySign, $data['sign']) === 0) {
			return true;
		} else {
			return false;
		}
	}
}
