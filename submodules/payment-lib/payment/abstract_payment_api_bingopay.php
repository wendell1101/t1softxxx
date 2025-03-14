<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * BINGOPAY
 *
 * * BINGOPAY_PAYMENT_API, ID: 673
 * * BINGOPAY_QUICKPAY_PAYMENT_API, ID: 674
 * * BINGOPAY_WEIXIN_PAYMENT_API, ID: 675
 * * BINGOPAY_ALIPAY_PAYMENT_API, ID: 676
 * * BINGOPAY_QQPAY_PAYMENT_API, ID: 677
 * * BINGOPAY_JDPAY_PAYMENT_API, ID: 678
 * * BINGOPAY_UNIONPAY_PAYMENT_API, ID: 679
 * * BINGOPAY_WEIXIN_H5_PAYMENT_API, ID: 680
 * * BINGOPAY_ALIPAY_H5_PAYMENT_API, ID: 681
 * * BINGOPAY_QQPAY_H5_PAYMENT_API, ID: 682
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Sha key
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

abstract class Abstract_payment_api_bingopay extends Abstract_payment_api {

	//PRODUCT_ID
	const DEFAULTNANK_BANK = '0499';
	const DEFAULTNANK_QUICKPAY = '0300';
	const DEFAULTNANK_QRCODE = '0100';

	//BUS_NO
	const DEFAULTNANK_WEIXIN = '0101';
	const DEFAULTNANK_ALIPAY = '0201';
	const DEFAULTNANK_QQPAY = '0501';
	const DEFAULTNANK_JDPAY = '0601';
	const DEFAULTNANK_UNIONPAY = '0701';

	const DEFAULTNANK_WEIXIN_H5 = '0103';
	const DEFAULTNANK_ALIPAY_H5 = '0203';
	const DEFAULTNANK_QQPAY_H5 = '0503';
	const DEFAULTNANK_JDPAY_H5 = '0603';

	const RETURN_SUCCESS_CODE = '0000';


	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these to specify pay type
	protected abstract function configParams(&$params,&$data, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function getSecretInfoList() {
		$secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'sign_key');
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
		$params['merno']      = $this->getSystemInfo("account");
		$params['amount']     = $this->convertAmountToCurrency($amount);
		$params['goods_info'] = $requestId;
		$params['order_id']   = $order->secure_id;
		$params['return_url'] = $this->getReturnUrl($orderId);
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['card_type']  = '1';
		$params['channelid']  = '1';

		$data['requestId']	= $params['order_id'].date('Ymd');
		$data['orgId']		= $this->getSystemInfo("bingopay_account");
		$data['timestamp']	= date('YmdHis');
		$data['dataSignType'] = 1;
		$this->configParams($params, $data, $order->direct_pay_extra_info);

		$this->CI->utils->debug_log('=========================bingopay params before sign', $params);
		$data['businessData']	= $this->CreateBusinessData($params);
		$data['signData']	= $this->sign($data);

		$this->CI->utils->debug_log('=========================bingopay generatePaymentUrlForm', $data);

		return $this->processPaymentUrlForm($data);
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
		$errCode = curl_errno($curlConn);
		$error = curl_error($curlConn);
		$statusCode = curl_getinfo($curlConn, CURLINFO_HTTP_CODE);
		$curlSuccess = (curl_errno($curlConn) == 0);

		$this->CI->utils->debug_log('curlSuccess', $curlSuccess, $curlResult);

		$requestId = substr($params['requestId'],0,13);
		$this->CI->utils->debug_log('===========================bingopay processPaymentUrlFormQRCode requestId ',$requestId);
		#save response result
		$response_result_id = $this->submitPreprocess($params, $curlResult, $this->getSystemInfo('url'), $curlResult, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $requestId);

		$errorMsg=null;
		if($curlSuccess) {
			# parses return XML result into array, validate it, and get QRCode URL
			## Parse Json array
			$result = $this->parseResultJson($curlResult);

			## Flatten the parsed xml array
			$result_array = $this->parseResultJson($result['result']);

			## Validate result data
			$curlSuccess = $this->validateResult($result);


			if(array_key_exists('respMsg', $result)) {
				$errorMsg = $result['respMsg'];
			} elseif (array_key_exists('respMsg', $result)) {
				$errorMsg = $result['respMsg'];
			}

			if ($curlSuccess) {
				## All good, return with qrcode link
				$qrCodeUrl = urldecode($result_array['url']);

				if(!$qrCodeUrl) {
					$curlSuccess = false;
					$errorMsg = $result['msg'];
				}
			}

		} else {
			# curl error
			$errorMsg = curl_error($curlConn);
		}

		curl_close($curlConn);

		if($curlSuccess) {
			if($params['bus_no'] = self::DEFAULTNANK_WEIXIN_H5){
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_URL,
					'url' => $qrCodeUrl,
				);
			}else{
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_QRCOD,
					'url' => $qrCodeUrl,
				);
			}

		} else {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $errorMsg
			);
		}
	}

	protected function processPaymentUrlFormPost($params) {
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
			## Parse Json array
			$result = $this->parseResultJson($curlResult);

			## Flatten the parsed xml array
			$result_array = $this->parseResultJson($result['result']);

			## Validate result data
			$curlSuccess = $this->validateResult($result);


			if(array_key_exists('respMsg', $result)) {
				$errorMsg = $result['respMsg'];
			} elseif (array_key_exists('respMsg', $result)) {
				$errorMsg = $result['respMsg'];
			}

			if ($curlSuccess) {
				## All good, return with qrcode link
				$Url = urldecode($result_array['url']);

				if(!$Url) {
					$curlSuccess = false;
					$errorMsg = $result['msg'];
				}
			}
		} else {
			# curl error
			$errorMsg = curl_error($curlConn);
		}

		curl_close($curlConn);

		if($curlSuccess) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_FORM,
				'url' => $Url,
				'params' => $params,
				'post' => true,
			);
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['plat_order_id'], null, null, null, $response_result_id);
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
			'orgid', 'merno', 'amount', 'goods_info', 'trade_date', 'trade_status', 'order_id', 'plat_order_id', 'sign_data', 'timestamp'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=========================bingopay checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if ($fields['sign_data']!=$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=========================bingopay checkCallbackOrder validateSign Error', $fields);
			return false;
		}

		$callbackValid = true; # callbackValid is set to true once the signature verification pass

		if ($fields['trade_status'] != "0") {
			$this->writePaymentErrorLog('=========================bingopay checkCallbackOrder result['.$fields['v_result'].'] payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['amount']) {
			$this->writePaymentErrorLog("=========================bingopay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['order_id'] != $order->secure_id) {
			$this->writePaymentErrorLog("=========================bingopay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
			array('label' => '工商银行', 'value' => '102'),
			array('label' => '农业银行', 'value' => '103'),
			array('label' => '中国银行', 'value' => '104'),
			array('label' => '建设银行', 'value' => '105'),
			array('label' => '中信银行', 'value' => '302'),
			array('label' => '光大银行', 'value' => '303'),
			array('label' => '华夏银行', 'value' => '304'),
			array('label' => '广发银行', 'value' => '306'),
			array('label' => '招商银行', 'value' => '308'),
			array('label' => '兴业银行', 'value' => '309'),
			array('label' => '浦发银行', 'value' => '310'),
			array('label' => '邮储银行', 'value' => '403')
		);
	}

	public function convertAmountToCurrency($amount) {
		return number_format($amount*100, 0, '.', '');
	}

	public function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	# -- signing --
	public function sign($data) {
		$signStr = $this->createSignStr($data);
		$sign = strtoupper(md5($signStr));
		return $sign;
	}

	private function validateSign($params) {
		$origin_sign = $params['sign_data'];
		unset($params['sign_data']);

		$signStr = $this->createSignStr($params);
		$sign = md5($signStr);
		return $sign;
	}

	public function createSignStr($params) {
		ksort($params);
		$signStr = "";
		foreach ($params as $key=>$value) {
			$signStr .= $key."=".$value."&";
		}
		$signStr = rtrim($signStr,"&");
		return $signStr.$this->getSystemInfo('sign_key');
	}

	private function validateResult($param) {
		if ($param['respCode'] != "00") {
			$this->writePaymentErrorLog("============================bingopay payment failed, ResCode = [".$param['respCode']."], ResDesc = [".$param['respMsg']."], Params: ", $param);
			return false;
		} else {
			return true;
		}
	}

	public function parseResultJson($result) {
		$arr = json_decode($result, true);
		return $arr;
	}

	public function CreateBusinessData($params){
		$params_str = json_encode($params);
		$key = $this->getSystemInfo('key');
		$businessdata = $this->encrypt($params_str, $key);
		$businessdata = urlencode($businessdata);
		return $businessdata;
	}


	public function encrypt($str,$key){
		$size = @mcrypt_get_block_size(MCRYPT_DES,MCRYPT_MODE_ECB);
		$str = $this->PaddingPKCS7($str);
		$key = str_pad($key,8,'0');
		$td = @mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
		@mcrypt_generic_init($td, $key, '');
		$data = @mcrypt_generic($td, $str);
		@mcrypt_generic_deinit($td);
		@mcrypt_module_close($td);
		$data = base64_encode($data);
		return $data;
	}

	public function PaddingPKCS7($data) {
		$block_size = @mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_ECB);
		$padding_char = $block_size - (strlen($data) % $block_size);
		$data .= str_repeat(chr($padding_char),$padding_char);
		return $data;
	}
}