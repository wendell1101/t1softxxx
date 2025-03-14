<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * 收米云 ShoumiPay
 * http://www.shoumipay.com
 *
 * * SHOUMIPAY_PAYMENT_API, ID: 162
 * * SHOUMIPAY_ALIPAY_PAYMENT_API, ID: 163
 * * SHOUMIPAY_WEIXIN_PAYMENT_API, ID: 164
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.shoumipay.com/gatepay.do
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_shoumipay extends Abstract_payment_api {
	const CHANNEL_BANK = 1;
	const CHANNEL_ALIPAY = 4;
	const CHANNEL_WEIXIN = 3;
	const SDK_VERSION = '3.1.3';
	const REQUEST_TYPE_WEB = 0;
	const REQUEST_TYPE_WAP = 1;
	const RETURN_SUCCESS_CODE = 'success';
	const RETURN_FAIL_CODE = 'fail';
	const P_ERRORCODE_PAYMENT_SUCCESS = 0;

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Returns one of the constants defined above: CHANNEL_XXX
	public abstract function getChannelId();

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

		$params = array();
		$params['P_UserId'] = $this->getSystemInfo('account');
		$params['P_OrderId'] = $order->secure_id;
		$params['P_FaceValue'] = $this->convertAmountToCurrency($amount);
		$params['P_CustormId'] = $this->getCustormId($playerId, $params['P_UserId']);
		$params['P_ChannelId'] = $this->getChannelId();
		$params['P_SDKVersion'] = self::SDK_VERSION;
		$params['P_RequestType'] = ($this->utils->is_mobile() ? self::REQUEST_TYPE_WAP : self::REQUEST_TYPE_WEB);
		$params['P_Subject'] = 'Topup';
		$params['P_Price'] = $this->convertAmountToCurrency($amount);
		$params['P_Quantity'] = 1;
		#$params['P_Description'] = 'Topup';
		#$params['P_Notic'] = 'Topup';
		$params['P_Result_URL'] = $this->getNotifyUrl($orderId);
		$params['P_Notify_URL'] = $this->getReturnUrl($orderId);
		#$params['attach'] = '';
		#$params['P_Phone'] = '';
		#$params['P_CertNO'] = '';
		#$params['P_RealName'] = '';

		$params['P_PostKey'] = $this->sign($params);

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
			$this->CI->sale_order->updateExternalInfo($order->id,
				$params['P_SMPayId'], '',
				null, null, $response_result_id);
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
			'P_UserId', 'P_OrderId', 'P_SMPayId', 'P_FaceValue', 'P_ChannelId', 'P_PostKey'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['P_ErrCode'] != self::P_ERRORCODE_PAYMENT_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['P_PayMoney']))
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

	# Hide banklist by default, as this API does not support bank selection during form submit
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

	## Format the amount value for the API
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- signatures --
	private function getCustormId($playerId, $P_UserId) {
		return $playerId.'_'.md5($P_UserId.'|'.$this->getSystemInfo('key').'|'.$playerId);
	}

	private function sign($params) {
		$keys = array('P_UserId', 'P_OrderId', 'P_FaceValue', 'P_ChannelId', 'P_SDKVersion', 'P_RequestType');
		$signStr = "";
		foreach($keys as $key) {
			$signStr .= $params[$key] . '|';
		}
		$signStr .= $this->getSystemInfo('key');
		$sign = md5($signStr);
		return $sign;
	}

	private function validateSign($params) {
		$keys = array('P_UserId', 'P_OrderId', 'P_SMPayId', 'P_FaceValue', 'P_ChannelId');
		$signStr = "";
		foreach($keys as $key) {
			$signStr .= $params[$key] . '|';
		}
		$signStr .= $this->getSystemInfo('key');
		$sign = md5($signStr);
	
		return strcasecmp($sign, $params['P_PostKey']) === 0;
	}
}
