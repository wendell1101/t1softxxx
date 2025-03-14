<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * XFUOO 信付通
 *
 * * XFUOO_PAYMENT_API, ID: 380
 * * XFUOO_ALIPAY_PAYMENT_API, ID: 381
 * * XFUOO_WEIXIN_PAYMENT_API, ID: 382
 * * XFUOO_QQPAY_PAYMENT_API, ID: 383
 * * XFUOO_WITHDRAWAL_PAYMENT_API, ID: 384
 * * XFUOO_JDPAY_PAYMENT_API, ID: 399
 * * XFUOO_UNIONPAY_PAYMENT_API, ID: 400
 * * XFUOO_QUICKPAY_PAYMENT_API, ID: 416
 * * XFUOO_UNIONQRPAY_PAYMENT_API, ID: 557
 * * XFUOO_JDPAY_H5_PAYMENT_API, ID: 632
 * * XFUOO_ALIPAY_H5_PAYMENT_API, ID: 685
 * * XFUOO_QUICKPAY_H5_PAYMENT_API, ID: 5394
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Sha key
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_xfuoo extends Abstract_payment_api {

	const DEFAULTBANK_ALIPAY     = 'ALIPAY';
	const DEFAULTBANK_WEIXIN     = 'WXPAY';
	const DEFAULTBANK_QQPAY      = 'QQPAY';
	const DEFAULTBANK_JDPAY      = 'JDPAY';
	const DEFAULTBANK_UNIONPAY   = 'UNIONPAY';
	const DEFAULTBANK_UNIONQRPAY = 'UNIONQRPAY';
	const DEFAULTBANK_QUICKPAY   = 'EASYQUICK';
	const DEFAULTBANK_BDPAY      = 'BDPAY';

	const RETURN_SUCCESS_CODE  = 'success';
	const TRADE_STATUS_SUCCESS = 'T';
	const RETURN_FAILED_CODE   = 'FAILED';
	const REPONSE_SUCCESS      = 'S0001';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these to specify pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params = array();
		$params['body']        = 'Deposit';
		$params['charset']     = 'UTF-8';
		$params['isApp']       = 'web';
		$params['merchantId']  = $this->getSystemInfo("account");
		$params['notifyUrl']   = $this->getNotifyUrl($orderId);
		$params['orderNo']     = $order->secure_id;
		$params['paymentType'] = '1';
		$params['paymethod']   = 'directPay';
		$params['returnUrl']   = $this->getReturnUrl($orderId);
		$params['service']     = 'online_pay';
		$params['title']       = 'Deposit';
		$params['totalFee']    = $this->convertAmountToCurrency($amount);
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign']        = $this->sign($params);
		$params['signType']    = 'SHA';

		$this->CI->utils->debug_log('======================================xfuoo generatePaymentUrlForm: ', $params);
		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
		// $queryStr = $this->createSignStr($params);
		$url = $this->getSystemInfo('url').'/payment/v1/order/'.$params['merchantId'].'-'.$params['orderNo'];
		$this->CI->utils->debug_log('======================================xfuoo url: ', $url);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $url,
			'params' => $params,
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

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$this->utils->debug_log('callbackFrom' . ucfirst($source) . ': [' . $orderId .'], params:', $params);

		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		if (!$order) {
			$this->utils->error_log("=======================xfuoo callback order ID [$orderId] not found.");
			return $result;
		}
		if($source == 'server' ){
			$callbackValid = false;
			$paymentSuccessful = $this->checkCallbackOrder($order, $params, $callbackValid); # $callbackValid is also assigned

			# Do not print success msg if callback fails integrity check
			if(!$callbackValid) {
				return $result;
			}

			# Do not proceed to update order status if payment failed, but still print success msg as callback response
			if(!$paymentSuccessful) {
				$result['return_error'] = self::RETURN_FAILED_CODE;
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
			$this->CI->sale_order->updateExternalInfo($order->id,$params['order_no'], '',null, null, $response_result_id);
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
			'body', 'gmt_create', 'gmt_logistics_modify', 'gmt_payment', 'is_success', 'is_total_fee_adjust', 'notify_id', 'notify_time', 'notify_type', 'order_no', 'payment_type', 'price', 'quantity', 'seller_actions', 'seller_email', 'seller_id', 'title', 'total_fee', 'trade_no', 'trade_status', 'sign', 'signType'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=======================xfuoo checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		if($this->ignore_callback_sign){
			$this->CI->utils->debug_log('=======================xfuoo checkCallbackOrder ignore callback sign', $fields, $order, $this->validateSign($fields));

		}else{
			# is signature authentic?
			if (!$this->validateSign($fields)) {
				$this->writePaymentErrorLog('=======================xfuoo checkCallbackOrder signature Error', $fields);
				return false;
			}
		}

		$callbackValid = true; # callbackValid is set to true once the signature verification pass

		if ($fields['order_no'] != $order->secure_id) {
			$this->writePaymentErrorLog("=======================xfuoo checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		if ($fields['is_success'] != self::TRADE_STATUS_SUCCESS) {
			$this->writePaymentErrorLog('=======================xfuoo checkCallbackOrder payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['total_fee']) {
			$this->writePaymentErrorLog("=======================xfuoo checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
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
			array('value' => 'CMB', 'label' => '招商银行'),
			array('value' => 'ICBC', 'label' => '工商银行'),
			array('value' => 'CCB', 'label' => '建设银行'),
			array('value' => 'BOC', 'label' => '中国银行'),
			array('value' => 'ABC', 'label' => '农业银行'),
			array('value' => 'BOCM', 'label' => '交通银行'),
			array('value' => 'SPDB', 'label' => '浦发银行'),
			array('value' => 'CGB', 'label' => '广发银行'),
			array('value' => 'CITIC', 'label' => '中信银行'),
			array('value' => 'CEB', 'label' => '光大银行'),
			array('value' => 'CIB', 'label' => '兴业银行'),
			array('value' => 'PAYH', 'label' => '平安银行'),
			array('value' => 'CMBC', 'label' => '民生银行'),
			array('value' => 'HXB', 'label' => '华夏银行'),
			array('value' => 'PSBC', 'label' => '邮储银行'),
			array('value' => 'BCCB', 'label' => '北京银行'),
			array('value' => 'SHBANK', 'label' => '上海银行'),
		);
	}

	private function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	# -- signing --
	public function sign($params) {
		$signStr = $this->createSignStr($params);

		$signStr .= $this->getSystemInfo('key');
		$shaSign = strtoupper(sha1($signStr));

		return $shaSign;
	}

	public function validateSign($params) {
		$signStr = $this->createSignStr($params);

		$signStr .= $this->getSystemInfo('key');
		$shaSign = strtoupper(sha1($signStr));

		return strcasecmp($shaSign, $params['sign']) === 0;
	}

	public function createSignStr($params) {
	    ksort($params);
	    $string = "";

	    foreach ($params as $name => $value) {
			if($name != 'sign' && $name != 'signType') {
				$string .= $name . '=' . $value . '&';
			}
		}
	    $string = substr($string, 0, strlen($string) -1 );

	    return $string;
	}
}
