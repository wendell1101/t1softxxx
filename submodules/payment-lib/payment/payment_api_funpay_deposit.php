<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * Funpay (乐盈)
 * http://www.funpay.com
 *
 * FUNPAY_DEPOSIT_PAYMENT_API, ID: 90
 *
 * Note: This API is used to send payment.
 *
 * Required Fields:
 *
 * * URL
 * * Extra Info
 *
 * Field Values:
 *
 * * Live URL: https://www.funpay.com/website/pay.htm
 * * Sandbox URL: https://www.funpay.com/website/pay.htm
 * * Extra Info
 * > {
 * >      "funpay_merchant_code": "## Merchant Code ##",
 * >      "funpay_long_md5key": "## MD5 Key ##",
 * >      "funpay_version" : "1.0",
 * >      "funpay_display_name" : "MGM",
 * >      "funpay_goods_name" : "MGM",
 * >      "funpay_goods_count" : "1",
 * >      "funpay_coupon_flag" : "0",
 * >      "funpay_charset" : "1",
 * >      "funpay_type" : "1000",
 * >      "funpay_sign_type" : "2",
 * >      "bank_list" : ""
 * > }
 *
 * Note: Other than funpay_merchant_code and funpay_long_md5key, other extra info fields are optional.
 *
 * @category Payment
 * @copyright 2013-2022 tot
 *
 */
class Payment_api_funpay_deposit extends Abstract_payment_api {
	const CHARSET_UTF_8 = '1';
	const SIGN_TYPE_MD5 = '2';
	const PAYEE_TYPE_PERSONAL = 1;
	const VERSION='1.0';
	const ORDER_TYPE_INSTANT_PAYMENT='1000';
	const CURRENCY_CODE_CNY = '1';
	const BORROWING_MARKED_NONE = '0';
	const COUPON_FLAG_DISABLED='0';
	const PAYTYPE_CODE_WECHAT = 'WX';
	const STATE_CODE_FAILED = '3';
	const RETURN_SUCCESS_CODE = 'success';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return FUNPAY_DEPOSIT_PAYMENT_API;
	}

	public function getPrefix() {
		return 'funpay_deposit';
	}

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}
		# Setup parameters. Reference: Documentation section 3.1.3
		$params['version'] = $this->getSystemInfo('funpay_version', self::VERSION);

		# order-related params
		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params['serialID'] = $order->secure_id;
		$params['submitTime'] = $this->formatTime($order->created_at);
		$params['failureTime'] = $this->formatTime($order->timeout_at);

		$displayName=$this->getSystemInfo('funpay_display_name', 'FunPay');
		$goodsName=$this->getSystemInfo('funpay_goods_name', 'deposit');
		$goodsCount=$this->getSystemInfo('funpay_goods_count', 1);
		$params['orderDetails'] = $order->secure_id.','.$this->convertAmountToCurrency($amount).','.
			$displayName.','.$goodsName.','.$goodsCount;

		$params['totalAmount'] = $this->convertAmountToCurrency($amount);
		$params['type'] = $this->getSystemInfo('funpay_type', self::ORDER_TYPE_INSTANT_PAYMENT);

		//$payType, orgCode, directFlag from bank
		$direct_pay_extra_info = $order->direct_pay_extra_info;
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['directFlag'] = 1;
				if(self::PAYTYPE_CODE_WECHAT==strtoupper($extraInfo['bank'])) {
					$params['payType'] = 'WX';
					$params['orgCode'] = 'wx';
				} else {
					$params['payType'] = 'BANK_B2C';
					$params['orgCode'] = $extraInfo['bank'];
				}
			}
		}

		$params['currencyCode'] = self::CURRENCY_CODE_CNY;
		$params['couponFlag']=$this->getSystemInfo('funpay_coupon_flag', self::COUPON_FLAG_DISABLED);
		$params['borrowingMarked'] = self::BORROWING_MARKED_NONE;
		$params['partnerID'] = $this->getSystemInfo('funpay_merchant_code');
		$params['noticeUrl'] = $this->getNotifyUrl($orderId);
		$params['returnUrl'] = $this->getReturnUrl($orderId);

		$params['remark']=$order->player_id;
		$params['charset']=$this->getSystemInfo('funpay_charset', self::CHARSET_UTF_8);
		$params['signType']=$this->getSystemInfo('funpay_sign_type', self::SIGN_TYPE_MD5);

		# sign param
		$params['signMsg'] = $this->sign($params);

		#$this->CI->utils->debug_log('params', $params);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true,
		);
	}

	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
		#return array('success'=>true, 'next_url' => $this->getPlayerBackUrl(), 'go_success_page' => true);
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ': [' . $order->id .']', $params);

		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			return $result;
		}

		$success = true; # we have checked that callback is valid

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
				$params['orderNo'], '',
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->CI->sale_order->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : '';
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		# does all required fields exist?
		$requiredFields = array(
			'orderID', 'stateCode', 'orderAmount', 'payAmount', 'partnerID',
			'remark', 'charset', 'signType', 'signMsg',
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->validateSign($fields, $fields['signMsg'])) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		# is payment successful?
		if (strcasecmp($fields['stateCode'], self::STATE_CODE_FAILED) == 0) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		# does amount match?
		if (
			$this->convertAmountToCurrency($order->amount) != $fields['payAmount']
		) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# does merchNo match?
		if ($fields['partnerID'] != $this->getSystemInfo('funpay_merchant_code')) {
			$this->writePaymentErrorLog("Merchant codes do not match, expected [" . $this->getSystemInfo('funpay_merchant_code') . "]", $fields);
			return false;
		}

		# does order_no match?
		if ($fields['orderID'] != $order->secure_id) {
			$this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}


	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- private helper functions --
	private function formatTime($dateTime){
		if(is_string($dateTime)){
			$dateTime=new DateTime($dateTime);
		}

		return $dateTime->format('YmdHis');
	}

	private function getNotifyUrl($transId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $transId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function getBankListInfoFallback() {
		return array(
			array('label' => '微信', 'value' => 'wx'),
			array('label' => '支付宝', 'value' => 'zfb'),
			array('label' => '银联', 'value' => 'unionpay'),
			array('label' => '工商银行', 'value' => 'icbc'),
			array('label' => '农业银行', 'value' => 'abc'),
			array('label' => '建设银行', 'value' => 'ccb'),
			array('label' => '中国银行', 'value' => 'boc'),
			array('label' => '交通银行', 'value' => 'comm'),
			array('label' => '招商银行', 'value' => 'cmb'),
			array('label' => '民生银行', 'value' => 'cmbc'),
			array('label' => '兴业银行', 'value' => 'cib'),
			array('label' => '浦发银行', 'value' => 'spdb'),
			array('label' => '华夏银行', 'value' => 'hxb'),
			array('label' => '中信银行', 'value' => 'ecitic'),
			array('label' => '光大银行', 'value' => 'ceb'),
			array('label' => '广发银行', 'value' => 'gdb'),
			array('label' => '邮政储蓄', 'value' => 'post'),
			array('label' => '深发展银行', 'value' => 'sdb'),
			array('label' => '东亚银行', 'value' => 'bea'),
			array('label' => '宁波银行', 'value' => 'nb'),
			array('label' => '北京银行', 'value' => 'bccb'),
			array('label' => '平安银行', 'value' => 'pingan'),
		);
		/* == extra_info config ==
			"bank_list": {
				"wx" : "_json: { \"1\": \"WeChat\", \"2\": \"微信\" }",
				"unionpay" : "_json: { \"1\": \"UnionPay\", \"2\": \"银联\" }",
				"zfb" : "_json: { \"1\": \"AliPay\", \"2\": \"支付宝\" }",
				"icbc" : "_json: { \"1\": \"ICBC\", \"2\": \"工商银行\" }",
				"abc" : "_json: { \"1\": \"ABC\", \"2\": \"农业银行\" }",
				"ccb" : "_json: { \"1\": \"CCB\", \"2\": \"建设银行\" }",
				"boc" : "_json: { \"1\": \"BOC\", \"2\": \"中国银行\" }",
				"comm" : "_json: { \"1\": \"COMM\", \"2\": \"交通银行\" }",
				"cmb" : "_json: { \"1\": \"CMB\", \"2\": \"招商银行\" }",
				"cmbc" : "_json: { \"1\": \"CMBC\", \"2\": \"民生银行\" }",
				"cib" : "_json: { \"1\": \"CIB\", \"2\": \"兴业银行\" }",
				"spdb" : "_json: { \"1\": \"SPDB\", \"2\": \"浦发银行\" }",
				"hxb" : "_json: { \"1\": \"HXB\", \"2\": \"华夏银行\" }",
				"ecitic" : "_json: { \"1\": \"CNCB\", \"2\": \"中信银行\" }",
				"ceb" : "_json: { \"1\": \"CEB\", \"2\": \"光大银行\" }",
				"gdb" : "_json: { \"1\": \"CGB\", \"2\": \"广发银行\" }",
				"post" : "_json: { \"1\": \"PSBC\", \"2\": \"邮政储蓄\" }",
				"sdb" : "_json: { \"1\": \"SDB\", \"2\": \"深发展银行\" }",
				"bea" : "_json: { \"1\": \"BEA\", \"2\": \"东亚银行\" }",
				"nb" : "_json: { \"1\": \"NBCB\", \"2\": \"宁波银行\" }",
				"bccb" : "_json: { \"1\": \"BOBJ\", \"2\": \"北京银行\" }",
				"pingan" : "_json: { \"1\": \"PAB\", \"2\": \"平安银行\" }"
			}
		*/
	}

	## Reference: Documentation section 3.1.3, 4.1
	public function sign($params) {
		$keys = array(
			"version", "serialID", "submitTime", "failureTime", "customerIP",
			"orderDetails", "totalAmount", "type", "buyerMarked", "payType",
			"orgCode", "currencyCode", "directFlag", "borrowingMarked", "couponFlag",
			"platformID", "returnUrl", "noticeUrl", "partnerID", "remark", "charset",
			"signType"
		);
		$signStr = "";

		foreach($keys as $key){
			if(isset($params[$key])){
				$signStr .= $key.'='.$params[$key].'&';
			} else { # 空值也需参与签名
				$signStr .= $key.'=&';
			}
		}

		$signStr .= 'pkey='.$this->getSystemInfo('funpay_long_md5key');
		$md5=md5($signStr);
		$this->utils->debug_log("Signing: [$signStr], value:".$md5);
		return $md5;
	}

	## Reference: Documentation section 3.2.3, 4.1
	public function validateSign($params) {
		$keys = array(
			"orderID", "resultCode", "stateCode", "orderAmount", "payAmount",
			"acquiringTime", "completeTime", "orderNo", "partnerID",
			"remark", "charset", "signType"
		);
		$signStr = "";

		foreach($keys as $key){
			if(isset($params[$key])){
				$signStr .= $key.'='.$params[$key].'&';
			} else { # 空值也需参与签名
				$signStr .= $key.'=&';
			}
		}

		$signStr .= 'pkey='.$this->getSystemInfo('funpay_long_md5key');
		$md5=md5($signStr);
		$this->utils->debug_log("Signing: [$signStr], value:".$md5);
		return strcasecmp($md5, $params['signMsg']) == 0;
	}

	## Reference: Documentation section 3.1.4, amount in cent
	protected function convertAmountToCurrency($amount) {
		return number_format($amount * 100, 0, '.', '');
	}
}
