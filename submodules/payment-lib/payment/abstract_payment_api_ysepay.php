<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 *
 * YSEPay 银盛支付
 *
 * YSEPAY_PAYMENT_API, ID: 156
 *
 * Required Fields:
 * * URL
 * * Account
 * * ExtraInfo
 *
 * Field Values:
 * * URL
 * 		- Live: https://openapi.ysepay.com/gateway.do
 * 		- Sandbox: https://mertest.ysepay.com/openapi_gateway/gateway.do
 * * Account: ## Partner ID ##
 * * ExtraInfo
 * > {
 * >	"ysepay_seller_id" : "## Seller ID ##",
 * >	"ysepay_seller_name" : "## Seller Name ##",
 * >	"ysepay_business_code" : "## Business Code ##",
 * >	"ysepay_rsa_pub_key" : "## Path to public key file ##",
 * >	"ysepay_rsa_priv_key" : "## Path to private key file ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_ysepay extends Abstract_payment_api {
	const PAY_METHOD_DIRECTPAY = 'ysepay.online.directpay.createbyuser';
	const BANKACCOUNTTYPE_PERSONAL_DEBIT = '11';
	const RETURN_SUCCESS_CODE = 'success';
	const RETURN_FAIL_CODE = 'fail';
	const TRADE_STATUS_SUCCESS = 'TRADE_SUCCESS';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	abstract public function getBankType($direct_pay_extra_info);

	abstract protected function getMethod();

	public function getSecretInfoList() {
		$secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'ysepay_rsa_pub_key', 'ysepay_rsa_priv_key');
		return $secretsInfo;
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

		$params = array();

		$params['method'] = $this->getMethod();
		$params['partner_id'] = $this->getSystemInfo('account');
		$params['timestamp'] = date('Y-m-d H:i:s');
		$params['charset'] = 'UTF-8';
		$params['sign_type'] = 'RSA';
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['return_url'] = $this->getReturnUrl($orderId);
		$params['version'] = '3.0';

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$params['out_trade_no'] = date('Ymd').$order->secure_id;
		$params['subject'] = 'Topup';

		$params['total_amount'] = $this->convertAmountToCurrency($amount);
		$params['seller_id'] = $this->getSystemInfo('ysepay_seller_id');
		$params['seller_name'] = $this->getSystemInfo('ysepay_seller_name');
		$params['timeout_express'] = '1h'; # timeout after 1 hour
		$params['pay_mode'] = 'internetbank';
		$params['bank_type'] = $this->getBankType($order->direct_pay_extra_info);
		$params['bank_account_type'] = self::BANKACCOUNTTYPE_PERSONAL_DEBIT;
		$params['support_card_type'] = 'debit';
		$params['business_code'] = $this->getSystemInfo('ysepay_business_code');
		# 没有值的参数无需传递，也无需包含进待签名数据

		# sign param
		$params['sign'] = $this->sign($params);

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
				$params['trade_no'], $params['notify_time'],
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = self::RETURN_FAIL_CODE;
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
		$requiredFields = array(
			'sign', 'out_trade_no', 'trade_status', 'total_amount'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->verifySignature($fields)) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['trade_status'] != self::TRADE_STATUS_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['total_amount']))
		) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
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
			array('label' => '中国银行', 'value' => '1041000'),
			array('label' => '中国农业银行', 'value' => '1031000'),
			array('label' => '中国工商银行', 'value' => '1021000'),
			array('label' => '中国建设银行', 'value' => '1051000'),
			array('label' => '交通银行', 'value' => '3012900'),
			array('label' => '招商银行', 'value' => '3085840'),
			array('label' => '中国民生银行', 'value' => '3051000'),
			array('label' => '兴业银行', 'value' => '3093910'),
			array('label' => '上海浦东发展银行', 'value' => '3102900'),
			array('label' => '广东发展银行', 'value' => '3065810'),
			array('label' => '中信银行', 'value' => '3021000'),
			array('label' => '光大银行', 'value' => '3031000'),
			array('label' => '中国邮政储蓄银行', 'value' => '4031000'),
			array('label' => '平安银行', 'value' => '3071000'),
			array('label' => '北京银行', 'value' => '3131000'),
			array('label' => '南京银行', 'value' => '3133010'),
			array('label' => '宁波银行', 'value' => '3133320'),
			array('label' => '上海农村商业银行', 'value' => '3222900'),
			array('label' => '东亚银行', 'value' => '5021000'),
			array('label' => '华夏银行', 'value' => '3041000'),
			array('label' => '银联在线', 'value' => '9001000')
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

	# -- signing functions --
	## Reference: PHP DEMO code
	private function getSignStr($params) {
		ksort($params);
		$signStr = "";
		foreach ($params as $key => $val) {
			if(empty($key) || empty($val) || $key == 'sign') {
				continue;
			}
			$signStr .= $key . '=' . $val . '&';
		}
		$signStr = trim($signStr, '&');
		return $signStr;
	}

	private function validateSign($params) {
		$signStr = $this->getSignStr($params);
	
		$success = $this->sign_check($params['sign'], $signStr);
		$this->utils->debug_log("Signature valid? ", $success);
		return $success;
	}

	/**
	 * 验签
	 * @param input check
	 * @param input msg
	 * @return data
	 * @return success
	 */
	private function sign_check($sign, $data) {

		$publickeyFile = $this->getSystemInfo('ysepay_rsa_pub_key'); //公钥

		// 公钥
		$certificateCAcerContent = file_get_contents($publickeyFile);
		$certificateCApemContent = '-----BEGIN CERTIFICATE-----' . PHP_EOL . chunk_split(base64_encode($certificateCAcerContent), 64, PHP_EOL) . '-----END CERTIFICATE-----' . PHP_EOL;

		// 签名验证
		return openssl_verify($data, base64_decode($sign), openssl_get_publickey($certificateCApemContent), OPENSSL_ALGO_SHA1);
	}

	private function sign($params) {
		$signStr = $this->getSignStr($params);
		$sign = $this->sign_encrypt($signStr);

	
		return $sign;
	}

	/**
	 * 签名加密
	 * @param input data
	 * @return success
	 * @return check
	 * @return msg
	 */
	private function sign_encrypt($input) {
		$return = array('success' => 0, 'msg' => '', 'check' => '');

		$pkcs12 = file_get_contents($this->getSystemInfo('ysepay_rsa_priv_key')); //私钥
		if (openssl_pkcs12_read($pkcs12, $certs, '123456')) {
			$privateKey = $certs['pkey'];
			$publicKey  = $certs['cert'];

			$signedMsg = "";
			if (openssl_sign($input, $signedMsg, $privateKey, OPENSSL_ALGO_SHA1)) {
				$return['success'] = 1;
				$return['check']   = base64_encode($signedMsg);
				$return['msg']     = base64_encode($input['data']);

			}
		}

		return $return;
	}

}
