<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * KDPay 口袋支付
 * http://www.kdpay.com/
 *
 * KDPAY_PAYMENT_API, ID: 41
 *
 * Required Fields:
 *
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: http://Api.Duqee.Com/pay/Bank.aspx
 * * Extra Info
 * > {
 * >     "kdpay_P_UserId": "##User ID##",
 * >     "bank_list": {
 * >         "2": "_json:{\"1\": \"Alipay\", \"2\": \"支付宝\"}",
 * >         "3": "_json:{\"1\": \"Tenpay\", \"2\": \"财付通\"}",
 * >         "21": "_json:{\"1\": \"WeChat Pay\", \"2\": \"微信\"}",
 * >         "10001": "_json:{\"1\": \"ICBC\", \"2\": \"中国工商银行\"}",
 * >         "10002": "_json:{\"1\": \"ABC\", \"2\": \"中国农业银行\"}",
 * >         "10003": "_json:{\"1\": \"CMB\", \"2\": \"招商银行\"}",
 * >         "10004": "_json:{\"1\": \"BOC\", \"2\": \"中国银行\"}",
 * >         "10005": "_json:{\"1\": \"CCB\", \"2\": \"中国建设银行\"}",
 * >         "10006": "_json:{\"1\": \"CMBC\", \"2\": \"中国民生银行\"}",
 * >         "10007": "_json:{\"1\": \"CNCB\", \"2\": \"中信银行\"}",
 * >         "10008": "_json:{\"1\": \"COMM\", \"2\": \"交通银行\"}",
 * >         "10009": "_json:{\"1\": \"CIB\", \"2\": \"兴业银行\"}",
 * >         "10010": "_json:{\"1\": \"CEB\", \"2\": \"光大银行\"}",
 * >         "10011": "_json:{\"1\": \"???\", \"2\": \"深圳发展银行\"}",
 * >         "10012": "_json:{\"1\": \"PSBC\", \"2\": \"中国邮政\"}",
 * >         "10013": "_json:{\"1\": \"BOBJ\", \"2\": \"北京银行\"}",
 * >         "10014": "_json:{\"1\": \"PAB\", \"2\": \"平安银行\"}",
 * >         "10015": "_json:{\"1\": \"SPDB\", \"2\": \"上海浦东发展银行\"}",
 * >         "10016": "_json:{\"1\": \"CGB\", \"2\": \"广东发展银行\"}",
 * >         "10017": "_json:{\"1\": \"CBHB\", \"2\": \"渤海银行\"}",
 * >         "10018": "_json:{\"1\": \"BEA\", \"2\": \"东亚银行\"}",
 * >         "10019": "_json:{\"1\": \"???\", \"2\": \"宁波银行\"}",
 * >         "10020": "_json:{\"1\": \"BJCRB\", \"2\": \"北京农村商业银行\"}",
 * >         "10021": "_json:{\"1\": \"BONJ\", \"2\": \"南京银行\"}",
 * >         "10022": "_json:{\"1\": \"CZSB\", \"2\": \"浙商银行\"}",
 * >         "10023": "_json:{\"1\": \"???\", \"2\": \"上海银行\"}",
 * >         "10024": "_json:{\"1\": \"???\", \"2\": \"上海农村商业银行\"}",
 * >         "10025": "_json:{\"1\": \"HXB\", \"2\": \"华夏银行\"}",
 * >         "10027": "_json:{\"1\": \"BOHZ\", \"2\": \"杭州银行\"}",
 * >         "10028": "_json:{\"1\": \"???\", \"2\": \"浙江稠州商业银行\"}"
 * >     }
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_kdpay extends Abstract_payment_api {
	# Messages to print for the asynchronous callback
	const RETURN_SUCCESS_CODE = 'errCode=0';
	const RETURN_FAILED_CODE = 'errCode=1';
	# Configurations. Reference: HTTP支付接入文档V3.3.doc -> 附录
	const KDPAY_CHANNEL_IBANKING = '1';
	const KDPAY_CHANNEL_ALIPAY = '2';
	const KDPAY_CHANNEL_TENPAY = '3';
	const KDPAY_CHANNEL_WECHAT = '21';
	const KDPAY_NOT_SMARTPHONE = '0';

	private $info;

	public function __construct($params = null) {
		parent::__construct($params);

		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->info = $this->getInfoByEnv();
	}

	# -- implement abstract functions --
	public function getPlatformCode() {
		return KDPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'kdpay';
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

		# read some parameters from config
		$paramNames = array('P_UserId');
		$params = array();
		foreach ($paramNames as $p) {
			$params[$p] = $this->getSystemInfo("kdpay_$p");
		}

		# other parameters
		$params['P_ChannelId'] = self::KDPAY_CHANNEL_IBANKING;
		$params['P_IsSmart'] = self::KDPAY_NOT_SMARTPHONE;
		$params['P_Result_URL'] = $this->getNotifyUrl($orderId);
		$params['P_Notify_URL'] = $this->getReturnUrl($orderId);

		# order-related params
		# data format reference the code sample, normalPay.php
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$params['P_OrderId'] = $order->secure_id;
		$params['P_FaceValue'] = $this->convertAmountToCurrency($amount);
		$params['P_Notic'] = '';
		$params['P_Subject'] = lang('pay.deposit');
		$params['P_Price'] = $this->convertAmountToCurrency($amount);
		$params['P_Quantity'] = 1;

		# all text fields must be in GB2312 encoding
		$params['P_Subject'] = iconv("UTF-8", "GB2312", $params['P_Subject']);

		# bank id
		$direct_pay_extra_info = $order->direct_pay_extra_info;
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bankCode = array_key_exists('banktype', $extraInfo) ? $extraInfo['banktype'] : $extraInfo['bank'];
				# Based on the sample code, P_Description is bankid
				if ($bankCode == self::KDPAY_CHANNEL_ALIPAY) {
					$params['P_ChannelId'] = self::KDPAY_CHANNEL_ALIPAY;
				} else if ($bankCode == self::KDPAY_CHANNEL_TENPAY) {
					$params['P_ChannelId'] = self::KDPAY_CHANNEL_TENPAY;
				} else if ($bankCode == self::KDPAY_CHANNEL_WECHAT) {
					$params['P_ChannelId'] = self::KDPAY_CHANNEL_WECHAT;
				} else {
					$params['P_Description'] = $bankCode;
				}
			}
		}

		# sign param
		$params['P_PostKey'] = $this->sign($params);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->info['url'],
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

	## $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			return $result;
		}

		# Update order payment status and balance
		// $this->CI->sale_order->startTrans();

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
				'', '', # neither of payment gateway order id or bank order id exist. Reference: documentation section (2)
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->CI->sale_order->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}
		$success = true; // $this->CI->sale_order->endTransWithSucc();

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

	## Validates whether the callback from API contains valid info and matches with the order
	## Reference: code sample, callback.php
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		# does all required fields exist?
		$requiredFields = array(
			'P_UserId', 'P_OrderId', 'P_FaceValue', 'P_ChannelId', 'P_ErrCode', 'P_PostKey',
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->verifyCallbackSign($fields, $fields['P_PostKey'])) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		# check User ID, payment amount, order ID, and return code
		# does merchNo match?
		if ($fields['P_UserId'] != $this->getSystemInfo('kdpay_P_UserId')) {
			$this->writePaymentErrorLog("Merchant codes do not match, expected [" . $this->getSystemInfo('kdpay_merchNo') . "]", $fields);
			return false;
		}

		# is payment successful?
		if (intval($fields['P_ErrCode']) != 0) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		# does amount match?
		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['P_FaceValue']))
		) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# does order_no match?
		if ($fields['P_OrderId'] != $order->secure_id) {
			$this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# Reference: HTTP支付接入文档V3.3.doc -> 银行通道附表
	protected function getBankListInfoFallback() {
		return array(
			array('label' => '微信', 'value' => '21'),
			array('label' => '支付宝', 'value' => '2'),
			array('label' => '财付通', 'value' => '3'),
			array('label' => '中国工商银行', 'value' => '10001'),
			array('label' => '中国农业银行', 'value' => '10002'),
			array('label' => '招商银行', 'value' => '10003'),
			array('label' => '中国银行', 'value' => '10004'),
			array('label' => '中国建设银行', 'value' => '10005'),
			array('label' => '中国民生银行', 'value' => '10006'),
			array('label' => '中信银行', 'value' => '10007'),
			array('label' => '交通银行', 'value' => '10008'),
			array('label' => '兴业银行', 'value' => '10009'),
			array('label' => '光大银行', 'value' => '10010'),
			array('label' => '深圳发展银行', 'value' => '10011'),
			array('label' => '中国邮政', 'value' => '10012'),
			array('label' => '北京银行', 'value' => '10013'),
			array('label' => '平安银行', 'value' => '10014'),
			array('label' => '上海浦东发展银行', 'value' => '10015'),
			array('label' => '广东发展银行', 'value' => '10016'),
			array('label' => '渤海银行', 'value' => '10017'),
			array('label' => '东亚银行', 'value' => '10018'),
			array('label' => '宁波银行', 'value' => '10019'),
			array('label' => '北京农村商业银行', 'value' => '10020'),
			array('label' => '南京银行', 'value' => '10021'),
			array('label' => '浙商银行', 'value' => '10022'),
			array('label' => '上海银行', 'value' => '10023'),
			array('label' => '上海农村商业银行', 'value' => '10024'),
			array('label' => '华夏银行', 'value' => '10025'),
			array('label' => '杭州银行', 'value' => '10027'),
			array('label' => '浙江稠州商业银行', 'value' => '10028'),
		);
		/* == extra_info config ==
			"bank_list": {
				"test" : {
					"name" : "_json:{\"1\": \"WeChat Pay\", \"2\": \"微信\"}", "sub_list" : {
						"2"  : "_json:{\"1\": \"Alipay\", \"2\": \"支付宝\"}",
						"3"  : "_json:{\"1\": \"Tenpay\", \"2\": \"财付通\"}"
					}
				},
				"21" : "_json:{\"1\": \"WeChat Pay\", \"2\": \"微信\"}",
				"2"  : "_json:{\"1\": \"Alipay\", \"2\": \"支付宝\"}",
				"3"  : "_json:{\"1\": \"Tenpay\", \"2\": \"财付通\"}",
				"10001" : "_json:{\"1\": \"ICBC\", \"2\": \"中国工商银行\"}",
				"10002" : "_json:{\"1\": \"ABC\", \"2\": \"中国农业银行\"}",
				"10003" : "_json:{\"1\": \"CMB\", \"2\": \"招商银行\"}",
				"10004" : "_json:{\"1\": \"BOC\", \"2\": \"中国银行\"}",
				"10005" : "_json:{\"1\": \"CCB\", \"2\": \"中国建设银行\"}",
				"10006" : "_json:{\"1\": \"CMBC\", \"2\": \"中国民生银行\"}",
				"10007" : "_json:{\"1\": \"CNCB\", \"2\": \"中信银行\"}",
				"10008" : "_json:{\"1\": \"COMM\", \"2\": \"交通银行\"}",
				"10009" : "_json:{\"1\": \"CIB\", \"2\": \"兴业银行\"}",
				"10010" : "_json:{\"1\": \"CEB\", \"2\": \"光大银行\"}",
				"10011" : "_json:{\"1\": \"???\", \"2\": \"深圳发展银行\"}",
				"10012" : "_json:{\"1\": \"PSBC\", \"2\": \"中国邮政\"}",
				"10013" : "_json:{\"1\": \"BOBJ\", \"2\": \"北京银行\"}",
				"10014" : "_json:{\"1\": \"PAB\", \"2\": \"平安银行\"}",
				"10015" : "_json:{\"1\": \"SPDB\", \"2\": \"上海浦东发展银行\"}",
				"10016" : "_json:{\"1\": \"CGB\", \"2\": \"广东发展银行\"}",
				"10017" : "_json:{\"1\": \"CBHB\", \"2\": \"渤海银行\"}",
				"10018" : "_json:{\"1\": \"BEA\", \"2\": \"东亚银行\"}",
				"10019" : "_json:{\"1\": \"???\", \"2\": \"宁波银行\"}",
				"10020" : "_json:{\"1\": \"BJCRB\", \"2\": \"北京农村商业银行\"}",
				"10021" : "_json:{\"1\": \"BONJ\", \"2\": \"南京银行\"}",
				"10022" : "_json:{\"1\": \"CZSB\", \"2\": \"浙商银行\"}",
				"10023" : "_json:{\"1\": \"???\", \"2\": \"上海银行\"}",
				"10024" : "_json:{\"1\": \"???\", \"2\": \"上海农村商业银行\"}",
				"10025" : "_json:{\"1\": \"HXB\", \"2\": \"华夏银行\"}",
				"10027" : "_json:{\"1\": \"BOHZ\", \"2\": \"杭州银行\"}",
				"10028" : "_json:{\"1\": \"???\", \"2\": \"浙江稠州商业银行\"}"
			}
		*/
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

	## sign the params
	public function sign($params) {
		# made it public for testing purpose
		$preEncodeStr =
		$params['P_UserId'] . "|" .
		$params['P_OrderId'] . "|" .
		(array_key_exists('P_CardId', $params) ? $params['P_CardId'] : '') . "|" .
		(array_key_exists('P_CardPass', $params) ? $params['P_CardPass'] : '') . "|" .
		$params['P_FaceValue'] . "|" .
		$params['P_ChannelId'] . "|" .
		$this->info['key'];
		$this->utils->debug_log('Siging this string: ' . $preEncodeStr);
		return md5($preEncodeStr);
	}

	## Verify param against a signature
	private function verify($data, $signature) {
		$mySign = $this->sign($data);
		return (strcasecmp($mySign, $signature) === 0);
	}

	public function callbackSign($params){

		$preEncodeStr =
		$params['P_UserId'] . "|" .
		$params['P_OrderId'] . "|" .
		(array_key_exists('P_CardId', $params) ? $params['P_CardId'] : '') . "|" .
		(array_key_exists('P_CardPass', $params) ? $params['P_CardPass'] : '') . "|" .
		$params['P_FaceValue'] . "|" .
		$params['P_ChannelId'] . "|" .
		$params['P_PayMoney'] . "|" .
		$params['P_ErrCode'] . "|" .
		$this->info['key'];
		$md5=md5($preEncodeStr);
		$this->CI->utils->debug_log('Siging this string: ' . $preEncodeStr, $md5);
		return $md5;

	}

	public function verifyCallbackSign($data, $signature){
		$mySign = $this->callbackSign($data);

		return (strcasecmp($mySign, $signature) === 0);
	}

}
