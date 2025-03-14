<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * SCTEKPAY 盛灿
 *
 *
 * * SCTEKPAY_PAYMENT_API, ID: 462
 * * SCTEKPAY_QUICKPAY_PAYMENT_API, ID: 463
 *
 *
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * PAY
 * * LIVE-URL: https://rpi.speedpos.cn/ecurrencypay/pay
 * * TEST-URL: http://rpi.snsshop.net/ecurrencypay/pay
 *
 * * QUICKPAY
 * * LIVE-URL: https://upay.szyinfubao.com/quickPay/pay
 * * TEST-URL: http://routepay.snsshop.net/quickPay/pay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_sctekpay extends Abstract_payment_api {

	//const QRCODE_REPONSE_CODE_SUCCESS = 'SUCCESS';
	//const ORDER_STATUS_SUCCESS = 'success002';
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const RETURN_FAILED_CODE = 'FAIL';
	const TRADE_STATUS_SUCCESS = '0'; //错误码,0 表示成功,其他表示失败
	const TRADE_ERROR_MESSAGE = ''; //返回信息,如非空,为错误原因

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
		$params['mch_key'] = $this->getSystemInfo('key');
		$params['mch_id'] = $this->getSystemInfo('account');
		$params['user_id'] = $this->getSystemInfo('account');
		$params['out_order_no'] = $order->secure_id;
		$params['card_type'] = '2';
		$params['pay_type'] = 'B2C';
		$params['payment_fee'] = ($this->convertAmountToCurrency($amount)*100);
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['return_url'] = $this->getReturnUrl($orderId);
		$params['body'] = 'deposit';

		$this->configParams($params, $order->direct_pay_extra_info);

		ksort($params);

		$jsonString = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

		$signature = '';
		$signature = $this->sign($params);
		$postParams = array('biz_content' => $jsonString,'signature' => $signature,'sign_type' => 'MD5');

		$this->CI->utils->debug_log("=====================sctekpay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($postParams);

	}

	protected function processPaymentUrlFormPost($params) {

		$queryString = http_build_query($params);
		$postUrl = $this->getSystemInfo('url').'?'.$queryString;

		$this->CI->utils->debug_log("=====================sctekpay postUrl", $postUrl);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $postUrl,
			'params' => $params,
			'post' => false
		);
	}

	# Display QRCode get from curl
	// protected function processPaymentUrlFormQRCode($params) {
	// 	$this->CI->utils->debug_log('=====================sctekpay scan url', $this->getSystemInfo('url'));
	// 	$queryString = http_build_query($params);
	// 	$postUrl = $this->getSystemInfo('url').'?'.$queryString;
	// 	$response = $this->submitPostForm($postUrl, $params, true, $params['out_order_no']);
	// 	$response = json_decode($response, true);

	// 	$this->CI->utils->debug_log('=====================sctekpay response', $response);

	// 	$msg = lang('Invalidte API response');

	// 	if($response['errCode'] == '0') {
	// 		return array(
	// 			'success' => true,
	// 			'type' => self::REDIRECT_TYPE_QRCODE,
	// 			'url' => $response['scanurl']
	// 		);
	// 	}
	// 	else {
	// 		if($response['errmsg']) {
	// 			$msg = $response['errmsg'];
	// 		}

	// 		return array(
	// 			'success' => false,
	// 			'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
	// 			'message' => $msg
	// 		);
	// 	}
	// }

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

        $this->CI->utils->debug_log("=====================sctekpay callbackFrom $source params", $params);

		if($source == 'server'){
			$raw_post_data = file_get_contents('php://input');
			$flds = json_decode($raw_post_data, true);
			$params = array_merge( $params, $flds );

			if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
				return $result;
			}
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['order_no'], null, null, null, $response_result_id);
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
			$result['return_error'] = self::RETURN_FAILED_CODE;
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
			'ret_code', 'ret_msg', 'signature', 'biz_content'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================sctekpay checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['ret_code'] != self::TRADE_STATUS_SUCCESS) {
			$this->writePaymentErrorLog('=======================sctekpay checkCallbackOrder payment was not successful', $fields);
			return false;
		}

		// if ($fields['ret_msg'] != self::TRADE_ERROR_MESSAGE) {
		// 	$this->writePaymentErrorLog('=======================sctekpay checkCallbackOrder payment_message was not return', $fields);
		// 	return false;
		// }

		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================sctekpay checkCallbackOrder signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['biz_content']['out_order_no'] != $order->secure_id) {
			$this->writePaymentErrorLog("=====================sctekpay checkCallbackOrder payment , Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}


		if (
			$this->convertAmountToCurrency($order->amount)*100 !=
			$this->convertAmountToCurrency(floatval($fields['biz_content']['payment_fee']))
		) {
			$this->writePaymentErrorLog("=====================sctekpay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
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

		ksort($params);

		$jsonString = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
		$stringSignTemp =  'biz_content=' . $jsonString . "&key=" . $params['mch_key'];

		$string = '';
		$string = md5($stringSignTemp);
		$sign = strtoupper($string);

		return $sign;
	}

	##validate whether callback signature is correspond with sign of callback biz_conent or not
	private function validateSign($content){
		$key = $this->getSystemInfo('key');

		$callback_params = $content['biz_content'];
		$callback_params['create_time'] = str_replace('_', ' ', $callback_params['create_time']);
		$callback_params['pay_time'] = str_replace('_', ' ', $callback_params['pay_time']);
		$callback_sign = $content['signature'];

		ksort($callback_params);

		$jsonString = json_encode($callback_params,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
		$StringSignTemp = 'biz_content=' . $jsonString . "&key=" . $key;
		$String = md5($StringSignTemp);
		$sign = strtoupper($String);

		if($callback_sign != $sign){
			return false;
		}

		return true;
	}

	protected function getBankListInfoFallback() {
		return array(
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '建设银行', 'value' => 'CCB'),
			array('label' => '光大银行', 'value' => 'CEB'),
			array('label' => '工商银行', 'value' => 'ICBC'),
			array('label' => '交通银行', 'value' => 'BCOM'),
			array('label' => '邮储银行', 'value' => 'PSBC'),
			array('label' => '北京银行', 'value' => 'BOB'),
			array('label' => '上海银行', 'value' => 'BOS'),
			array('label' => '农业银行', 'value' => 'ABC')
		);
	}

	protected function getBankName($bankType) {

		$bankList = $this->getBankListInfoFallback();
		foreach ($bankList as $list) {
			if ($list['value'] == $bankType) {
				return $list['label'];
			}
		}
	}
}
