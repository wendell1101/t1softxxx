<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * KCPAY 卡诚
 *
 *
 * * KCPAY_PAYMENT_API, ID: 206
 * * KCPAY_ALIPAY_PAYMENT_API, ID: 207
 * * KCPAY_WEIXIN_PAYMENT_API, ID: 208
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
 * * URL: http://api.kcpay.net/paybank.aspx
 * * Extra Info
 * > {
 * >	"kcpay_partner" : "##Partner ID##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_kcpay extends Abstract_payment_api {
	const BANK_TYPE_WEIXIN = 'WEIXIN';
	const BANK_TYPE_WEIXIN_WAP = 'WEIXIN_WAP';
	const BANK_TYPE_ALIPAY = 'ALIPAY';
	const BANK_TYPE_ALIPAY_WAP = 'ALIPAYWAP';
	const RETURN_SUCCESS_CODE = 'ok';
	const RETURN_FAILED_CODE = 'failed';
	const PAYMENT_SUCCESS = '1';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	public abstract function getBankType($direct_pay_extra_info);

	/**
	 * detail: Constructs an URL so that the caller can redirect / invoke it to make payment through this API, See controllers/redirect.php for detail.
	 *
	 * @param int $orderId order id
	 * @param int $playerId player id
	 * @param float $amount amount
	 * @param string $orderDateTime
	 * @param int $playerPromoId
	 * @param string $enabledSecondUrl
	 * @param int $bankId
	 * @return array
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params['partner'] = $this->getSystemInfo("kcpay_partner");
		$params['paymoney'] = $this->convertAmountToCurrency($amount);
		$params['ordernumber'] = $order->secure_id;

		$params['callbackurl'] = $this->getNotifyUrl($orderId);
		$params['hrefbackurl'] = $this->getReturnUrl($orderId);

		$params['banktype'] = $this->getBankType($order->direct_pay_extra_info);

		$params['sign'] = $this->sign($params);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => false, # sent using GET
		);
	}

	/**
	 * detail: This will be called when the payment is async, API server calls our callback page,
	 * When that happens, we perform verifications and necessary database updates to mark the payment as successful
	 *
	 * @param int $orderId order id
	 * @param array $params
	 * @return array
	 */
	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	/**
	 * detail: This will be called when user redirects back to our page from payment API
	 *
	 * @param int $orderId order id
	 * @param array $params
	 * @return array
	 */
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
				$params['sysnumber'], '', null, null, $response_result_id);
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

	/**
	 * detail: Validates whether the callback from API contains valid info and matches with the order
	 *
	 * @return boolean
	 */
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		# does all required fields exist?
		$requiredFields = array(
			'partner', 'ordernumber', 'orderstatus', 'paymoney', 'sysnumber', 'sign'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->verify($fields, $fields['sign'])) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		# check parameter values: orderStatus, tradeAmt, orderNo, merchNo
		# is payment successful?
		if ($fields['orderstatus'] != self::PAYMENT_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		# does amount match?
		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['paymoney']))
		) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# does merchNo match?
		if ($fields['partner'] != $this->getSystemInfo('kcpay_partner')) {
			$this->writePaymentErrorLog("Merchant codes do not match, expected [" . $this->getSystemInfo('kcpay_merchNo') . "]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	/**
	 *
	 * detail: a static bank list information
	 *
	 * note: Reference: sample code, Mobaopay.Config.php
	 *
	 * @return array
	 */
	protected function getBankListInfoFallback() {
		return array(
			array('label' => '微信', 'value' => 'WEIXIN'),
			array('label' => '微信手机', 'value' => 'WEIXINWAP'),
			array('label' => '支付宝', 'value' => 'ALIPAY'),
			array('label' => '财付通', 'value' => 'TENPAY'),
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
		);
	}

	# -- Private functions --
	/**
	 * detail: After payment is complete, the gateway will invoke this URL asynchronously
	 *
	 * @param int $orderId
	 * @return void
	 */
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: After payment is complete, the gateway will send redirect back to this URL
	 *
	 * @param int $orderId
	 * @return void
	 */
	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: Format the amount value for the API
	 *
	 * @param float $amount
	 * @return float
	 */
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- private helper functions --

	/**
	 *
	 * detail: copied from code sample MobaoPay.class.php
	 *
	 * @name	准备签名/验签字符串
	 * @desc prepare urlencode data
	 * @web_pay_b2c,wap_pay_b2c
	 * apiName,apiVersion,platformID,merchNo,orderNo,tradeDate,amt,merchUrl,merchParam,tradeSummary
	 * @pay_result_notify
	 * apiName,notifyTime,tradeAmt,merchNo,merchParam,orderNo,tradeDate,accNo,accDate,orderStatus
	 *
	 * @param array $data
	 * @return array
	 */
	private function prepareSign($data) {
		$array = array();
		$keys = array('partner', 'banktype', 'paymoney', 'ordernumber', 'callbackurl');
		foreach ($keys as $key) {
			array_push($array, $key . '=' . $data[$key]);
		}
		return implode($array, '&');
	}

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($data) {
		$dataStr = $this->prepareSign($data);
		$signature = MD5($dataStr . $this->getSystemInfo('key'));
		return $signature;
	}

	public function prepareVerify($data) {
		$array = array();
		$keys = array('partner', 'ordernumber', 'orderstatus', 'paymoney');
		foreach ($keys as $key) {
			array_push($array, $key . '=' . $data[$key]);
		}
		return implode($array, '&');
	}

	/**
	 * detail: verify signature
	 *
	 * @param array $data
	 * @param string $signature
	 * @return	string
	 */
	public function verify($data, $signature) {
		$dataStr = $this->prepareVerify($data);
		$original = $dataStr . $this->getSystemInfo('key');
		$signature = MD5($original);

		if ($data['sign']== $signature) {
			return true;
		} else {
			return false;
		}
	}

}
