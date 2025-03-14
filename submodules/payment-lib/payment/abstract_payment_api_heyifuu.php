<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
{
"heyifuu_apiVersion" : "1.0.0.0",
"heyifuu_platformID" : "",
"heyifuu_merchNo" : ""
}
 */
abstract class Abstract_payment_api_heyifuu extends Abstract_payment_api {
	const HEYIFUU_APINAME_PAY = "WEB_PAY_B2C";
	const HEYIFUU_CALLBACK = "PAY_RESULT_NOTIFY";
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const RETURN_FAILED_CODE = 'FAILED';
	private $info;
	public function __construct($params = null) {
		parent::__construct($params);
		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->info = $this->getInfoByEnv();
	}
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return HEYIFUU_PAYMENT_API;
	}
	public function getPrefix() {
		return 'heyifuu';
	}
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
		# read some parameters from config
		$paramNames = array('apiVersion', 'platformID', 'merchNo');
		$params = array();
		foreach ($paramNames as $p) {
			$params[$p] = $this->getSystemInfo("heyifuu_$p");
		}
		# other parameters
		$params['apiName'] = self::HEYIFUU_APINAME_PAY;
		$params['merchUrl'] = $this->getNotifyUrl($orderId);
		# order-related params
		# data format reference the code sample, normalPay.php
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$params['orderNo'] = $order->secure_id;
		$params['tradeDate'] = $orderDateTime->format('Ymd'); # test shows API only allows this format, no time info
		$params['amt'] = $this->convertAmountToCurrency($amount);
		$params['merchParam'] = ''; # No parameter needed
		$params['tradeSummary'] = lang('pay.deposit'); # this will be displayed on the payment page

		$direct_pay_extra_info = $order->direct_pay_extra_info;
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['bankCode'] = $extraInfo['bank'];
			}
		}

		$this->configParams($params, $order->direct_pay_extra_info);

		# sign param
		$params['signMsg'] = $this->sign($params);
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->info['url'],
			'params' => $params,
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
		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			return $result;
		}
		# Update order payment status and balance
		$this->CI->sale_order->startTrans();
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['accNo'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}
		$success = $this->CI->sale_order->endTransWithSucc();
		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
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
		# does all required fields exist?
		$requiredFields = array(
			'apiName', 'notifyTime', 'tradeAmt', 'merchNo', 'orderNo',
			'tradeDate', 'accNo', 'accDate', 'orderStatus', 'signMsg',
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}
		# is signature authentic?
		if (!$this->verify($fields, $fields['signMsg'])) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}
		$processed = true; # processed is set to true once the signature verification pass
		# check parameter values: orderStatus, tradeAmt, orderNo, merchNo
		# is payment successful?
		if ($fields['orderStatus'] !== '1') {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}
		# does amount match?
		if (
			$this->convertAmountToCurrency($order->amount) !==
			$this->convertAmountToCurrency(floatval($fields['tradeAmt']))
		) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}
		# does merchNo match?
		if ($fields['merchNo'] !== $this->getSystemInfo('heyifuu_merchNo')) {
			$this->writePaymentErrorLog("Merchant codes do not match, expected [" . $this->getSystemInfo('heyifuu_merchNo') . "]", $fields);
			return false;
		}
		# does order_no match?
		if ($fields['orderNo'] !== $order->secure_id) {
			$this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}
		# everything checked ok
		return true;
	}
	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	public function getBankListInfoFallback() {
		return array(
			array('label' => '中国工商银行', 'value' => 'ICBC'),
			array('label' => '中国农业银行', 'value' => 'ABC'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '中国建设银行', 'value' => 'CCB'),
			array('label' => '交通银行', 'value' => 'COMM'),
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '浦发银行', 'value' => 'SPDB'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '中国民生银行', 'value' => 'CMBC'),
			array('label' => '广发银行', 'value' => 'GDB'),
			array('label' => '中信银行', 'value' => 'CNCB'),
			array('label' => '中国光大银行', 'value' => 'CEB'),
			array('label' => '华夏银行', 'value' => 'HXB'),
			array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
			array('label' => '平安银行', 'value' => 'PAB')
		);
	}

	# -- Private functions --
	## After payment is complete, the gateway will invoke this URL asynchronously
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
	# -- private helper functions --
	# copied from code sample MobaoPay.class.php
	/**
	 * @name	准备签名/验签字符串
	 * @desc prepare urlencode data
	 * #@web_pay_b2c,wap_pay_b2c
	 * #apiName,apiVersion,platformID,merchNo,orderNo,tradeDate,amt,merchUrl,merchParam,tradeSummary
	 * #@pay_result_notify
	 * #apiName,notifyTime,tradeAmt,merchNo,merchParam,orderNo,tradeDate,accNo,accDate,orderStatus
	 */
	private function prepareSign($data) {
		if ($data['apiName'] == 'WEB_PAY_B2C') {
			$result = sprintf(
				"apiName=%s&apiVersion=%s&platformID=%s&merchNo=%s&orderNo=%s&tradeDate=%s&amt=%s&merchUrl=%s&merchParam=%s&tradeSummary=%s",
				$data['apiName'], $data['apiVersion'], $data['platformID'], $data['merchNo'], $data['orderNo'], $data['tradeDate'], $data['amt'], $data['merchUrl'], $data['merchParam'], $data['tradeSummary']
			);
			return $result;
		} else if ($data['apiName'] == 'PAY_RESULT_NOTIFY') {
			$result = sprintf(
				"apiName=%s&notifyTime=%s&tradeAmt=%s&merchNo=%s&merchParam=%s&orderNo=%s&tradeDate=%s&accNo=%s&accDate=%s&orderStatus=%s",
				$data['apiName'], $data['notifyTime'], $data['tradeAmt'], $data['merchNo'], $data['merchParam'], $data['orderNo'], $data['tradeDate'], $data['accNo'], $data['accDate'], $data['orderStatus']
			);
			return $result;
		}
		$array = array();
		foreach ($data as $key => $value) {
			array_push($array, $key . '=' . $value);
		}
		return implode($array, '&');
	}
	/**
	 * @name	生成签名
	 * @param	sourceData
	 * @return	签名数据
	 */
	public function sign($data) {
		# made it public for testing purpose
		$dataStr = $this->prepareSign($data);
		$signature = MD5($dataStr . $this->info['key']);
		return $signature;
	}
	/*
		 * @name	验证签名
		 * @param	data 原数据
		 * @param	signature 签名数据
		 * @return
	*/
	private function verify($data, $signature) {
		$mySign = $this->sign($data);
		if (strcasecmp($mySign, $signature) === 0) {
			return true;
		} else {
			return false;
		}
	}
}