<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * PAYK9 快支付
 * http://www.payk9.com
 *
 * * PAYK9_ALIPAY_PAYMENT_API, ID: 201
 * * PAYK9_WEIXIN_PAYMENT_API, ID: 202
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Code
 * * Key - Terminal ID
 * * Secret - MD5 Key
 *
 * Field Values:
 *
 * * URL: http://payk9.com/payindex
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_payk9 extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const KEY_TYPE_MD5 = 1;
	const NOTICE_TYPE_BOTH = 0; # both Server and Browser callback
	const RESULT_SUCCESS = 1;

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these to specify pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params = array();
		$params['MemberID'] = $this->getSystemInfo("account");
		$params['TerminalID'] = $this->getSystemInfo("key");
		$params['InterfaceVersion'] = '4.0';
		$params['KeyType'] = self::KEY_TYPE_MD5;
		$params['TradeDate'] = date('YmdHis'); # 2014年01月01日01点01分钟01秒，格式如下：TradeDate=20140101010101
		$params['TransID'] = $order->secure_id;
		$params['OrderMoney'] = $this->convertAmountToCurrency($amount);
		$params['Amount'] = 1; # Amount of product
		$params['ReturnUrl'] = $this->getNotifyUrl($orderId);
		$params['PageUrl'] = $this->getReturnUrl($orderId);

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['NoticeType'] = self::NOTICE_TYPE_BOTH;
		$params['Signature'] = $this->sign($params);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true,
		);
	}

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
		$this->utils->debug_log('callbackFrom' . ucfirst($source) . ': [' . $orderId .'], params:', $params);

		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		if (!$order) {
			$this->utils->error_log("Order ID [$orderId] not found.");
			return $result;
		}

		$callbackValid = false;
		$paymentSuccessful = $this->checkCallbackOrder($order, $params, $callbackValid); # $callbackValid is also assigned

		# Do not print success msg if callback fails integrity check
		if(!$callbackValid) {
			return $result;
		}

		# Do not proceed to update order status if payment failed, but still print success msg as callback response
		if(!$paymentSuccessful) {
			$result['return_error'] = self::RETURN_SUCCESS_CODE;
			return $result;
		}

		# We can respond with ack to callback now
		$success = true;
		$result['message'] = self::RETURN_SUCCESS_CODE;

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
				$success = $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		# This $success marks whether the order status update is successful
		$result['success'] = $success;

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	# returns true if callback is valid and payment is successful
	# sets the $callbackValid parameter if callback is valid
	private function checkCallbackOrder($order, $fields, &$callbackValid) {
		# does all required fields exist?
		$requiredFields = array(
		 	'MemberID', 'TerminalID', 'TransID', 'Result', 'FactMoney', 'Md5Sign'
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

		$callbackValid = true; # callbackValid is set to true once the signature verification pass

		if ($fields['Result'] != self::RESULT_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['FactMoney']) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['TransID'] != $order->secure_id) {
			$this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	# -- private helper functions --
	private function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	# -- signing --
	private function sign($params) {
		$signStr = '';
		$keys = array('MemberID', 'PayID', 'TradeDate', 'TransID', 'OrderMoney', 'PageUrl', 'ReturnUrl', 'NoticeType');
		foreach($keys as $key) {
			$signStr .= $params[$key]."~|~";
		}
		$signStr .= $this->getSystemInfo('secret');
		$sign = md5($signStr);
		return $sign;
	}

	private function validateSign($params) {
		$signStr = '';
		$keys = array('MemberID', 'TerminalID', 'TransID', 'Result', 'ResultDesc', 'FactMoney', 'AdditionalInfo', 'SuccTime');
		foreach($keys as $key) {
			$signStr .= $key.'='.$params[$key]."~|~";
		}
		$signStr .= $this->getSystemInfo('secret');
		$valid = (strcasecmp($signStr, $params['Md5Sign']) == 0);
		return $valid;
	}
}
