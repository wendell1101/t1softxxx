<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * TrueGateway
 * http://www.truegateway.com/
 *
 * TRUEGATEWAY_PAYMENT_API, ID: 78
 *
 *
 * Required Fields:
 *
 * * URL
 * * Key - Merchant Passcode
 * * Extra Info
 *
 * Field Values:
 *
 * * URL: https://secure.truegateway.com/transaction/directPayment
 * * Extra Info
 * > {
 * >     "truegateway_merchantID": "##Merchant ID##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_truegateway extends Abstract_payment_api {
	const RESPONSE_CODE_APPROVED = 1;

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return TRUEGATEWAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'truegateway';
	}

	# -- override common API functions --
	## Constructs an URL so that the caller can redirect / invoke it to make payment through this API
	## See controllers/redirect.php for detail.
	## Ref: "Web Redirect - Simple Payment" (page 15)
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
		$params['merchantID'] = $this->getSystemInfo("truegateway_merchantID");
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['currency'] = $order->currency;
		$params['orderID'] = $order->secure_id;

		$params['returnURL'] = $this->getReturnUrl($orderId);
		$params['notifyURL'] = $this->getNotifyUrl($orderId);

		# sign param
		$params['pSign'] = $this->signSHA($params);

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
	## Reference: "Merchant Asynchronous Notifications" (page 17)
	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	## This will be called when user redirects back to our page from payment API
	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$this->utils->debug_log("callback received from [$source] for orderId [$orderId]", $params);

		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			return $result;
		}

		# Update order payment status and balance
		$success=true;

		# Update player balance based on order status
		# if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
		$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
		if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
			$this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
			if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
				$this->CI->sale_order->setStatusToSettled($orderId);
			}
		} else {
			# Fill in optional fields in case they are not present
			if(!array_key_exists('bankAuthCode', $params)) {
				$params['bankAuthCode'] = '';
			}
			if(!array_key_exists('bankResultCode', $params)) {
				$params['bankResultCode'] = '';
			}

			# update player balance
			$this->CI->sale_order->updateExternalInfo($order->id,
				$params['transactionID'], $params['bankAuthCode'],
				$params['responseCode'], $params['bankResultCode'], $response_result_id);
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

	## Validates whether the callback from API contains valid info and matches with the order
	## Reference: Documentation page 17
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		# does all required fields exist?
		$requiredFields = array(
			'responseCode', 'reasonCode', 'transactionID', 'orderID', 'executed', 'pSign'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->verifySHA($fields, $fields['pSign'])) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true as the decryption already done

		# is payment successful?
		if ($fields['responseCode'] != self::RESPONSE_CODE_APPROVED) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
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

	public function getPlayerInputInfo() {
		return array(
			array('type' => ''),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
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

	## Signing and verifying. Reference: documentation page 3
	public function signSHA($params) {
		$fieldNames = array('merchantID', 'amount', 'currency', 'orderID', 'returnURL', 'notifyURL', 'description');
		$signStr = $this->getSystemInfo('key');

		foreach($fieldNames as $index) {
			if(array_key_exists($index, $params)){
				$signStr .= $params[$index];
			}
		}

		$this->utils->debug_log("signSHA", $signStr);
		return sha1($signStr);
	}

	private function verifySHA($params, $sha) {
		$fieldNames = array('responseCode', 'reasonCode', 'transactionID', 'orderID', 'executed', 'bankResultCode', 'bankAuthCode');
		$signStr = $this->getSystemInfo('key');

		foreach($fieldNames as $index) {
			if(array_key_exists($index, $params)){
				$signStr .= $params[$index];
			}
		}

		$sign = sha1($signStr);

		$this->utils->debug_log("verifySHA", $signStr);
		$this->utils->debug_log("signed value", $sign, "expected", $sha);
		return strcasecmp($sign, $sha) == 0;
	}
}
