<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * HAOHAOPAY
 *
 * * HAOHAOPAY_PAYMENT_API, ID: 748
 * * HAOHAOPAY_QUICKPAY_PAYMENT_API, ID: 749
 * * HAOHAOPAY_JDPAY_PAYMENT_API, ID: 750
 * * HAOHAOPAY_ALIPAY_PAYMENT_API, ID: 751
 * * HAOHAOPAY_WEIXIN_PAYMENT_API, ID: 752
 * * HAOHAOPAY_QQPAY_PAYMENT_API, ID: 753
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Sha key
 *
 * Field Values:
 *
 * * Extra Info:
 * > {
 * >    "sellerEmail" : "## Seller email address, system will show you when the merchant opens ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

abstract class Abstract_payment_api_haohaopay extends Abstract_payment_api {

	//PRODUCT_ID
	const DEFAULTNANK_BANK = 'wy';
	const DEFAULTNANK_QUICKPAY = 'qpay';

	//BUS_NO
	const DEFAULTNANK_WEIXIN = 'wxsm';
	const DEFAULTNANK_ALIPAY = 'alipaysm';
	const DEFAULTNANK_QQPAY = 'qqsm';
	const DEFAULTNANK_JDPAY = 'jdsm';
	const DEFAULTNANK_UNIONPAY = 'Unionsm';

	const DEFAULTNANK_WEIXIN_WAP = 'wxwap';
	const DEFAULTNANK_ALIPAY_WAP = 'alipaywap';
	const DEFAULTNANK_QQPAY_WAP = 'qqwap';
	const DEFAULTNANK_JDPAY_WAP = 'jdwap';
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const RECALL_SUCCESS_CODE = 'TRADE_FINISHED';


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

		$params['mchNo'] = $this->getSystemInfo("account");
		$params['orderID'] = $order->secure_id;
		$params['money'] = $this->convertAmountToCurrency($amount);
		$params['body'] ='deposit';
		$params['callbackurl'] = base64_encode($this->getReturnUrl($orderId));
		$params['notifyUrl'] = base64_encode($this->getNotifyUrl($orderId));
		$params['clientip'] = $this->getClientIp();
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log('=========================haohaopay generatePaymentUrlForm', $params);
		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormQRCode($params) {
		# CURL post the data to Dinpay
		$postString['requestBody'] = json_encode($params);

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

		$this->CI->utils->debug_log("============================before curlResult ", $curlResult);

		$this->CI->utils->debug_log('curlSuccess', $curlSuccess, $curlResult);

		$errorMsg=null;
		if($curlSuccess) {
			# parses return XML result into array, validate it, and get QRCode URL
			## Parse Json array
			$result = $this->parseResultJson($curlResult);

			$this->CI->utils->debug_log("============================after curlresult ", $result);


			## Flatten the parsed xml array
			## Validate result data
			$curlSuccess = $this->validateResult($result);


			if(array_key_exists('resultMsg', $result)) {
				$errorMsg = $result['resultMsg'];
			} elseif (array_key_exists('resultMsg', $result)) {
				$errorMsg = $result['resultMsg'];
			}

			if ($curlSuccess) {
				## All good, return with qrcode link
				$qrCodeUrl = urldecode($result['codeImageUrl']);

				if(!$qrCodeUrl) {
					$curlSuccess = false;
					$errorMsg = $result['resultMsg'];
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
				'type' => self::REDIRECT_TYPE_QRCODE,
				'image_url' => $qrCodeUrl,
			);
		} else {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $errorMsg
			);
		}
	}

	protected function processPaymentUrlFormPost($params) {
		$postString['requestBody'] = json_encode($params);
		$url=$this->getSystemInfo('url');

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $url,
			'params' => $postString,
			'post' => true,
		);
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
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		if (!$order) {
			$this->utils->error_log("Order ID [$orderId] not found.");
			return $result;
		}

		$callbackValid = false;
		if($source == 'server'){
			$params = json_decode($params['result'], true);
			if (!$order || !$this->checkCallbackOrder($order, $params, $callbackValid)) {
				return $result;
			}
		}

		# Do not proceed to update order status if payment failed, but still print success msg as callback response
		if(!$callbackValid) {
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['orderID'], null, null, null, $response_result_id);
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
			'orderID', 'money', 'transID', 'status', 'notifyUrl', 'count', 'sign'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=========================haohaopay checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if ($fields['sign']!=$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=========================haohaopay checkCallbackOrder validateSign Error', $fields);
			return false;
		}

		$callbackValid = true; # callbackValid is set to true once the signature verification pass

		if ($fields['status'] != self::RECALL_SUCCESS_CODE) {
			$this->writePaymentErrorLog('=========================haohaopay checkCallbackOrder result['.$fields['v_result'].'] payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['money']) {
			$this->writePaymentErrorLog("=========================haohaopay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['orderID'] != $order->secure_id) {
			$this->writePaymentErrorLog("=========================haohaopay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
			array('label' => '工商银行', 'value' => 'icbc'),
			array('label' => '中国银行', 'value' => 'boc'),
			array('label' => '招商银行', 'value' => 'cmb'),
			array('label' => '广发银行', 'value' => 'gdb'),
			array('label' => '中信银行', 'value' => 'cncb'),
			array('label' => '光大银行', 'value' => 'ceb'),
			array('label' => '农业银行', 'value' => 'abc'),
			array('label' => '建设银行', 'value' => 'ccb'),
			array('label' => '交通银行', 'value' => 'comm'),
			array('label' => '兴业银行', 'value' => 'cib'),
			array('label' => '民生银行', 'value' => 'cmbc')
		);
	}

	public function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	public function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	# -- signing --
	public function sign($data) {
		ksort($data);
		$signStr = $this->createSignStr($data);
		$signStr .="key=" .$this->getSystemInfo('key');

		$sign = md5($signStr);
		return $sign;
	}

	private function validateSign($params) {
		$signStr = $this->getSystemInfo('key');
		$signStr .= $params['orderID'].$params['money'].$params['status'];
		$sign = md5($signStr);
		return $sign;
	}

	public function createSignStr($params) {
		$signStr="";
		foreach ($params as $key=>$value) {
			$signStr .= $key."=".$value."&";
		}
		return $signStr;
	}

	private function validateResult($param) {
		if ($param['resultCode'] != "0000") {
			$this->writePaymentErrorLog("============================haohaopay payment failed, ResCode = [".$param['resultCode']."], ResDesc = [".$param['resultMsg']."], Params: ", $param);
			return false;
		}else{

			return true;
		}
	}

	public function parseResultJson($result) {
		$arr =  json_decode($result, true);
		$this->utils->debug_log("============================haohaopay parseResultJson Param: ", $arr);
		return $arr;
	}
}