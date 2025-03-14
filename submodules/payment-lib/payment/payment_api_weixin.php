<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
require_once dirname(__FILE__) . '/weixin/WxPay.NativePay.php';
require_once dirname(__FILE__) . '/weixin/PayNotifyCallback.php';
/**
 * WEIXIN 微信支付
 * http://paysdk.weixin.qq.com/
 *
 * WEIXIN_PAYMENT_API, ID: 46
 *
 * Required Fields:
 *
 * * Key - app key
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * Extra Info
 * > {
 * >     "weixin_app_id": "##app id##",
 * >     "weixin_mch_id": "##merchant id##",
 * >     "weixin_order_expire": "600"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_weixin extends Abstract_payment_api {

	private $info;

	public function __construct($params = null) {
		parent::__construct($params);

		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->info = $this->getInfoByEnv();

		# Set configurations defined in weixin/lib/WxPay.Config.php
		WxPayConfig::$APPID = $this->info['system_info']['weixin_app_id'];
		WxPayConfig::$MCHID = $this->info['system_info']['weixin_mch_id'];
		WxPayConfig::$KEY = $this->info['key'];
	}

	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'weixin';
	}

	# -- override common API functions --
	## Constructs an URL so that the caller can redirect / invoke it to make payment through this API
	## See controllers/redirect.php for detail.
	##
	## Retuns a hash containing these fields:
	## array(
	##	'success' => true,
	##	'type' => self::REDIRECT_TYPE_QRCODE,  ## constants defined in abstract_payment_api.php
	##	'url' => $info['url'],
	## );
	## Reference: documentation - https://pay.weixin.qq.com/wiki/doc/api/native.php?chapter=6_5
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		# 模式二, 调用统一下单，取得code_url
		# Reference: sample code native.php
		# Field reference: Documentation - https://pay.weixin.qq.com/wiki/doc/api/native.php?chapter=9_1
		$notify = new NativePay();
		$input = new WxPayUnifiedOrder();
		$input->SetTrade_type("NATIVE");
		$input->SetBody(lang('pay.deposit')); # 商品或支付单简要描述
		$input->SetAttach(''); # 附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据的值
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$input->SetOut_trade_no($order->secure_id); # 商户系统内部的订单号,32个字符内、可包含字母
		$input->SetTotal_fee($this->convertAmountToCurrencyCent($amount)); # 订单总金额. 支付金额单位为【分】，参数值不能带小数
		$input->SetTime_start(date("YmdHis")); # 交易起始时间, 格式为yyyyMMddHHmmss
		$orderExpireTime = intval($this->getSystemInfo("weixin_order_expire"));
		$input->SetTime_expire(date("YmdHis", time() + ($orderExpireTime < 300 ? 300 : $orderExpireTime))); # 订单失效时间，格式为yyyyMMddHHmmss, 最短失效时间间隔必须大于5分钟
		$input->SetGoods_tag(''); # 商品标记，代金券或立减优惠功能的参数
		$input->SetNotify_url($this->getNotifyUrl($orderId)); # 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数
		$input->SetProduct_id($playerId); # use playerId for now; trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义
		$result = $notify->GetPayUrl($input);

		# Debug output
		$this->utils->debug_log("WxPayUnifiedOrder: ", $input->GetValues());
		$this->utils->debug_log("WxPayUnifiedOrder Result: ", $result);

		# 通信失败
		if($result["return_code"] == "FAIL") {
			return array(
				'success' => false,
				'message' => $result["return_msg"],
			);
		}

		# 交易失败
		if($result["result_code"] == "FAIL") {
			return array(
				'success' => false,
				'err_code' => $result["err_code"], # Error code reference: https://pay.weixin.qq.com/wiki/doc/api/native.php?chapter=9_1
				'message' => $result["err_code_des"],
			);
		}

		# 成功
		$url = $result["code_url"];
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_QRCODE,
			'url' => $url,
		);
	}

	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	## Reference: sample code, callback.php
	## Field Reference: documentation - https://pay.weixin.qq.com/wiki/doc/api/native.php?chapter=9_7
	# Note: WeChat server will callback this url with xml data in $GLOBALS['HTTP_RAW_POST_DATA'], so the $params will
	# come from the PayNotifyCallBack class
	public function callbackFromServer($orderId, $params) {
		# PayNotifyCallBack class will handle signature validation and replying back to server
		$notify = new PayNotifyCallBack();
		$notify->Handle(false);
		$params = $notify->getData();

		$this->utils->debug_log("Weixin callback: ", $orderId, $params);
		$response_result_id = parent::callbackFromServer($orderId, $params);

		$result = array('success' => false);
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		if ($notify->GetReturn_code() != "SUCCESS") {
			$this->utils->debug_log("Weixin callback: Return Code is not SUCCESS", $notify->GetReturn_msg());
			$result['message'] = $notify->GetReturn_msg();
			return $result;
		}
		# callback signature verified by PayNotifyCallBack
		$processed = true;

		# validate business logic (result_code etc)
		if (!$order || !$this->checkCallbackOrder($order, $params)) {
			$result['message'] = lang('error.payment.failed'); # Generic error message
			return $result;
		}

		# Update order payment status and balance
		$success=true;
		// $this->CI->sale_order->startTrans();

		# Update player balance based on order status
		# if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
		$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
		if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
			$this->CI->utils->debug_log('callbackFromServer, already get callback for order:' . $order->id, $params);
			if ($order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
				$this->CI->sale_order->setStatusToSettled($orderId);
			}
		} else {
			# update player balance
			$this->CI->sale_order->updateExternalInfo($order->id,
				$params['transaction_id'], $params['time_end'],
				null, null, $response_result_id);

			$this->CI->sale_order->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
		}
		// $success = $this->CI->sale_order->endTransWithSucc();

		$result['success'] = $success;
		# note: if this step fails, there is no error message

		# PayNotifyCallBack class already handled the reply to server
		if($success) {
			unset($result['message']);	# avoid corrupting the reply
		}

		return $result;
	}

	## This will be called when user redirects back to our page from payment API
	## Which does not happen with QRCode payment
	public function callbackFromBrowser($orderId, $params) {
		$this->utils->debug_log("Unexpected: QRCode payment does not callback from browser.");
	}

	## Validates whether the callback from API contains valid info and matches with the order
	## Reference: code sample, callback.php
	private function checkCallbackOrder($order, $fields) {
		# does all required fields exist?
		$requiredFields = array(
			'mch_id', 'result_code', 'total_fee', 'out_trade_no'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# check parameter values: mch_id, result_code, total_fee, out_trade_no
		# is payment successful?
		if ($fields['result_code'] != 'SUCCESS') {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		# does amount match?
		if (
			$this->convertAmountToCurrencyCent($order->amount) != $fields['total_fee']
		) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected ["
				.$this->convertAmountToCurrencyCent($order->amount)."]", $fields);
			return false;
		}

		# does merchNo match?
		if ($fields['mch_id'] != $this->getSystemInfo('weixin_mch_id')) {
			$this->writePaymentErrorLog("Merchant codes do not match, expected [" . $this->getSystemInfo('weixin_mch_id') . "]", $fields);
			return false;
		}

		# does order_no match?
		if ($fields['out_trade_no'] != $order->secure_id) {
			$this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
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
	protected function convertAmountToCurrencyCent($amount) {
		return number_format($amount * 100, 0, '.', '');
	}

	# -- View control --
	public function getPlayerInputInfo() {
		# Only deposit amount, no bank selection
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
