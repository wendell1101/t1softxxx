<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * 339PAY叁叁玖
 * http://www.sz339pay.com:9001/
 *
 * * 339PAY_PAYMENT_API, ID: 214
 * * 339PAY_ALIPAY_PAYMENT_API, ID: 215
 * * 339PAY_WEIXIN_PAYMENT_API, ID: 216
 * * 339PAY_QQPAY_PAYMENT_API, ID: 224
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: http://test-pay.sz339pay.com:9001/gateway/gateway_init.action
 * * URL: http://test-pay.sz339pay.com:9001/trade/alipayApi.action
 * * URL: http://test-pay.sz339pay.com:9001/trade/weixinApi.action
 * * URL: http://test-pay.sz339pay.com:9001/trade/qqApi.action
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_339pay extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'success|100';
	//const RESULT_CODE_SUCCESS = 'success'; # 'Success001'; In actual testing, the successful wechat payment returns success002
	const RESULT_CODE_SUCCESS = '100';
	const QRCODE_RESULT_CODE_SUCCESS = '100';


	# Implement these to specify pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function getSecretInfoList() {
		$secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', '339pay_priv_key');
		return $secretsInfo;
	}

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params['p1_MerchantNo'] = $this->getSystemInfo("account");
		$params['p2_OrderNo'] = $order->secure_id;
		$params['p3_Amount'] = $this->convertAmountToCurrency($amount);
		$params['p4_Cur'] = '1';
		$params['p5_ProductName'] = 'Topup';
		$params['p6_Mp'] = 'Topup';
		$params['p7_ReturnUrl'] = $this->getReturnUrl($orderId);
		$params['p8_NotifyUrl'] = $this->getNotifyUrl($orderId);
		$params['p9_FrpCode'] = $this->getBankCode($order->direct_pay_extra_info);
		$params['pa_OrderPeriod'] = 0;

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['hmac'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================339pay___generatePaymentUrlForm->params", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
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
		$response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['p2_OrderNo']);
		$response = json_decode($response, true);

		$this->CI->utils->debug_log('========================================response', $response);

		if($response['rb_Code'] && $response['rb_Code'] == self::QRCODE_RESULT_CODE_SUCCESS) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'base64' => $response['r8_CodeStream']
			);
		}
		else if($response['rc_CodeMsg']) {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $response['rc_CodeMsg']
			);
		}
		else {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => lang('Invalidte API response')
			);
		}
	}

	private function validateResult($param) {
		# validate success code
		if ($param['rb_Code'] != self::QRCODE_RESULT_CODE_SUCCESS) {
			$this->utils->error_log("payment failed, rb_Code = [".$param['rb_Code']."], rc_CodeMsg = [".$param['rc_CodeMsg']."], Params: ", $param);
			return false;
		}

		return true;
	}

	public abstract function getBankCode($direct_pay_extra_info);

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
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			return $result;
		}

		# Update order payment status and balance
		$success=true;

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
				$params['OrdNo'], null, null, null, $response_result_id);
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
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		# does all required fields exist?
		$requiredFields = array(
			'r1_MerchantNo', 'r2_OrderNo', 'r3_Amount', 'r4_Cur', 'r5_Mp', 'r6_Status', 'r7_TrxNo', 'r8_BankOrderNo', 'r9_BankTrxNo', 'ra_PayTime', 'rb_DealTime', 'rc_BankCode', 'hmac'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if ($this->sign($fields) != $fields['hmac']) {
			$this->writePaymentErrorLog('======================================Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		# is payment successful?
		if ($fields['r6_Status'] != self::RESULT_CODE_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
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
			array('label' => '工商银行', 'value' => 'ICBC_NET_B2C'),
			array('label' => '招商银行', 'value' => 'CMBCHINA_NET_B2C'),
			array('label' => '中国农业银行', 'value' => 'ABC_NET_B2C'),
			array('label' => '建设银行', 'value' => 'CCB_NET_B2C'),
			array('label' => '北京银行', 'value' => 'BCCB_NET_B2C'),
			array('label' => '交通银行', 'value' => 'BOCO_NET_B2C'),
			array('label' => '兴业银行', 'value' => 'CIB_NET_B2C'),
			array('label' => '中国民生银行', 'value' => 'CMBC_NET_B2C'),
			array('label' => '光大银行', 'value' => 'CEB_NET_B2C'),
			array('label' => '中国银行', 'value' => 'BOC_NET_B2C'),
			array('label' => '平安银行 ', 'value' => 'PINGANBANK_NET_B2C'),
			array('label' => '中信银行', 'value' => 'ECITIC_NET_B2C'),
			array('label' => '深圳发展银行', 'value' => 'SDB_NET_B2C'),
			array('label' => '广发银行', 'value' => 'CGB_NET_B2C'),
			array('label' => '上海浦东发展银行', 'value' => 'SPDB_NET_B2C'),
			array('label' => '中国邮政', 'value' => 'POST_NET_B2C'),
			array('label' => '华夏银行', 'value' => 'HXB_NET_B2C')
		);
	}

	# -- Private functions --
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	public function sign($data) {
		$prestr = $this->prepareSign($data);
		$dataStr = $this->characet($prestr);

		$priv_key = $this->getPrivKey();

		$res = openssl_pkey_get_private($priv_key);
		if($res) {
			openssl_sign($dataStr, $sign, $res, OPENSSL_ALGO_MD5);
		}

		openssl_free_key($res);
		//base64编码
		$sign = base64_encode($sign);
		return $sign;
	}

	# Returns the private key generated by merchant
	private function getPrivKey() {
		$_339pay_priv_key = $this->getSystemInfo('339pay_priv_key');

		$priv_key = '-----BEGIN PRIVATE KEY-----' . PHP_EOL . chunk_split($_339pay_priv_key, 64, PHP_EOL) . '-----END PRIVATE KEY-----' . PHP_EOL;
		return openssl_get_privatekey($priv_key);
	}

	private function prepareSign($para) {
		$arg  = "";
		while (list ($key, $val) = each ($para)) {
			$arg.=$val;
		}

		//如果存在转义字符，那么去掉转义
		if(get_magic_quotes_gpc()) {
			$arg = stripslashes($arg);
		}

		return $arg;
	}

	private function characet($data){
		if( !empty($data) ){
			$fileType = mb_detect_encoding($data , array('UTF-8','GBK','LATIN1','BIG5')) ;
			if( $fileType != 'UTF-8'){
			  $data = mb_convert_encoding($data ,'utf-8' , $fileType);
			}
		}
		return $data;
	}
}
