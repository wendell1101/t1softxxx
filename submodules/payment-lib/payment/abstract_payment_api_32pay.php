<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * 32PAY 32支付
 *
 * * _32PAY_PAYMENT_API, ID: 509
 * * _32PAY_ALIPAY_PAYMENT_API, ID: 510
 * * _32PAY_TENPAY_PAYMENT_API, ID: 511
 * * _32PAY_WEIXIN_PAYMENT_API, ID: 512
 * * _32PAY_QQPAY_PAYMENT_API, ID: 513
 * * _32PAY_JDPAY_PAYMENT_API, ID: 514
 * * _32PAY_BDPAY_PAYMENT_API, ID: 515
 * * _32PAY_UNIONPAY_PAYMENT_API, ID: 516
 * * _32PAY_QUICKPAY_PAYMENT_API, ID: 517
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.32pay.com/Pay/KDBank.aspx
 * * Account: ## merchant ID ##
 * * Key: ## secret key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_32pay extends Abstract_payment_api {
	#2018-06-22
	const P_CHANNEL_BANK = '1'; #OK
	const P_CHANNEL_BANK_WAP = '31';
	const P_CHANNEL_ALIPAY = '2';
	const P_CHANNEL_ALIPAY_WAP = '36';
	const P_CHANNEL_TENPAY = '3';
	const P_CHANNEL_WEIXIN = '21';
	const P_CHANNEL_WEIXIN_WAP = '33';
	const P_CHANNEL_QQPAY = '89'; #OK
	const P_CHANNEL_QQPAY_WAP = '92'; #OK
	const P_CHANNEL_JDPAY = '91';
	const P_CHANNEL_JDPAY_WAP = '98';
	const P_CHANNEL_BDPAY = '90';
	const P_CHANNEL_UNIONPAY = '95'; #OK
	const P_CHANNEL_QUICKPAY = '32';
	const P_CHANNEL_QUICKPAY_WAP = '101';

	const RETURN_SUCCESS_CODE = 'ErrCode=0';
	const RETURN_FAIL_CODE = 'ProcessError';
	const P_ERRORCODE_SUCCESS = '0';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params = array();
		$params['P_UserId'] = $this->getSystemInfo('account');
		$params['P_OrderId'] = $order->secure_id;
		$params['P_FaceValue'] = $this->convertAmountToCurrency($amount);
		$params['P_Price'] = '1';
		$params['P_Result_URL'] = $this->getReturnUrl($orderId);
		$params['P_Notify_URL'] = $this->getNotifyUrl($orderId);

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['P_PostKey'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================32pay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	protected function processPaymentUrlFormPost($params) {
		$queryString = http_build_query($params);
		$postUrl = $this->getSystemInfo('url').'?'.$queryString;

		$this->CI->utils->debug_log("=====================32pay postUrl", $postUrl);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $postUrl,
			'params' => $params,
			'post' => false
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

		$raw_post_data = file_get_contents('php://input', 'r');
		$this->CI->utils->debug_log("=====================32pay raw_post_data", $raw_post_data);
		$this->CI->utils->debug_log("=====================32pay params", $params);

		if (!$order) {
			$this->utils->error_log("=====================32pay order ID [$orderId] not found.");
			return $result;
		}
		if($source == 'server' ){
			$callbackValid = false;
			$paymentSuccessful = $this->checkCallbackOrder($order, $params, $processed);
			# Do not proceed to update order status if payment failed, but still print success msg as callback response
			if(!$paymentSuccessful) {
				return $result;
			}
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
		if ($processed) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = self::RETURN_FAIL_CODE;
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
		$requiredFields = array(
			'P_UserId', 'P_OrderId','P_CardId','P_CardPass' ,'P_FaceValue', 'P_ChannelId', 'P_PayMoney', 'P_ErrCode'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================32pay checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================32pay checkCallbackOrder signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['P_ErrCode'] != self::P_ERRORCODE_SUCCESS) {
			$this->writePaymentErrorLog('=====================32pay checkCallbackOrder payment was not successful', $fields);
			return false;
		}

		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['P_FaceValue']))
		) {
			$this->writePaymentErrorLog("=====================32pay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
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

	# -- signatures --
	private function getCustormId($playerId, $P_UserId) {
		return $playerId.'_'.md5($P_UserId.'|'.$this->getSystemInfo('key').'|'.$playerId);
	}

	private function sign($params) {
		$keys = array('P_UserId', 'P_OrderId', 'P_CardId', 'P_CardPass', 'P_FaceValue', 'P_ChannelId');
		$signStr = "";
		foreach($keys as $key) {
			if (array_key_exists($key, $params)) {
				$signStr .= $params[$key] . '|';
			}
			else if($key == 'P_CardId' || $key == 'P_CardPass') {
				$signStr .=  '|';
			}
		}
		$signStr .= $this->getSystemInfo('key');
		$sign = md5($signStr);
		return $sign;
	}

	private function validateSign($params) {
		$keys = array('P_UserId', 'P_OrderId', 'P_CardId', 'P_CardPass', 'P_FaceValue', 'P_ChannelId', 'P_PayMoney' , 'P_ErrCode');

		$signStr = "";
		foreach($keys as $key) {
			if (array_key_exists($key, $params)) {
				$signStr .= $params[$key] . '|';
			}
			else if($key == 'P_CardId' || $key == 'P_CardPass') {
				$signStr .=  '|';
			}
		}
		$signStr .= $this->getSystemInfo('key');
		$sign = md5($signStr);
		return strcasecmp($sign, $params['P_PostKey']) === 0;
	}

	protected function getBankListInfoFallback() {
		return array(
			array('label' => '工商银行', 'value' => '10001'),
			array('label' => '农业银行', 'value' => '10002'),
			array('label' => '招商银行', 'value' => '10003'),
			array('label' => '中国银行', 'value' => '10004'),
			array('label' => '建设银行', 'value' => '10005'),
			array('label' => '民生银行', 'value' => '10006'),
			array('label' => '中信银行', 'value' => '10007'),
			array('label' => '交通银行', 'value' => '10008'),
			array('label' => '兴业银行', 'value' => '10009'),
			array('label' => '光大银行', 'value' => '10010'),
			array('label' => '深圳发展银行', 'value' => '10011'),
			array('label' => '邮政储蓄', 'value' => '10012'),
			array('label' => '北京银行', 'value' => '10013'),
			array('label' => '平安银行', 'value' => '10014'),
			array('label' => '上海浦发银行', 'value' => '10015'),
			array('label' => '广发银行', 'value' => '10016'),
			array('label' => '渤海银行', 'value' => '10017'),
			array('label' => '东亚银行', 'value' => '10018'),
			array('label' => '宁波银行', 'value' => '10019'),
			array('label' => '北京农村商业银行', 'value' => '10020'),
			array('label' => '南京银行', 'value' => '10021'),
			array('label' => '浙商银行', 'value' => '10022'),
			array('label' => '上海银行', 'value' => '10023'),
			array('label' => '上海农村商业银行', 'value' => '10024'),
			array('label' => '华夏银行', 'value' => '10025'),
			array('label' => '杭州银行', 'value' => '10027'),
			array('label' => '浙江江稠州商业银行', 'value' => '10028')
		);
	}
}
