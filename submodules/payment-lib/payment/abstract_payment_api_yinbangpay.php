<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * 银邦支付 YINBANGPAY
 * https://sup.yinbangpay.com/
 *
 * YINBANGPAY_PAYMENT_API, ID: 217
 * YINBANGPAY_ALIPAY_PAYMENT_API, ID: 218
 * YINBANGPAY_WEIXIN_PAYMENT_API, ID: 219
 *
 * Required Fields:
 * * URL
 * * Extra Info:
 * * {
 * *    "terminal_id"
 * *    "merchant_id"
 * *    "yinbangpay_pub_key"
 * *    "yinbangpay_priv_key"
 * * }
 *
 *
 * Field Values:
 * * URL: https://www.yinbangpay.com/gateway/orderPay
 * * Extra Info:
 * * {
 * *    "terminal_id": ## Terminal ID ##,
 * *    "merchant_id": ## Merchant ID ##,
 * *    "yinbangpay_pub_key" : "## pem formatted public key (escaped) ##",
 * *    "yinbangpay_priv_key" : "## pem formatted private key (escaped) ##"
 * * }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_yinbangpay extends Abstract_payment_api
{

	const REQUEST_ENCODING = 'UTF-8';
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const RESULT_CODE_SUCCESS = 1000;
	const VERSION = '1.0.9';

	const PAY_TYPE_CODE_DEFAULT = 1000;
	const PAY_TYPE_CODE_QUICK_PAY = 1001;
	const PAY_TYPE_CODE_CARD = 1002;
	const PAY_TYPE_CODE_BANK = 1003;
	const PAY_TYPE_CODE_WEIXIN = 1005;
	const PAY_TYPE_CODE_ALIPAY = 1006;
	const PAY_TYPE_CODE_FOREIGN_PAY = 1007;
	const PAY_TYPE_CODE_ALIPAY_APP = 1008;
	const PAY_TYPE_CODE_WEIXIN_APP = 1009;

	const APP_SENCE_PC = 1001;
	const APP_SENCE_H5 = 1002;
	const APP_SENCE_FAST_API = 1003;
	const APP_SENCE_FAST_SDK = 1004;

	/**
	 * get signature and encoded params by algorithm of yinbangpay
	 *
	 * @param $params
	 * @return array
	 */
	public function getSignatureByParams($params)
	{

		$pubkey= openssl_get_publickey($this->getServerPubKeyStr());
		$priKey= openssl_get_privatekey($this->getPrivKeyStr());

		$enc_json = json_encode($params,JSON_UNESCAPED_UNICODE);
		$encParam_encrypted = '';

		$Split = str_split($enc_json, 64);
		foreach($Split as $Part)
		{
			openssl_public_encrypt($Part,$PartialData,$pubkey);//服务器公钥加密
			$t = strlen($PartialData);
			$encParam_encrypted .= $PartialData;
		}

		$sign_info = '';
		$encodedParams = base64_encode(($encParam_encrypted));//加密的业务参数
		openssl_sign($encParam_encrypted, $sign_info, $priKey);
		$signature = base64_encode($sign_info);//加密业务参数的签名

		return [$encodedParams, $signature];
	}

	public function decrypt($data) {
		$priKey= openssl_get_privatekey($this->getPrivKeyStr());
		$data=base64_decode($data);
		$Split = str_split($data, 128);
		$back='';
		foreach($Split as $k=>$v){
			openssl_private_decrypt($v, $decrypted, $priKey);
			$back.= $decrypted;
		}

		return $back;
	}

	public function validateSign($encodedParams, $signature) {

		$pubkey= openssl_pkey_get_public($this->getServerPubKeyStr());
		$result = openssl_verify(base64_decode($encodedParams),base64_decode($signature), $pubkey);

		return $result == 1 ? true : false;
	}

	# Implement these to specify pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'yinbangpay_server_pub_key', 'yinbangpay_priv_key');
        return $secretsInfo;
    }

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null)
	{
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params = array();
		$params['terId'] = $this->getSystemInfo('terminal_id');
		$params['businessOrdid'] = $orderId;
		$params['orderName'] = 'deposit';
		$params['tradeMoney'] = $this->convertAmountToCurrency($amount);
		$params['syncURL'] = $this->getReturnUrl($orderId);
		$params['asynURL'] = $this->getNotifyUrl($orderId);

		$this->configParams($params, $order->direct_pay_extra_info);

		$this->CI->utils->debug_log("=====================generatePaymentUrlForm->params", $params);

		list($encodedParams, $signature) = $this->getSignatureByParams($params);

	

		$merchant_id = $this->getSystemInfo('merchant_id');

		$final_params = array(
			'sign'		=>	$signature,
			'merId'		=>	$merchant_id,
			'version'	=>	self::VERSION,
			'encParam'	=>	$encodedParams,
		);

		return $this->processPaymentUrlForm($final_params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
		$this->CI->utils->debug_log("=========================processPaymentUrlFormPost->final_params", $params);
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true,
		);
	}

	# Display QRCode get from curl
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
		$curlSuccess = (curl_errno($curlConn) == 0);

		$this->CI->utils->debug_log('============================================curlSuccess', $curlSuccess, $curlResult);

		$errorMsg=null;
		if($curlSuccess) {
			# parses return XML result into array, validate it, and get QRCode URL
			## Parse xml array

			$result = json_decode($curlResult,true);

			$requiredFields = array(
				'sign', 'merId', 'version', 'encParam'
			);
			foreach ($requiredFields as $f) {
				if (!array_key_exists($f, $result)) {
					$this->writePaymentErrorLog("=============================Missing parameter: [$f]", $result);
					return false;
				}
			}

			# is signature authentic?
			if (!$this->validateSign($result['encParam'], $result['sign'])) {
				$this->writePaymentErrorLog('Signature Error', $result);
				return false;
			}

			$callbackValid = true; # callbackValid is set to true once the signature verification pass

			$decrypted_encParam = $this->decrypt($result['encParam']);
			$response=json_decode($decrypted_encParam,true);


			$this->CI->utils->debug_log("=========================response", $response);
			## Validate result data


			## All good, return with qrcode link
			$qrCodeUrl = $response['code_img_url'];

			if(array_key_exists('respDesc', $response)) {
				$errorMsg = $response['respDesc'];
			} else {
				$errorMsg = 'response error';
			}

			if(!$response || $response['respCode'] != self::RESULT_CODE_SUCCESS) {
				return array(
					'success' => false,
					'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
					'message' => $errorMsg
				);
			} else {
				$realQrcodeUrl = explode("qrcode?uuid=", $qrCodeUrl)[1];

				if($this->CI->utils->is_mobile() && $params['payType'] == self::PAY_TYPE_CODE_QQPAY) {
					return array(
						'success' => true,
						'type' => self::REDIRECT_TYPE_URL,
						'url' => urldecode($realQrcodeUrl)
					);
				}

				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_QRCODE,
					'url' => $qrCodeUrl,
				);
			}


		} else {
			# curl error
			$errorMsg = curl_error($curlConn);
		}

		curl_close($curlConn);
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
			$this->CI->sale_order->updateExternalInfo($order->id,
				$params['trade_no'], $params['bank_seq_no'],
				null, null, $response_result_id);
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

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : '';
		}

		return $result;
	}

	# returns true if callback is valid and payment is successful
	# sets the $callbackValid parameter if callback is valid
	private function checkCallbackOrder($order, $fields, &$callbackValid) {
		# does all required fields exist?
		$requiredFields = array(
			'sign', 'merId', 'version', 'encParam'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->validateSign($fields['encParam'], $fields['sign'])) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}

		$callbackValid = true; # callbackValid is set to true once the signature verification pass

		$decrypted_encParam = $this->decrypt($fields['encParam']);
		$res=json_decode($decrypted_encParam,true);

		$this->CI->utils->debug_log("=====================checkCallbackOrder->fields", $fields);
		$this->CI->utils->debug_log("=====================checkCallbackOrder->encParam", $fields['encParam']);
		$this->CI->utils->debug_log("=====================checkCallbackOrder->decrypted_encParam", $decrypted_encParam);
		$this->CI->utils->debug_log("=====================checkCallbackOrder->res", $res);

		if ($res['respCode'] != self::RESULT_CODE_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	# -- Private functions --
	# After payment is complete, the gateway will invoke this URL asynchronously
	public function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## After payment is complete, the gateway will send redirect back to this URL
	public function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	protected function getBankListInfoFallback() {
		$bankList = array(
			array('label' => '默认', 'value' => '1000'),
		);

		return $bankList;
	}

	## Format the amount value for the API
	public function convertAmountToCurrency($amount)
	{
		return number_format($amount*100, 2, '.', '') ;
	}

	# Returns public key given by gateway
	public function getServerPubKeyStr() {

		$yinbangpay_pub_key = $this->getSystemInfo('yinbangpay_server_pub_key');
		$pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($yinbangpay_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;

		return $pub_key;
	}

	# Returns the private key generated by merchant
	private function getPrivKeyStr() {

		$yinbangpay_priv_key = $this->getSystemInfo('yinbangpay_priv_key');
		$priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($yinbangpay_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;

		return $priv_key;
	}

	protected function parseResultXML($resultXml) {
		$obj = simplexml_load_string($resultXml);
		$arr = $this->CI->utils->xmlToArray($obj);

		if(isset($arr['yinbangpay'])){
			return $arr;
		}else{
			return ['yinbangpay'=>$arr];
		}
	}

	protected function flattenResult($xmlResult) {
		$this->CI->utils->debug_log('xmlResult to be flattened', $xmlResult);
		return $xmlResult["yinbangpay"]["response"];
	}
}