<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * H8PAY (迅付通)
 * http://www.h8pay.com/
 *
 * H8PAY_ALIPAY_PAYMENT_API, ID: 141
 * H8PAY_WECHAT_PAYMENT_API, ID: 142
 *
 * Required Fields:
 *
 * * URL
 * * Account (Merchant ID)
 * * Key (MD5 signing key)
 *
 * 代付地址：http://remit.h8pay.com/api/remit.action
 * 微信地址：http://wx.h8pay.com/api/pay.action
 * 支付宝地址：http://zfb.h8pay.com/api/pay.action
 * 支付宝WAP地址http://zfbwap.h8pay.com/api/pay.action
 *
 * @category Payment
 * @copyright 2013-2022 tot
 *
 */
abstract class Abstract_payment_api_h8pay extends Abstract_payment_api {

	const RETURN_SUCCESS_CODE = '0';
	const STATE_CODE_SUCCESS = '00';
	const PAY_RESULT_SUCCESS = '00';
	const NETWAY_ALIPAY = 'ZFB';
	const NETWAY_WECHAT = 'WX';
	const NETWAY_ALIPAY_WAP = 'ZFB_WAP';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# 支付宝填写ZFB,微信填写WX,支付宝WAP填写：ZFB_WAP
	public abstract function getNetWay();

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

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params['merNo'] = $this->getSystemInfo('account');
		$params['netway'] = $this->getNetWay();
		$params['random'] = (string) rand(1000,9999); # 4位随机数    必须是文本型
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['orderNum'] = $order->secure_id;
		$params['goodsName'] = 'Topup';
		$params['callBackUrl'] = $this->getNotifyUrl($orderId);
		$params['callBackViewUrl'] = $this->getReturnUrl($orderId);
		$params['sign'] = $this->sign($params);

		$respJson = $this->submitPostForm($this->getSystemInfo('url'), array("data" => json_encode($params)), false, $params['orderNum']);
		$this->CI->utils->debug_log("Post form return: ", $respJson);
		$resp = json_decode($respJson, true);

		if ($resp['stateCode'] != self::STATE_CODE_SUCCESS){
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR,
				'message' => $resp['stateCode'].': '.$resp['msg'],
			);
		}

		if ($this->validateResp($resp)){ #验证返回签名数据
			$qrcodeUrl = $resp['qrcodeUrl'];
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $resp['qrcodeUrl'],
			);
		}

		# Validation fail
		return array(
			'success' => false,
			'type' => self::REDIRECT_TYPE_ERROR,
			'message' => lang('Invalid response'),
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
		$params = json_decode($params['data'], true);

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
			$this->CI->sale_order->updateExternalInfo($order->id, null, null, null, null, $response_result_id);
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
	## Reference: code sample, callback.php
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		if(empty($fields)){
			$this->writePaymentErrorLog("Missing fields, it's empty", $fields);
			return false;
		}

		$requiredFields = array(
			'merNo', 'netway', 'amount', 'sign'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		if (!$this->verifySignature($fields)) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['payResult'] != self::PAY_RESULT_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		$convertedAmount = $this->convertAmountToCurrency($order->amount);
		if ($convertedAmount != $fields['amount']) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$convertedAmount]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# Hide bank list dropdown as its not needed
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
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

	# 分为单位
	protected function convertAmountToCurrency($amount) {
		return number_format($amount * 100, 0, '.', '');
	}

	# -- private helper functions --
	private function validateResp($resp) {
		$r_sign = $resp['sign'];
		$sign = $this->sign($resp);
		if ($sign == $r_sign){
			return true;
		} else {
			return false;
		}
	}

	private function sign($data) {
		$key = $this->getSystemInfo('key');

		# Note: signing will omit the 'sign' value
		if (array_key_exists('sign', $data)) {
			unset($data['sign']);
		}

		ksort($data);
		$signSource = $this->json_encode_custom($data).$key;
		$sign = strtoupper(md5($signSource));
		return $sign;
	}

	private function verifySignature($data) {
		$mySign = $this->sign($data);
		if (strcasecmp($mySign, $data['sign']) === 0) {
			return true;
		} else {
			return false;
		}
	}

	# Reference: sample code, so that our encode result matches API's
	private function json_encode_custom ($input){
		if(is_string($input)){
			$text = $input;
			$text = str_replace('\\', '\\\\', $text);
			$text = str_replace(
				array("\r", "\n", "\t", "\""),
				array('\r', '\n', '\t', '\\"'),
				$text);
			return '"' . $text . '"';
		}else if(is_array($input) || is_object($input)){
			$arr = array();
			$is_obj = is_object($input) || (array_keys($input) !== range(0, count($input) - 1));
			foreach($input as $k=>$v){
				if($is_obj){
					$arr[] = $this->json_encode_custom($k) . ':' . $this->json_encode_custom($v);
				}else{
					$arr[] = $this->json_encode_custom($v);
				}
			}
			if($is_obj){
				return '{' . join(',', $arr) . '}';
			}else{
				return '[' . join(',', $arr) . ']';
			}
		}else{
			return $input . '';
		}
	}
}
