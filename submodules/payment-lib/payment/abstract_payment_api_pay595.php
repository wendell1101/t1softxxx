<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * 595PAY 瞬付
 * http://sh.595pay.com:9090/public/login.jsp
 *
 * * PAY595_ALIPAY_PAYMENT_API, ID: 209
 * * PAY595_WEIXIN_PAYMENT_API, ID: 210
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Number
 * * Key - MD5 Key
 *
 * Field Values:
 *
 * * URL: http://139.199.195.194:8080/api/pay.action
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_pay595 extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = '000000';
	const RESULT_CODE_SUCCESS = '00';
	const STATE_CODE_SUCCESS = '00';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these to specify pay type
	protected abstract function getPayNetway();

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params = array();
		$params['merNo'] = $this->getSystemInfo("account");
		$params['random'] = (string) rand(1000,9999);  #4位随机数,必须是文本型
		$params['orderNo'] = $order->secure_id;  #商户订单号
		$params['amount'] = $this->convertAmountToCurrency($amount);  #默认分为单位,必须是文本型
		$params['goodsInfo'] = 'Deposit';  #商品名称
		$params['callBackUrl'] = $this->getNotifyUrl($orderId);
		$params['callBackViewUrl'] = $this->getReturnUrl($orderId);
		$params['clientIP'] = $this->getClientIp2();  #客户请求IP

		$params['sign'] = $this->sign($params);

		return $this->processPaymentUrlFormQRCode($params);
	}

	# Display QRCode get from curl
	protected function processPaymentUrlFormQRCode($params) {
		# CURL post the data to Dinpay
		$postString = http_build_query(['data' => json_encode($params)]);
		$curlConn = curl_init($this->getSystemInfo('url'));
		curl_setopt($curlConn, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curlConn, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curlConn, CURLOPT_POSTFIELDS, $postString);
		$this->utils->debug_log("CURL post field: ", $postString);
		# Need to specify the referer when doing CURL submit. since we use redirect 2nd url, we can take the HTTP_HOST
		curl_setopt($curlConn, CURLOPT_REFERER, "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

		$curlResult = curl_exec($curlConn);
		$curlSuccess = (curl_errno($curlConn) == 0);

		$this->CI->utils->debug_log('curlSuccess', $curlSuccess, $curlResult);

		$errorMsg=null;
		if($curlSuccess) {
			# parses return JSON result into array, validate it, and get QRCode URL
			$result = json_decode($curlResult, true);

			## Validate result data
			$curlSuccess = $this->validateResult($result);

			if ($curlSuccess) {
				## All good, return with qrcode link
				$qrCodeUrl = $result['qrcode'];

				if(!$qrCodeUrl) {
					$curlSuccess = false;
				}
			}

			if(array_key_exists('msg', $result)) {
				$errorMsg = $result['msg'];
			}
		} else {
			# curl error
			$errorMsg = curl_error($curlConn);
		}

		curl_close($curlConn);

		if($curlSuccess) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $qrCodeUrl,
			);
		} else {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $errorMsg
			);
		}
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
			'merNo', 'orderNo', 'amount', 'resultCode', 'sign'
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

		# if result code is not successful, there are only resultCode and resultMsg fields
		if ($fields['stateCode'] != self::STATE_CODE_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['amount']) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['orderNo'] != $order->secure_id) {
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
	private function convertAmountToCurrency($amount) {
		return number_format($amount*100, 2, '.', '');
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	# This get IP implementation deals with the case of multiple IPs
	public function getClientIp2() {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif (!empty($_SERVER['REMOTE_ADDR'])) {
			$ip = $_SERVER['REMOTE_ADDR'];
		} else {
			$ip = '127.0.0.1';
		}

		# If there are multiple IPs, take the first one
		$multipleIps = explode(",", $ip);
		return trim($multipleIps[0]);
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	# -- signing --
	private function sign($params) {
		unset($params['sign']);
		ksort($params);
		$signStr = json_encode($params).$this->getSystemInfo('key');
		$sign = strtoupper(md5($signStr));
		$this->utils->debug_log("Getting signature from string [$signStr], signature [$sign]. Source Param: ", $params);
		return $sign;
	}

	private function validateSign($params) {
		$sign = $this->sign($params);
		$valid = (0 == strcasecmp($sign, $params['sign']));
		$this->utils->debug_log("Validating signature [$sign], success = [$valid].");
		return $valid;
	}

	private function validateResult($param) {
		$this->utils->debug_log("Validating curl result", $param);

		# validate success code first - if unsuccessful, there is no signature
		if ($param['stateCode'] != self::STATE_CODE_SUCCESS) {
			$this->utils->error_log("payment failed, stateCode = [".$param['stateCode']."], msg = [".$param['msg']."], Params: ", $param);
			return false;
		}

		# validate signature
		if (!$this->validateSign($param)) {
			$this->utils->error_log("payment failed, invalid signature. Params: ", $param);
			return false;
		}

		return true;
	}

}
