<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * YPAY_PAYMENT_API
 *
 * * 'YPAY_PAYMENT_API', 6103
 * * 'YPAY_BANKCARD_PAYMENT_API', 6104
 * * 'YPAY_QRCODE_PAYMENT_API', 6105
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
abstract class Abstract_payment_api_ypay extends Abstract_payment_api {
    const DEVICE_PC    = 'web';
    const DEVICE_PHONE = 'wap';
	const RETURN_SUCCESS_CODE = 'success';
    const REQUEST_SUCCESS     = 0;

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

		$params['appid']      = $this->getSystemInfo("account");
		$params['cliIP']      = $this->getClientIp();
		$params['cliNA']      = $this->CI->utils->is_mobile() ? self::DEVICE_PHONE : self::DEVICE_PC;
		$params['uid']    	  = $playerId;
		$params['order']      = $order->secure_id;
		$params['price']      = $this->convertAmountToCurrency($amount); //元
		$params['reback_url'] = $this->getReturnUrl($orderId);
		$params['notifyUrl']  = $this->getNotifyUrl($orderId);
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sn']     	  = $this->sign($params);
		$this->CI->utils->debug_log("=====================================ypay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitGetForm($this->getSystemInfo('url'), $params, true, $params['order']);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================yapy processPaymentUrlFormPost response json to array', $response);

        $msg = lang('Invalidate API response');
        if(isset($response['e']) && ($response['e'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['d']['h5'],
            );
        }else {
            if(isset($response['m']) && !empty($response['m'])) {
                $msg = $response['m'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
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
        $this->CI->utils->debug_log('=====================================ypay callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
            $this->CI->utils->debug_log('=====================================ypay callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, '', '', null, null, $response_result_id);
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

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array('uid', 'order','amount','transaction_id','sn');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================ypay missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['amount'] )
		) {
			$this->writePaymentErrorLog("=====================ypay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['order'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================ypay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================ypay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass

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
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 2, '.', '');
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
        $sign=md5($signStr);

		return $sign;

	}

    public function verifySignature($data) {
	    $callback_sign = $data['sn'];
        $signStr = $this->createSignStr($data);
        $sign= md5($signStr);

        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }

    public function createSignStr($params) {
    	ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
        	if($key == 'sn' || $key == 'notifyUrl'){
        		continue;
        	}
            $signStr .= $key.'='.urlencode($value);
        }
        $signStr .= 'secret='.$this->getSystemInfo('key');
    	return $signStr;
	}
}
