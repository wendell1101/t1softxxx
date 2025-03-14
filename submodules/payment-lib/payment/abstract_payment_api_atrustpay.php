<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ATRUSTPAY 信付宝
 *
 * * ATRUSTPAY_PAYMENT_API, ID: 477
 * * ATRUSTPAY_WEIXIN_PAYMENT_API, ID: 478
 * *
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_atrustpay extends Abstract_payment_api {
	const PAYMODE_BANK = '00020';
	const PAYMODE_WEXIN_WAP = '00020';


	const QRCODE_REPONSE_CODE_SUCCESS = '00';
	const ORDER_STATUS_SUCCESS = '01';

	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const RETURN_FAILED_CODE = 'FAIL';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

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

		$params['versionId'] = '1.0';
		$params['orderAmount'] = $this->convertAmountToCurrency($amount);
		$params['orderDate'] = date('YmdHis');
		$params['currency'] = 'RMB';

		$params['accountType'] = '0';
		$params['transType'] = '008';
		$params['asynNotifyUrl'] = $this->getNotifyUrl($orderId);
		$params['synNotifyUrl'] = $this->getReturnUrl($orderId);
		$params['signType'] = 'MD5';
		$params['merId'] = $this->getSystemInfo("account");
		$params['prdOrdNo'] = $order->secure_id;


		$params['prdName'] = 'Deopsit';
		$params['prdDesc'] = 'Deopsit';
		$params['pnum'] = '1';


		$this->configParams($params, $order->direct_pay_extra_info);

		$params['signData'] = $this->sign($params);


		$this->CI->utils->debug_log("=====================atrustpay generatePaymentUrlForm", $params);

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
	protected function processPaymentUrlFormWeixinH5($params) {
		$response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['prdOrdNo']);

		$url = preg_replace('/[\r\n\t ]+/','', strip_tags($response));
		$this->CI->utils->debug_log("=====================atrustpay processPaymentUrlFormWeixinH5 strip_tags", $url);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_URL,
			'url' => $url,
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

		if($source == 'server'){
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['prdOrdNo'], null, null, null, $response_result_id);
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
		$requiredFields = array(
			'versionId', 'transType', 'asynNotifyUrl', 'synNotifyUrl', 'merId', 'orderAmount', 'prdOrdNo', 'orderStatus', 'payId', 'payTime', 'signType', 'signData'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================atrustpay missing parameter: [$f]", $fields);
				return false;
			}
		}


		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================atrustpay checkCallbackOrder signature Error', $fields);
			return false;
		}


		if ($fields['prdOrdNo'] != $order->secure_id) {
			$this->writePaymentErrorLog("=====================atrustpay checkCallbackOrder payment , Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		if ($fields['orderStatus'] != self::ORDER_STATUS_SUCCESS) {
			$payStatus = $fields['orderStatus'];
			$this->writePaymentErrorLog("=====================atrustpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['orderAmount'] )
		) {
			$this->writePaymentErrorLog("=====================atrustpay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	public function getBankListInfoFallback() {
		return array(
			array('label' => '工商银行', 'value' => '102'),
			array('label' => '农业银行', 'value' => '103'),
			array('label' => '中国银行', 'value' => '104'),
			array('label' => '建设银行', 'value' => '105'),
			array('label' => '交通银行', 'value' => '301'),
			array('label' => '中信银行', 'value' => '302'),
			array('label' => '光大银行', 'value' => '303'),
			array('label' => '华夏银行', 'value' => '304'),
			array('label' => '广发银行', 'value' => '306'),
			array('label' => '平安银行', 'value' => '307'),
			array('label' => '兴业银行', 'value' => '309'),
			array('label' => '浦发银行', 'value' => '310'),
			array('label' => '北京银行', 'value' => '313'),
			array('label' => '上海银行', 'value' => '325'),
			array('label' => '邮储银行', 'value' => '403')
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
		return number_format($amount * 100, 0, '.', '');
	}

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
		ksort($params);
		$signStr = '';
		foreach ($params as $name => $value) {
			$signStr .= $name . '=' . $value . '&';
		}

		$signStr .= 'key='. $this->getSystemInfo('key');
	    $sign = strtoupper(md5($signStr));
		return $sign;
	}


	## callback signature
	private function validateSign($params){
		$callback_sign = strtoupper($params['signData']) ;
		unset($params['signData']);

		ksort($params);
		$signStr = '';

		foreach ($params as $name => $value) {
			if ($name == 'key' || $name == 'signData' || is_null($value) || $value == '') {
				continue;
			}
			$signStr .= $name . '=' . $value . '&';

		}

		$signStr .= 'key='. $this->getSystemInfo('key');
	    $sign = strtoupper(md5($signStr));

		if($callback_sign != $sign){
			return false;
		} else {
			return true;
		}
	}
}
