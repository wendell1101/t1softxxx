<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * SDPay_2ND 速达支付 2ND
 * http://www.sdsystem.hk
 *
 * SDPAY2ND_UNIONPAY_PAYMENT_API, ID: 122
 * SDPAY2ND_ALIPAY_PAYMENT_API, ID: 123
 * SDPAY2ND_WECHATPAY_PAYMENT_API, ID: 124
 *
 *
 * Required Fields:
 *
 * * URL
 * * Key
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: ## SDPay payment URL ##
 * * Key: ## SDPay MD5 key ##
 * * Extra Info
 * > {
 * >     "sdpay_merchantId": "## merchant ID ##",
 * >     "sdpay_key1": "## RSA key 1 ##",
 * >     "sdpay_key2": "## RSA key 2 ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_sdpay2nd extends Abstract_payment_api {
	const CURRENCY_CNY = 1;
	const CURRENCY_USD = 2;
	const PAYMENT_RESULT_SUCCESS = 1;
	const PAYMENT_RESULT_FAILED = 2;
	const PAYMENT_RESULT_INVALID = 3;
	const RESULT_CODE_SUCCESS = 100;
	const RESULT_CODE_MERCHANT_ID_ERROR = 101;
	const RESULT_CODE_WRONG_CHAR = 102;
	const RESULT_CODE_KEY_FAIL = 103;
	const RESULT_CODE_OP_ERROR = 104;
	const RESULT_CODE_SYS_ERROR = 105;
	const RESULT_CODE_WRONG_USERNAME = 106;

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# -- functions that can be overridden by child classes --
	protected function getPaymentUrl() {
		if( !empty( $this->getSystemInfo('h5_api_url') ) ) {
			return $this->getSystemInfo('h5_api_url');
		}

		return $this->getSystemInfo('url');
	}

	protected function getCmdCode() {
		if( !empty($this->getSystemInfo('h5_api_url')) ) {
			return '6011';
		}

		return '6006';
	}

	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
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

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$playerId = $order->player_id;

		# Reference: 2ND Integration Doc section 3.2
		$cmdCode = $this->getCmdCode();
		$merchantId = $this->getSystemInfo('sdpay_merchantId');
		$orderSecureId = $order->secure_id;

		$this->CI->load->model('player');
		$player = $this->CI->player->getPlayerById($playerId);
		$username = $player['username'];
		$userrealnameXml = (!empty($this->getSystemInfo('h5_api_url'))) ? "<userrealname>$username</userrealname>" : "";

		$money = $this->convertAmountToCurrency($amount);
		$unit = self::CURRENCY_CNY;
		$time = date('Y-m-d H:i:s');

		$backurl = $this->getNotifyUrl($orderId);
		$backurlbrowser = $this->getReturnUrl($orderId);

		# Construct the XML content for the request
		$requestXml = '<?xml version="1.0" encoding="utf-8" ?>'.
			"<message>".
				"<cmd>$cmdCode</cmd>".
				"<merchantid>$merchantId</merchantid>".
				"<language>zh-cn</language>".
				"<userinfo>".
					"<order>$orderSecureId</order>".
					"<username>$username</username>".
					$userrealnameXml.
					"<money>$money</money>".
					"<unit>$unit</unit>".
					"<time>$time</time>".
					"<remark>Deposit</remark>".
					"<backurl>$backurl</backurl>".
					"<backurlbrowser>$backurlbrowser</backurlbrowser>".
				"</userinfo>".
			"</message>";

		$this->utils->error_log("====================sdpay requestXml", $requestXml);
		# Encrypt the xml request data, ref: documentation section 3.3
		$md5key = $this->getSystemInfo('key');
		$md5encrypt = md5($requestXml.$md5key);
		$data = $requestXml.$md5encrypt;

		$params = array();
		$params['pid'] = $merchantId;
		$params['des'] = $this->encrypt($data);

		$this->utils->error_log("====================sdpay getPaymentUrl", $this->getPaymentUrl());

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getPaymentUrl(),
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
	## Simply display a success page, we use server callback to determine the actual status
	public function callbackFromBrowser($orderId, $params) {
		return array('success' => true, 'next_url' => $this->getPlayerBackUrl());
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false);

		# Decrypt callback info, reference: documentation section 5.3
		$encryptedData = $params['res'];
		$decryptedData = $this->decrypt($encryptedData);
		$md5Data = substr($decryptedData, -32); # get the last 32 char
		$xmlData = substr($decryptedData, 0, -32); # remove the md5 chars


		# Verify the signature
		if(!$this->verify($xmlData, $md5Data)) {
			$this->utils->error_log("Signature verification failed for MD5 data: [$md5Data], XML data: [$xmlData]");
			$result = array('success' => false);
			$result['return_error'] = $this->getResponseXml($params, self::RESULT_CODE_KEY_FAIL);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$params = $this->parseResultXML($xmlData);
		$resultCode = self::RESULT_CODE_SYS_ERROR;

		if (!$order || !$this->checkCallbackOrder($order, $params, $resultCode)) {
			$this->utils->error_log("No order or checkCallbackOrder fail, resultCode:", $resultCode);
			$result = array('success' => false);
			$result['return_error'] = $this->getResponseXml($params, $resultCode);
			return $result;
		}

		# Update order payment status and balance
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
			$this->CI->sale_order->updateExternalInfo($order->id,
				$params['order'], 'Third Party Payment (No Bank Order Number)', # no info available
				null, null, $response_result_id);
			$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
		}

		$result = array('success' => true);
		$result['message'] = $this->getResponseXml($params, self::RESULT_CODE_SUCCESS);
		return $result;
	}

	# Returns the response XML used as return value for callback
	# Definition for resultCode can be found at the top of this file as constants
	private function getResponseXml($params, $resultCode) {
		# construct the XML response
		$merchantId = $params['merchantid'];
		$orderId = $params['order'];
		$username = $params['username'];

		$responseXml = <<< EOD
<?xml version="1.0" encoding="utf-8" ?>
<message>
	<cmd>60071</cmd>
	<merchantid>$merchantId</merchantid>
	<order>$orderId</order>
	<username>$username</username>
	<result>$resultCode</result>
</message>
EOD;

		return $responseXml;
	}

	## Validates whether the callback from API contains valid info and matches with the order
	## Reference: Documentation section 4
	## Decryption and validation is before this check
	private function checkCallbackOrder($order, $fields, &$resultCode = self::RESULT_CODE_SUCCESS) {
		# does all required fields exist?
		$requiredFields = array(
			'merchantid', 'money', 'result'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				$resultCode = self::RESULT_CODE_OP_ERROR;
				return false;
			}
		}

		if ($fields['result'] != self::PAYMENT_RESULT_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			$resultCode = self::RESULT_CODE_SUCCESS;
			return false;
		}

		if($fields['merchantid'] != $this->getSystemInfo('sdpay_merchantId')) {
			$this->writePaymentErrorLog("Merchant IDs do not match", $fields);
			$resultCode = self::RESULT_CODE_MERCHANT_ID_ERROR;
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['money']) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			$resultCode = self::RESULT_CODE_SYS_ERROR;
			return false;
		}

		if ($fields['order'] != $order->secure_id) {
			$this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $fields);
			$resultCode = self::RESULT_CODE_SYS_ERROR;
			return false;
		}

		# everything checked ok
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

	## Format the amount value for the API
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- private helper functions --
	protected function encrypt($data) {
		$mencrypt = new MEncrypt($this->getSystemInfo('sdpay_key1'), $this->getSystemInfo('sdpay_key2'));
		$encryptedData = $mencrypt->encryptData($data);
		return $encryptedData;
	}

	# for testing purpose
	public function encryptWithKey($data, $key1, $key2) {
		$mencrypt = new MEncrypt($key1, $key2);
		$encryptedData = $mencrypt->encryptData($data);
		return $encryptedData;
	}

	protected function decrypt($encryptedData) {
		$decrypt = new Decrypt($this->getSystemInfo('sdpay_key1'), $this->getSystemInfo('sdpay_key2'));
		$xml = $decrypt->decryptData($encryptedData);
		$this->utils->debug_log("Decrypted data", $xml);
		return $xml;
	}

	protected function parseResultXML($resultXml) {
		$arr = (array)simplexml_load_string($resultXml);
		$this->utils->debug_log("Parsed XML [$resultXml] into array", $arr);
		return $arr;
	}

	protected function verify($xmlData, $md5Data) {
		$md5key = $this->getSystemInfo('key');
		$md5encrypt = md5($xmlData.$md5key);

		if (strcasecmp($md5encrypt, $md5Data) === 0) {
			return true;
		} else {
			return false;
		}
	}
}

# Reference: Code sample
class MEncrypt{
	public $key;
	public $iv;

	function __construct($key, $iv)
	{
		if (empty($key) || empty($iv)) {
			echo 'key and iv is not valid';
			exit();
		}
		$this->key = $key;
		$this->iv = $iv;
	}

	public function encryptData($value){
		$md5hash = md5($this->GetMac().date("Y-m-d h:m:s"));
		$value=$value.$md5hash;
		return $this->encrypt($value);
	}

	public function decryptData($value){
		$des = $this->decrypt($value);
		return substr($des, 0,strlen($des)-32);
	}

	public function encrypt($value)
	{
		$td = @mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
		$iv = base64_decode($this->iv);
		$value = $this->PaddingPKCS7($value);
		$key = base64_decode($this->key);
		@mcrypt_generic_init($td, $key, $iv);
		$ret = base64_encode(@mcrypt_generic($td, $value));
		@mcrypt_generic_deinit($td);
		@mcrypt_module_close($td);
		return $ret;
	}

	public function decrypt($value)
	{
		$td = @mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
		$iv = base64_decode($this->iv);
		$key = base64_decode($this->key);
		@mcrypt_generic_init($td, $key, $iv);
		$ret = trim(@mdecrypt_generic($td, base64_decode($value)));
		$ret = $this->UnPaddingPKCS7($ret);
		@mcrypt_generic_deinit($td);
		@mcrypt_module_close($td);
		return $ret;
	}

	private function PaddingPKCS7($data)
	{
		$block_size = @mcrypt_get_block_size('tripledes', 'cbc');
		$padding_char = $block_size - (strlen($data) % $block_size);
		$data .= str_repeat(chr($padding_char), $padding_char);
		return $data;
	}

	private function UnPaddingPKCS7($text)
	{
		$pad = ord($text[strlen($text) - 1]);
		if ($pad > strlen($text)) {
			return false;
		}
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
			return false;
		}
		return substr($text, 0, - 1 * $pad);
	}

	function GetMd5Hash($input){
		return sha1($input);
	}

	function GetMac(){
		return	date("Y-m-d h:m:s").rand();
	}
}

# Reference: Code sample
class Decrypt{
	public $key;
	public $iv;

	function __construct ($key, $iv)
	{
		if (empty($key) || empty($iv)) {
			echo 'key and iv is not valid';
			exit();
		}
		$this->key = $key;
		$this->iv = $iv;
	}


	public  function decryptData($value){
		$des = $this->decrypt($value);
		return substr($des, 0,strlen($des)-32);
	}
	public function decrypt ($value)
	{
		$td = @mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
		$iv = base64_decode($this->iv);
		$key = base64_decode($this->key);
		@mcrypt_generic_init($td, $key, $iv);
		$ret = trim(@mdecrypt_generic($td, base64_decode($value)));
		$ret = $this->UnPaddingPKCS7($ret);
		@mcrypt_generic_deinit($td);
		@mcrypt_module_close($td);
		return $ret;
	}

	private function PaddingPKCS7 ($data)
	{
		$block_size = @mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_CBC);
		$padding_char = $block_size - (strlen($data) % $block_size);
		$data .= str_repeat(chr($padding_char), $padding_char);
		return $data;
	}

	private function UnPaddingPKCS7($text)
	{
		$pad = ord($text[strlen($text) - 1]);
		if ($pad > strlen($text)) {
			return false;
		}
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
			return false;
		}
		return substr($text, 0, - 1 * $pad);
	}
	function GetMd5Hash($input){
		return sha1($input);
	}
	function GetMac(){
		return	date("Y-m-d h:m:s").rand();
	}
}
