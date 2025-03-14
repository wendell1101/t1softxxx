<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * JHZ 金海哲
 * http://wz.szjhzxxkj.com
 *
 * * JHZ_PAYMENT_API - 173
 * * JHZ_WEIXINAPP_PAYMENT_API - 174
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Code
 * * ExtraInfo - pub key and priv key
 *
 * Field Values:
 *
 * * URL: http://zf.szjhzxxkj.com/ownPay/pay
 * * Extra Info:
 * > {
 * > 	"jhz_priv_key" : "## pem formatted private key (escaped) ##",
 * > 	"jhz_pub_key" : "## pem formatted public key (escaped) ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_jhz extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const PAYMENT_SUCCESS_CODE = '1000';
	const DEFAULT_TIMEOUT_MINUTES = 60; # i.e. 1 hour
	const BANK_ACCOUNT_TYPE_DEBIT = '11';
	const PAY_METHOD_BANK = '6002'; # 6002 网银支付
	const PAY_METHOD_ALIPAY = '6003'; # 6003 支付宝扫码
	const PAY_METHOD_WEIXIN = '6001'; # 6001 微信扫码
	const PAY_METHOD_QQPAY = '6011'; # 6001 微信扫码

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'jhz_pub_key', 'jhz_priv_key');
        return $secretsInfo;
    }

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params = array();
		$params['merchantNo'] = $this->getSystemInfo("account");
		$params['requestNo'] = $order->secure_id;
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['pageUrl'] = $this->getReturnUrl($orderId);
		$params['backUrl'] = $this->getNotifyUrl($orderId);
		$params['payDate'] = $orderDateTime->getTimestamp();
		$params['agencyCode'] = ''; # ????
		#$params['cashier'] = ''; # ????
		$params['remark1'] = 'Deposit';
		$params['remark2'] = 'Deposit';
		$params['remark3'] = 'Deposit';

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['signature'] = $this->sign($params);

		$this->CI->utils->debug_log("============================jhz_generatePaymentUrlForm->params", $params);

		return $this->processPaymentUrlForm($params);
	}

	protected function processPaymentUrlFormPost($params) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true
		);
	}

	protected function processPaymentUrlFormQRCode($params) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $this->getSystemInfo('url'));
        $response =  curl_exec($ch);

        curl_close($ch);

		$outParams = array();

		if($this->validateResponse($response, $outParams)) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $outParams['backQrCodeUrl'],
			);
		} else {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR,
				'message' => 'Invalid Response',
			);
		}
	}

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

		$retArr = json_decode($fields['ret']);
		$msgArr = json_decode($fields['msg']);

		# Do not proceed to update order status if payment failed, but still print success msg as callback response
		if(!$paymentSuccessful) {
			if ($source == 'server') {
				$result['return_error'] = self::RETURN_SUCCESS_CODE;
				return $result;
			} else {
				$result['return_error'] = $retArr['msg'];
				return $result;
			}
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
			$this->CI->sale_order->updateExternalInfo($order->id, $msgArr['payNo'], null, null, null, $response_result_id);
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
	}

	# returns true if callback is valid and payment is successful
	# sets the $callbackValid parameter if callback is valid
	private function checkCallbackOrder($order, $fields, &$callbackValid) {
		# does all required fields exist?
		$requiredFields = array(
			'ret', 'msg', 'sign'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('===============================================Signature Error', $fields);
			return false;
		}

		$callbackValid = true; # callbackValid is set to true once the signature verification pass

		$retArr = json_decode($fields['ret']);
		$msgArr = json_decode($fields['msg']);

		if ($retArr['code'] != self::PAYMENT_SUCCESS_CODE) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $msgArr['money']) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($msgArr['no'] != $order->secure_id) {
			$this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- private helper functions --
	protected function getBankListInfoFallback() {
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
		);
	}

	private function convertAmountToCurrency($amount) {
		return number_format($amount * 100, 0, '.', '');
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	# -- signing --
	private function sign($params) {
		$signStr = $this->createSignStr($params);

		$pr_key ='';
		if(openssl_pkey_get_private($this->getPrivKey())){
		    $pr_key = openssl_pkey_get_private($this->getPrivKey());
		}
		openssl_sign($signStr, $sign_info, $pr_key);
		openssl_free_key($pr_key);

		$sign = base64_encode($sign_info);

		return $sign;
	}

	private function validateSign($fields) {
		$data = stripslashes($fields['ret'].'|'.$fields['msg']);
		$valid = openssl_verify($data, base64_decode($fields['sign']), $this->getPubKey());
		return $valid;
	}

	private function createSignStr($params) {
		ksort($params);
		$signKeys = array('merchantNo', 'requestNo', 'amount', 'pageUrl', 'backUrl', 'payDate', 'agencyCode', 'remark1', 'remark2', 'remark3');
		$signStr = '';
		foreach($signKeys as $key) {
			$signStr .= $params[$key] . '|';
		}
		return rtrim($signStr, '|');
	}

	# Ref: PHP Demo code
	private function validateResponse($response, &$outParams) {
		$str = stripslashes($response);
		//转成Array
		$arr = json_decode($str,true);
		//获取返回字符串sign
		$resultsign = $arr['sign'];
		//从arr去掉sign
		unset($arr['sign']);
		//去掉斜杠
		$original_str = stripslashes(json_encode($arr));

		if(1 == openssl_verify($original_str, base64_decode($resultsign), $this->getPubKey())) {
			$outParams = $arr;
			return true;
		} else {
			$this->utils->error_log("Validate Response: Failed");
			return false;
		}
	}

	# Returns public key given by gateway
	private function getPubKey() {
		return openssl_get_publickey($this->getSystemInfo('jhz_pub_key'));
	}

	# Returns the private key generated by merchant
	private function getPrivKey() {
		return openssl_get_privatekey($this->getSystemInfo('jhz_priv_key'));
	}
}
