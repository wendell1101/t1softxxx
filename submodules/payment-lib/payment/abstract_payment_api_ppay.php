<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * PPAY PPAY支付
 *
 * * PPAY_ALIPAY_PAYMENT_API, ID: 5469
 * * PPAY_ALIPAY_H5_PAYMENT_API, ID: 5470
 * * PPAY_UNIONPAY_PAYMENT_API, ID: 5471
 * *
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
abstract class Abstract_payment_api_ppay extends Abstract_payment_api {
	const PAYTYPE_UNIONPAY = "ysf";
	const PAYTYPE_ALIPAY = "alipay";

	const CALLBACK_SUCCESS_CODE = '1';
	const RETURN_SUCCESS = 'ok';
	const RETURN_FAILED = 'failed';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

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

        $params['amount'] = $this->convertAmountToCurrency($amount); //分
		$this->configParams($params, $order->direct_pay_extra_info); //$params['paytype']
        $params['outtradeid'] = $order->secure_id;
		$params['payto'] = $this->getSystemInfo("account");
		$params['returnurl'] = $this->getReturnUrl($orderId);
		$params['callbackurl'] = $this->getNotifyUrl($orderId);
		$params['remark'] = "Deposit";
		$params['verification'] = $this->sign($params);


		$this->CI->utils->debug_log("=====================ppay generatePaymentUrlForm", $params);

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

        	if(empty($params)){
        		$raw_post_data = file_get_contents('php://input', 'r');
				$this->CI->utils->debug_log('=======================ppay callbackFrom raw_post_data input R', $raw_post_data);
		    	$params = json_decode($raw_post_data, true);
        	}

            $this->CI->utils->debug_log('=======================ppay callbackFromServer server callbackFrom', $params);
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['outtradeid'], null, null, null, $response_result_id);
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
			$result['return_error'] = $processed ? self::RETURN_SUCCESS : self::RETURN_FAILED;
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
			'outtradeid', 'amount', 'status', 'verification'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================ppay missing parameter: [$f]", $fields);
				return false;
			}
		}


		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================ppay checkCallbackOrder signature Error', $fields);
			return false;
		}

		if ($fields['status'] != self::CALLBACK_SUCCESS_CODE) {
			$payStatus = $fields['status'];
			$this->writePaymentErrorLog("=====================ppay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != $fields['amount'] ) {
			$this->writePaymentErrorLog("=====================ppay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}


		if ($fields['outtradeid'] != $order->secure_id) {
			$this->writePaymentErrorLog("=====================ppay checkCallbackOrder payment , Order IDs do not match, expected [$order->secure_id]", $fields);
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
		return number_format($amount*100, 0, '.', '');
	}

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
    protected function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtolower(md5($signStr));
        return $sign;
    }

    private function createSignStr($params) {
		$signStr = '';
        foreach($params as $key => $value) {
            if(($key == 'verification')) {
                continue;
            }
            $signStr .= $value;
        }
        return $signStr.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
		$keys = array(
			'id' => $params['id'],
			'outtradeid' => $params['outtradeid'],
			'payto' => $params['payto'],
			'amount' => $params['amount'],
			'paytime' => $params['paytime'],
			'status' => $params['status'],
			'remark' => $params['remark']
		);
		$signStr = '';
        foreach($keys as $key => $value) {
            $signStr .= $value;
        }
        $sign = strtolower(md5($signStr.$this->getSystemInfo('key')));
        if($params['verification'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }
}
