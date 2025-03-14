<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * Lamic 莱米支付
 * https://www.lamic.cn
 *
 * LAMIC_PAYMENT_API, ID: 100
 *
 *
 * Required Fields:
 *
 * * URL
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: https://www.lamic.cn
 * * Extra Info
 * > {
 * >	"lamic_uid": "## account ##",
 * >	"lamic_pwd": "## password ##",
 * >	"lamic_des_key": "## des encryption key ##",
 * >	"bank_list" : {
 * >		"alipay" : "_json: { \"1\": \"ALIPAY\", \"2\": \"支付宝\" }",
 * >		"wxpay" : "_json: { \"1\": \"WXPAY\", \"2\": \"微信支付\" }",
 * >		"tenpay" : "_json: { \"1\": \"TENPAY\", \"2\": \"QQ钱包\" }"
 * >	},
 * >	"account_rotation" : {
 * >		"00:00-05:59" : [ "## account 1 ##", "## password 1 ##" ],
 * >		"06:00-11:59" : [ "## account 2 ##", "## password 2 ##" ],
 * >		"12:00-17:59" : [ "## account 3 ##", "## password 3 ##" ],
 * >		"18:00-23:59" : [ "## account 4 ##", "## password 4 ##" ]
 * > 	}
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_lamic extends Abstract_payment_api {
	public function __construct($params = null) {
		parent::__construct($params);
	}

	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return LAMIC_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lamic';
	}

	# -- override common API functions --
	## Constructs an URL so that the caller can redirect / invoke it to make payment through this API
	## See controllers/redirect.php for detail.
	##
	## Reference: documentation section 2.5
	##
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		# If order already exist, do not generate new one
		if(!empty($order->bank_order_id)) {
			# Display the QRCode scan page using stored info
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $order->account_image_filepath,
				'status_url' => $this->getReturnUrl($orderId),
				'status_success_key' => $order->external_order_id
			);
		}

		$params = array();
		$params['uid'] = $this->getUid();
		$params['token'] = $this->getToken();

		# Prepare request data
		$direct_pay_extra_info = $order->direct_pay_extra_info;
		# typical extra info: ["{\"bankTypeId\":\"34\",\"deposit_from\":\"40\",\"banktype\":\"ABC\",\"deposit_amount\":\"1\"}"]
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo) && array_key_exists('bank', $extraInfo)) {
				$params['type'] = $this->encrypt($extraInfo['bank']);
			}
		}

		$params['money'] = $this->encrypt($this->convertAmountToCurrency($amount));
		$params['payorderid'] = $this->encrypt($order->secure_id);

		$resp = $this->postForm('port/pay/ospaymoney', $params);

		if (!$resp->isResultTrue) {
			return array(
				'success' => false,
				'message' => $resp->resultMsg
			);
		} else {
			# Some external info is already available, update them here
			$this->CI->sale_order->updateExternalInfo($order->id,
				$resp->resultMsg, # externalOrderId = poid, we need to use this id to query API for payment status
				$resp->payId, # bankOrderId = payId, 为支付渠道订单 id
				null, null, null);
			$this->CI->sale_order->updateQRCodeLink($order->id, $resp->code_url);

			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $resp->code_url,
				# The QRCode page will poll status_url page for status_key. If status_success_key detected, redirect to status_url to
				# display payment success page
				'status_url' => $this->getReturnUrl($orderId),
				'status_success_key' => $resp->resultMsg
			);
		}
	}


	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	public function callbackFromServer($orderId, $params) {
		$this->CI->utils->error_log("Error: server callback not supported");
	}

	## This will be called when user redirects back to our page from payment API
	public function callbackFromBrowser($orderId, $params) {
		return $this->callbackFrom('browser', $orderId, array(), null);
	}

	# $source can be 'server' or 'browser'
	# Special: Lamic does not provide callback mechanism, so this callback is triggered manually and without params
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$this->utils->debug_log("Callback triggered for order [$orderId]");

		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		if (!$order) {
			return array('success' => false, 'next_url' => null, 'message' => lang('Payment order not found'));
		}

		# query order info from API
		$params = array();
		$params['uid'] = $this->getUid();
		$params['token'] = $this->getToken();
		$params['out_trade_no'] = $this->encrypt($order->external_order_id);

		$resp = $this->postForm('port/pay/mverify', $params);

		if (!$resp->isResultTrue) { # Payment is not done, stop here
			return array('success' => false, 'next_url' => null, 'message' => $resp->resultMsg);
		}

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
			# (update of external order id etc skipped)
			$this->CI->sale_order->approveSaleOrder($order->id, 'callback ' . $this->getPlatformCode(), false);
		}

		return array('success' => true, 'message' => $resp->resultMsg);
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# According to the information returned at the login stage, available payment gateways: alipay, wxpay, tenpay
	public function getBankListInfoFallback() {
		return array(
			array('label' => '支付宝', 'value' => 'alipay'),
			array('label' => '微信支付', 'value' => 'wxpay'),
			array('label' => 'QQ钱包', 'value' => 'tenpay'),
		);
	}

	# -- Private functions --
	// ## After payment is complete, the gateway will invoke this URL asynchronously
	// private function getNotifyUrl($orderId) {
	// 	return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	// }

	## Special: This API uses browser callback url to poll API for payment status
	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## Format the amount value for the API
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- helper functions --
	# Returns a unique timestamp when this is first called
	private $_reqTime;
	private function getReqTime() {
		if(!$this->_reqTime) {
			$this->_reqTime = date("YmdHis");
		}
		return $this->_reqTime;
	}

	# Returns encrypted uid
	private $_uid;
	private function getUid() {
		if(!$this->_uid) {
			$rawUid = $this->getRawUid() . '<reqtime>' .$this->getReqTime();
			$this->_uid = $this->encrypt($rawUid);
		}
		return $this->_uid;
	}
	private function getRawUid() {
		if($this->getCurrentAccountRotation()) {
			$currentAccount = $this->getCurrentAccountRotation();
			return $currentAccount[0];
		}
		return $this->getSystemInfo('lamic_uid');
	}

	# Returns encrypted pwd
	private $_pwd;
	private function getPwd() {
		if(!$this->_pwd) {
			$rawPwd = $this->getRawPwd() . '<reqtime>' .$this->getReqTime();
			$this->_pwd = $this->encrypt($rawPwd);
		}
		return $this->_pwd;
	}
	private function getRawPwd() {
		if($this->getCurrentAccountRotation()) {
			$currentAccount = $this->getCurrentAccountRotation();
			return $currentAccount[1];
		}
		return $this->getSystemInfo('lamic_pwd');
	}

	# Returns the current credentials based on time-based account rotation, or false if not configured / configured value not found
	private $_currentAccount = false;
	private function getCurrentAccountRotation() {
		if(!$this->_currentAccount){
			$accountRotationInfo = $this->getSystemInfo("account_rotation", false);
			$this->utils->debug_log('Curren rotation account info:', $accountRotationInfo);
			if(!$accountRotationInfo) {
				return false;
			}

			foreach($accountRotationInfo as $timespan => $credential) {
				list($startTime, $endTime) = explode('-', $timespan);
				$currentTime = time();
				if(strtotime($startTime) <= $currentTime && $currentTime <= strtotime($endTime)) {
					$this->utils->debug_log("Selected timespan: ", $timespan, $credential);
					$this->_currentAccount = $credential;
					break;
				}
			}
		}
		return $this->_currentAccount;
	}

	# Login using username and pwd, get the login token
	# Reference: Documentation section 2.1
	private $_token;
	public function getToken() {
		if(!$this->_token) {
			$resp = $this->postForm('port/pay/login', array(
				'uid' => $this->getUid(),
				'pwd' => $this->getPwd()
			));

			if($resp->isResultTrue) {
				$this->utils->debug_log("Login successful, token = [{$resp->data->token}]");
				$this->_token = $this->encrypt($resp->data->token);
			} else {
				$this->utils->error_log("Login failed, message = ", $resp->resultMsg);
				$this->_token = '';
			}
		}
		return $this->_token;
	}

	# Calls Lamic's encrypt URL to encrypt a string
	# Reference: Sample code
	# Note: Not in production use, for validation purpose only
	public function encryptAPI($str) {
		$resp = $this->postForm('port/pay/encrypt', array('str' => $str));
		return $resp->resultMsg;
	}

	# Perform DES encryption
	public function encrypt($str) {
		$mencrypt = new MEncrypt($this->getSystemInfo("lamic_des_key"));
		return $mencrypt->encrypt($str);
	}

	# Posts request form to the configured TLY URL. Handles signing too.
	public function postForm($gateway, $data) {
		try {
			$url = $this->getGatewayUrl($gateway);
			$this->CI->utils->debug_log('POST form for Lamic:', $url, $data);
			$response = \Httpful\Request::post($url)
				->method(\Httpful\Http::POST)
				->expectsJson()
				->body($data)
				->sendsType(\Httpful\Mime::FORM)
				->send();
			$this->CI->utils->debug_log('POST form response', $response->body);
			return $response->body;
		} catch (Exception $e) {
			$this->CI->utils->error_log('POST failed', $e);
		}
	}

	# Returns the full URL for a specific gateway function
	# e.g. return https://www.lamic.cn/port/pay/encrypt if the $gateway = 'port/pay/encrypt'
	private function getGatewayUrl($gateway) {
		$url = $this->getSystemInfo('url');
		$url = rtrim($url, '/');
		return $url.'/'.$gateway;
	}

}

# Modified class from sdpay; performs DES encryption in CBC mode
class MEncrypt{
	public $key;

	function __construct ($key)
	{
		if (empty($key)) {
			echo 'key and iv is not valid';
			exit();
		}
		$this->key = $key;
	}


	public function encrypt ($value)
	{
		$value = $this->PaddingPKCS7($value);
		$encrypted = @mcrypt_encrypt(MCRYPT_DES, $this->key, $value, MCRYPT_MODE_ECB);
		return strtoupper(bin2hex($encrypted));
	}

	private function PaddingPKCS7 ($data)
	{
		$block_size = @mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_CBC);
		$padding_char = $block_size - (strlen($data) % $block_size);
		$data .= str_repeat(chr($padding_char), $padding_char);
		return $data;
	}
}
