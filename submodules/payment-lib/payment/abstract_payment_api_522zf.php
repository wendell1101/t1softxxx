<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * 522ZF
 *
 * * _522ZF_ALIPAY_PAYMENT_API, ID: 824
 * * _522ZF_WEIXIN_PAYMENT_API, ID: 825
 * * _522ZF_ALIPAY_H5_PAYMENT_API, ID: 832
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://13.230.254.56:11010/admin/to_login
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_522zf extends Abstract_payment_api {
	const PAY_TYPE_ALIPAY = "100";
	const PAY_TYPE_WEIXIN = "200";
	const RESULT_CODE_SUCCESS = 1;
	const RESULT_MSG_SUCCESS = 'succcess';

	const CALLBACK_SUCCESS = 1;
	const RETURN_SUCCESS_CODE = 'SUCCESS';


	public function __construct($params = null) {
		parent::__construct($params);
		$this->_custom_curl_header = array('Content-Type:application/json');
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
		$params['uid']        = $this->getSystemInfo('account');
		$params['orderid']    = $order->secure_id;
		$params['orderuid']   = $playerId;
		$params['price']      = $this->convertAmountToCurrency($amount);
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['notifyurl'] = $this->getNotifyUrl($orderId);
		$params['returnurl'] = $this->getReturnUrl($orderId);
		$params['goodsname']  = $order->secure_id;
		$params['key']        = $this->sign($params);
		$this->CI->utils->debug_log('=====================522zf generatePaymentUrlForm params', $params);

		return $this->processPaymentUrlForm($params);
	}

	# Implement processPaymentUrlForm
	protected function processPaymentUrlFormPost($params) {
		$response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['orderid']);
		$response = json_decode($response, true);
		$this->CI->utils->debug_log('=====================522zf processPaymentUrlFormPost response', $response);

		if($response['code'] == self::RESULT_CODE_SUCCESS && $response['msg'] == self::RESULT_MSG_SUCCESS) {
			$order = $this->CI->sale_order->getSaleOrderBySecureId($params['orderid']);
			$this->CI->sale_order->updateExternalInfo($order->id, $response['data']['result']['transid']);

			$use_image_url = ($this->getSystemInfo('use_image_url', false));
			if($use_image_url){
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_QRCODE,
					'url' => strtolower($response['data']['result']['url']),
				);
			}
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_URL,
				'url' => $response['data']['result']['url'],
			);
		}
		else if($response['msg']) {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $response['code'].': '.$response['msg'].', '.$response['err_msg']
			);
		}
		else {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => lang('Invalidate API response')
			);
		}
	}

	protected function processPaymentUrlFormQRCode($params) {
		$response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['orderid']);
		$response = json_decode($response, true);
		$this->CI->utils->debug_log('=====================522zf processPaymentUrlFormQRCode response', $response);

		if($response['code'] == self::RESULT_CODE_SUCCESS && $response['msg'] == self::RESULT_MSG_SUCCESS) {
			$order = $this->CI->sale_order->getSaleOrderBySecureId($params['orderid']);
			$this->CI->sale_order->updateExternalInfo($order->id, $response['data']['result']['transid']);

			$use_image_url = ($this->getSystemInfo('use_image_url', false));
			if($use_image_url){
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_QRCODE,
					'url' => strtolower($response['data']['result']['url']),
				);
			}
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_URL,
				'url' => $response['data']['result']['qrurl'],
			);
		}
		else if($response['msg']) {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $response['code'].': '.$response['msg']
			);
		}
		else {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => lang('Invalidate API response')
			);
		}
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

		$this->CI->utils->debug_log("=====================522zf params", $params);

		if($source == 'server' ){
			if (empty($params)) {
				$raw_post_data = file_get_contents('php://input', 'r');
				$this->CI->utils->debug_log("=====================522zf raw_post_data", $raw_post_data);
				$params = json_decode($raw_post_data,true);
				$this->CI->utils->debug_log("=====================522zf json_decode params", $params);
			}

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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['transid'], '', null, null, $response_result_id);
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
		$requiredFields = array(
			'uid', 'orderid', 'transid', 'price', 'status', 'key', 'version'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================522zf checkCallbackOrder Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================522zf checkCallbackOrder Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['status'] != self::CALLBACK_SUCCESS) {
			$this->writePaymentErrorLog("======================522zf checkCallbackOrder Payment status is not success", $fields);
			return false;
		}

		if ($fields['price'] != $this->convertAmountToCurrency($order->amount)) {
            #because player need to enter amount at Alipay
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $this->CI->utils->debug_log('=====================522zf amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['amount']/100, $notes);

            }
            else{
                $this->writePaymentErrorLog("=====================522zf Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
		}

		if ($fields['orderid'] != $order->secure_id) {
			$this->writePaymentErrorLog("======================522zf checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- signatures --
	# Reference: PHP Demo
	private function sign($params) {
		$signStr = $this->createSignStr($params);
		$sign = md5($signStr);
		return $sign;
	}

	private function createSignStr($params) {
		ksort($params);
		$signStr = '';
		foreach($params as $key => $value) {
			if(empty($value) || $key == 'key') {
				continue;
			}
			$signStr .= "$key=$value&";
		}
		$signStr = rtrim($signStr, '&');
		return $signStr.$this->getSystemInfo('key');
	}

	private function validateSign($params) {
		ksort($params);
		$signStr = '';
		foreach($params as $key => $value) {
			if( ($key == 'key') || (empty($value)) ) {
				continue;
			}
			$signStr .= "$key=$value&";
		}
		$signStr = rtrim($signStr, '&');
		$sign = md5($signStr.$this->getSystemInfo('key'));
		if($params['key'] == $sign){
			return true;
		}
		else{
			return false;
		}
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
		return number_format($amount * 100, 0, '.', '');
	}
}