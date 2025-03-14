<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * anyi51ayf  安亿
 *
 * * 'ANYI51AYF_PAYMENT_API', ID 957
 * * 'ANYI51AYF_ALIPAY_PAYMENT_API', ID 958
 * * 'ANYI51AYF_ALIPAY_H5_PAYMENT_API', ID 959
 * * 'ANYI51AYF_UNIONPAY_PAYMENT_API', ID 960
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
abstract class Abstract_payment_api_anyi51ayf extends Abstract_payment_api {

    const PAYTYPE_ALIPAY 	= 'alipay'; //  支付宝
    const PAYTYPE_ALIPAY_H5	= 'alipayH5'; // 支付寶 H5 ALIPAYWAP
    const PAYTYPE_ONLINEBANK= 'gateway'; //網銀支付
  	const PAYTYPE_UNIONPAY 	= 'unionpay'; //銀聯掃碼

	const RETURN_SUCCESS_CODE = 'true';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '1';
	const PAY_RESULT_SUCCESS = 'true';

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

		$params['platSource'] = $this->getSystemInfo("account");
		$params['payAmt'] = $this->convertAmountToCurrency($amount); //元
		$params['orderNo'] = $order->secure_id;
		$params['notifyUrl'] = $this->getNotifyUrl($orderId);
		$params['ip'] = $this->getClientIp();

		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================anyi51ayf generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
	    $url = $this->getSystemInfo('url');
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $url,
			'params' => $params,
			'post' => true,
		);
	}

	# Submit URL form
	protected function processPaymentUrlFormURL($params) {
	    $url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, false, $params['orderNo']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================anyi51ayf processPaymentUrlFormQRcode response json to array', $decode_data);

        $msg = lang('Invalidate API response');
        if(!empty($decode_data['codeUrl'])&& ($decode_data['code'] == self::REQUEST_SUCCESS)) {
    		return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decode_data['codeUrl'],
            );
        }else {
            if(!empty($decode_data['message'])) {
                $msg = $decode_data['message'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
    	}
	}

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
    	$url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, false, $params['orderNo']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================anyi51ayf processPaymentUrlFormQRcode response json to array', $decode_data);

        $msg = lang('Invalidate API response');
    	if(!empty($decode_data['info']['qrCode'])) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $decode_data['info']['qrCode'],
            );
        }else {
            if(!empty($decode_data['message'])) {
                $msg = $decode_data['message'];
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
            $this->CI->utils->debug_log('=======================anyi51ayf callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderNo'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['message'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
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

		$requiredFields = array('platSource', 'orderNo','payAmt','success','sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================anyi51ayf missing parameter: [$f]", $fields);
				return false;
			}
		}

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================anyi51ayf checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

		if ($fields['success'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['success'];
			$this->writePaymentErrorLog("=====================anyi51ayf Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

        if($fields['payAmt'] != $this->convertAmountToCurrency($order->amount)) {
            #because player need to enter amount at Alipay
            $diff = abs($fields['payAmt'] - $order->amount);
            if($diff < $this->getSystemInfo('allow_callback_amount_diff_range', 0)){
                $this->CI->utils->debug_log('=====================anyi51ayf amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['payAmt'], $notes);
            }
            else{
                $this->writePaymentErrorLog("=====================anyi51ayf Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['orderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================anyi51ayf checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	public function getBankListInfoFallback() {
		return array(
            array('label' => '中国工商银行', 'value' => 'ICBC'),
            array('label' => '中国农业银行', 'value' => 'ABC'),
            array('label' => '中国建设银行', 'value' => 'CCB'),
            array('label' => '中国光大银行', 'value' => 'CEB'),
            array('label' => '中国民生银行', 'value' => 'CMBC'),
            array('label' => '北京银行', 'value' => 'BOB'),
            array('label' => '上海银行', 'value' => 'SHB'),
            array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
		);
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
        ksort($params);
       	$signStr = '';
		foreach ($params as $key => $value) {
			$signStr .= $value."|";
		}
		$signStr .= $this->getSystemInfo('key');

		return strtoupper(md5($signStr));
	}

    public function verifySignature($data) {
        ksort($data);
        $signStr = '';
        foreach ($data as $key => $value) {
            if($key == 'sign'){
                continue;
            }
			$signStr .= $value."|";
		}
		$signStr .= $this->getSystemInfo('key');
        $sign = strtoupper(md5($signStr));
        return $sign == $data['sign'];
    }
}
