<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * hftpay
 *
 * * 'HFTPAY_ALIPAY_PAYMENT_API', ID 5240
 * * 'HFTPAY_ALIPAY_H5_PAYMENT_API', ID 5241
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
abstract class Abstract_payment_api_hftpay extends Abstract_payment_api {

    const CODE_TYPE_ALIPAY= 2;

    const RETURN_SUCCESS_CODE = '0';
    const RETURN_SUCCESS = 'OK';



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
		$params['partner_no'] = $this->getSystemInfo("account");
        $params['mch_order_no'] = $order->secure_id;
        $params['body'] = "body";
		$params['money'] = $this->convertAmountToCurrency($amount);
		$params['time_stamp'] = $this->getMillisecond();
        $params['token'] = $this->sign($params);
        $params['callback_url'] = $this->getNotifyUrl($orderId);
        $this->configParams($params, $order->direct_pay_extra_info);

		$this->CI->utils->debug_log("=====================hftpay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
    }

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
        $response = $this->submitGetForm($this->getSystemInfo('url').$this->getSystemInfo('url_tail'), $params, true, $params['mch_order_no']);

        $response = json_decode($response, true);
		$this->CI->utils->debug_log('=====================hftpay processPaymentUrlFormQRCode response', $response);

    	if($response['code'] == self::RETURN_SUCCESS_CODE) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['code_link']
            );
        }
        else if($response['code'] != self::RETURN_SUCCESS_CODE) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['code'].': '.$response['msg']
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

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
        $response = $this->submitGetForm($this->getSystemInfo('url').$this->getSystemInfo('url_tail'), $params, true, $params['mch_order_no']);
        $response = json_decode($response, true);
		$this->CI->utils->debug_log('=====================hftpay processPaymentUrlFormQRCode response', $response);

    	if($response['code'] == self::RETURN_SUCCESS_CODE) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['code_img_url']
            );
        }
        else if($response['code'] != self::RETURN_SUCCESS_CODE) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['code'].': '.$response['msg']
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

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================hftpay callbackFromServer server callbackFrom', $params);
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
			$result['message'] = "FAIL";
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
		$requiredFields = array('code','money','mch_order_no','order_no','time_stamp','money_order','payAmount','attach','token');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================hftpay missing parameter: [$f]", $fields);
				return false;
			}
		}
		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================hftpay checkCallbackOrder Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['code'] != self::RETURN_SUCCESS_CODE) {
			$payStatus = $fields['code'];
			$this->writePaymentErrorLog("=====================hftpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($fields['payAmount'] != $this->convertAmountToCurrency($order->amount)) {
            #because player need to enter amount at Alipay
            if($this->getSystemInfo('allow_callback_amount_diff')) {
				$lastAmount = abs($this->convertAmountToCurrency($order->amount) - $fields['payAmount']);
                if($lastAmount < 1 && $fields['payAmount']==$fields['money']) {
					$notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
					$this->CI->sale_order->fixOrderAmount($order->id, $fields['payAmount']/100, $notes);
				}
				else{
					$this->writePaymentErrorLog("=====================hftpay Payment amounts do not match, expected [$order->amount]", $fields ,$lastAmount);
					return false;
				}
            }
            else{
                $this->writePaymentErrorLog("=====================hftpay Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['mch_order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================hftpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- money --
	protected function convertAmountToCurrency($amount) {
        return number_format($amount * 100, 0, '.', ''); //1元=100分
	}


    # -- time_stamp --
	private function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    # -- signatures --
     private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    private function createSignStr($params) {
        $params = array("partner_no"=>$params['partner_no'],"mch_order_no"=>$params['mch_order_no'],"time_stamp"=>$params['time_stamp'],"money"=>$params['money']);
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            $signStr.=$key."=".$value."&";
        }
        $signStr = $signStr."key=".$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $token = $params['token'];
        $params = array("money"=>$params['money'],"mch_order_no"=>$params['mch_order_no'],"order_no"=>$params['order_no'],"time_stamp"=>$params['time_stamp']);
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            $signStr.=$key."=".$value."&";
        }
        $signStr = $signStr."key=".$this->getSystemInfo('secret');
		$sign = md5($signStr);
		if($token == $sign){
			return true;
		}
		else{
			return false;
		}
    }

    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}


