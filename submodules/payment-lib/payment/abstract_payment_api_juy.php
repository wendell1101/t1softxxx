<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 *
 * juy 聚源
 *
 * JUY_PAYMENT_API, ID: 103
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: http://pay.juypay.com/PayBank.aspx
 * * Extra Info:
 * > {
 * >  	"juy_partner" : "## Partner ID ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_juy extends Abstract_payment_api {

	const RETURN_SUCCESS_CODE = 'ok';
	const ORDER_STATUS_SUCCESS = 1;

	public function __construct($params = null) {
		parent::__construct($params);
	}

	abstract public function getBankType($direct_pay_extra_info);

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

		# Reference: Documentation section 3.1
		# constant parameters
		$params['partner'] = $this->getSystemInfo('juy_partner');

		# order-related params
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$params['banktype'] = $this->getBankType($order->direct_pay_extra_info);

		$params['paymoney'] = $this->convertAmountToCurrency($amount);
		$params['ordernumber'] = $order->secure_id;

		$params['callbackurl'] = $this->getNotifyUrl($orderId);
		$params['hrefbackurl'] = $this->getReturnUrl($orderId);

		# sign param
		$params['sign'] = $this->sign($params);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => false,
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['sysnumber'], null, null, null, $response_result_id);
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
		$requiredFields = array(
			'partner', 'ordernumber', 'orderstatus', 'paymoney'
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

		if ($fields['orderstatus'] != self::ORDER_STATUS_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['paymoney']))
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
			array('label' => '工商银行', 'value' => 'ICBC'),
			array('label' => '农业银行', 'value' => 'ABC'),
			array('label' => '建设银行', 'value' => 'CCB'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '北京银行', 'value' => 'BCCB'),
			array('label' => '交通银行', 'value' => 'BOCO'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '南京银行', 'value' => 'NJCB'),
			array('label' => '民生银行', 'value' => 'CMBC'),
			array('label' => '光大银行', 'value' => 'CEB'),
			array('label' => '平安银行', 'value' => 'PINGANBANK'),
			array('label' => '渤海银行', 'value' => 'CBHB'),
			array('label' => '东亚银行', 'value' => 'HKBEA'),
			array('label' => '宁波银行', 'value' => 'NBCB'),
			array('label' => '中信银行', 'value' => 'CTTIC'),
			array('label' => '广发银行', 'value' => 'GDB'),
			array('label' => '上海银行', 'value' => 'SHB'),
			array('label' => '上海浦东发展银行', 'value' => 'SPDB'),
			array('label' => '中国邮政', 'value' => 'PSBS'),
			array('label' => '华夏银行', 'value' => 'HXB'),
			array('label' => '北京农村商业银行', 'value' => 'BJRCB'),
			array('label' => '上海农商银行', 'value' => 'SRCB'),
			array('label' => '深圳发展银行', 'value' => 'SDB'),
			array('label' => '浙江稠州商业银行', 'value' => 'CZB'),
			array('label' => '支付宝', 'value' => 'ALIPAY'),
			array('label' => '支付宝扫码', 'value' => 'ALIPAYSCAN'),
			array('label' => '财付通', 'value' => 'TENPAY'),
			array('label' => '微信', 'value' => 'WEIXIN'),
			array('label' => 'QQ钱包', 'value' => 'QQ'),
			array('label' => 'WAP支付宝', 'value' => 'ALIPAYWAP'),
			array('label' => 'WAP财付通', 'value' => 'TENPAYWAP'),
			array('label' => 'WAP微信', 'value' => 'WEIXINWAP'),
			array('label' => 'WAPQQ钱包', 'value' => 'QQWAP'),
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

	# -- private helper functions --
	# Sign the data for submitting to payment API
	# Reference: Documentation, last section
	public function sign($data) {
		$partner = $data['partner'];
		$banktype = $data['banktype'];
		$paymoney = $data['paymoney'];
		$ordernumber = $data['ordernumber'];
		$callbackurl = $data['callbackurl'];
		$key = $this->getSystemInfo('key');

		$signSource = sprintf("partner=%s&banktype=%s&paymoney=%s&ordernumber=%s&callbackurl=%s%s", $partner, $banktype, $paymoney, $ordernumber, $callbackurl, $key);
		$sign = md5($signSource);

		return strtolower($sign);
	}

	public function signCallback($data) {
		$partner = $data['partner'];
		$ordernumber = $data['ordernumber'];
		$orderstatus = $data['orderstatus'];
		$paymoney = $data['paymoney'];
		$key = $this->getSystemInfo('key');

		$signSource = sprintf("partner=%s&ordernumber=%s&orderstatus=%s&paymoney=%s%s", $partner, $ordernumber, $orderstatus, $paymoney, $key);
		$sign = md5($signSource);

		return strtolower($sign);
	}

	public function verifySignature($data) {
		$mySign = $this->signCallback($data);
		if (strcasecmp($mySign, $data['sign']) === 0) {
			return true;
		} else {
			return false;
		}
	}

}
