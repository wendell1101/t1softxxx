<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * Allscore (商银信)
 * http://www.allscore.com
 *
 * ALLSCORE_PAYMENT_API, ID: 90
 *
 * Required Fields:
 *
 * * URL
 * * Key (MD5 signing key)
 * * Extra Info
 *
 * Field Values:
 *
 * * Live URL: https://paymenta.allscore.com/olgateway/serviceDirect.htm
 * * Sandbox URL: http://119.61.12.89:8090/olgateway/serviceDirect.htm
 * * Extra Info
 * > {
 * >      "allscore_merchantId": "## Merchant ID ##",
 * >      "allscore_signType" : "MD5/RSA",
 * >      "allscore_priv_key" : "",
 * >      "allscore_pub_key" : "",
 * >      "allscore_scan_pay_url" : "https://paymenta.allscore.com/olgateway/scan/scanPay.htm",
 * >      "disable_scan_pay" : "",
 * >      "bank_list" : ""
 * > }
 *
 * Note: If allscore_signType is MD5, fill in MD5 signing key; if allscore_signType is RSA, fill in
 * allscore_priv_key and allscore_pub_key.
 *
 * @category Payment
 * @copyright 2013-2022 tot
 *
 */
class Payment_api_allscore extends Abstract_payment_api {
	const TRADE_STATUS_FAILED = '1';
	const RESP_CODE_SUCCESS = 'SUCCESS';
	const RETURN_SUCCESS_CODE = 'success';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return ALLSCORE_PAYMENT_API;
	}

	public function getPrefix() {
		return 'allscore';
	}

	public function getSecretInfoList() {
		$secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'allscore_priv_key', 'allscore_pub_key');
		return $secretsInfo;
	}

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		# Setup parameters. Reference: Documentation section 5.2
		$params['service'] = 'directPay';
		$params['merchantId'] = $this->getSystemInfo("allscore_merchantId");
		$params['notifyUrl'] = $this->getNotifyUrl($orderId);
		$params['returnUrl'] = $this->getReturnUrl($orderId);
		$params['signType'] = $this->getSystemInfo('allscore_signType');
		$params['inputCharset'] = 'UTF-8';

		# order-related params
		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params['outOrderId'] = $order->secure_id;
		$params['subject'] = lang('pay.deposit');
		$params['body'] = lang('pay.deposit');
		$params['transAmt'] = $this->convertAmountToCurrency($amount);
		$params['payMethod'] = 'bankPay';

		$direct_pay_extra_info = $order->direct_pay_extra_info;
		$scanCode = false;
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo) && array_key_exists('bank', $extraInfo)) {
				$params['defaultBank'] = $extraInfo['bank'];

				# Detect special case, wechat or alipay
				if(strpos($params['defaultBank'], 'default') === 0){
					$scanCode = true;
					$params['payMethod'] = $params['defaultBank'];
					unset($params['defaultBank']);
				}
			}
		}
		$params['channel'] = 'B2C';
		$params['cardAttr'] = '01'; # test shows that 02 not supported

		# sign param
		$params['sign'] = $this->sign($params);

		if(!$scanCode) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_FORM,
				'url' => $this->getSystemInfo('url'),
				'params' => $params,
				'post' => false, # Submit using GET
			);
		} else {
			unset($params['cardAttr']);
			unset($params['channel']);
			unset($params['returnUrl']);
			$params['params']=$order->id;

			# params specific for scan code
			$params['ip'] = $this->getClientIP();

			# sign param again since it's modified
			$params['sign'] = $this->sign($params);

			# Use this to return XML from webpage, for debugging
			/*return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_FORM,
				'url' => $weixinUrl,
				'params' => $params,
				'post' => true,
			);*/

			# CURL submit the data to scan pay url
			$scanPayUrl = $this->getSystemInfo("allscore_scan_pay_url");
			$postString = http_build_query($params);

			$this->CI->utils->debug_log('scanPayUrl', $scanPayUrl, 'post', $postString);

			$curlConn = curl_init($scanPayUrl);
			curl_setopt($curlConn, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curlConn, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curlConn, CURLOPT_POSTFIELDS, $postString);

			$curlResult = curl_exec($curlConn);
			$curlSuccess = (curl_errno($curlConn) == 0);

			$this->CI->utils->debug_log('AllScore curlSuccess', $curlSuccess, $curlResult);

			$errorMsg=null;
			if($curlSuccess) {
				# parses return XML result into array, validate it, and get QRCode URL
				## Parse xml array
				$result = $this->parseResultXML($curlResult);

				$this->CI->utils->debug_log('result xml to array', $result);

				## Validate result data
				//ignore validation, according allscore
				$curlSuccess = true; // $this->validateXMLResult($result);

				if ($curlSuccess) {
					## All good, return with qrcode link
					$qrCodeUrl = $result['payCode'];

					if(empty($qrCodeUrl)) {
						$curlSuccess = false;
					}
				}

				if(array_key_exists('message', $result)) {
					$errorMsg = urldecode($result['message']); # the message is url encoded
				}
			} else {
				# curl error
				$errorMsg = curl_error($curlConn);
			}

			curl_close($curlConn);

			$this->CI->utils->debug_log('Scan Code errorMsg', $errorMsg);

			if($curlSuccess) {
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_QRCODE,
					'url' => $qrCodeUrl,
				);
			} else {
				$this->utils->error_log("Scan Code payment failed ", $errorMsg, "Post String", $postString);

				return array(
					'success' => false,
					'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
					'message' => $errorMsg
				);
			}
		}
	}

	public function parseResultXML($resultXml) {
		$obj = simplexml_load_string($resultXml);
		return $this->CI->utils->xmlToArray($obj);
	}

	public function validateXMLResult($param){
		# validate success code
		if (strcasecmp($param['reCode'], self::RESP_CODE_SUCCESS) !== 0) {
			$this->utils->error_log("Scan Code payment failed, reCode = [".$param['reCode']."], Params: ", $param);
			return false;
		}

		# validate signature
		if (!$this->validateSign($param)) {
			$this->utils->error_log("Scan Code payment failed, invalid signature. Params: ", $param);
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
				$params['localOrderId'], '',
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

	# Ref: Documentation section 6.3
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		# does all required fields exist?
		$requiredFields = array(
			'notifyId', 'notifyTime', 'sign', 'outOrderId', 'merchantId'
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

		# is payment successful?
		if (strcasecmp($fields['tradeStatus'], self::TRADE_STATUS_FAILED) == 0) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		# does amount match?
		if ($this->convertAmountToCurrency($order->amount) != $fields['transAmt']) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# does merchNo match?
		if ($fields['merchantId'] != $this->getSystemInfo('allscore_merchantId')) {
			$this->writePaymentErrorLog("Merchant codes do not match, expected [" . $this->getSystemInfo('allscore_merchant_code') . "]", $fields);
			return false;
		}

		# does order_no match?
		if ($fields['outOrderId'] != $order->secure_id) {
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
	private function getNotifyUrl($transId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $transId);
	}

	private function getReturnUrl($orderId) {
		return parent::getBrowserCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function getBankListInfoFallback() {
		$bankList = array(
			array('label' => '中国工商银行', 'value' => 'ICBC'),
			array('label' => '中国农业银行', 'value' => 'ABC'),
			array('label' => '建设银行', 'value' => 'CCB'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '光大银行', 'value' => 'CEB'),
			array('label' => '浦发银行', 'value' => 'SPDB'),
			array('label' => '平安银行', 'value' => 'SPABANK'),
			array('label' => '农业银行', 'value' => 'ABC'),
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '邮储银行', 'value' => 'PSBC'),
			array('label' => '中信银行', 'value' => 'CITIC'),
			array('label' => '华夏银行', 'value' => 'HXBANK'),
			array('label' => '民生银行', 'value' => 'CMBC'),
			array('label' => '广发银行', 'value' => 'GDB'),
			array('label' => '北京银行', 'value' => 'BJBANK'),
			array('label' => '上海银行', 'value' => 'SHBANK'),
		);

		if(!$this->getSystemInfo("disable_scan_pay")) {
			array_unshift($bankList, array('label'=>'支付宝', 'value'=>'default_alipay'));
			array_unshift($bankList, array('label'=>'微信', 'value'=>'default_wechat'));
		}

		if($this->getSystemInfo("only_wechat", false)) {
			$bankList = [array('label'=>'微信', 'value'=>'default_wechat')];
		}

		return $bankList;
	}

	## Reference: Documentation section 8.1
	public function sign($params) {
		$signStr = $this->getSignStr($params);

		if ('MD5' == $this->getSystemInfo('allscore_signType')) {
			$md5Sign = md5($signStr.$this->getSystemInfo("key"));
			$this->utils->debug_log("Signing: [".$signStr.$this->getSystemInfo("key")."] with MD5, value: ", $md5Sign);
			return $md5Sign;
		} elseif ('RSA' == $this->getSystemInfo('allscore_signType')) {
			$rsaBinary = "";
			$privateKeyStr = $this->getSystemInfo('allscore_priv_key');
			openssl_sign($signStr, $rsaBinary, $this->formatRSAKey($privateKeyStr, 'RSA PRIVATE KEY'));
			return base64_encode($rsaBinary);
		}
	}

	## Reference: Documentation section 6.3
	public function validateSign($params) {
		$signStr = $this->getSignStr($params);

		if ('MD5' == $this->getSystemInfo('allscore_signType')) {
			$md5Sign = md5($signStr.$this->getSystemInfo("key"));
			$this->utils->debug_log("Validate Sign: [$signStr], value: ", $md5Sign);
			return strcasecmp($params['sign'], $md5Sign) === 0;
		} elseif ('RSA' == $this->getSystemInfo('allscore_signType')) {
			$rsaBinary = base64_decode($params['sign']);
			$publicKeyStr = $this->getSystemInfo('allscore_pub_key');
			return (openssl_verify($signStr, $rsaBinary, $this->formatRSAKey($publicKeyStr, 'PUBLIC KEY')) === 1);
		}
	}

	private function getSignStr($params) {
		$signStr = "";
		$params = $this->argSort($params);
		foreach($params as $key=>$val){
			if($key == "sign" || $key == "signType" || empty($val)) {
				continue;
			}
			$signStr .= $key.'='.$val.'&';
		}
		return rtrim($signStr, '&');
	}

	private function formatRSAKey($str, $headerStr) {
		$keyStr = "-----BEGIN $headerStr-----\n";
		for($i = 0; $i < strlen($str); $i++) {
			$keyStr .= $str[$i];
			if(($i+1) % 64 == 0) {
				$keyStr .= "\n";
			}
		}
		$keyStr .= "\n-----END $headerStr-----";
		return $keyStr;
	}

	private function argSort($para) {
		ksort($para);
		reset($para);
		return $para;
	}

	## Reference: Documentation section 5.2, amount in CNY Yuan
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}
}
