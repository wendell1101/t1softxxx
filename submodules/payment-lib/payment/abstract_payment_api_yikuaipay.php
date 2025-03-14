<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * YIKUAIPAY  壹快付
 *
 * * YIKUAIPAY_PAYMENT_API, ID: 610
 * * YIKUAIPAY_ALIPAY_API, ID: 611
 * * YIKUAIPAY_WEIXIN_PAYMENT_API, ID: 612
 * * YIKUAIPAY_QQPAY_PAYMENT_API, ID: 613
 * * YIKUAIPAY_BANK_H5_PAYMENT_API, ID: 743
 * * YIKUAIPAY_ALIPAY_H5_PAYMENT_API, ID: 744
 * * YIKUAIPAY_WEIXIN_H5_PAYMENT_API, ID: 745
 * * YIKUAIPAY_QUICKPAY_PAYMENT_API, ID: 5003
 * *
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_yikuaipay extends Abstract_payment_api {
	const PRODUCT_BANK_WEP   = '1';
	const PRODUCT_BANK_WAP   = '2';
	const PRODUCT_WEIXIN_QR  = '3';
	const PRODUCT_ALIPAY_QR  = '4';
	const PRODUCT_QQPAY      = '5';
	const PRODUCT_WEIXIN_SDK = '6';
	const PRODUCT_ALIPAY_SDK = '7';
	const PRODUCT_QUICKPAY   = '8';

    const ORDER_STATUS_SUCCESS  = '1';
    const ORDER_STATUS_FAILED   = '2';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

        $this->CI->load->model(array('player'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$player = $this->CI->player->getPlayerById($playerId);

		$params = array();
		$params['clientid']    = $this->getSystemInfo("account");
		$params['clientkey']   = $this->getSystemInfo("clientkey");
		$params['encryptkey']  = $this->getSystemInfo("encryptkey");
		$params['clientuser']  = $player['username'];
		$params['depositid']   = $order->secure_id;
		$params['amount']      = $this->convertAmountToCurrency($amount);
		$params['bankcode']    = '';
		$params['callbackurl'] = $this->getNotifyUrl($orderId);
		$params['rand_str1']   = $this->getSystemInfo("rand_str1");
		$params['rand_str2']   = $this->getSystemInfo("rand_str2");
		$params['api_url']     = $this->getSystemInfo('url');
		$this->configParams($params, $order->direct_pay_extra_info);
		$this->CI->utils->debug_log("=====================yikuaipay before sign", $params);

		#use when getBankListInfoFallback dynamic bank list not working
		// if(!empty($params['bankcode'])){
		// 	$bankExist = $this->checkBankExist($params['bankcode']);
		// 	if($bankExist['success'] == false){
		// 		return $bankExist;
		// 	}
		// }


		$hash_key = array('clientid', 'depositid', 'amount', 'bankcode', 'callbackurl','clientkey');
		$hash_str = $this->createHashStr($params, $hash_key);
        $params['hashkey'] = md5($hash_str);

		$msg_key = array('depositid', 'amount', 'clientuser', 'bankcode', 'callbackurl', 'hashkey');
		$datas['Sign']    = $this->sign($params);
		$datas['Msg']     = $this->msg($params, $msg_key);
		$datas['Product'] = $params['Product'];


		$this->CI->utils->debug_log("=====================yikuaipay generatePaymentUrlForm", $datas);
		return $this->processPaymentUrlForm($datas);
	}

	#use when getBankListInfoFallback dynamic bank list not working
	public function checkBankExist($bankcode) {
		$params = array();
		$params['clientid']   = $this->getSystemInfo("account");
		$params['clientkey']  = $this->getSystemInfo("clientkey");
		$params['encryptkey'] = $this->getSystemInfo("encryptkey");
		$params['rand_str1']  = $this->getSystemInfo("rand_str1");
		$params['rand_str2']  = $this->getSystemInfo("rand_str2");
		$this->CI->utils->debug_log("=====================yikuaipay checkBankExist before sign", $params);

		$hash_key = array('clientid', 'clientkey');
		$hash_str = $this->createHashStr($params, $hash_key);
		$params['hashkey'] = md5($hash_str);

		$msg_key = array('hashkey');
		$datas['Sign']    = $this->sign($params);
		$datas['Msg']     = $this->msg($params, $msg_key);
        if($this->CI->utils->is_mobile()) {
			$datas['Product'] = self::PRODUCT_BANK_WAP;
		}
		else {
			$datas['Product'] = self::PRODUCT_BANK_WEP;
		}

		$this->CI->utils->debug_log('=========================yikuaipay checkBankExist Get Bank Data', $datas);
		$url = $this->getSystemInfo('getbankUrl');
		$response = $this->submitPostForm($url, $datas);
		$this->CI->utils->debug_log('=========================yikuaipay checkBankExist response ', $response);

		if($errCode == 0) {
			$search = "<bankcode>".$bankcode."</bankcode>";
			$catch = strrpos($response, $search);
			if($catch){
				$this->CI->utils->debug_log('=========================yikuaipay checkBankExist catched value', $search);
				return array(
					'success' => true
				);
			}
			else if(strrpos($response,"err")) {
				$this->CI->utils->debug_log("=========================yikuaipay checkBankExist response err", $response);
				return array(
					'success' => false,
					'type' => self::REDIRECT_TYPE_ERROR,
					'message' => $response
				);
			}
			else{
				$this->CI->utils->debug_log('=========================yikuaipay checkBankExist bankcode missed value', $search);
				return array(
					'success' => false,
					'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
					'message' => $this->getSystemInfo('bank_message')
				);
			}
		}
		else{
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $errCode. ': '.$error
			);
		}
	}

	protected function processPaymentUrlFormPost($datas) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $datas,
			'post' => true,
		);
	}

	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}

	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;
		if($source == 'server' ){
			$raw_post_data = file_get_contents('php://input', 'r');
			$this->CI->utils->debug_log("=====================yikuaipay raw_post_data", $raw_post_data);
			$this->CI->utils->debug_log("=====================yikuaipay params", $params);

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
			$this->CI->sale_order->updateExternalInfo($order->id,
				'', '', # no info available
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($processed) {
			$result['message'] = self::ORDER_STATUS_SUCCESS ;
		} else {
			$result['return_error'] = self::ORDER_STATUS_FAILED  ;
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array(
			'clientid', 'orderid', 'amount', 'bankcode', 'callbackurl', 'remarks', 'status', 'yeepayorderid', 'outorderid', 'hashkey'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================yikuaipay missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['orderid'] != $order->secure_id) {
			$this->writePaymentErrorLog("=====================yikuaipay checkCallbackOrder payment , Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		if ($fields['status'] != self::ORDER_STATUS_SUCCESS) {
			$payStatus = $fields['orderStatus'];
			$this->writePaymentErrorLog("=====================yikuaipay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['amount'] )
		) {
			$this->writePaymentErrorLog("=====================yikuaipay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================yikuaipay checkCallbackOrder signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	public function getBankListInfoFallback() {
		$params = array();
		$params['clientid']   = $this->getSystemInfo("account");
		$params['clientkey']  = $this->getSystemInfo("clientkey");
		$params['encryptkey'] = $this->getSystemInfo("encryptkey");
		$params['rand_str1']  = $this->getSystemInfo("rand_str1");
		$params['rand_str2']  = $this->getSystemInfo("rand_str2");
		$this->CI->utils->debug_log('======================yikuaipay getBankListInfoFallback before sign', $params);

		$hash_key = array('clientid', 'clientkey');
		$hash_str = $this->createHashStr($params, $hash_key);
		$params['hashkey'] = md5($hash_str);

		$msg_key = array('hashkey');
		$datas['Sign'] = $this->sign($params);
		$datas['Msg']  = $this->msg($params, $msg_key);
        if($this->CI->utils->is_mobile()) {
			$datas['Product'] = self::PRODUCT_BANK_WAP;
		}
		else {
			$datas['Product'] = self::PRODUCT_BANK_WEP;
		}
		$this->CI->utils->debug_log('======================yikuaipay getBankListInfoFallback datas', $datas);

		$url = $this->getSystemInfo('getbankUrl');
		$response = $this->submitPostForm($url, $datas);
		$this->CI->utils->debug_log('======================yikuaipay getBankListInfoFallback response type', gettype($response));
		$this->CI->utils->debug_log('======================yikuaipay getBankListInfoFallback response', $response);
		$result = $this->parseResultXML($response);
		$this->CI->utils->debug_log('======================yikuaipay getBankListInfoFallback response xml parsed', $result);
		if(!is_null($result)){
			foreach ($result as $key => $value) {
			    $banklist[$key]['label'] = $value['bankname'];
			    $banklist[$key]['value'] = $value['bankcode'];
			}
			return $banklist;
		}
		else{
			$getbank_fail_msg = $this->getSystemInfo("getbank_fail_msg", "無可用銀行");
			return array(
				array('label' => $getbank_fail_msg, 'value' => ''),
			);
		}
    }

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	public function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	public function sign($params) {
		$sign_key = array('rand_str1', 'clientid', 'rand_str2');
		$sign_str = '';
		foreach ($sign_key as $key) {
			if(array_key_exists($key, $params)) {
				$sign_str .= $params[$key] . ',';
			}
		}
		$sign_str = substr($sign_str, 0, strlen($sign_str) -1 );
        $des = $this->Des($params['encryptkey']);
		$sign = $this->encrypt($sign_str);


	
		return $sign;
	}

    public function msg($params, $msg_key) {
		$rPass     = md5($params['clientkey']);
		$des       = $this->Des($rPass);
		$msg_str   = $this->createMsgStr($params, $msg_key);
		$msg       = $this->encrypt($msg_str);

		$this->CI->utils->debug_log('======================yikuaipay msg: ', $msg, $msg_str);
		return $msg;
	}

	public function createMsgStr($params, $msg_key) {
		$msg_str = '';
		foreach ($msg_key as $key) {
			if(array_key_exists($key, $params)) {
				$msg_str .= $params[$key] . ',';
			}
		}
		$msg_str = substr($msg_str, 0, strlen($msg_str) - 1 );
		return $msg_str;
	}

	public function validateSign($params){
		$callback_sign = $params['hashkey'] ;

		$params['depositid'] =$params['orderid'];
		$hash_key = array('clientid', 'depositid', 'amount', 'bankcode', 'callbackurl','clientkey');

		$params['clientkey'] = $this->getSystemInfo("clientkey");
		$signStr = $this->createHashStr($params, $hash_key);
		$sign = md5($signStr);


		if($callback_sign != $sign){
			return false;
		}
		return true;
	}

	public function createHashStr($params, $params_key) {
	    $hash_str = '';
		foreach ($params_key as $key) {
			if(array_key_exists($key, $params)) {
				$hash_str .= $key . '=[' . $params[$key] . ']';
			}
		}
	    return $hash_str;
	}

	public function parseResultXML($resultXml) {
		if (preg_match("/xml/", $resultXml)){
			$obj  = simplexml_load_string($resultXml);
			$json = json_encode($obj, JSON_UNESCAPED_UNICODE);
			$arr  = json_decode($json, true);

			$this->CI->utils->debug_log('======================yikuaipay parseResultXML', $arr);
			$result = $arr['subnode'];

			return $result;
		}
		else{
			return null;
		}
	}


	##DES from demo code
	var $key;
	var $iv;
	private function Des($key){
		$by = array();
		$by[0]=0x32;
		$by[1]=0xCD;
		$by[2]=0x13;
		$by[3]=0x58;
		$by[4]=0x21;
		$by[5]=0xAB;
		$by[6]=0xBC;
		$by[7]=0xEF;
		$this->key = $key;
		$this->iv = $this->toStr($by);
	}

	private function toStr($bytes){
		$str = '';
		foreach($bytes as $ch){
			$str .= chr($ch);
		}
		return $str;
	}

	private function encrypt($input) {
		$size = @mcrypt_get_block_size('des', 'ecb');
		$input = $this->pkcs5pad($input, $size);
		$key = $this->key;
		$td = @mcrypt_module_open('des', '', 'ecb', '');
		$iv = @mcrypt_create_iv (@mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		@mcrypt_generic_init($td, $key, $iv);
		$data = @mcrypt_generic($td, $input);
		@mcrypt_generic_deinit($td);
		@mcrypt_module_close($td);
		$data = base64_encode($data);
		return preg_replace("/\s*/", '',$data);
	}

	private function decrypt($encrypted){
		$encrypted = base64_decode($encrypted);
		$key = $this->key;
		$td = @mcrypt_module_open('des','','ecb','');
		$iv = @mcrypt_create_iv(@mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		$ks = @mcrypt_enc_get_key_size($td);
		@mcrypt_generic_init($td, $key, $iv);
		$decrypted = @mdecrypt_generic($td, $encrypted);
		@mcrypt_generic_deinit($td);
		@mcrypt_module_close($td);
		$y = $this->pkcs5unpad($decrypted);
		return $y;
	}

	private function pkcs5Unpad($text) {
		$pad = ord($text[strlen($text) - 1]);
		if ($pad > strlen($text))
			return false;
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
			return false;
		return substr($text, 0, -1 * $pad);
	}

	private function pkcs5Pad($text, $blocksize) {
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}

	private function genRandStr($len){
		$chars = array(
			"a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
			"l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
			"w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
			"H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
			"S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
			"3", "4", "5", "6", "7", "8", "9" );
		$charsLen = count($chars) - 1;

		shuffle($chars);    // 将数组打乱

		$output = "";
		for ($i=0; $i<$len; $i++) {
			$output .= $chars[mt_rand(0, $charsLen)];
		}
		return $output;
	}

	private function genRandNum($len) {
		$chars = array( "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" );
		$charsLen = count($chars) - 1;
		shuffle($chars);    // 将数组打乱
		$output = "";
		for ($i=0; $i<$len; $i++) {
			$output .= $chars[mt_rand(0, $charsLen)];
		}
		return $output;
	}
}
