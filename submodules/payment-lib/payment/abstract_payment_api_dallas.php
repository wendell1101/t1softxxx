<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * * DALLAS_PAYMENT_API, ID: 6133
 * * DALLAS_WITHDRAWAL_PAYMENT_API, 6134
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
abstract class Abstract_payment_api_dallas extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE 	  = 'success';
	const PAY_RESULT_SUCCESS  	  = 'PENDING';
	const CALLBACK_RESULT_SUCCESS = 'DONE';

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
		$params['appId'] 		   = $this->getSystemInfo("account");
		$params['merOrderNo'] 	   = $order->secure_id;
		$params['currency']  	   = 'BRL';
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['amount']     	   = $this->convertAmountToCurrency($amount);
		$params['returnUrl'] 	   = $this->getReturnUrl($orderId);
		$params['notifyUrl'] 	   = $this->getNotifyUrl($orderId);
		$params['sign']     	   = $this->sign($params);

		$this->CI->utils->debug_log("=====================dallas generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

    protected function processPaymentUrlFormPost($params) {
		$response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['merOrderNo']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================dallas processPaymentUrlFormPost response', $response);
        $msg = '';
        if(isset($response['data']['status']) && $response['data']['status'] == self::PAY_RESULT_SUCCESS){
            if(isset($response['data']['params']['url']) && !empty($response['data']['params']['url']))
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data']['params']['url'],
            );
        }
        else if(isset($response['error']) && !empty($response['error'])) {
        	$msg = $response['error'];
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidte API response')
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
	public function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server' ){
            if(empty($params)){
                $raw_post_data = file_get_contents('php://input', 'r');
                $params = json_decode($raw_post_data, true);
            }

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['merOrderNo'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
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

		$requiredFields = array('status', 'merOrderNo', 'amount', 'sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================dallas missing parameter: [$f]", $fields);
				return false;
			}
		}

		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================dallas checkCallbackOrder signature Error', $fields);
			return false;
		}

		if ($fields['status'] != self::CALLBACK_RESULT_SUCCESS) {
			$payStatus = $fields['status'];
			$this->writePaymentErrorLog("=====================dallas checkCallbackOrder Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['amount'] )
		) {
			$this->writePaymentErrorLog("=====================dallas checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['merOrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================dallas checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
		return number_format($amount, 0, '.', '');
	}

	# -- public helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = hash('sha256', $signStr);

		return $sign;

	}

   	public function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $sign = hash('sha256', $signStr);

        if($params['sign'] == $sign){
            return true;
        }else{
            return false;
        }
    }

    public function createSignStr($params)
    {
		ksort($params);
		$signStr = '';
		$extraStr = '';
		foreach ($params as $key => $value) {
			if ($key == 'sign' || empty($value)){
		    	continue;
		   	}
			if($key == 'extra' && is_array($value)){
			   	ksort($value);
			   	foreach ($value as $extra_key => $extra_value) {
			   		$extraStr .= "$extra_key=$extra_value&";
			   	}
			   	$value = rtrim($extraStr, '&');
		   	}
		   $signStr .= "$key=$value&";
		}
		return $signStr . "key=" . $this->getSystemInfo('key');
    }

}