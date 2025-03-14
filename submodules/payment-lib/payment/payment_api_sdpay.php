<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * SDPay 速达支付
 * http://www.sdpaysoftware.com/
 *
 * SDPAY_PAYMENT_API, ID: 54
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
 * * URL: https://deposit2.sdapayapi.com/9001/ApplyForABank.asmx?wsdl
 * * Extra Info
 * > {
 * >     "sdpay_LoginAccount": "##account##",
 * >     "sdpay_key1": "##key 1##",
 * >     "sdpay_key2": "##key 2##",
 * >     "bank_list": {
 * >         "ALIPAY": "_json: { \"1\": \"ALIPAY\", \"2\": \"支付宝\" }"
 * >     },
 * >     "alipay_url": "https://www.alipay.com/"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sdpay extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = '<span id="resultLable">Success</span>';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return SDPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'sdpay';
	}

	# -- override common API functions --
	# Used by callback/fixed_browser to decide order ID
	public function getOrderIdFromParameters($params){
		# Decrypt callback info
		$encryptedData = $params['HiddenField1'];
		$params = $this->decrypt($encryptedData);
		$this->utils->debug_log("Decrypted data", $params);
		return $params['sPlayersId'];  # During SOAP submission, we used this field to store the $orderId variable
	}

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

		$params = array();
		$params['LoginAccount'] = $this->getSystemInfo("sdpay_LoginAccount");

		$req = array(); # Request parameters, needs to be converted into XML and encrypted

		# Prepare request data
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$direct_pay_extra_info = $order->direct_pay_extra_info;
		# typical extra info: ["{\"bankTypeId\":\"34\",\"deposit_from\":\"40\",\"banktype\":\"ABC\",\"deposit_amount\":\"1\"}"]
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$req['sBank1'] = $extraInfo['banktype'];
			}
		}

		$req['storeOrderId'] = $order->secure_id;
		# query player name using player ID
		$this->CI->load->model('player');
		$player = $this->CI->player->getPlayerById($playerId);
		if($player) {
			if($player['language'] == 'Chinese'){ # temporarily determine full name using langauge
				$req['sName'] = $player['lastName'].$player['firstName'];
			} else {
				$req['sName'] = $player['firstName'] . ' ' . $player['lastName'];
			}
		}
		$req['sPrice'] = $this->convertAmountToCurrency($amount);
		$req['sPlayersId'] = $orderId;  # Note: We use this playerId field to store the $orderId

		# encrypt the request and send using SoapClient
		# Reference: Sample code
		$success = true;
		$message = '';
		try {
			$soap = new SoapClient($this->getSystemInfo('url'), array('trace' => 1));
			$result = $soap->ApplyBank($this->getSystemInfo('sdpay_LoginAccount'), $this->encrypt($req));
		} catch (SoapFault $exception) {
			$this->CI->utils->error_log("SoapFault", $exception);
			$success = false;
			$message = "SoapFault";
		}

		$this->CI->utils->debug_log("SoapResult", $result);

		# If SOAP call fail, $result will be a return code e.g. -10
		$message = $this->getErrorMsg($result);
		if($message){
			$success = false;
			$data = array();
		} else {
			$resp = $this->decrypt($result);
			# $resp is now an array containing key-values. define a few keys to check for.
			$compareKeys = array('storeOrderId', 'sPrice', 'sPlayersId');
			$respValid = true;
			foreach($compareKeys as $key) {
				if($resp[$key] != $req[$key]) {
					$this->utils->error_log("Response error in [$key]: expected [$req[$key]], found [$resp[$key]]");
					$message = "[$key] mismatch";
					$respValid = false;
					$success = false;
					$data = array();
					break;
				}
			}

			if($respValid) {
				$bankName = $this->getBankName($resp['eBank']);
				$data['Beneficiary Bank'] = $bankName;
				# Reference: documentation 6.1
				if(strtoupper($resp['eBank']) == 'ABC') {
					$data['Beneficiary Bank Branch'] = $resp['eBank2'];
				}
				if(strtoupper($resp['eBank']) == 'CMB') {
					$data['Beneficiary Bank City'] = $resp['eBank2'];
				}
				#$data['Depositor Name'] = $resp['sName']; # To avoid confusion, this field is hidden from user
				$data['Beneficiary Account'] = @$resp['eBankAccount'];
				$data['Beneficiary Name'] = @$resp['eName'];
				$data['Deposit Amount'] = $resp['ePrice'];
				$data['Email'] = @$resp['email'];

				$paymentUrl = $this->getPaymentUrl($resp['eBank']);
				if(!empty($paymentUrl)) {
					$data['Payment Link'] = "<a href='".$paymentUrl."'>".lang('Login to ').$bankName.'</a>';
				}
			}
		}

		return array(
			'success' => $success,
			'type' => self::REDIRECT_TYPE_STATIC,
			'title' => lang('payment.type.'.$this->getPlatformCode()),
			'data' => $data,
		);
	}

	# Reference: Documentation section 8.2
	private function getErrorMsg($result){
		$errorMsg["-1"] =  "未知原因";
		$errorMsg["-10"] = "无收款银行";
		$errorMsg["-11"] = "无收款卡";
		$errorMsg["-12"] = "密钥错误";
		$errorMsg["-13"] = "登录账号长度等于0";
		$errorMsg["-14"] = "登录账号为null";
		$errorMsg["-15"] = "申请的玩家同名";
		$errorMsg["-16"] = "存款金额不能小于等于0元";
		if(array_key_exists($result, $errorMsg)) {
			return $errorMsg[$result];
		}
	}

	private function getBankName($bankCode){
		$bankList = $this->getBankListInfo();
		foreach($bankList as $aBankArray){
			if(strtoupper($bankCode) == $aBankArray['value']) {
				return $aBankArray['label'];
			}
		}
	}

	private function getPaymentUrl($bankCode) {
		return $this->getSystemInfo(strtolower($bankCode).'_url');
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
		$this->CI->utils->error_log("Error: browser callback not supported");
		return;
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		# Decrypt callback info
		$encryptedData = $params['HiddenField1'];
		$params = $this->decrypt($encryptedData);

		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			return array('success' => false, 'return_error' => $processed ? self::RETURN_SUCCESS_CODE : '');
		}

		# Update order payment status and balance
		$success=true;
		// $this->CI->sale_order->startTrans();

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
				$params['accNo'], '', # only platform order id exist. Reference: documentation section 2.4.2
				null, null, $response_result_id);
			$this->CI->sale_order->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
		}
		// $success = $this->CI->sale_order->endTransWithSucc();

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : '';
		}

		return $result;
	}

	## Validates whether the callback from API contains valid info and matches with the order
	## Reference: Documentation section 3.3, 8.1
	## Assuming that the callback is the same structure as returned from payment submit
	## Note: The return value is XML, need to decrypt and transform to array
	## Decryption and validation is before this check
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		# does all required fields exist?
		$requiredFields = array(
			'storeOrderId', 'sPrice', 'state'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		$processed = true; # processed is set to true as the decryption already done

		# check parameter values: orderStatus, tradeAmt, orderNo, merchNo
		# is payment successful?
		# 0代表【未处理】,  1代表【成功】、  2代表【失败】
		if ($fields['state'] != '1') {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		# does amount match?
		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['sPrice']))
		) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# does order_no match?
		if ($fields['storeOrderId'] != $order->secure_id) {
			$this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	public function getPlayerInputInfo() {
		return array(
			array('name' => 'banktype', 'type' => 'list', 'label_lang' => 'pay.bank',
				'list' => $this->getBankList(), 'list_tree' => $this->getBankListTree()),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	# Reference: documentation, section 9.1
	// public function getBankListInfo() {
	// 	# Currently only ALIPAY has bank card configured
	// 	return array(
	// 		// array('label' => '工商银行', 'value' => 'ICBC'),
	// 		// array('label' => '农业银行', 'value' => 'ABC'),
	// 		// array('label' => '招商银行', 'value' => 'CMB'),
	// 		// array('label' => '建设银行', 'value' => 'CCB'),
	// 		// array('label' => '中国银行', 'value' => 'BOC'),
	// 		// array('label' => '中信银行', 'value' => 'ECITIC'),
	// 		// array('label' => '交通银行', 'value' => 'COMM'),
	// 		// array('label' => '兴业银行', 'value' => 'CIB'),
	// 		// array('label' => '浦发银行', 'value' => 'SPDB'),
	// 		// array('label' => '深圳发展银行', 'value' => 'SDB'),
	// 		// array('label' => '广东发展银行', 'value' => 'GDB'),
	// 		// array('label' => '北京银行', 'value' => 'BOB'),
	// 		// array('label' => '广州银行', 'value' => 'GZB'),
	// 		// array('label' => '浙商银行', 'value' => 'CZB'),
	// 		// array('label' => '中国邮政储蓄', 'value' => 'PSBC'),
	// 		// array('label' => '东莞银行', 'value' => 'BOD'),
	// 		// array('label' => '光大银行', 'value' => 'CEB'),
	// 		// array('label' => '杭州银行', 'value' => 'HZB'),
	// 		// array('label' => '华夏银行', 'value' => 'HXB'),
	// 		// array('label' => '民生银行', 'value' => 'CMBC'),
	// 		// array('label' => '上海银行', 'value' => 'BOS'),
	// 		// array('label' => '渤海银行', 'value' => 'CBHB'),
	// 		// array('label' => '北京农商', 'value' => 'BJRCB'),
	// 		// array('label' => '宁波银行', 'value' => 'NBCB'),
	// 		array('label' => '支付宝', 'value' => 'ALIPAY'),
	// 		// array('label' => '财付通', 'value' => 'TENPAY'),
	// 	);
	// }
	/* == extra_info config ==
		"bank_list": {
			"ICBC" : "_json: { \"1\": \"ICBC\", \"2\": \"工商银行\" }",
			"ABC" : "_json: { \"1\": \"ABC\", \"2\": \"农业银行\" }",
			"CMB" : "_json: { \"1\": \"CMB\", \"2\": \"招商银行\" }",
			"CCB" : "_json: { \"1\": \"CCB\", \"2\": \"建设银行\" }",
			"BOC" : "_json: { \"1\": \"BOC\", \"2\": \"中国银行\" }",
			"ECITIC" : "_json: { \"1\": \"ECITIC\", \"2\": \"中信银行\" }",
			"COMM" : "_json: { \"1\": \"COMM\", \"2\": \"交通银行\" }",
			"CIB" : "_json: { \"1\": \"CIB\", \"2\": \"兴业银行\" }",
			"SPDB" : "_json: { \"1\": \"SPDB\", \"2\": \"浦发银行\" }",
			"SDB" : "_json: { \"1\": \"SDB\", \"2\": \"深圳发展银行\" }",
			"GDB" : "_json: { \"1\": \"GDB\", \"2\": \"广东发展银行\" }",
			"BOB" : "_json: { \"1\": \"BOB\", \"2\": \"北京银行\" }",
			"GZB" : "_json: { \"1\": \"GZB\", \"2\": \"广州银行\" }",
			"CZB" : "_json: { \"1\": \"CZB\", \"2\": \"浙商银行\" }",
			"PSBC" : "_json: { \"1\": \"PSBC\", \"2\": \"中国邮政储蓄\" }",
			"BOD" : "_json: { \"1\": \"BOD\", \"2\": \"东莞银行\" }",
			"CEB" : "_json: { \"1\": \"CEB\", \"2\": \"光大银行\" }",
			"HZB" : "_json: { \"1\": \"HZB\", \"2\": \"杭州银行\" }",
			"HXB" : "_json: { \"1\": \"HXB\", \"2\": \"华夏银行\" }",
			"CMBC" : "_json: { \"1\": \"CMBC\", \"2\": \"民生银行\" }",
			"BOS" : "_json: { \"1\": \"BOS\", \"2\": \"上海银行\" }",
			"CBHB" : "_json: { \"1\": \"CBHB\", \"2\": \"渤海银行\" }",
			"BJRCB" : "_json: { \"1\": \"BJRCB\", \"2\": \"北京农商\" }",
			"NBCB" : "_json: { \"1\": \"NBCB\", \"2\": \"宁波银行\" }",
			"ALIPAY" : "_json: { \"1\": \"ALIPAY\", \"2\": \"支付宝\" }",
			"TENPAY" : "_json: { \"1\": \"TENPAY\", \"2\": \"财付通\" }"
		}
	*/

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
	public function encrypt($data) {
		$xml = "<t_savingApply><id>0</id><storeOrderId>".$data['storeOrderId']."</storeOrderId><sBank1>".$data['sBank1']."</sBank1><sName>".$data['sName']."</sName><sPrice>".$data['sPrice']."</sPrice><sPlayersId>".$data['sPlayersId']."</sPlayersId></t_savingApply>";

		$this->utils->debug_log("Encrypting xml", $xml);

		$mencrypt = new MEncrypt($this->getSystemInfo('sdpay_key1'), $this->getSystemInfo('sdpay_key2'));
		$encryptedData = $mencrypt->encryptData($xml);
		return $encryptedData;
	}

	public function decrypt($encryptedData) {
		$decrypt = new Decrypt($this->getSystemInfo('sdpay_key1'), $this->getSystemInfo('sdpay_key2'));
		$xml = $decrypt->decryptData($encryptedData);

		$this->utils->debug_log("Decrypted xml", $xml);

		$data = $this->parseResultXML($xml);

		$this->utils->debug_log("Decrypted data", $data);
		return $data;
	}

	private function parseResultXML($resultXml) {
		$arr = (array)simplexml_load_string($resultXml);
		$this->utils->debug_log("Parsed XML [$resultXml] into array", $arr);
		return $arr;
	}

	private function verify($data, $signature) {
		$mySign = $this->sign($data);
		if (strcasecmp($mySign, $signature) === 0) {
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

	function __construct ($key, $iv)
	{
		if (empty($key) || empty($iv)) {
			echo 'key and iv is not valid';
			exit();
		}
		$this->key = $key;
		$this->iv = $iv;
	}


	public  function encryptData($value){
		$md5hash = md5($this->GetMac().date("Y-m-d h:m:s"));
		$value=$value.$md5hash;

		return $this->encrypt($value);
	}
	public  function decryptData($value){
		$des = $this->decrypt($value);
		return substr($des, 0,strlen($des)-32);
	}
	public function encrypt ($value)
	{
		$td = @mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
		$key = base64_decode($this->key);
		$iv = base64_decode($this->iv);
		$value = $this->PaddingPKCS7($value);
		@mcrypt_generic_init($td, $key, $iv);
		$ret = base64_encode(@mcrypt_generic($td, $value));
		@mcrypt_generic_deinit($td);
		@mcrypt_module_close($td);
		return $ret;
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
