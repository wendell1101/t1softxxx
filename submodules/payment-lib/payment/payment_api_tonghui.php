<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * TONGHUI 通汇卡
 * http://www.41.cn
 *
 * TONGHUI_PAYMENT_API, ID: 45
 *
 * Required Fields:
 *
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: https://pay.41.cn/gateway
 * * Extra Info:
 * > {
 * >  	"tonghui_merchant_code" : "##merchant code##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tonghui extends Abstract_payment_api {
	const TONGHUI_CHARSET = "UTF-8";
	const TONGHUI_PAYTYPE_IBANKING = 1;

	const RETURN_SUCCESS_CODE = 'success';

	private $info;

	public function __construct($params = null) {
		parent::__construct($params);

		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->info = $this->getInfoByEnv();
	}

	public function getBankCode($order) {
		# bank code from user selection
		$direct_pay_extra_info = $order->direct_pay_extra_info;
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				return $extraInfo['banktype'];
			}
		}
	}

	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return TONGHUI_PAYMENT_API;
	}

	public function getPrefix() {
		return 'tonghui';
	}

	# -- override common API functions --
	## Constructs an URL so that the caller can redirect / invoke it to make payment through this API
	## See controllers/redirect.php for detail.
	##
	## Retuns a hash containing these fields:
	## array(
	##	'success' => true,
	##	'type' => self::REDIRECT_TYPE_FORM,
	##	'url' => $info['url'],
	##	'params' => $params,
	##	'post' => true
	## );
	##
	## Reference: documentation section 3.2.2
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$params['merchant_code'] = $this->getSystemInfo("tonghui_merchant_code");
		$params['req_referer'] = $_SERVER['HTTP_HOST'];

		# constant config
		$params['input_charset'] = self::TONGHUI_CHARSET;
		$params['pay_type'] = self::TONGHUI_PAYTYPE_IBANKING;

		# anti-phishing
		$params['customer_ip'] = $this->getClientIp();

		# other parameters
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['return_url'] = $this->getReturnUrl($orderId);

		# order-related params
		# data format reference the code sample, normalPay.php
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$params['order_no'] = $order->secure_id;
		$params['order_amount'] = $this->convertAmountToCurrency($amount);
		$params['order_time'] = $orderDateTime->format('Y-m-d H:i:s'); # test shows API only allows this format, no time info
		$params['product_name'] = lang('pay.deposit'); # this will be displayed on the payment page

		$params['bank_code'] = $this->getBankCode($order);

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
			$this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
			if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
				$this->CI->sale_order->setStatusToSettled($orderId);
			}
		} else {
			# update player balance
			$this->CI->sale_order->updateExternalInfo($order->id,
				$params['trade_no'], $params['trade_time'], # bankOrderId does not exist. Reference: documentation section 2.4.2
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
	## Reference: documentation, section 3.3; sample code, payReturn.php
	public function checkCallbackOrder($order, $fields, &$processed = false) {
		# does all required fields exist?
		$requiredFields = array(
			'merchant_code', 'order_no', 'order_amount', 'order_time',
			'notify_type', 'trade_no', 'trade_time', 'trade_status', 'sign',
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

		# check parameter values: orderStatus, tradeAmt, orderNo, merchNo
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

		# does merchant code match?
		if ($fields['merchant_code'] != $this->getSystemInfo('tonghui_merchant_code')) {
			$this->writePaymentErrorLog("Merchant codes do not match, expected [" . $this->getSystemInfo('tonghui_merchant_code') .
				"], actual [" . $fields['merchant_code'] . "]", $fields);
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

	public function getBankListTree() {
		$tree = array();
		$bankListInfo = $this->getBankListInfo();
		foreach ($bankListInfo as $bankInfo) {
			$subList = array();
			if (!empty($bankInfo['sub_list'])) {
				foreach ($bankInfo['sub_list'] as $val => $label) {
					$subList[] = array('value' => $val, 'label' => $label);
				}
			}
			$tree[$bankInfo['value']] = $subList;
		}
		return $tree;
	}

	# Reference: documentation section 4.2
	public function getBankListInfo() {
		if ($this->getSystemInfo('only_WEIXIN')) {
			return array(
				array('label' => '微信', 'value' => 'WEIXIN'),
			);
		}

		$bank_list_json=$this->getSystemInfo('bank_list');
		if(!empty($bank_list_json)){
			$bank_list= json_decode($bank_list_json,true);
			if(!empty($bank_list)){
				return $bank_list;
			}
		}

		return array(
            #array('label' => '微信', 'value' => 'WEIXIN'),
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
	# Reference: code sample helper.php
	function getClientIp() {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif (!empty($_SERVER['REMOTE_ADDR'])) {
			$ip = $_SERVER['REMOTE_ADDR'];
		} else {
			$ip = '127.0.0.1';
		}
		return $ip;
	}

	## Reference: sample code helper.php, class KeyValues
	public function sign($data) {
		$strb = "";
		ksort($data);
		$this->utils->debug_log("Signing data", $data);
		foreach ($data as $key => $val) {
			if ($key == 'key' || $key == 'sign' || is_null($val) || $val == '') {
				continue;
			}
			$this->appendParam($strb, $key, $val);
		}

		// $this->appendParam($strb, 'merchant_code', $data['merchant_code']);
		// $this->appendParam($strb, 'notify_type', $data['notify_type']);
		// $this->appendParam($strb, 'order_no', $data['order_no']);
		// $this->appendParam($strb, 'order_amount', $data['order_amount']);
		// $this->appendParam($strb, 'order_time', $data['order_time']);
		// $this->appendParam($strb, 'return_params', $data['return_params']);
		// $this->appendParam($strb, 'trade_no', $data['trade_no']);
		// $this->appendParam($strb, 'trade_time', $data['trade_time']);
		// $this->appendParam($strb, 'trade_status', $data['trade_status']);

		$this->appendParam($strb, 'key', $this->info['key']);

		$strb = substr($strb, 1, strlen($strb) - 1);

		$this->CI->utils->debug_log('sign original', $strb);

		$md5str = md5($strb);
		$this->CI->utils->debug_log('md5', $md5str, 'sign', (isset($data['sign']) ? $data['sign'] : '') );
		return $md5str;

		// $merchantCode = $data[AppConstants::$MERCHANT_CODE];
		// $notifyType = $data[AppConstants::$NOTIFY_TYPE];
		// $orderNo = $data[AppConstants::$ORDER_NO];
		// $orderAmount = $data[AppConstants::$ORDER_AMOUNT];
		// $orderTime = $data[AppConstants::$ORDER_TIME];
		// $returnParams = $data[AppConstants::$RETURN_PARAMS];
		// $tradeNo = $data[AppConstants::$TRADE_NO];
		// $tradeTime = $data[AppConstants::$TRADE_TIME];
		// $tradeStatus = $data[AppConstants::$TRADE_STATUS];
		// $sign = $data[AppConstants::$SIGN];

		// $kvs = new KeyValues();
		// $kvs->add(AppConstants::$MERCHANT_CODE, $merchantCode);
		// $kvs->add(AppConstants::$NOTIFY_TYPE, $notifyType);
		// $kvs->add(AppConstants::$ORDER_NO, $orderNo);
		// $kvs->add(AppConstants::$ORDER_AMOUNT, $orderAmount);
		// $kvs->add(AppConstants::$ORDER_TIME, $orderTime);
		// $kvs->add(AppConstants::$RETURN_PARAMS, $returnParams);
		// $kvs->add(AppConstants::$TRADE_NO, $tradeNo);
		// $kvs->add(AppConstants::$TRADE_TIME, $tradeTime);
		// $kvs->add(AppConstants::$TRADE_STATUS, $tradeStatus);
		// $_sign = $kvs->sign($this->info['key']);

		// $this->CI->utils->debug_log('sign', $sign, '_sign', $_sign);

		// return $_sign;
	}

	## Reference: sample code helper.php, class URLUtils
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

	public function verify($data, $signature) {
		$mySign = $this->sign($data);
		if (strcasecmp($mySign, $signature) === 0) {
			return true;
		} else {
			return false;
		}
	}

}

// class AppConstants {
// 	public static $INPUT_CHARSET = "input_charset";
// 	public static $NOTIFY_URL = "notify_url";
// 	public static $RETURN_URL = "return_url";
// 	public static $PAY_TYPE = "pay_type";
// 	public static $BANK_CODE = "bank_code";
// 	public static $MERCHANT_CODE = "merchant_code";
// 	public static $ORDER_NO = "order_no";
// 	public static $ORDER_AMOUNT = "order_amount";
// 	public static $ORDER_TIME = "order_time";
// 	public static $PRODUCT_NAME = "product_name";
// 	public static $PRODUCT_NUM = "product_num";
// 	public static $REQ_REFERER = "req_referer";
// 	public static $CUSTOMER_IP = "customer_ip";
// 	public static $CUSTOMER_PHONE = "customer_phone";
// 	public static $RECEIVE_ADDRESS = "receive_address";
// 	public static $RETURN_PARAMS = "return_params";

// 	public static $NOTIFY_TYPE = "notify_type";
// 	public static $TRADE_NO = "trade_no";
// 	public static $TRADE_TIME = "trade_time";
// 	public static $TRADE_STATUS = "trade_status";

// 	public static $KEY = "key";
// 	public static $SIGN = "sign";

// }

// class URLUtils {
// 	static function appendParam(&$sb, $name, $val, $and = true, $charset = null) {
// 		if ($and) {
// 			$sb .= "&";
// 		} else {
// 			$sb .= "?";
// 		}
// 		$sb .= $name;
// 		$sb .= "=";
// 		if (is_null($val)) {
// 			$val = "";
// 		}
// 		if (is_null($charset)) {
// 			$sb .= $val;
// 		} else {
// 			$sb .= urlencode($val);
// 		}
// 	}
// }

// class KeyValues {
// 	private $kvs = array();

// 	function items() {
// 		return $this->kvs;
// 	}
// 	function add($k, $v) {
// 		if (!is_null($v)) {
// 			$this->kvs[$k] = $v;
// 		}

// 	}
// 	function sign($merchant_key) {
// 		return md5($this->link($merchant_key));
// 	}
// 	function link($merchant_key) {
// 		$strb = "";
// 		ksort($this->kvs);
// 		foreach ($this->kvs as $key => $val) {
// 			URLUtils::appendParam($strb, $key, $val);
// 		}
// 		URLUtils::appendParam($strb, AppConstants::$KEY, $merchant_key);
// 		$strb = substr($strb, 1, strlen($strb) - 1);
// 		return $strb;
// 	}
// }
