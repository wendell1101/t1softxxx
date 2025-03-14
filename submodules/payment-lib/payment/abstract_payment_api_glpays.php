<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * GLPAYS
 *
 * * 'GLPAYS_ALIPAY_PAYMENT_API', ID 5324
 * * 'GLPAYS_ALIPAY_H5_PAYMENT_API', ID 5325
 * * 'GLPAYS_QUICKPAY_PAYMENT_API', ID 5326
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
abstract class Abstract_payment_api_glpays extends Abstract_payment_api {

    const PAYTYPE_ALIPAY= 2;
	const PAYTYPE_QUICK_PAY = 4;

	const RETURN_SUCCESS_CODE = '0';
	const RETURN_SUCCESS = 'success';

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
		$params['mch_id'] = $this->getSystemInfo("account");
		$params['cp_order_no'] = $order->secure_id;
		$params['order_uid'] = $playerId;
		$params['order_amount'] = (int)$this->convertAmountToCurrency($amount); //分
		$params['ip'] = $this->getClientIp();
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['returnUrl'] = $this->getReturnUrl($orderId);
		$params['goods_id'] = "";
		$this->configParams($params, $order->direct_pay_extra_info); //$params['trade_type']
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================glpays generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}


    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {

    	$url = $this->getSystemInfo('url');
        $this->CI->utils->debug_log('=====================glpays processPaymentUrlFormQRcode scan url',$url);
		$response = $this->submitPostForm($url, $params, true, $params['cp_order_no']);

		$this->CI->utils->debug_log('========================================glpays processPaymentUrlFormQRcode received response', $response);

		$decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================glpays processPaymentUrlFormQRcode response[1] json to array', $decode_data);
		$msg = lang('Invalidte API response');


		if($decode_data['retcode'] == self::RETURN_SUCCESS_CODE) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decode_data['data']['pay_url'],
            );
        }else {
            if(!empty($decode_data['retdesc'])) {
                $msg = $decode_data['retdesc'];
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

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================glpays callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['order_no'], null, null, null, $response_result_id);
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
			$result['message'] = $processed ? self::RETURN_SUCCESS : 'failed';
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
		$requiredFields = array('cp_order_no','pay_amount');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================glpays missing parameter: [$f]", $fields);
				return false;
			}
		}
		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================glpays checkCallbackOrder Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['pay_amount'] != $this->convertAmountToCurrency($order->amount)) {
            #because player need to enter amount at Alipay
            if($this->getSystemInfo('allow_callback_amount_diff')) {
				if($fields['pay_amount']<$fields['order_amount']) {
					$this->CI->utils->debug_log('=====================glpays amount not match expected [$order->amount]');
					$notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
					$this->CI->sale_order->fixOrderAmount($order->id, $fields['pay_amount']/100, $notes);
				}
            }
            else{
                $this->writePaymentErrorLog("=====================glpays Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['cp_order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================glpays checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
		$params = array(
            'cp_order_no'  => $params['cp_order_no'],
            'mch_id'       => $params['mch_id'],
            'notify_url'   => $params['notify_url'],
            'order_amount' => $params['order_amount']
        );
        ksort($params);

        $srcData = "";
        foreach ($params as $key => $val) {
            if($val === null || $val === "sign" ){
                continue;
            }
            $srcData .= "$key=$val" . "&";
        }
        $srcData = substr($srcData, 0, strlen($srcData) - 1);

		$srcData .= $this->getSystemInfo('key');
        $sign = md5($srcData);
		return $sign;
	}

	private function validateSign($params) {
		$key = array(
            'cp_order_no'  => $params['cp_order_no'],
            'goods_id'     => $params['goods_id'],
            'order_amount' => $params['order_amount'],
            'order_uid'    => $params['order_uid'],
            'pay_amount'   => $params['pay_amount']
        );
		ksort($key);
		$signStr = '';
		foreach($key as $key => $value) {
			$signStr .= "$key=$value&";
		}
		$signStr = rtrim($signStr, '&');
		$sign = md5($signStr.$this->getSystemInfo('key'));
		if($params['sign'] == $sign){
			return true;
		}
		else{
			return false;
		}
	}
}


