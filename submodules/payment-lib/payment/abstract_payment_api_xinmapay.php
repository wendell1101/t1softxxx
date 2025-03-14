<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * xinmapay 新码支付
 *
 * * XINMAPAY_PAYMENT_API, ID: 376
 * * XINMAPAY_ALIPAY_PAYMENT_API, ID: 377
 * * XINMAPAY_WEIXIN_PAYMENT_API, ID: 378
 * * XINMAPAY_QQPAY_PAYMENT_API, ID: 379
 *
 * Required Fields:
 *
 * * URL
 * * Account : ## branch_id ##
 * * ExtraInfo - callback host
 *
 * Field Values:
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_xinmapay extends Abstract_payment_api {
	const PAYTYPE_BANK = '30';
	const PAYTYPE_ALIPAY = '20';
	const PAYTYPE_WEIXIN = '10';
	const PAYTYPE_QQPAY = '50';
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const RETURN_CALLBACK_STATUS = '02';
	const RESP_CODE_SUCCESS = '00';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these to specify pay type
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
		$params['out_trade_no'] = $order->secure_id;
		$params['branch_id'] = $this->getSystemInfo("account");
		$params['total_fee'] = $this->convertAmountToCurrency($amount);
		$params['prod_name'] = 'Deposit';
		$params['prod_desc'] = 'Deposit';
		$params['back_notify_url'] = $this->getNotifyUrl($orderId);
		$params['front_notify_url'] = $this->getReturnUrl($orderId);
		$params['nonce_str'] = 'Deposit';

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================xinmapay generatePaymentUrlForm params", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
		$errorMsg = "Payment failed for unknown reason.";

		$data_string = $this->zh_json_encode($params);
		$url = $this->getSystemInfo('url');
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_POSTFIELDS,$data_string);
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
		curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在

		curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
		curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer

		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		        'Content-Type: application/json',
		        'Content-Length: ' . strlen($data_string))
		);
		$result = curl_exec($curl);
		if (curl_errno($curl)) {
		    echo 'Errno'.curl_error($curl);//捕抓异常
		}
		curl_close($curl);

		$this->CI->utils->debug_log('=====================xinmapay result', $result);
		$errorMsg = $result;

		$resultJson = json_decode($result, true);

		$this->CI->utils->debug_log('=====================xinmapay resultJson', $resultJson);

		if(isset( $resultJson['resDesc']) ) {
			$errorMsg = $resultJson['resDesc'];
		}

		return array(
			'success' => false,
			'type' => self::REDIRECT_TYPE_ERROR,
			'message' => $errorMsg
		);
	}

	# Submit form via CURL and get QRCode return data
	protected function processPaymentUrlQRCode($params) {
		$errorMsg = "Payment failed for unknown reason.";

		$data_string = $this->zh_json_encode($params);
		$url = $this->getSystemInfo('url');
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_POSTFIELDS,$data_string);
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
		curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在

		curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
		curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer

		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		        'Content-Type: application/json',
		        'Content-Length: ' . strlen($data_string))
		);
		$result = curl_exec($curl);
		if (curl_errno($curl)) {
		    echo 'Errno'.curl_error($curl);//捕抓异常
		}
		curl_close($curl);

		$this->CI->utils->debug_log('=====================xinmapay result', $result);


		$resultJson = json_decode($result, true);

		$this->CI->utils->debug_log('=====================xinmapay result', $resultJson);

		$callbackValid = true;
		$curlSuccess = $this->validateCurlReturn($resultJson);

		if($curlSuccess) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $resultJson['payUrl'],
			);
		} else {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR,
				'message' => $resultJson['resDesc'],
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
		$processed = false;

		$raw_post_data = file_get_contents('php://input', 'r');
		$flds = json_decode($raw_post_data, true);
		$params = array_merge( $params, $flds );

		$callbackValid = false;


		$paymentSuccessful = $this->checkCallbackOrder($order, $params, $callbackValid); # $callbackValid is also assigned

		# Do not print success msg if callback fails integrity check
		if(!$callbackValid) {
			return $result;
		}

		# Do not proceed to update order status if payment failed, but still print success msg as callback response
		if(!$paymentSuccessful) {
			$result['return_error'] = 'failed';
			return $result;
		}

		# We can respond with ack to callback now
		$success = true;
		$result['message'] = self::RETURN_CALLBACK_STATUS;

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
		$result['json_result'] = array("resCode" => "00", "resDesc" => "success");

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
			'resultCode', 'resultDesc', 'resCode', 'resDesc', 'nonceStr', 'sign', 'branchId', 'createTime', 'orderAmt', 'orderNo', 'outTradeNo', 'productDesc', 'status'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================xinmapay checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================xinmapay checkCallbackOrder signature Error', $fields);
			return false;
		}

		$callbackValid = true; # callbackValid is set to true once the signature verification pass

		if ($fields['outTradeNo'] != $order->secure_id) {
			$this->writePaymentErrorLog("=====================xinmapay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		if ($fields['status'] != self::RETURN_CALLBACK_STATUS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['orderAmt']) {
			$this->writePaymentErrorLog("=====================xinmapay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	private function validateCurlReturn($fields) {
		# does all required fields exist?
		$requiredFields = array(
			'resultCode', 'resultDesc', 'resCode', 'resDesc', 'nonceStr', 'sign'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================xinmapay missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('S=====================xinmapay validate signature Error', $fields);
			return false;
		}

		if ($fields['resultCode'] != self::RESP_CODE_SUCCESS || $fields['resCode'] != self::RESP_CODE_SUCCESS) {
			$this->writePaymentErrorLog('=====================xinmapay payment was not successful', $fields);
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
			array('value' => 'ICBCD', 'label' => '工商银行'),
			array('value' => 'ABCD', 'label' => '农业银行'),
			array('value' => 'BOCSH', 'label' => '中国银行'),
			array('value' => 'CCBD', 'label' => '建设银行'),
			array('value' => 'CMB', 'label' => '招商银行'),
			array('value' => 'SPDB', 'label' => '浦发银行'),
			array('value' => 'GDB', 'label' => '广发银行'),
			array('value' => 'BOCOM', 'label' => '交通银行'),
			array('value' => 'CNCB', 'label' => '中信银行'),
			array('value' => 'CMBCD', 'label' => '民生银行'),
			array('value' => 'CIB', 'label' => '兴业银行'),
			array('value' => 'CEBD', 'label' => '光大银行'),
			array('value' => 'HXB', 'label' => '华夏银行'),
			array('value' => 'BOS', 'label' => '上海银行'),
			array('value' => 'SRCB', 'label' => '上海农商'),
			array('value' => 'PSBCD', 'label' => '邮政储蓄'),
			array('value' => 'BCCB', 'label' => '北京银行'),
			array('value' => 'BRCB', 'label' => '北京农商'),
			array('value' => 'PAB', 'label' => '平安银行')
		);
	}

	private function convertAmountToCurrency($amount) {
		return number_format($amount * 100, 2, '.', '');
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
		$signStr .= "&key=". $this->getSystemInfo('key');
		$md5Sign = strtoupper(md5($signStr));

	
		return $md5Sign;
	}

	private function validateSign($params) {
		$signStr = $this->createSignStr($params);

		$signStr .= "&key=". $this->getSystemInfo('key');
		$md5Sign = strtoupper(md5($signStr));


		return strcasecmp($md5Sign, $params['sign']) === 0;
	}

	private function createSignStr($params, $urlencode = false) {
		if(isset($params['sign'])) {
			unset($params['sign']);
		}
		ksort($params);
		$result = array();

		foreach ($params as $key => $value) {
		    if($value == null) {
		        continue;
		    }
		    if($urlencode) {
		        $value = urlencode($value);
		    }
		    $result[$key] = $value;

		}
		return urldecode(http_build_query($result));
	}

	protected function zh_json_encode($array) {
	    $array = $this->urlencode_array($array);
	    return urldecode(json_encode($array));
	}

	protected function urlencode_array($array) {
	    foreach($array as $k => $v) {
	        if(is_array($v)) {
	            $array[$k] = urlencode_array($v);
	        } else {
	            $array[$k] = urlencode($v);
	        }
	    }
	    return $array;
	}
}
