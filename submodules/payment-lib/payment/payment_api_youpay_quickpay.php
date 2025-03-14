<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lyingpay.php';

/**
 * YOUPAY 友付 - 快捷
 *
 *
 * YOUPAY_QUICKPAY_PAYMENT_API, ID: 5252
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.surperpay.com/pay/quickPay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_youpay_quickpay extends Abstract_payment_api_lyingpay {
	const RESULT_CODE_QUICKPAY_SUCCESS = '1';

	public function getPlatformCode() {
		return YOUPAY_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'youpay_quickpay';
	}

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params['version'] = '1.0';
		$params['spid'] = $this->getSystemInfo("account");
		$params['spbillno'] = $order->secure_id;
		$params['tran_amt'] = $this->convertAmountToCurrency($amount);
		$params['backUrl'] = $this->getReturnUrl($orderId);
		$params['notify_url'] = $this->getNotifyUrl($orderId);

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("=========================youpay quickpay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlRedirect($params) {
		$this->CI->utils->debug_log('=========================youpay quickpay scan url', $this->getSystemInfo('url'));

		$post_xml_data = $this->array2xml($params);
		$this->CI->utils->debug_log('=========================youpay quickpay post_xml_data', $post_xml_data);

		$curlConn = curl_init();
		$curlData = array();
		$curlData[CURLOPT_POST] = true;
		$curlData[CURLOPT_URL] = $this->getSystemInfo('url');
		$curlData[CURLOPT_RETURNTRANSFER] = true;
		$curlData[CURLOPT_TIMEOUT] = 120;
		$curlData[CURLOPT_POSTFIELDS] = $post_xml_data;
        $curlData[CURLOPT_HTTPHEADER] = [ "Content-type: text/xml;charset='utf-8'" ];
		curl_setopt_array($curlConn, $curlData);

		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYHOST, false);

		// Need to specify the referer when doing CURL submit. since we use redirect 2nd url, we can take the HTTP_HOST
		// curl_setopt($curlConn, CURLOPT_REFERER, "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

		$response = curl_exec($curlConn);
		$errCode     = curl_errno($curlConn);
        $error       = curl_error($curlConn);
        $statusCode  = curl_getinfo($curlConn, CURLINFO_HTTP_CODE);

        $curlSuccess = ($errCode == 0);
        $response_result_id = $this->submitPreprocess($params, $response, $this->getSystemInfo('url'), $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['spbillno']);

		$this->CI->utils->debug_log('=====================youpay quickpay xml response', $curlSuccess, $response);

		$response = $this->parseResultXML($response);

		$this->CI->utils->debug_log('=====================youpay quickpay response', $response);

		$msg = lang('Invalidte API response');

		if($response['retcode'] == self::QRCODE_REPONSE_CODE_SUCCESS) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_URL,
				'url' => $response['openUrl']
			);
		}
		else if(isset($response['retmsg'])) {
			$msg = 'Error Code: '.$response['retcode']. ', '.$response['retmsg'];
		}

		return array(
			'success' => false,
			'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
			'message' => $msg
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlRedirect($params);
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
	public function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$this->utils->debug_log('=====================youpay quickpay callbackFrom' . ucfirst($source) . ': [' . $orderId .'], params:', $params);

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
				$params['transaction_id'], '',
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->CI->sale_order->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
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

	public function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array(
			'retcode', 'retmsg', 'spbillno', 'transaction_id', 'tran_amt', 'result');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=========================youpay quickpay missing parameter: [$f]", $fields);
				return false;
			}
		}

		$callbackSign = $this->sign($fields, true);

		# is signature authentic?
		if ($fields['sign'] != $callbackSign) {
			$this->writePaymentErrorLog("=========================youpay quickpay check callback sign error, signature is [$callbackSign], match? ", $fields['sign']);
			return false;
		}

		if ($fields['result'] != self::RESULT_CODE_QUICKPAY_SUCCESS) {
			$resultCode = $fields['result'];
			$this->writePaymentErrorLog("=========================youpay quickpay Payment was not successful, result is [$resultCode]", $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != floatval( $fields['tran_amt']) ) {
			$this->writePaymentErrorLog("=========================youpay quickpay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		# everything checked ok
		return true;
	}
}
