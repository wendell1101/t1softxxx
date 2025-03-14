<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * uyinpay U付
 * http://www.uyinpay.com
 *
 * * UYINPAY_PAYMENT_API, ID: 190
 * * UYINPAY_ALIPAY_PAYMENT_API, ID: 191
 * * UYINPAY_WEIXIN_PAYMENT_API, ID: 192
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * ExtraInfo - pub key and priv key
 *
 * Field Values:
 *
 * * URL
 * 		- Production: https://payment.uyinpay.com/sfpay/payServlet
 * 		- Test: http://pay.miggoo.com/sfpay/payServlet
 * * Scan Code URL
 * 		- Production: https://payment.uyinpay.com/sfpay/scanCodePayServlet
 *   	- Test: http://pay.miggoo.com/sfpay/scanCodePayServlet
 * * Test Merchant ID: GWP_TEST
 * * Extra Info:
 * > {
 * > 	"uyinpay_priv_key" : "## pem formatted private key (escaped) ##",
 * > 	"uyinpay_pub_key" : "## pem formatted public key (escaped) ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_uyinpay extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const TRADE_STATUS_SUCCESS = 'SUCCESS';
	const RESP_CODE_SUCCESS = '00';
	const QRCODE_RESULT_CODE_SUCCESS = 0;

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these to specify pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'uyinpay_pub_key', 'uyinpay_priv_key');
        return $secretsInfo;
    }

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params = array();
		$params['merchantId'] = $this->getSystemInfo("account");
		$params['notifyUrl'] = $this->getNotifyUrl($orderId);
		$params['returnUrl'] = $this->getReturnUrl($orderId);
		$params['transAmt'] = $this->convertAmountToCurrency($amount);

		$params['outOrderId'] = $order->secure_id;
		$params['subject'] = 'Deposit';
		$params['body'] = 'Deposit';

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['sign'] = $this->sign($params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true,
		);
	}

	# Submit form via CURL and get QRCode return data
	protected function processPaymentUrlQRCode($params) {
		$resultString = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['outOrderId']);
		$result = json_decode($resultString, true);

		$callbackValid = true;
		$curlSuccess = $this->validateCurlReturn($result);

		if($curlSuccess) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $result['payCode'],
			);
		} else {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR,
				'message' => $result['respMsg'],
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
			$this->CI->sale_order->updateExternalInfo($order->id,
				$params['localOrderId'], '',
				null, null, $response_result_id);
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
			'sign', 'outOrderId', 'merchantId', 'transAmt', 'respCode'
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

		if ($fields['outOrderId'] != $order->secure_id) {
			$this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		if ($fields['respCode'] != self::RESP_CODE_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['transAmt']) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	private function validateCurlReturn($fields) {
		# does all required fields exist?
		$requiredFields = array(
			'sign', 'outOrderId', 'merchantId', 'respCode'
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

		if ($fields['respCode'] != self::RESP_CODE_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- private helper functions --
	protected function getBankListInfoFallback() {
		return array(
			array('value' => '01000000', 'label' => '中国邮储银行'),
			array('value' => '01020000', 'label' => '工商银行'),
			array('value' => '01030000', 'label' => '农业银行'),
			array('value' => '01040000', 'label' => '中国银行'),
			array('value' => '01050000', 'label' => '建设银行'),
			array('value' => '03010000', 'label' => '交通银行'),
			array('value' => '03020000', 'label' => '中信银行'),
			array('value' => '03030000', 'label' => '光大银行'),
			array('value' => '03040000', 'label' => '华夏银行'),
			array('value' => '03050000', 'label' => '民生银行'),
			array('value' => '03060000', 'label' => '广发银行'),
			array('value' => '03070000', 'label' => '平安银行'),
			array('value' => '03080000', 'label' => '招商银行'),
			array('value' => '03090000', 'label' => '兴业银行'),
			array('value' => '03100000', 'label' => '浦发银行'),
			array('value' => '04031000', 'label' => '北京银行'),
			array('value' => '04083320', 'label' => '宁波银行'),
			array('value' => '04243010', 'label' => '南京银行'),
		);
	}

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
		$signStr = $this->createSignStr($params);
		openssl_sign($signStr, $sign_info, $this->getPrivKey(), OPENSSL_ALGO_MD5);
		$sign = base64_encode($sign_info);
		return $sign;
	}

	private function validateSign($params) {
		$signStr = $this->createSignStr($params);
		$sign = base64_decode($params['sign']);
		$valid = openssl_verify($signStr, $sign, $this->getPubKey(), OPENSSL_ALGO_MD5);
		return $valid;
	}

	private function createSignStr($params) {
		ksort($params);
		$signStr = '';
		foreach($params as $key => $value) {
			if(empty($value) || $key == 'sign' || $key == 'signType') {
				continue;
			}
			$signStr .= "$key=$value&";
		}
		return rtrim($signStr, '&');
	}

	# Returns public key given by gateway
	private function getPubKey() {
		return openssl_get_publickey($this->getSystemInfo('uyinpay_pub_key'));
	}

	# Returns the private key generated by merchant
	private function getPrivKey() {
		return openssl_get_privatekey($this->getSystemInfo('uyinpay_priv_key'));
	}
}
