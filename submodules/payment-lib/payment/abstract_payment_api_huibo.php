<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * HuiBo 汇博支付
 *
 * * HUIBO_PAYMENT_API, ID: 117
 * * HUIBO_ALIPAY_PAYMENT_API, ID: 118
 * * HUIBO_WEIXIN_PAYMENT_API, ID: 119
 *
 * Required Fields:
 *
 * * URL
 * * account
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: http://47.90.92.130:9899/HBConn/online
 * * Account: ## merchant account ##
 * * Extra Info
 * > {
 * >     "huibo_api_url" : "http://47.90.92.130:9899/HBConn/LFT",
 * >     "huibo_priv_key": "## path to merchant's private key ##",
 * >     "huibo_pub_key" : "## path to merchant's public key ##",
 * >     "huibo_api_pub_key" : "## path to API's public key ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_huibo extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'success';
	const ORDER_ID_PADDING = '0000000'; # pad secure_id (length 13) into length 20 (or above)

	public function __construct($params = null) {
		parent::__construct($params);
	}

	protected abstract function getChannelCode();

	protected abstract function getOrderCode();

	protected abstract function handlePaymentUrlForm($params, $context);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'huibo_priv_key', 'huibo_pub_key', 'huibo_api_pub_key');
        return $secretsInfo;
    }

	# Huibo uses fixed_process as callback URL, add this function to make sure we get the correct callback
	# Callback URI: /callback/fixed_process/117
	public function getOrderIdFromParameters($params) {
		$huiboOrderId = $params['orderId'];
		$secure_id = substr($huiboOrderId, 0, -strlen(self::ORDER_ID_PADDING));
		$this->utils->debug_log("Callback returned order ID [$huiboOrderId], trim and get system order id [$secure_id]");
		$this->CI->load->model(array('sale_order'));
		$order = $this->CI->sale_order->getSaleOrderBySecureId($secure_id);
		return $order->id;
	}

	# -- override common API functions --
	## Constructs an URL so that the caller can redirect / invoke it to make payment through this API
	## See controllers/redirect.php for detail.
	## Reference: Sample code UNIPAY.php, ScanPay.php; Documentation 3.4.1
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

		$priKey = $this->getPrivKey();
		$pubKey = $this->getAPIPubKey();

		# Prepare content for msg and data field, with encryption and sign
		$array_msg = array();
		# order ID min size 20, secure_id length = 13. Pad fixed length chars
		$orderSecureId = $order->secure_id.self::ORDER_ID_PADDING;
		$array_msg['orderId'] = $orderSecureId;
		$array_msg['amount'] = $this->convertAmountToCurrency($amount);
		$array_msg['rentunURL'] = $this->getReturnUrl($orderId);
		$msg = json_encode($array_msg);
		$base64msg = base64_encode($msg);
		openssl_sign($base64msg,$sign_info,$priKey,OPENSSL_ALGO_MD5);
		$signmsg = base64_encode($sign_info);


		$array_data = array();
		$array_data['account'] = $this->getSystemInfo("account");
		$array_data['channelCode'] = $this->getChannelCode();
		$array_data['msg'] = $base64msg;
		$array_data['orderCode'] = $this->getOrderCode();
		$data = json_encode($array_data);
		$data = base64_encode($data);
		$crypttext ='';
		while($data){
			$input = substr($data,0,117);
			$data = substr($data,117);
			$ok = openssl_public_encrypt($input, $encrypted, $pubKey);
			$crypttext.=$encrypted;
		}
		$data = base64_encode($crypttext);

		$params = array();
		$params['data'] = $data;
		$params['signature'] = $signmsg;

		$context=[
			'orderSecureId'=>$orderSecureId,
			'amount'=>$amount,
			'playerId'=>$playerId,
		];

		return $this->handlePaymentUrlForm($params, $context);
	}

	# Reference: Documentation section 5.3
	protected function getErrorMsg($result){
		$errorMsg = array(
			'000000' => '成功',
			'100034' => '解密失败',
			'100001' => '商户未审核',
			'100000' => '交易系统异常',
			'100089' => '订单号错误',
			'100032' => '订单确认系统异常',
			'100050' => '信息有误',
			'100099' => '等待支付',
			'100333' => '金额有误'
		);
		if(array_key_exists($result, $errorMsg)) {
			return $errorMsg[$result];
		}
		return 'Unknown error msg: '.$result;
	}

	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	## Reference: sample code, callback.php
	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	## This will be called when user redirects back to our page from payment API
	## As we are relying on server callback, simply return success here
	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		#return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
		return array('success' => true, 'next_url' => $this->getPlayerBackUrl());
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			return array('success' => false, 'return_error' => $processed ? self::RETURN_SUCCESS_CODE : '');
		}

		# Update order payment status and balance
		$success=true;

		# Update player balance based on order status
		# if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
		$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
		if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
			$this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
			if ($order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
				$this->CI->sale_order->setStatusToSettled($orderId);
			}
		} else {
			# update player balance
			$this->CI->sale_order->updateExternalInfo($order->id, $params['respInfo'], null, null, null, $response_result_id);
			$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : '';
		}

		return $result;
	}

	## Validates whether the callback from API contains valid info and matches with the order
	## Reference: Sample code HBNotify.php
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		# validate callback data signature
		$pubKey= $this->getAPIPubKey();
		$orderId = $fields['orderId'];
		$respCode = $fields['respCode'];
		$respInfo = $fields['respInfo'];
		$amount = $fields['amount'];
		$signature = $fields['signature'];
		$signaturemsg = $orderId.$respCode.$respInfo.$amount;
		$signature2 = base64_decode($signature);
		$signature2 = base64_decode($signature2);
		if(!openssl_verify($signaturemsg, $signature2, $pubKey, OPENSSL_ALGO_MD5 )){
			$this->CI->utils->error_log("Callback signature validation failure");
			return false;
		}

		# signature validation success
		$processed = true;

		if($respCode != "0000"){
			$this->CI->utils->error_log("API returned failure, message: ", $this->getErrorMsg($respCode));
			return false;
		}

		if($this->convertAmountToCurrency($order->amount) != $amount){
			$this->CI->utils->error_log("wrong amount", $fields);
			return false;
		}

		# callback is ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
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

	## Format the amount value for the API, unit: cent
	protected function convertAmountToCurrency($amount) {
		return number_format($amount * 100, 0, '.', '');
	}

	# Returns the private key generated by merchant
	protected function getPrivKey() {
		$privKey=file_get_contents($this->getSystemInfo('huibo_priv_key'));
		return openssl_pkey_get_private($privKey);
	}

	# protected the public key generated by merchant
	protected function getPubKey() {
		return openssl_get_publickey(file_get_contents($this->getSystemInfo('huibo_pub_key')));
	}

	# Returns the public key provided by API
	protected function getAPIPubKey() {
		return openssl_get_publickey(file_get_contents($this->getSystemInfo('huibo_api_pub_key')));
	}
}
