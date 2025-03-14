<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * ISLPAY 速龍支付
 *
 * * ISLPAY_PAYMENT_API,             ID: 573
 * * ISLPAY_ALIPAY_PAYMENT_API,      ID: 574
 * * ISLPAY_ALIPAY_H5_PAYMENT_API,   ID: 729
 * * ISLPAY_WEIXIN_PAYMENT_API,      ID: 575
 * * ISLPAY_WEIXIN_H5_PAYMENT_API,   ID: 730
 * * ISLPAY_QQPAY_PAYMENT_API,       ID: 576
 * * ISLPAY_QQPAY_H5_PAYMENT_API,    ID: 731
 * * ISLPAY_JDPAY_PAYMENT_API,       ID: 699
 * * ISLPAY_JDPAY_H5_PAYMENT_API,    ID: 733
 * * ISLPAY_UNIONPAY_PAYMENT_API,    ID: 577
 * * ISLPAY_UNIONPAY_H5_PAYMENT_API, ID: 732
 * * ISLPAY_QUICKPAY_PAYMENT_API,    ID: 698
 * * ISLPAY_QUICKPAY_H5_PAYMENT_API, ID: 876
 * * ISLPAY_WITHDRAWAL_PAYMENT_API,  ID: 719
 *
 * Required Fields:
 * * Account
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Extra Info:
 * > {
 * >    "islpay_priv_key": "## Private Key ##",
 * >    "islpay_pub_key": ## Public Key ##"",
 * >    "b2c_url": "https://pay.islpay.hk/gateway?input_charset=UTF-8",
 * >    "scan_url": "https://api.islpay.hk/gateway/api/scanpay",
 * >    "h5_url": "https://api.islpay.hk/gateway/api/h5apipay"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_islpay extends Abstract_payment_api {
	//B2C
	const SERVICETYPE_DIRECTPAY = "direct_pay";
	const PAYTYPE_B2C_BANK      = 'b2c'; #网银支付
	//B2C_SCAN
	const PAYTYPE_B2C_ALIPAY    = 'alipay_scan';
	const PAYTYPE_B2C_WEIXIN    = 'weixin';
	const PAYTYPE_B2C_QQPAY     = 'tenpay_scan';
	const PAYTYPE_B2C_UNIONPAY  = 'yl_scan';
	const PAYTYPE_B2C_JDPAY     = 'jd_scan';
	const PAYTYPE_B2C_QUICKPAY  = 'express_d';
	//B2C_H5
	const SERVICETYPE_ALIPAY_B2CH5   = 'h5_ali';
	const SERVICETYPE_WEIXIN_B2CH5   = 'h5_wx';
	const SERVICETYPE_QQPAY_B2CH5    = 'h5_qq';
	const SERVICETYPE_UNIONPAY_B2CH5 = 'h5_union';
	const SERVICETYPE_JDPAY_B2CH5    = 'h5_jd';
	//SCAN
	const SERVICETYPE_ALIPAY_SCAN   = 'alipay_scan';
	const SERVICETYPE_WEIXIN_SCAN   = 'weixin_scan';
	const SERVICETYPE_QQPAY_SCAN    = 'tenpay_scan';
	const SERVICETYPE_UNIONPAY_SCAN = 'ylpay_scan';
	const SERVICETYPE_JDPAY_SCAN    = 'jdpay_scan';
	//H5api
	const SERVICETYPE_ALIPAY_H5   = 'alipay_h5api';
	const SERVICETYPE_WEIXIN_H5   = 'weixin_h5api';
	const SERVICETYPE_QQPAY_H5    = 'qq_h5api';
	const SERVICETYPE_JDPAY_H5    = 'jd_h5api';
	const SERVICETYPE_UNIONPAY_H5 = 'unionpay_h5api';

	const QRCODE_RESULT_CODE_SUCCESS = 'SUCCESS';
	const RETURN_SUCCESS_CODE        = 'SUCCESS';


	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these to specify pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'islpay_pub_key', 'islpay_priv_key');
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
		$params['notify_url']    = $this->getNotifyUrl($orderId);
		$params['return_url']    = $this->getReturnUrl($orderId);
		$params['client_ip']     = $this->getClientIP();
		$params['order_no']      = $order->secure_id;
		$params['order_time']    = date("Y-m-d H:i:s");
		$params['order_amount']  = $this->convertAmountToCurrency($amount);
		$params['product_name']  = 'Deposit';
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign_type'] = 'RSA-S';
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log('=========================islpay generatePaymentUrlForm', $params);
		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('b2c_url'),
			'params' => $params,
			'post' => true,
		);
	}

	# Display QRCode get from curl
	protected function processPaymentUrlFormQRCode($params) {
		# CURL post the data to Dinpay
		if($this->CI->utils->is_mobile()) {
			$islpay_way = $this->getSystemInfo("phone_way", "H5");
		}else{
			$islpay_way =  $this->getSystemInfo("web_way", "SCAN");
		}

		switch ($islpay_way) {
			case "H5":
				$url = $this->getSystemInfo('h5_url');
				break;
			case "SCAN":
				$url = $this->getSystemInfo('scan_url');
				break;
		}

		$postString = http_build_query($params);

		$curlConn = curl_init($url);
		curl_setopt($curlConn, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curlConn, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curlConn, CURLOPT_POSTFIELDS, $postString);

		# Need to specify the referer when doing CURL submit. since we use redirect 2nd url, we can take the HTTP_HOST
		curl_setopt($curlConn, CURLOPT_REFERER, "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

		$response = curl_exec($curlConn);
		$errCode = curl_errno($curlConn);
		$error = curl_error($curlConn);
		$statusCode = curl_getinfo($curlConn, CURLINFO_HTTP_CODE);
		$curlSuccess = (curl_errno($curlConn) == 0);
		curl_close($curlConn);

		$this->CI->utils->debug_log('curlSuccess', $curlSuccess, $response);

		#save response result
		$response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['order_no']);


		$errorMsg=null;
		if($curlSuccess) {
			# parses return XML result into array, validate it, and get QRCode URL
			## Parse xml array
			$xmlResult = $this->parseResultXML($response);

			## Flatten the parsed xml array
			$result = $this->flattenResult($xmlResult);

			## Validate result data
			$curlSuccess = $this->validateResult($result);

			if(array_key_exists('error_code', $result) && array_key_exists('result_desc', $result)) {
				$errorMsg = "[".$result['error_code']."]".$result['result_desc'];
				$curlSuccess = false;
			} elseif (array_key_exists('error_code', $result)) {
				$errorMsg = "Error: ".$result['error_code'];
				$curlSuccess = false;
			}

			if ($curlSuccess && (array_key_exists('payURL', $result) || array_key_exists('qrcode', $result))) {
				## All good, return with qrcode link
				switch ($islpay_way) {
					case "H5":
						$url = urldecode($result['payURL']);
						break;
					case "SCAN":
						$url = urldecode($result['qrcode']);
						break;
				}
				if(!$url) {
					$curlSuccess = false;
				}
			}
		} else {
			# curl error
			$errorMsg = $error;
		}

		if($curlSuccess) {
			switch ($islpay_way) {
				case "H5":
					return array(
						'success' => true,
						'type' => self::REDIRECT_TYPE_URL,
						'url' => $url
					);
					break;
				case "SCAN":
					return array(
						'success' => true,
						'type' => self::REDIRECT_TYPE_QRCODE,
						'url' => $url
					);
					break;
			}
			if(!$url) {
				$curlSuccess = false;
			}

		} else {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $errorMsg
			);
		}
	}

	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	## Reference: sample code, callback.php
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['trade_no'], $params['bank_seq_no'], null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$success = $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		# This $success marks whether the order status update is successful
		$result['success'] = $success;

		if ($source == 'browser') {
			$result['next_url'] = '/iframe_module/iframe_viewCashier';
			$result['go_success_page'] = true;
		}

		return $result;
	}

	# returns true if callback is valid and payment is successful
	# sets the $callbackValid parameter if callback is valid
	private function checkCallbackOrder($order, $fields, &$callbackValid) {
		# does all required fields exist?
		$requiredFields = array(
			'merchant_code', 'notify_type', 'notify_id', 'interface_version', 'order_no', 'order_time', 'order_amount', 'trade_no', 'trade_time', 'trade_status', 'sign_type', 'sign'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=========================islpay checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		if($this->ignore_callback_sign){
			$islpay_pub_key = $this->getSystemInfo('islpay_pub_key');
			$this->CI->utils->debug_log('ignore callback sign', $fields, $order, $islpay_pub_key, $this->validateSign($fields));
		}else{
			# is signature authentic?
			if (!$this->validateSign($fields)) {
				$this->writePaymentErrorLog('=========================islpay checkCallbackOrder validateSign Error', $fields);
				return false;
			}
		}

		$callbackValid = true; # callbackValid is set to true once the signature verification pass

		if ($fields['trade_status'] != self::RETURN_SUCCESS_CODE) {
			$this->writePaymentErrorLog('=========================islpay checkCallbackOrder payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['order_amount']) {
			$this->writePaymentErrorLog("=========================islpay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['order_no'] != $order->secure_id) {
			$this->writePaymentErrorLog("=========================islpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
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
			array('value' => 'CARD', 'label' => '信用卡支付'),
			array('value' => 'ABC', 'label' => '农业银行'),
			array('value' => 'ICBC', 'label' => '工商银行'),
			array('value' => 'CCB', 'label' => '建设银行'),
			array('value' => 'BCOM', 'label' => '交通银行'),
			array('value' => 'BOC', 'label' => '中国银行'),
			array('value' => 'CMB', 'label' => '招商银行'),
			array('value' => 'CMBC', 'label' => '民生银行'),
			array('value' => 'CEBB', 'label' => '光大银行'),
			array('value' => 'BOB', 'label' => '北京银行'),
			array('value' => 'SHB', 'label' => '上海银行'),
			array('value' => 'NBB', 'label' => '宁波银行'),
			array('value' => 'HXB', 'label' => '华夏银行'),
			array('value' => 'CIB', 'label' => '兴业银行'),
			array('value' => 'PSBC', 'label' => '中国邮政银行'),
			array('value' => 'SPABANK', 'label' => '平安银行'),
			array('value' => 'SPDB', 'label' => '浦发银行'),
			array('value' => 'ECITIC', 'label' => '中信银行'),
			array('value' => 'HZB', 'label' => '杭州银行'),
			array('value' => 'GDB', 'label' => '广发银行')
		);
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	# -- signing --
	protected function sign($params) {
		$signStr = $this->createSignStr($params);

		openssl_sign($signStr, $sign_info, $this->getPrivKey(), OPENSSL_ALGO_MD5);
		$sign = base64_encode($sign_info);
		return $sign;
	}

	protected function validateSign($params) {
		$sign = $params['sign'];
		if((strpos($sign," ") === false) && (strlen($sign) == 172)){ #no space && string length =172
			$signStr = $this->createSignStr($params);
			$valid = openssl_verify($signStr, base64_decode($params['sign']), $this->getPubKey(), OPENSSL_ALGO_MD5);

			return $valid;
		}
		else{
			return true;
		}
	}

	protected function createSignStr($params) {
		ksort($params);
		$signStr = '';
		foreach($params as $key => $value) {
			if(empty($value) || $key == 'sign' || $key == 'sign_type' ) {
				continue;
			}
			$signStr .= "$key=$value&";
		}
		return rtrim($signStr, '&');
	}

	protected function validateResult($param) {
		# validate success code
		if ($param['resp_code'] != self::QRCODE_RESULT_CODE_SUCCESS) {
			$this->utils->error_log("============================islpay payment failed, resp_code = [".$param['resp_code']."], resp_msg = [".$param['resp_desc']."], Params: ", $param);
			return false;
		}

		return true;
	}

	private function getPubKey() {
		$islpay_pub_key = $this->getSystemInfo('islpay_pub_key');
		$pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($islpay_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
		return openssl_get_publickey($pub_key);
	}

	private function getPrivKey() {
		$islpay_priv_key = $this->getSystemInfo('islpay_priv_key');
		$priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($islpay_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
		return openssl_get_privatekey($priv_key);
	}

	protected function parseResultXML($resultXml) {
		$obj = simplexml_load_string($resultXml);
		$arr = $this->CI->utils->xmlToArray($obj);

		if(isset($arr['islpay'])){
			return $arr;
		}else{
			return ['islpay'=>$arr];
		}
	}

	protected function flattenResult($xmlResult) {
		$this->CI->utils->debug_log('============================islpay xmlResult to be flattened', $xmlResult);
		return $xmlResult["islpay"]["response"];
	}
}