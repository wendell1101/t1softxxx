<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * BOFUBAOPAY 博付宝
 *
 *
 * * BOFUBAOPAY_PAYMENT_API, ID: 458
 * * BOFUBAOPAY_WEIXIN_PAYMENT_API, ID: 459
 * * BOFUBAOPAY_QQPAY_PAYMENT_API, ID: 460
 * * BOFUBAOPAY_ALIPAY_PAYMENT_API, ID: 461
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://bofubao.qingzhuzi.com/qingpay.php
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_bofubaopay extends Abstract_payment_api {
	const BANK_BANK = 'BANK';
	const BANK_WEIXIN = 'WEIXIN';
	const BANK_QQPAY = 'QQ';
	const BANK_ALIPAY = 'ALIPAY';

	const RETURN_SUCCESS_CODE = 'ok';
	const RETURN_FAILED_CODE = 'faile';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these for specific pay type
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
		$params['c'] = 'qingpay';
		$params['money'] = $this->convertAmountToCurrency($amount);
		$params['order'] = $order->secure_id;
		$params['return'] = $this->getReturnUrl($orderId);
		$params['notify'] = $this->getNotifyUrl($orderId);
		$params['uid'] = $this->getSystemInfo('account');
		$params['key'] = $this->getSystemInfo('key');

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['md5'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================bofubaopay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);

	}

	protected function processPaymentUrlFormPost($params) {

		$queryString = http_build_query($params);
		$postUrl = $this->getSystemInfo('url').'?'.$queryString;

		$this->CI->utils->debug_log("=====================bofuaopay postUrl", $postUrl);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $postUrl,
			'params' => $params,
			'post' => false
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
			$this->CI->sale_order->updateExternalInfo($order->id, null, null, null, null, $response_result_id);
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
			'apiuid', 'apikey', 'money', 'num', 'paytype', 'ods', 'md5'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================bofubaopay checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		// if (!$this->validateSign($fields)) {
		// 	$this->writePaymentErrorLog('=====================bofubaopay checkCallbackOrder signature Error', $fields);
		// 	return false;
		// }

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['ods'] != $order->secure_id) {
			$this->writePaymentErrorLog("=====================bofubaopay checkCallbackOrder payment , Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}


		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['money']))
		) {
			$this->writePaymentErrorLog("=====================bofubaopay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
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

	private function sign($params) {
		$keys = array('money', 'bank', 'order', 'notify', 'uid');
		$signStr = '';
		foreach($keys as $key) {
			$signStr .= $params[$key];
		}
		$signStr .= $this->getSystemInfo('key');
		$sign = md5($signStr);
		return $sign;
	}

	protected function getBankListInfoFallback() {
		return array(
			array('label' => '中国农业银行', 'value' => 'ABC'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '中国建设银行', 'value' => 'CCB'),
			array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
			array('label' => '交通银行', 'value' => 'BOCO'),
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '中国民生银行', 'value' => 'CMBC'),
			array('label' => '中国光大银行', 'value' => 'CEB'),
			array('label' => '中信银行', 'value' => 'CITIC'),
			array('label' => '广发银行', 'value' => 'GDB'),
			array('label' => '华夏银行', 'value' => 'HXB'),
			array('label' => '浦发银行', 'value' => 'SPDB'),
			array('label' => '北京银行', 'value' => 'BJBANK'),
			array('label' => '北京农商银行', 'value' => 'BJRCB'),
			array('label' => '宁波银行', 'value' => 'NBBANK'),
			array('label' => '深圳发展银行', 'value' => 'SDB'),
			array('label' => '杭州银行', 'value' => 'HZCB'),
			array('label' => '南京银行', 'value' => 'NJCB')
		);
	}
}
