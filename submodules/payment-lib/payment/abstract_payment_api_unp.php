<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * UNP UNP支付
 * http://wiki.unpayonline.com:8800/doku.php?id=api_for_unp
 *
 * UNP_PAYMENT_API, ID: 271
 * UNP_ALIPAY_PAYMENT_API, ID: 272
 * UNP_QQPAY_PAYMENT_API, ID: 273
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
 * * URL: http://center.qpay888.com/Bank
 * * Extra Info
 * > {
 * >	"unp_partner" : "## Partner ID ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_unp extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'opstate=0';
	const RETURN_FAILED_CODE = 'opstate!=0';
	const OPSTAT_SUCCESS = '0';
	const PAYMENT_TYPE_BANK = '102';


	const PAYMENT_TYPE_ALIPAY_H5 = '980';
	const PAYMENT_TYPE_ALIPAY = '98';
	const PAYMENT_TYPE_WEIXIN = '99';
	const PAYMENT_TYPE_WEIXIN_H5 = '990';
	const PAYMENT_TYPE_QQPAY = '100';
	const PAYMENT_TYPE_QQPAY_H5 = '201';

	const PAYMENT_TYPE_UNIONPAY = '101';
	const PAYMENT_TYPE_JDPAY = '202';
	const PAYMENT_TYPE_QUICKPAY = '1020';


	# Add in implementation-dependent specific params
	protected abstract function configParams(&$params, $direct_pay_extra_info);

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

		$params['parter'] = $this->getSystemInfo("account");
		$params['type'] = $this->getBankType($order->direct_pay_extra_info);

		$params['value'] = $this->convertAmountToCurrency($amount);
		$params['orderid'] = $order->secure_id;

		$params['callbackurl'] = $this->getNotifyUrl($orderId);
		$params['hrefbackurl'] = $this->getReturnUrl($orderId);

		$params['agent'] = '';

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================unp generatePaymentUrlForm params", $params);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => false, # sent using GET
		);
	}

	public abstract function getBankType($direct_pay_extra_info);

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
		# According to documentation, callback from browser cannot be used to change order status. We need to rely on callback from server.
		# Return success here.
		return array('success' => true, 'next_url' => $this->getPlayerBackUrl());
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;
		if($source == 'server' ){
			if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
				return $result;
			}
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
				$params['sysorderid'], '', # only platform order id exist
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
			'orderid', 'opstate', 'ovalue', 'sign'
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
		if ($fields['opstate'] != self::OPSTAT_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		# does amount match?
		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['ovalue']))
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

	/**
	 *
	 * detail: a static bank list information
	 *
	 * note: Reference: sample code, Mobaopay.Config.php
	 *
	 * @return array
	 */
	public function getBankListInfoFallback() {
		return array(
			array('label' => '中信银行', 'value' => '962'),
			array('label' => '中国银行', 'value' => '963'),
			array('label' => '中国农业银行', 'value' => '964'),
			array('label' => '中国建设银行', 'value' => '965'),
			array('label' => '中国工商银行', 'value' => '967'),
			array('label' => '浙商银行', 'value' => '968'),
			array('label' => '招商银行', 'value' => '970'),
			array('label' => '邮政储蓄', 'value' => '971'),
			array('label' => '兴业银行', 'value' => '972'),
			array('label' => '深圳发展银行', 'value' => '974'),
			array('label' => '上海银行', 'value' => '975'),
			array('label' => '上海农村商业银行', 'value' => '976'),
			array('label' => '浦东发展银行', 'value' => '977'),
			array('label' => '平安银行', 'value' => '978'),
			array('label' => '南京银行', 'value' => '979'),
			array('label' => '民生银行', 'value' => '980'),
			array('label' => '交通银行', 'value' => '981'),
			array('label' => '华夏银行', 'value' => '982'),
			array('label' => '杭州银行', 'value' => '983'),
			array('label' => '广州农村商业银行', 'value' => '984'),
			array('label' => '广东发展银行', 'value' => '985'),
			array('label' => '光大银行', 'value' => '986'),
			array('label' => '东亚银行', 'value' => '987'),
			array('label' => '渤海银行', 'value' => '988'),
			array('label' => '北京银行', 'value' => '989'),
			array('label' => '北京农商银行', 'value' => '990'),
			array('label' => '广州银行', 'value' => '995'),
			array('label' => '中国银联', 'value' => '996'),
			array('label' => '宁波银行', 'value' => '998'),
			array('label' => '徽商银行', 'value' => '999'),
			array('label' => 'PC 微信扫码', 'value' => '991'),
			array('label' => 'PC 支付宝扫码', 'value' => '992'),
			array('label' => '手机微信扫码', 'value' => '1007'),
			array('label' => '手机支付宝扫码', 'value' => '1006')
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

	private function prepareSign($data) {
		$array = array();
		$keys = array('parter', 'type', 'value', 'orderid', 'tyid', 'callbackurl');
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
		$keys = array('orderid', 'opstate', 'ovalue');
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
		$signature = MD5($dataStr.$this->getSystemInfo('key'));
		if ($data['sign'] == $signature) {
			return true;
		} else {
			return false;
		}
	}

}
