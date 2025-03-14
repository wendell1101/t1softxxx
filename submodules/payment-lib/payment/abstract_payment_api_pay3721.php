<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * PAY3721  恒久
 *
 * * 'PAY3721_WEIXIN_PAYMENT_API', ID 5287
 * * 'PAY3721_WEIXIN_H5_PAYMENT_API', ID 5288
 * * 'PAY3721_WITHDRAWAL_PAYMENT_API', ID: 5291
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
abstract class Abstract_payment_api_pay3721 extends Abstract_payment_api {


    const PAYTYPE_WEIXIN = 'weixin';
    const PAYTYPE_WEIXIN_H5 = 'wxwap';


    const RETURN_SUCCESS_CODE = '0000';
	const RETURN_SUCCESS = 'success';
	const RETURN_FAILED = 'fail';

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

		$params['channel'] = $this->getSystemInfo("account");
        $params['callback'] = $this->getNotifyUrl($orderId);
        $params['returnUrl'] = $this->getReturnUrl($orderId);
		$params['orderid'] = $order->secure_id;
		$params['txnAmt'] = $this->convertAmountToCurrency($amount); //元
		$this->configParams($params, $order->direct_pay_extra_info); //$params['paytype']
		$params['ip'] = $this->getClientIP();
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================pay3721 generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}


	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
		return array(
			'success' => true,
            'type' => self::REDIRECT_TYPE_URL,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true,
		);
	}

	# Display QRCode get from curl
	protected function processPaymentUrlFormQRCode($params) {
        $response = $this->submitGetForm($this->getSystemInfo('url'), $params, false, $params['orderid']);
        $response = json_decode($response,true);

        $msg = lang('Invalidate API response');
    	if(!empty($response['codeUrl']) && ($response['resultCode'] == self::RETURN_SUCCESS_CODE)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['codeUrl'],
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
            $this->CI->utils->debug_log('=======================pay3721 callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['order_id'], null, null, null, $response_result_id);
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

		$requiredFields = array('respCode', 'merOrderId','txnAmt','orgMerOrderId');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================pay3721 missing parameter: [$f]", $fields);
				return false;
			}
		}
        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=======================pay3721 checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['respCode'] != self::RETURN_SUCCESS_CODE) {
			$payStatus = $fields['respCode'];
			$this->writePaymentErrorLog("=====================pay3721 Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($fields['txnAmt'] != $this->convertAmountToCent($this->convertAmountToCurrency($order->amount))) {
			$this->writePaymentErrorLog("=====================pay3721 Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['merOrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================pay3721 checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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

	protected function convertAmountToCent($amount) {
		return number_format($amount * 100, 0, '.', '');
	}

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
       	$signStr =  $this->createSignStr($params);
        $sign = strtolower(md5($signStr));
		return $sign;
	}

    private function createSignStr($params) {
	    $params = array('channel'=>$params['channel'],'callback'=>$params['callback'],'orderid'=>$params['orderid'],'txnAmt'=>$params['txnAmt'],'paytype'=>$params['paytype'],'ip'=>$params['ip']);
        ksort($params);
		$signStr = '';
		foreach ($params as $key => $value) {

			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'key='. $this->getSystemInfo('key');
		return $signStr;
    }

    public function validateSign($params) {
		$keys = array('respCode'=>$params['respCode'],'merOrderId'=>$params['merOrderId'],'txnAmt'=>$params['txnAmt'],'orgMerOrderId'=>$params['orgMerOrderId']);
        ksort($keys);
        $signStr = '';
		foreach ($keys as $key => $value) {

			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'key='. $this->getSystemInfo('key');
        $sign = strtolower(md5($signStr));
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

}
