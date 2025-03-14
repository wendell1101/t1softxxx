<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * PAYTRUST88
 *
 * * PAYTRUST88_PAYMENT_API, ID: 505
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
abstract class Abstract_payment_api_paytrust88 extends Abstract_payment_api {
	const STATUS_REPONSE_CODE_SUCCESS = '0';
	const ORDER_STATUS_SUCCESS = '1';
	const ORDER_STATUS_REJECTED = '-1';
	const ORDER_STATUS_ERROR = '-2';
	const RETURN_SUCCESS_CODE = '';
	const RETURN_FAILED_CODE = 'faile';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function getSecretInfoList() {
		$secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'api_password');
		return $secretsInfo;
	}

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
		$playerDetails = $this->getPlayerDetails($playerId);
		$username =  (isset($playerDetails[0]) && !empty($playerDetails[0]['username']))  	  ? $playerDetails[0]['username']	   : 'no username';
		$firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) 	  ? $playerDetails[0]['firstName']	   : 'no firstName';
		$lastname =  (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))  	  ? $playerDetails[0]['lastName']	   : 'no lastName';
		$emailAddr = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))     	  ? $playerDetails[0]['email'] 		   : 'no email';
		$telephone = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '+12063582043';

		$params = array();

		$params['return_url']        = $this->getReturnUrl($orderId);
		$params['failed_return_url'] = $this->getReturnUrl($orderId);
		$params['http_post_url']     = $this->getNotifyUrl($orderId);
		$params['amount']            = $this->convertAmountToCurrency($amount);
		$params['currency']          = $this->getSystemInfo('currency');
		$params['item_id']           = $order->secure_id;
		$params['item_description']  = $username;
		$params['name']              = $firstname.' '.$lastname;
		$params['telephone']         = $telephone;
		$params['email']             = $emailAddr;
		$params['client_ip']         = $this->getClientIp();

		$this->configParams($params, $order->direct_pay_extra_info);

		return $this->processPaymentUrlForm($params);
	}

	protected function processPaymentToken($params) {
		$url = $this->getSystemInfo('url'). '?' . http_build_query($params);
		$username = $this->getSystemInfo('key');
		$password = $this->getSystemInfo('api_password');

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);

		curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeoutSecond());
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());

		$fullResponse = curl_exec($ch);
		$errCode = curl_errno($ch);
		$error   = curl_error($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$responseStr = substr($fullResponse, $header_size);
		curl_close($ch);

		$response = json_decode($responseStr, true);

		$response_result_id = $this->submitPreprocess($params, $responseStr, $url, $fullResponse, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['item_id']);
		$this->CI->utils->debug_log('url', $url, 'params', $params , 'fullResponse', $fullResponse, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);


		$msg = lang('Invalidate API response');
		if(isset($response['status']) && $response['status'] == self::STATUS_REPONSE_CODE_SUCCESS) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_URL,
				'url' => $response['redirect_to']
			);
		}
		else {
			if(isset($response['error']) || isset($response['decline_reason'])) {
				$msg = isset($response['error']) ? $response['error'] : $msg;
				$msg = isset($response['decline_reason']) ? $response['decline_reason'] : $msg;
			}

			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $msg
			);
		}
	}

	# Display QRCode get from curl
	protected function processPaymentUrlFormQRCode($params) {
		$response = $this->submitPostForm($this->getSystemInfo('url'), $params);
		$response = json_decode($response, true);

		$msg = lang('Invalidate API response');
		if($response['code'] == self::QRCODE_REPONSE_CODE_SUCCESS) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $response['value']['qrcodeUrl']
			);
		}
		else {
			if($response['msg']) {
				$msg = $response['msg'];
			}

			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $msg
			);
		}
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

		if($source == 'server') {
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['item_id'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
					#redirect to success/fail page according to return params
					if($params['status'] == self::ORDER_STATUS_REJECTED || $params['status'] == self::ORDER_STATUS_ERROR){
						$this->CI->sale_order->declineSaleOrder($order->id, 'auto server callback declined ' . $this->getPlatformCode(), false);
					}
					else if($params['status'] == self::ORDER_STATUS_SUCCESS){
						$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
					}
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
			'apikey', 'transaction', 'status', 'amount', 'created_at', 'item_id', 'signature'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================paytrust88 missing parameter: [$f]", $fields);
				return false;
			}
		}

		$callbackSign = $this->sign($fields);

		# is signature authentic?
		if ($fields['signature'] != $callbackSign) {
			$this->writePaymentErrorLog("=====================paytrust88 check callback sign error, signature is [$callbackSign], match? ", $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != number_format($fields['amount'], 2, '.', '') ) {
			$this->writePaymentErrorLog("=====================paytrust88 Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}


		if ($fields['item_id'] != $order->secure_id) {
			$this->writePaymentErrorLog("======================paytrust88 checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}
		$processed = true; # processed is set to true once the signature verification pass

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function convertAmountToCurrency($amount) {
    	$convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 2, '.', '');
	}

	public function sign($params) {
		$apiKey = $this->getSystemInfo('key');

		$data = array("transaction", "amount", "created_at");

	    $arr = array();
	    for($i = 0; $i< count($data); $i++){
			if (array_key_exists($data[$i], $params)) {
				$arr[$i] = $params[$data[$i]];
			}
	    }
	    $signStr = implode('', $arr);

	    $sign = hash_hmac('sha256', $signStr, $apiKey);
		return $sign;
	}

	public function getPlayerDetails($playerId) {
		$this->CI->load->model(array('player_model'));
		$player = $this->CI->player_model->getPlayerDetails($playerId);

		return $player;
	}
}
