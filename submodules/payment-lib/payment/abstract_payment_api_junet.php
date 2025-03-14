<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * junet  通联
 *
 * * 'JUNET_ALIPAY_PAYMENT_API', ID 5336
 * * 'JUNET_ALIPAY_H5_PAYMENT_API', ID 5340
 *
 * Required Fields:
 * * Account
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_junet extends Abstract_payment_api {



    const RETURN_SUCCESS_CODE = 'success';
	const RETURN_SUCCESS = 'TRADE_SUCCESS';
	const RETURN_FAILED = 'TRADE_ERROR';

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
		$params['merchant_uuid'] = $this->getSystemInfo("account");
		$params['trade_no'] = $order->secure_id;
		$params['amount'] = $this->convertAmountToCurrency($amount); //元
        $params['return_type'] = 'POST';
		$params['return_url'] = $this->getNotifyUrl($orderId);
        $params['product_type'] = 'AliGateWay';
        $params['ReturnUrl'] = $this->getReturnUrl($orderId);
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================junet generatePaymentUrlForm", $params);

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
	protected function processPaymentUrlFormQRCode($params) {
        $response = $this->submitGetForm($this->getSystemInfo('url'), $params, false, $params['trade_no']);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================junet processPaymentUrlFormPost response json to array', $response);
        $msg = lang('Invalidate API response');

    	if(!empty($response['url']) && ($response['state'] == self::RETURN_SUCCESS_CODE)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['url'],
            );
        }else {
            if(!empty($response['resultMsg'])) {
                $msg = $response['resultMsg'];
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
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================junet callbackFromServer server callbackFrom', $params);
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['trade_no'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS;
		} else {
			$result['message'] = $processed ? self::RETURN_SUCCESS : self::RETURN_FAILED;
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

		$requiredFields = array('merchant_uuid', 'trade_no','out_trade_no','actual_amount','trade_status','total_amount');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================junet missing parameter: [$f]", $fields);
				return false;
			}
		}
        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=======================junet checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['trade_status'] != self::RETURN_SUCCESS || $fields['trade_status'] == self::RETURN_FAILED) {
			$payStatus = $fields['trade_status'];
			$this->writePaymentErrorLog("=====================junet Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($fields['total_amount'] != $this->convertAmountToCurrency($order->amount)) {

            if($this->getSystemInfo('allow_callback_amount_diff')) {
				$lastAmount = abs($this->convertAmountToCurrency($order->amount) - $fields['total_amount']);
                if($lastAmount > 1) {
                    $this->writePaymentErrorLog("=====================junet Payment amounts do not match, expected [$order->amount]", $fields ,$lastAmount);
                    return false;
                }
                $this->CI->utils->debug_log('=====================junet diff amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['total_amount'], $notes);
            }
            else{
                $this->writePaymentErrorLog("=====================junet Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['out_trade_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================junet checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
		$params = array('merchant_uuid'=>$params['merchant_uuid'],'trade_no'=>$params['trade_no'],'amount'=>$params['amount']);
		$signStr = '';
		foreach ($params as $key => $value) {

			$signStr .= $value;
		}
		$sign = md5($signStr);
		return $sign;
    }

    public function validateSign($params) {
		$keys = array('merchant_uuid'=>$params['merchant_uuid'],'out_trade_no'=>$params['out_trade_no'],'actual_amount'=>$params['actual_amount']);
        $signStr = '';
		foreach ($keys as $key => $value) {

			$signStr .= $value;
		}
		$sign = md5($signStr);
        if($params['sign_str'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }
}
