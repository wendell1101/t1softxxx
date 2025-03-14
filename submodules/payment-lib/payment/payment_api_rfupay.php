<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * RFUPAY 锐付
 * http://www.rfupay.com/
 *
 * RFUPAY_PAYMENT_API, ID: 62
 *
 * Required Fields:
 * * URL
 * * Key - md5 key
 * * Extra Info
 *
 * Field Values:
 * * URL: http://payment.rfupay.com/prod/commgr/control/inPayService
 * * Extra Info:
 * > {
 * > 	"rfupay_goods" : "##Order Prefix##",
 * > 	"rfupay_partyId" : "##Party ID##",
 * > 	"rfupay_accountId" : "##Account ID##",
 * > 	"bank_list" : {
 * >		"00004" : "_json: { \"1\": \"Industrial and Commercial Bank(ICBC)\", \"2\" : \"中国工商银行\" }",
 * >		"00021" : "_json: { \"1\": \"China Merchant Bank(CMB)\", \"2\" : \"招商银行\" }",
 * >		"00003" : "_json: { \"1\": \"China Construction Bank(CCB)\", \"2\" : \"中国建设银行\" }",
 * >		"00017" : "_json: { \"1\": \"Argicultural Bank of China(AGB)\", \"2\" : \"中国农业银行\" }",
 * >		"00083" : "_json: { \"1\": \"Bank of China(BOC)\", \"2\" : \"中国银行\" }",
 * >		"00051" : "_json: { \"1\": \"Postal Savings Bank of China\", \"2\" : \"中国邮政储蓄\" }",
 * >		"00032" : "_json: { \"1\": \"SPK Bank\", \"2\" : \"浦发银行\" }",
 * >		"00052" : "_json: { \"1\": \"Guangdong Development Bank (GDB)\", \"2\" : \"广发银行\" }",
 * >		"00057" : "_json: { \"1\": \"China Everbright Bank\", \"2\" : \"光大银行\" }",
 * >		"00013" : "_json: { \"1\": \"China Minsheng Banking Corp Ltd (CMBC)\", \"2\" : \"中国民生银行\" }",
 * >		"00054" : "_json: { \"1\": \"China Citic Bank\", \"2\" : \"中信银行\" }",
 * >		"00016" : "_json: { \"1\": \"Industrial Bank Co.Ltd\", \"2\" : \"兴业银行\" }",
 * >		"00006" : "_json: { \"1\": \"Ping An Bank\", \"2\" : \"平安银行\" }",
 * >		"00005" : "_json: { \"1\": \"Bank of Communications(BCOMM)\", \"2\" : \"交通银行\" }",
 * >		"00050" : "_json: { \"1\": \"Bank of Beijing\", \"2\" : \"北京银行\" }",
 * >		"00041" : "_json: { \"1\": \"Huaxia Bank\", \"2\" : \"华夏银行\" }",
 * >		"00101" : "_json: { \"1\": \"Bank of Tianjin\", \"2\" : \"天津银行\" }",
 * >		"00102" : "_json: { \"1\": \"Bank of Shanghai\", \"2\" : \"上海银行\" }",
 * >		"00103" : "_json: { \"1\": \"Bank of Ningbo\", \"2\" : \"寧波银行\" }",
 * >		"00104" : "_json: { \"1\": \"Bank of Nanjing\", \"2\" : \"南京银行\" }"
 * > 	}
 * > }
 *
 * _Note: Order Prefix should be TST for test order. OrderNo must start with this prefix._
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Payment_api_rfupay extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'checkok';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# -- implementation-specific functions --
	#public abstract function getPlatformCode();
	protected abstract function getAppType();
	protected abstract function getBankId($order);

	public function getPrefix() {
		return 'rfupay';
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
		$paramNames = array('goods', 'partyId', 'accountId');
		$params = array();
		foreach ($paramNames as $p) {
			$params[$p] = $this->getSystemInfo("rfupay_$p");
		}

		# other parameters
		$params['appType'] = $this->getAppType();
		$params['returnUrl'] = $this->getReturnUrl($orderId);
		$params['encodeType'] = 'Md5';

		# order-related params
		# data format reference the code sample, normalPay.php
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$params['orderNo'] = $params['goods'].$order->secure_id; # Append order prefix
		$params['orderAmount'] = $this->convertAmountToCurrency($amount);
		$params['cardType'] = '01'; # by default 人民幣轉帳卡. Reference: DEMO code, pay.php

		$params['bank'] = $this->getBankId($order);

		# sign param
		$params['signMD5'] = $this->signMD5($params);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true,
		);
	}

	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	## Reference: demo code, notification.php
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
		$this->utils->debug_log("callback received from [$source] for orderId [$orderId]", $params);

		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			return $result;
		} else if($source == 'browser') {
			## According to documentation, browser callback should not update payment status.
			## We need to rely on Async callback to update payment
			## As a result, browser callback processing stops here, return success if browser callback
			## does not indicate a immediate failure (which will be caught by checkCallbackOrder)
			$result['success'] = true;
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
			return $result;
		}

		# Prevent error as documentation does not specify these two fields as required
		if (!array_key_exists('tradeNo', $params)) {
			$params['tradeNo'] = '';
		}
		if (!array_key_exists('bankBillNo', $params)) {
			$params['bankBillNo'] = '';
		}

		# Update order payment status and balance
		$success=true;
		// $this->CI->sale_order->startTrans();

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
				$params['tradeNo'], $params['bankBillNo'],
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->CI->sale_order->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}
		// $success = $this->CI->sale_order->endTransWithSucc();

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

	## Validates whether the callback from API contains valid info and matches with the order
	## Reference: code sample, callback.php
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		# does all required fields exist?
		$requiredFields = array(
			'partyId', 'orderNo', 'orderAmount', 'goods', 'encodeType',
			'signMD5', 'succ', # 'tradeNo', 'bankBillNo'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->verifyMD5($fields, $fields['signMD5'])) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		# check parameter values: orderStatus, tradeAmt, orderNo, merchNo
		# is payment successful?
		if ($fields['succ'] != 'Y') {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		# does amount match?
		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['orderAmount']))
		) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# does credentials match?
		if ($fields['partyId'] != $this->getSystemInfo('rfupay_partyId')) {
			$this->writePaymentErrorLog("partyId does not match, expected [" . $this->getSystemInfo('rfupay_partyId') . "]", $fields);
			return false;
		}

		# does order_no match?
		if ($fields['orderNo'] != $fields['goods'].$order->secure_id) {
			$this->writePaymentErrorLog("Order IDs do not match, expected $fields[goods]+[$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	## functions to display banks etc on the cashier page
	# Reference: DEMO code, pay.php
	public function getBankListInfo() {
		return array(
			array('label' => '中国工商银行', 'value' => '00004'),
			array('label' => '招商银行', 'value' => '00021'),
			array('label' => '中国建设银行', 'value' => '00003'),
			array('label' => '中国农业银行', 'value' => '00017'),
			array('label' => '中国银行', 'value' => '00083'),
			array('label' => '中国邮政储蓄', 'value' => '00051'),
			array('label' => '浦发银行', 'value' => '00032'),
			array('label' => '广发银行', 'value' => '00052'),
			array('label' => '光大银行', 'value' => '00057'),
			array('label' => '中国民生银行', 'value' => '00013'),
			array('label' => '中信银行', 'value' => '00054'),
			array('label' => '兴业银行', 'value' => '00016'),
			array('label' => '平安银行', 'value' => '00006'),
			array('label' => '交通银行', 'value' => '00005'),
			array('label' => '北京银行', 'value' => '00050'),
			array('label' => '华夏银行', 'value' => '00041'),
			array('label' => '天津银行', 'value' => '00101'),
			array('label' => '上海银行', 'value' => '00102'),
			array('label' => '寧波银行', 'value' => '00103'),
			array('label' => '南京银行', 'value' => '00104'),
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
	# Reference: DEMO code, pay.php
	public function signMD5($data) {
		$pStr = "orderNo" . $this->sanitize($data['orderNo']) .
				"appType" . $this->sanitize(array_key_exists('appType', $data) ? $data['appType'] : '') .
				"orderAmount" . $this->sanitize($data['orderAmount']) .
				(array_key_exists('succ', $data) ? "succ$data[succ]" : "") .
				"encodeType" . $this->sanitize($data['encodeType']) .
				$this->getSystemInfo('key');
		$this->utils->debug_log("sign str", $pStr);
		return md5($pStr);
	}

	private function verifyMD5($data, $signature) {
		$mySign = $this->signMD5($data);
		if (strcasecmp($mySign, $signature) === 0) {
			return true;
		} else {
			return false;
		}
	}

	# Ref: DEMO code, return.php
	private function sanitize($data) {
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		return $data;
	}

	public function getOrderIdFromParameters($flds) {
		$orderId = null;

		$goods=$this->getSystemInfo("rfupay_goods");
		$orderNo=@$flds['orderNo'];
		if(!empty($orderNo)){

			// $orderId='';
			//remove prefix
			if(substr($orderNo, 0, strlen($goods))==$goods){
				$orderId=substr($orderNo, strlen($goods));
			}else{
				$orderId=substr($orderNo, 3);
			}
			// if(empty($orderId)){
			// 	//error
			// }
		}

		$this->utils->debug_log('orderNo: '.$orderNo.' to orderId: '.$orderId.' goods:'.$goods);

		//for fixed return url on browser
		if(!empty($orderId)){
			$secure_id = $orderId; // $flds['cartid'];

			$this->CI->load->model(array('sale_order'));
			$order = $this->CI->sale_order->getSaleOrderBySecureId($secure_id);

			$orderId = $order->id;

			$this->utils->debug_log('real order id', $orderId);
		}

		return $orderId;
	}

}
