<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * GMStone
 * http://www.gmstoneft.com
 *
 * * GM_PAYMENT_API, ID: 159
 * * GM_ALIPAY_PAYMENT_API, ID: 160
 * * GM_ALIPAY_H5_PAYMENT_API, ID: 615
 * * GM_ALIPAY_QRONLY_PAYMENT_API, ID: 225
 * * GM_WEIXIN_PAYMENT_API, ID: 161
 * * GM_WEIXIN_H5_PAYMENT_API, ID: 715
 * * GM_QQPAY_PAYMENT_API, ID: 365
 * * GM_QQPAY_H5_PAYMENT_API, ID: 716
 * * GM_JDPAY_PAYMENT_API, ID: 614
 * * GM_UNIONPAY_PAYMENT_API, ID: 717
 * * GM_QUICKPAY_PAYMENT_API, ID: 672
 * * GM2_PAYMENT_API, ID: 180
 * * GM2_ALIPAY_PAYMENT_API, ID: 181
 * * GM2_WEIXIN_PAYMENT_API, ID: 182
 * * GM_WITHDRAWAL_PAYMENT_API, ID: 385
 *
 * Required Fields:
 * * URL
 * * Account
 * * Extra Info
 *
 * Field Values:
 * * URL: http://www.master-egg.cn/GateWay/ReceiveBank.aspx
 * * Account: ## Merchant ID ##
 * * Extra Info:
 * > {
 * >    "gm_priv_key": ## Private Key ##,
 * >    "gm_pub_key": ## Public Key ##
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_gm extends Abstract_payment_api {
	const BANK_TYPE_ALIPAY = 'alipay';
	const BANK_TYPE_WEIXIN = 'wxcode';
	const BANK_TYPE_QQPAY = 'qqpay';
	const BANK_TYPE_JDPAY = 'jdpay';

	const BANK_TYPE_ALIPAY_H5 = 'alipayh5';
	const BANK_TYPE_WEIXIN_H5 = 'wechath5';
	const BANK_TYPE_QQPAY_H5 = 'qqpayh5';

	const BANK_TYPE_QUICKPAY = 'quickpay';
	const BANK_TYPE_UNIONPAY = 'unionpay';

	const RETURN_SUCCESS_CODE = 'success';
	const ORDER_STATUS_SUCCESS = 1;

	public function __construct($params = null) {
		parent::__construct($params);
	}

	public function getBankType($direct_pay_extra_info) {
		return ''; # Default return empty banktype, redirect to bank selection page
	}

	public function getSecretInfoList() {
		$secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'gm_pub_key', 'gm_priv_key');
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
		$params['p0_Cmd'] = 'Buy'; # fixed value
		$params['p1_MerId'] = $this->getSystemInfo('account');
		$params['p2_Order'] = $order->secure_id;
		$params['p3_Amt'] = $this->convertAmountToCurrency($amount);
		$params['p4_Cur'] = 'CNY'; # fixed value
		$params['p5_Pid'] = 'Topup';
		$params['p6_Pcat'] = 'Topup';
		$params['p7_Pdesc'] = 'Topup';
		$params['p8_Url'] = $this->getNotifyUrl($orderId);
		$params['p9_SAF'] = '0'; # fixed value
		$params['pa_MP'] = 'Topup';
		$params['pd_FrpId'] = $this->getBankType($order->direct_pay_extra_info);
		$params['pr_NeedResponse'] = '1'; # fixed value

        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log('=========================GM generatePaymentUrlForm params', $params);

		return $this->processPaymentUrlForm($params);
	}

	# QRCode implementation can overwrite this function to supply QRCode page
	protected function processPaymentUrlForm($params) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'use_iframe' => $params['pd_FrpId'] == self::BANK_TYPE_WEIXIN,
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['r2_TrxId'],
				array_key_exists('ro_BankOrderId', $params) ? $params['ro_BankOrderId'] : null,
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($processed) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = 'Error';
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
		$this->utils->debug_log("============================GM callback params", $fields);
		$requiredFields = array(
			'p1_MerId', 'r1_Code', 'r2_TrxId', 'r3_Amt', 'r6_Order'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("============================GM Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('============================GM Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['r1_Code'] != self::ORDER_STATUS_SUCCESS) {
			$this->writePaymentErrorLog('============================GM Payment was not successful', $fields);
			return false;
		}

		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['r3_Amt']))
		) {
			$this->writePaymentErrorLog("============================GM Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['r6_Order'] != $order->secure_id) {
            $this->writePaymentErrorLog("============================GM Payment order IDs do not match, expected [$order->secure_id]", $fields);
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
			array('label' => '中国农业银行', 'value' => 'ABC'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '中国光大银行', 'value' => 'CEB'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '中信银行', 'value' => 'CITIC'),
			array('label' => '中国工商银行', 'value' => 'ICBC'),
			array('label' => '华夏银行', 'value' => 'HXBANK'),
			array('label' => '浦发银行', 'value' => 'SPDB'),
			array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '中国建设银行', 'value' => 'CCB'),
			array('label' => '广发银行', 'value' => 'GDB')
		);
	}

	# -- Private functions --
	# After payment is complete, the gateway will invoke this URL asynchronously
	protected function getNotifyUrl($orderId) {
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

	# -- signatures --
	# Reference: PHP Demo
	public function sign($params) {
		$keys = array('p0_Cmd', 'p1_MerId', 'p2_Order', 'p3_Amt', 'p4_Cur', 'p5_Pid', 'p6_Pcat', 'p7_Pdesc', 'p8_Url', 'p9_SAF', 'pa_MP', 'pd_FrpId', 'pr_NeedResponse');
		$signStr = "";
		foreach($keys as $key) {
			if(array_key_exists($key, $params)) {
				$signStr .= $params[$key];
			}
		}

		openssl_sign($signStr, $sign_info, $this->getPrivKey());
		$sign = base64_encode($sign_info);
		return $sign;
	}

	private function validateSign($params) {
		$sign = $params['sign'];
		$sign = str_replace("*", "+", $sign);
		$sign = str_replace("-", "/", $sign);

		$keys = array('p1_MerId', 'r0_Cmd', 'r1_Code', 'r2_TrxId', 'r3_Amt', 'r4_Cur', 'r5_Pid', 'r6_Order', 'r7_Uid', 'r8_MP', 'r9_BType');
		$signStr = "";
		foreach($keys as $key) {
			if(array_key_exists($key, $params)) {
				$signStr .= $params[$key];
			}
		}

		$valid = openssl_verify($signStr, base64_decode($sign), $this->getPubKey());
		return $valid;

	}


	# Returns public key given by gateway
	private function getPubKey() {
		$gm_pub_key = $this->getSystemInfo('gm_pub_key');

		$pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($gm_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
		return openssl_get_publickey($pub_key);
	}

	# Returns the private key generated by merchant
	private function getPrivKey() {
		$gm_priv_key = $this->getSystemInfo('gm_priv_key');

		$priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($gm_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
		return openssl_get_privatekey($priv_key);
	}
}
