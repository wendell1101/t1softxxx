<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ZHIHPAY 智汇付
 * http://www.zhihpay.com
 *
 * Note: This API share mostly the same logic as DINPAY, but php code is better structured
 *
 * * ZHIHPAY_PAYMENT_API, ID: 175
 * * ZHIHPAY_ALIPAY_PAYMENT_API, ID: 176
 * * ZHIHPAY_WEIXIN_PAYMENT_API, ID: 177
 * * ZHIHPAY_TENPAY_PAYMENT_API, ID: 179
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Code
 * * ExtraInfo - pub key and priv key
 *
 * Field Values:
 *
 * * URL
 * 		- B2C: https://pay.zhihpay.com/gateway?input_charset=UTF-8
 * 		- Scan: https://api.zhihpay.com/gateway/api/scanpay
 * * Extra Info:
 * > {
 * > 	"zhihpay_priv_key" : "## pem formatted private key (escaped) ##",
 * > 	"zhihpay_pub_key" : "## pem formatted public key (escaped) ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_zhihpay extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const TRADE_STATUS_SUCCESS = 'SUCCESS';
	const RESP_CODE_SUCCESS = 'SUCCESS';
	const QRCODE_RESULT_CODE_SUCCESS = 0;

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these to specify pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function getSecretInfoList() {
		$secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'zhihpay_pub_key', 'zhihpay_priv_key');
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
		$params['merchant_code'] = $this->getSystemInfo("account");
		$params['input_charset'] = 'UTF-8';
		$params['sign_type'] = 'RSA-S';

		$params['order_no'] = $order->secure_id;
		$params['order_time'] = date('Y-m-d H:i:s'); # 时间格式：yyyy-MM-dd HH:mm:ss
		$params['order_amount'] = $this->convertAmountToCurrency($amount);
		$params['redo_flag'] = '1'; # Do not allow duplicated order
		$params['product_name'] = 'Deposit';
		//$params['extend_param'] = 'name^test|sex^Male';

		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['return_url'] = $this->getReturnUrl($orderId);

		//$params['client_ip_check'] = '0';

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

	# Submit POST form
	protected function processPaymentUrlFormQRCode($params) {
		# CURL post the data to Dinpay
		$postString = http_build_query($params);
		$curlConn = curl_init($this->getSystemInfo('url'));
		curl_setopt($curlConn, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curlConn, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curlConn, CURLOPT_POSTFIELDS, $postString);

		# Need to specify the referer when doing CURL submit. since we use redirect 2nd url, we can take the HTTP_HOST
		curl_setopt($curlConn, CURLOPT_REFERER, "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

		$curlResult = curl_exec($curlConn);
		$curlSuccess = (curl_errno($curlConn) == 0);

		$this->CI->utils->debug_log('curlSuccess', $curlSuccess, $curlResult);

		$errorMsg=null;
		if($curlSuccess) {
			# parses return XML result into array, validate it, and get QRCode URL
			## Parse xml array
			$xmlResult = $this->parseResultXML($curlResult);

			## Flatten the parsed xml array
			$result = $this->flattenResult($xmlResult);

			## Validate result data
			$curlSuccess = $this->validateResult($result);

			if ($curlSuccess) {
				## All good, return with qrcode link
				$qrCodeUrl = $result['qrcode'];

				if(!$qrCodeUrl) {
					$curlSuccess = false;
				}
			}

			if(array_key_exists('result_desc', $result)) {
				$errorMsg = $result['result_desc'];
			} elseif (array_key_exists('resp_desc', $result)) {
				$errorMsg = $result['resp_desc'];
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
			$this->CI->sale_order->updateExternalInfo($order->id,
				$params['trade_no'], $params['bank_seq_no'],
				null, null, $response_result_id);
			if ($source == 'browser') {
				//$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
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
			'merchant_code', 'order_no', 'order_amount', 'trade_status', 'sign'
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

		if ($fields['trade_status'] != self::TRADE_STATUS_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		/*if ($this->convertAmountToCurrency($order->amount) != $fields['order_amount']) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}*/

		if ($fields['order_no'] != $order->secure_id) {
			$this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		if($this->getSystemInfo("use_API_callback_amount") == true) {
			$notes = $order->notes . " diff amount, old amount is " . $order->amount. ", and api response amount is " . $fields['order_amount'];
			$success = $this->CI->sale_order->fixOrderAmount($order->id, $fields['order_amount'], $notes);
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- private helper functions --
	protected function getBankListInfoFallback() {
		return array(
			array('label' => '农业银行', 'value' => 'ABC'),
			array('label' => '工商银行', 'value' => 'ICBC'),
			array('label' => '建设银行', 'value' => 'CCB'),
			array('label' => '交通银行', 'value' => 'BCOM'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '民生银行', 'value' => 'CMBC'),
			array('label' => '光大银行', 'value' => 'CEBB'),
			array('label' => '北京银行', 'value' => 'BOB'),
			array('label' => '上海银行', 'value' => 'SHB'),
			array('label' => '宁波银行', 'value' => 'NBB'),
			array('label' => '华夏银行', 'value' => 'HXB'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '中国邮政', 'value' => 'PSBC'),
			array('label' => '平安银行', 'value' => 'SPABANK'),
			array('label' => '浦发银行', 'value' => 'SPDB'),
			array('label' => '中信银行', 'value' => 'ECITIC'),
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

	# -- signing --
	private function sign($params) {
		$signStr = $this->createSignStr($params);
		openssl_sign($signStr, $sign_info, $this->getPrivKey(), OPENSSL_ALGO_MD5);
		$sign = base64_encode($sign_info);
		
		return $sign;
	}

	private function validateSign($params) {
		if ( array_key_exists("orginal_money", $params) ) {
			unset($params["orginal_money"]);
		}
		$signStr = $this->createSignStr($params);
		$valid = openssl_verify($signStr, base64_decode($params['sign']), $this->getPubKey(), OPENSSL_ALGO_MD5);
		
		return $valid;
	}

	private function createSignStr($params) {
		ksort($params);
		$signStr = '';
		foreach($params as $key => $value) {
			if(empty($value) || $key == 'sign' || $key == 'sign_type') {
				continue;
			}
			$signStr .= "$key=$value&";
		}
		return rtrim($signStr, '&');
	}

	private function validateResult($param) {
		# validate signature (skip this check for now, always fail)
		// if (!$this->validateSign($param)) {
		// 	$this->utils->error_log("payment failed, invalid signature. Params: ", $param);
		// 	return false;
		// }

		# validate success code
		if ($param['result_code'] != self::QRCODE_RESULT_CODE_SUCCESS) {
			$this->utils->error_log("payment failed, resp_code = [".$param['resp_code']."], resp_msg = [".$param['resp_desc']."], Params: ", $param);
			return false;
		}

		return true;
	}


	# Returns public key given by gateway
	private function getPubKey() {
		return openssl_get_publickey($this->getSystemInfo('zhihpay_pub_key'));
	}

	# Returns the private key generated by merchant
	private function getPrivKey() {
		return openssl_get_privatekey($this->getSystemInfo('zhihpay_priv_key'));
	}

	# -- XML Parsing --
	/**
	 * "<?xml version=\"1.0\" encoding=\"UTF-8\" ?><dinpay><response><resp_code>ILLEGAL_PAY_BUSINESS</resp_code><resp_desc>业务未开启，请联系业务人员</resp_desc><sign_type>RSA-S</sign_type><sign>etsR5F3mGJfIKwrflGW0lxQfqNoQU5er/lVRLsvZIiS7miO6Lnk5ELi2XghKKfDVgaTQ3QPju7T9HOMUCLdyWjU+dTx0MUBdwRDx7v937NnMLTmuLvvdPiyIWMQrDyDWzpDv71zIoVIy8Ky/SewlqTH3jnLhRwn968ZOmwf5HCE=</sign><trade></trade></response></dinpay>";
	 * Parses xml string to array
	 *
	 * @param stirng $resultXml
	 *
	 * @return array
	 */
	protected function parseResultXML($resultXml) {
		$obj = simplexml_load_string($resultXml);
		$arr = $this->CI->utils->xmlToArray($obj);

		if(isset($arr['dinpay'])){
			return $arr;
		}else{
			return ['dinpay'=>$arr];
		}
	}

	protected function flattenResult($xmlResult) {
		$this->CI->utils->debug_log('xmlResult to be flattened', $xmlResult);
		return $xmlResult["dinpay"]["response"];
	}
}
