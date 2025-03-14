<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * *  daypaybd
 * *
 * * 'DAYPAYBD_PAYMENT_API', ID 6064
 * *
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
abstract class Abstract_payment_api_daypaybd extends Abstract_payment_api {
	const RESULT_CODE_SUCCESS = '200';
	const CALLBACK_SUCCESS = 'PAY_SUCCESS';
	const RETURN_SUCCESS_CODE = 'ok';

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
		$playerDetails = $this->getPlayerDetails($playerId);
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'no firstName';
        $lastname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName']      : 'no lastName';
        $phone     = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '8615551234567';
        $email     = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : 'sample@example.com';

		$params['merchant']	  	= $this->getSystemInfo("account");
		$params['orderId'] 		= $order->secure_id;
		$params['amount'] 	  	= $this->convertAmountToCurrency($amount); //元
		$params['customName'] 	= $lastname.$firstname;
		$params['customMobile'] = $phone;
		$params['customEmail'] 	= $email;
		$params['collectData'] 	= 'deposit';
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['notifyUrl']  	= $this->getNotifyUrl($orderId);
		$params['callbackUrl']  = $this->getReturnUrl($orderId);
		$params['sign']       	= $this->sign($params);

		$this->CI->utils->debug_log("=====================daypaybd generatePaymentUrlForm", $params);
		return $this->processPaymentUrlForm($params);
	}

	protected function processPaymentUrlFormURL($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['orderId']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================goldpays processPaymentUrlFormURL response', $response);

        if(isset($response['code']) && $response['code'] == self::RESULT_CODE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data']['url'],
            );
        }
        else if(isset($response['errorMessages']) && !empty($response['errorMessages'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['errorMessages']
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

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================daypaybd callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderId'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				if (!empty($params)){
                    if ($params['status'] == self::CALLBACK_SUCCESS) {
                        $this->CI->sale_order->updateExternalInfo($order->id, $params['orderId'], '', null, null, $response_result_id);
                        $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                    } else {
                        $this->CI->utils->debug_log("=====================checkCallbackOrder Payment status is not success", $params);
                    }
                }
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

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array('orderId','amount','status','sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================daypaybd missing parameter: [$f]", $fields);
				return false;
			}
		}

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================daypaybd checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("=======================paymero checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass
		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['amount'] )
		) {
			$this->writePaymentErrorLog("=====================daypaybd Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['orderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================daypaybd checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
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
		return number_format($amount, 2, '.', '');
	}

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
		$signStr = $this->createSignStr($params);
        $sign = md5($signStr);
		return $sign;
	}

    public function verifySignature($data) {
	    $callback_sign = $data['sign'];
        $signStr =  $this->createSignStr($data);
        $sign = md5($signStr);
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }

    public function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign' || empty($value)) {
                continue;
            }
            $signStr .= "$key=$value&";
        }

		return $signStr."key=".$this->getSystemInfo('key');
	}

	public function getPlayerDetails($playerId) {
        $this->CI->load->model(array('player_model'));
        $player = $this->CI->player_model->getPlayerDetails($playerId);
        return $player;
    }
}
