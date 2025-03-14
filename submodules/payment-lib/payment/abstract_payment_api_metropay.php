<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * Metro Pay
 * https://metro-pay.com
 *
 * * METROPAY_PAYMENT_API, ID: 5964
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * SFTP: /infos/C024_OLE777thb/Payments/MetroPay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
abstract class Abstract_payment_api_metropay extends Abstract_payment_api {

    const PAY_METHOD_BANK_CARD = 'Bank_Card';
    const RESULT_STATUS_SUCCESS = '0';
    const RETURN_SUCCESS = 'success';

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = ['Content-Type:application/json'];
    }

    /**
     * sign encryption
     * @param  integer $timestamp current time
     * @return integer
     */
    private function sign($timestamp = null) {
		if ($timestamp === null) {
			throw new Exception('timestamp is need');
		}

    	$key = $this->getSystemInfo('key');
    	$merchant = $this->getSystemInfo('account');
    	$sign = $merchant . $timestamp . $key;
    	$sign = md5($sign);
    	$this->CI->utils->debug_log('=====================metropay Sign', $sign);
    	return $sign;
	}

    /**
     * encrypt post field data
     * @param  array $data data field
     * @param  string $timestamp current time
     * @return string encrypted data
     */
    private function encryptData($data = [], $timestamp = null)
    {
    	if (empty($data)
    		|| empty($timestamp)
    	) {
    		throw new Exception('data and timestamp is needed');
    	}
    	$secret = $this->getSystemInfo('secret');
    	$data = json_encode($data);
    	$result = openssl_encrypt($data, 'AES-128-ECB', $secret);
    	return $result;
    }

    /**
     * decrpyt string to array data
     * @param  string $encrypted
     * @return array
     */
    private function decryptData($encrypted = '')
    {
    	if (empty($encrypted)) {
    		throw new Exception('metropay encrypted data is empty');
    	}

    	$secret = $this->getSystemInfo('secret');
    	$decrypted = openssl_decrypt($encrypted, 'AES-128-ECB', $secret);
    	if (empty($decrypted)) {
    		throw new Exception('metropay decrypted faild');
    	}

    	$decrypted = json_decode($decrypted, true);
    	if (empty($decrypted)) {
    		throw new Exception('metropay json decode failed');
    	}

    	return $decrypted;
    }

    /**
     * validate metro pay create online order response format
     * @param  array  $response 
     * @return void
     */
    private function validateCreateOnlineOrderResponseFormat($response = [])
    {
    	if (empty($response)) {
    		throw new Exception('metropay response error');
    	}

    	if (!isset($response['code'])) {
	    	throw new Exception('metropay response code format error');
    	}

    	if (!isset($response['data'])) {
    		throw new Exception('metropay response data format error');
    	}

    	if (!isset($response['data']['orderno'])) {
    		throw new Exception('metropay response data orderno is not set');
    	}

    	if (!isset($response['data']['payamount'])) {
    		throw new Exception('metropay response data payamount is not set');
    	}
    }

    /**
     * verify metro pay create online order reseponse value
     * @param  array  $respose
     * @param  array  $request
     * @return boolean
     */
    private function verifyCreateOnlineOrderResponse($response = [], $request = [])
    {
    	if ($response['code'] != self::RESULT_STATUS_SUCCESS) {
    		return $response['msg'];
    	}

    	if ($response['data']['orderno'] != $request['orderno']) {
    		throw new Exception('metropay response orderno not matched');
    	}

    	if ($response['data']['payamount'] != $request['payamount']) {
    		throw new Exception('metropay response payamount not matched');    		
    	}

    	return true;
    }


    /**
     * verify callback signed is correct
     * @param  array  $params 
     * @return boolean
     */
    private function verifyCallbackSigned($params = [])
    {
    	if (empty($params)
    		|| empty($params['sdorderno'])
    		|| empty($params['sign'])
	    ) {
    		return false;
    	}

    	$key = $this->getSystemInfo('key');
    	$signed = $params['sdorderno'] . $params['paytime'] . $key;
    	$signed = md5($signed);
    	$this->CI->utils->debug_log("===metropay signed code", $signed);

    	if ($signed != $params['sign']) {
    		return false;
    	}

    	return true;
    }

	protected abstract function processPaymentUrlForm($params);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
		$firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : 'no firstName';


		// get signed
		$timestamp = time();
		$sign = $this->sign($timestamp);
		$this->CI->utils->debug_log("=====================metropay payamount", $amount);
		$data = [
			'orderno' => $order->secure_id,
			'thirduserid' => $playerId,
			'thirduserlevel' => '1',
			'payamount' => floatval($amount),
			'trscode' => 'thaiqr_tobank',
			'callurl' => $this->getNotifyUrl($orderId)
		];
		$this->CI->utils->debug_log("=====================metropay callback url", $data['callurl']);
		$data = $this->encryptData($data, $timestamp);
		$postData = [
			'agentcode' => $this->getSystemInfo('account'),
			'data' => $data,
			'sign' => $sign,
			'timestamp' => $timestamp,
			'orderno' => $order->secure_id
		];

		$this->CI->utils->debug_log("=====================metropay generatePaymentUrlForm", $postData);

		return $this->processPaymentUrlForm($postData);
    }

	protected function processPaymentUrlFormPost($params) {
		$this->CI->utils->debug_log('=====================metropay processPaymentUrlFormPost params', $params);

        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['orderno']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================metropay processPaymentUrlFormPost response', $response);

        // validate response format
        try {
        	$this->validateCreateOnlineOrderResponseFormat($response);
        } catch (Exception $e) {
        	return [
        		'success' => false,
        		'type' => self::REDIRECT_TYPE_ERROR,
        		'msg' => lang('Invalid API response')
        	];
        }

        // decrypt request data for verify response
        $requestData = $params['data'];
        $requestData = $this->decryptData($requestData);

        // verify request and response data correctly
        $result = false;
        try {
        	$result = $this->verifyCreateOnlineOrderResponse($response, $requestData);
        } catch (Exception $e) {
        	return [
        		'success' => false,
        		'type' => self::REDIRECT_TYPE_ERROR
        	];
        }

        // response code not is success
        if ($result !== true) {
        	return [
        		'success' => false,
        		'type' => self::REDIRECT_TYPE_ERROR,
        		'msg' => $result
        	];
        }

        return [
        	'success' => true,
        	'type' => self::REDIRECT_TYPE_URL,
        	'url' => $response['data']['url']
        ];
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
	private function callbackFrom($source, $orderId, $params, $response_result_id)
	{
        if (empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

		$this->CI->utils->debug_log("======metropay", $params);
		$this->CI->utils->debug_log("======metropay source", $source);
		$this->CI->utils->debug_log("======metropay orderId", $orderId);
        $result = [
        	'success' => false, 
        	'next_url' => null, 
        	'message' => lang('error.payment.failed')
        ];
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $this->CI->utils->debug_log("======metropay order", $order);

        $processed = false;
        if ($source == 'server') {
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
            if (isset($params['order_id'])) {
                $this->CI->sale_order->updateExternalInfo($order->id, $params['order_id'], '', null, null, $response_result_id);
            }

            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS;
        } else {
            $result['return_error'] = 'Error';
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
		$requiredFields = [
			'status',
			'customerid',
			'sdorderno',
			'total_fee',
			'paytime',
			'sign'
		];

		$fields = $fields ?: [];
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================metropay missing parameter: [$f]", $fields);
				return false;
			}
		}

		// verify signification
		if (!$this->verifyCallbackSigned($fields)) {
			$this->writePaymentErrorLog('=====================metropay checkCallbackOrder Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['total_fee'] != $this->convertAmountToCurrency($order->amount)) {
			$this->writePaymentErrorLog("======================metropay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
			return false;
		}

		$this->CI->utils->debug_log('=====metropay secure_id', $order->secure_id);
	    if ($fields['sdorderno'] != $order->secure_id) {
	        $this->writePaymentErrorLog("========================metropay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
	        return false;
	    }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
    }


	# -- amount --
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- notifyURL --
	public function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

    # -- returnURL --
	public function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}
}
