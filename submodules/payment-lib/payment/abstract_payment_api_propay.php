<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * * PROPAY_PAYMENT_API, ID: 6069
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
abstract class Abstract_payment_api_propay extends Abstract_payment_api {
	const PAYTYPE_ONLINEBANK  = 'ebank';
    const REQUEST_SUCCESS     = 'success';
	const PAY_RESULT_SUCCESS  = '1';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$params['merchantId']          = $this->getSystemInfo("account");
		$params['transactionId'] 	   = $order->secure_id;
		$params['transactionType']     = 'D';
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['currency']            = $this->getSystemInfo("currency");
		$params['amount']     	   	   = $this->convertAmountToCurrency($amount);
		$params['callback']   	   	   = $this->getNotifyUrl($orderId);
		$params['response']     	   = $this->getReturnUrl($orderId);
		$params['lang']            	   = $this->getSystemInfo("lang")? $this->getSystemInfo("lang") :'th';
		$post_params['merchantId']     = $this->getSystemInfo("account");
		$post_params['message']    	   = $this->encrypt($params);
		$post_params['orderid']    	   = $order->secure_id;

		$this->CI->utils->debug_log("=====================propay generatePaymentUrlForm", $post_params);

		return $this->processPaymentUrlForm($post_params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
		$orderId = $params['orderid'];
		unset($params['orderid']);
    	$url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, true, $orderId);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('==============================propay processPaymentUrlFormQRcode response json to array', $decode_data);

        $msg = lang('Invalidate API response');
    	if($decode_data['status'] == self::REQUEST_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decode_data['redirectUrl'],
            );
        }else {
            if(!empty($decode_data['errors'])) {
                $msg = $decode_data['errors'];
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
	# $source can be 'server' or 'browser'
	public function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server' ){
            if(empty($params)){
                $raw_post_data = file_get_contents('php://input', 'r');
                $params = json_decode($raw_post_data, true);
            }

            $decryptData = json_decode($this->decrypt($params['message']), true);
            if(is_array($decryptData) && isset($params['transactionId']) && !empty($params['transactionId'])){
            	$decryptData['transactionId'] = $params['transactionId'];
            }

            if (!$order || !$this->checkCallbackOrder($order, $decryptData, $processed)) {
                return $result;
            }
        }

		# Update order payment status and balance
		$success=true;

		# Update player balance based on order status
		# if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
		$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
		if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
			$this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $decryptData);
			if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
				$this->CI->sale_order->setStatusToSettled($orderId);
			}
		} else {
			# update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $decryptData['transactionId'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$succ_return_msg = '{"status":"success","message":""}';
		$result['success'] = $success;
		if ($success) {
			$result['message'] = $succ_return_msg;
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

		$requiredFields = array('transactionId', 'status','amount','transactionDate');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================propay missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['status'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['returncode'];
			$this->writePaymentErrorLog("=====================propay checkCallbackOrder Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['amount'] )
		) {
			$this->writePaymentErrorLog("=====================propay checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['transactionId'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================propay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass

		# everything checked ok
		return true;
	}
	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- public functions --
	/**
	 * detail: After payment is complete, the gateway will invoke this URL asynchronously
	 *
	 * @param int $orderId
	 * @return void
	 */
	public function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: After payment is complete, the gateway will send redirect back to this URL
	 *
	 * @param int $orderId
	 * @return void
	 */
	public function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: Format the amount value for the API
	 *
	 * @param float $amount
	 * @return float
	 */
	public function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

    public function decrypt($encryptedData) {
		$secret_key = $this->getSystemInfo('key');
		$secret_iv = $this->getSystemInfo('merchant_pass1') . $this->getSystemInfo('merchant_pass2');
		$encrypted = base64_decode($encryptedData);
		if(16 !== strlen($secret_key)) $secret_key = hash('MD5', $secret_key, true);
		if(16 !== strlen($secret_iv)) $secret_iv = hash('MD5', $secret_iv, true);
		$data = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $secret_key, $encrypted, MCRYPT_MODE_CBC, $secret_iv);
		$padding = ord($data[strlen($data) - 1]);
		$message = substr($data, 0, -$padding);
		return $message;
	}

    public function encrypt($params){
    	unset($params['lang']);
    	$content = json_encode($params);
		$secret_key = $this->getSystemInfo('key');
		$secret_iv = $this->getSystemInfo('merchant_pass1').$this->getSystemInfo('merchant_pass2');
		if(16 !== strlen($secret_key)) $secret_key = hash('MD5', $secret_key, true);
		if(16 !== strlen($secret_iv)) $secret_iv = hash('MD5', $secret_iv, true);
		$padding = 16 - (strlen($content) % 16);
		$content .= str_repeat(chr($padding), $padding);
		$encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $secret_key, $content, MCRYPT_MODE_CBC, $secret_iv);
		$message = base64_encode($encrypted);
		return $message;
    }

}
