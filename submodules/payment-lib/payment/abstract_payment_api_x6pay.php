<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * X6PAY (迅汇宝)
 * http://www.x6pay.com/
 *
 * X6PAY_ALIPAY_PAYMENT_API, ID: 139
 * X6PAY_WECHAT_PAYMENT_API, ID: 140
 *
 * Required Fields:
 *
 * * URL
 * * Account (Merchant ID)
 * * Key (MD5 signing key)
 *
 * Field Values:
 *
 * * URL: http://pay.x6pay.com:8082/posp-api/passivePay
 *
 * @category Payment
 * @copyright 2013-2022 tot
 *
 */
abstract class Abstract_payment_api_x6pay extends Abstract_payment_api {
	const PAYTYPE_ALIPAY = 1;
	const PAYTYPE_WECHAT = 2;
	const SETTLE_TYPE_T1 = 1;
	const RESPCODE_SUCCESS = '00';
	const RETURN_SUCCESS_CODE = 'success';
	const PAYMENT_STATUS_SUCCESS = 1;

	public function __construct($params = null) {
		parent::__construct($params);
	}

	public abstract function getPayType();

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		# Setup parameters. Reference: Documentation section 5.2
		$params['merchno'] = $this->getSystemInfo('account');
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['traceno'] = $order->secure_id;
		$params['payType'] = $this->getPayType();
		$params['settleType'] = self::SETTLE_TYPE_T1;
		$params['notifyUrl'] = $this->getNotifyUrl($orderId);

		$params['signature'] = $this->sign($params);

		$this->CI->utils->debug_log('Posting form', $this->getSystemInfo('url'), $params);
		$respJson = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['traceno']);
		$respJsonUTF = iconv('GB2312', 'UTF-8', $respJson);
		$this->CI->utils->debug_log('Post form return (after convert encoding)', $respJsonUTF);
		$resp = json_decode($respJsonUTF, true);

		if ($this->validateResponse($resp)) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $resp['barCode'],
			);
		} else {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $resp['message']
			);
		}
	}

	private function validateResponse($resp){
		# validate success code
		if (strcasecmp($resp['respCode'], self::RESPCODE_SUCCESS) !== 0) {
			$this->utils->error_log("RespCode failed", $resp);
			return false;
		}

		return true;
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
		$this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ': [' . $orderId .']', $params);

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
				$params['orderno'], $params['channelOrderno'],
				null, null, $response_result_id);
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
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : '';
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	# -- private helper functions --
	private function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		# does all required fields exist?
		$requiredFields = array(
			'merchno', 'amount', 'status', 'signature'
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

		if ($fields['status'] != self::PAYMENT_STATUS_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['amount']) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['merchno'] != $this->getSystemInfo('account')) {
			$this->writePaymentErrorLog("Merchant codes do not match, expected [" . $this->getSystemInfo('account') . "]", $fields);
			return false;
		}

		if ($fields['traceno'] != $order->secure_id) {
			$this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	private function getNotifyUrl($transId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $transId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function sign($params) {
		ksort($params);
		$signStr = $this->getSignStr($params);
		$md5Sign = md5($signStr.'&'.$this->getSystemInfo("key"));
	
		return $md5Sign;
	}

	private function validateSign($params) {
		$signature = $this->sign($params);
		return strcasecmp($signature, $params['signature']) == 0;
	}

	private function getSignStr($params) {
		$signStr = "";
		ksort($params);
		foreach($params as $key=>$val){
			if($key == "signature" || empty($val)) {
				continue;
			}
			$signStr .= $key.'='.$val.'&';
		}
		return rtrim($signStr, '&');
	}
}
