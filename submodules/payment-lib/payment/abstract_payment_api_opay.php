<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * 摇钱树 OPAY
 *
 * * OPAY_PAYMENT_API, ID: 728
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://opay.arsomon.com:28443/vipay/reqctl.do
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_opay extends Abstract_payment_api {

    const ORDER_STATUS_SUCCESS = '100';
    const RETURN_SUCCESS_CODE = 'success';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	public function getBankType($direct_pay_extra_info) {
		return ''; # Default return empty banktype, redirect to bank selection page
	}
	//protected abstract function configParams(&$params, $direct_pay_extra_info);

	# -- override common API functions --
	## Constructs an URL so that the caller can redirect / invoke it to make payment through this API
	## See controllers/redirect.php for detail.
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
		$params['service'] = 'gateway'; # fixed value
		$params['mch_id'] = $this->getSystemInfo('account');
		$params['order_no'] = $order->secure_id;
		$params['goods'] = 'Topup';
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['bank_code'] = $this->getBankType($order->direct_pay_extra_info);
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log('=====================OPAY generatePaymentUrlForm params', $params);

		return $this->processPaymentUrlFormXML($params);
	}

	protected function processPaymentUrlFormPost($params) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $this->array2xml($params),
			'post' => true,
		);
	}

	protected function processPaymentUrlFormXML($params) {
		$this->CI->utils->debug_log('=====================OPAY post url', $this->getSystemInfo('url'));

		$post_xml_data = $this->array2xml($params);
		$this->CI->utils->debug_log('=====================OPAY post_xml_data', $post_xml_data);

		$curlConn = curl_init();
		$curlData = array();
		$curlData[CURLOPT_POST] = true;
		$curlData[CURLOPT_URL] = $this->getSystemInfo('url');
		$curlData[CURLOPT_RETURNTRANSFER] = true;
		$curlData[CURLOPT_TIMEOUT] = 120;
		$curlData[CURLOPT_POSTFIELDS] = $post_xml_data;
        $curlData[CURLOPT_HTTPHEADER] = [ "Content-type: text/xml;charset='utf-8'" ];
		curl_setopt_array($curlConn, $curlData);

		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYHOST, false);

		// Need to specify the referer when doing CURL submit. since we use redirect 2nd url, we can take the HTTP_HOST
		// curl_setopt($curlConn, CURLOPT_REFERER, "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

		$response = curl_exec($curlConn);
		$curlSuccess = (curl_errno($curlConn) == 0);

		$this->CI->utils->debug_log('=====================OPAY xml response', $curlSuccess, $response);

		$response = $this->parseResultXML($response);

		$this->CI->utils->debug_log('=====================OPAY parsed response', $response);

		$msg = lang('Invalidte API response');

		if($response['res_code'] == self::ORDER_STATUS_SUCCESS) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_URL,
				'url' => $response['url']."?".$this->array2xml($params)
			);
		}
		else {

			if(isset($response['res_msg'])) {
				$msg = $response['res_msg'];
			}

			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $msg
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

	## This will be called when user redirects back to our page from payment API
	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		if($source == 'server' ){
			$raw_post_data = file_get_contents('php://input', 'r');
			$this->CI->utils->debug_log("=====================OPAY raw_post_data", $raw_post_data);
			$params = $this->parseResultXML($raw_post_data);
			$this->CI->utils->debug_log("=====================OPAY params", $params);

			if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
				return $result;
			}
		}

		# Update order payment status and balance
		$success = true;

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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['fl_order_no'], '',null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($processed) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = 'Error';
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	## Validates whether the callback from API contains valid info and matches with the order
	## Reference: code sample, callback.php
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array('status', 'mch_id', 'sign', 'fl_order_no', 'order_no', 'amount');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================OPAY Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================OPAY Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['status'] != self::ORDER_STATUS_SUCCESS) {
			$this->writePaymentErrorLog('=====================OPAY Payment was not successful', $fields);
			return false;
		}

		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['amount']))
		) {
			$this->writePaymentErrorLog("=====================OPAY Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# Config in extra_info will overwrite this one
	public function getBankListInfoFallback() {
		return array(
			array('label' => '中国银行', 'value' => 'BOC_B2C'),
			array('label' => '中国工商银行', 'value' => 'ICBC_B2C'),
			array('label' => '中国建设银行', 'value' => 'CCB_B2C'),
			array('label' => '中国邮政储蓄银行', 'value' => 'PSBC_B2C'),
			array('label' => '民生银行', 'value' => 'CMBC_B2C'),
			array('label' => '光大银行', 'value' => 'CEB_B2C'),
			array('label' => '北京银行', 'value' => 'BCCB_B2C'),
			array('label' => '中信银行', 'value' => 'ECITIC_B2C'),
			array('label' => '上海银行', 'value' => 'SHB_B2C'),
			array('label' => '北京农商银行', 'value' => 'BJRCB_B2C'),
		);
	}

	# -- Private functions --
	# After payment is complete, the gateway will invoke this URL asynchronously
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

	# -- signatures --
	private function sign($params) {
		$signStr = $this->createSignStr($params);
		$sign = md5($signStr);
		return $sign;
	}

	private function createSignStr($params) {
		ksort($params);
		$signStr = '';
		foreach($params as $key => $value) {
			if(empty($value) || $key == 'sign') {
				continue;
			}
			$signStr .= "$key=$value&";
		}
		$signStr = $signStr."key=".$this->getSystemInfo('key');
		return $signStr;
	}

	private function validateSign($params) {
		$sign = $this->sign($params);
		if($params['sign'] == $sign){
			return true;
		}
		else{
			return false;
		}
	}


    #For XML
	public function array2xml($values){
		ksort($values);
		if (!is_array($values) || count($values) <= 0) {
		    return false;
		}
		$xml = "<xml>";
		foreach ($values as $key => $val) {
			if (is_numeric($val)) {
				$xml .= "<" . $key . ">" . $val . "</" . $key . ">";
			} else {
				$xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
			}
		}
		$xml .= "</xml>";
		$this->CI->utils->debug_log(' =========================OPAY array2xml', $xml);
		return $xml;
	}

	public function parseResultXML($resultXml) {
		$result = NULL;
		$obj = simplexml_load_string($resultXml);
		$arr = $this->CI->utils->xmlToArray($obj);
		$this->CI->utils->debug_log(' =========================OPAY parseResultXML', $arr);
		$result = $arr;

		return $result;
	}
}