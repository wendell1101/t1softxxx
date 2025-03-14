<?php
// header('Content-Type:text/html;charset=utf8');
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * macaubus
 *
 * *'MACAUBUS_ALIPAY_PAYMENT_API'ID: 784;
 * *'MACAUBUS_WEIXIN_PAYMENT_API'ID: 785;
 * *'MACAUBUS_QQPAY_PAYMENT_API'ID: 786;
 * *'MACAUBUS_JDPAY_PAYMENT_API'ID: 787;
 * *'MACAUBUS_UNIONPAY_PAYMENT_API'ID: 788;
 * *'MACAUBUS_QUICKPAY_PAYMENT_API'ID: 789;
 * *'MACAUBUS_QUICKPAY_H5_PAYMENT_API'ID: 790;
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
abstract class Abstract_payment_api_macaubus extends Abstract_payment_api {

    const SCANTYPE_ALIPAY= 'mbALIPAY'; //支付宝
    const SCANTYPE_WEIXIN= 'mbWECHAT'; //微信
    const SCANTYPE_QQPAY = 'mbQQ';    //QQ扫码
    const SCANTYPE_JDPAY = 'mbJD';    //京东扫码
    const SCANTYPE_UNIONPAY = 'mbUNIONQRPAY'; //银联扫码
    const SCANTYPE_QUICKPAY= 'mbQUICKPAY'; //快捷支付
    const SCANTYPE_QUICKPAY_H5= 'mbQUICKPAY'; //快捷支付 H5 跟快捷支付用同樣的
	const RETURN_SUCCESS_CODE = 'SUCCESS';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = 'SUCCESS';
	const PAY_RESULT_SUCCESS = '1';

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

		$params['app_id'] = $this->getSystemInfo("account");
		$params['order_sn'] = $order->secure_id;
		$params['order_amount'] = $this->convertAmountToCurrency($amount);
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================macaubus generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => false,
		);
	}

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['order_sn']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================macaubus processPaymentUrlFormQRcode response json to array', $decode_data);
        $msg = lang('Invalidate API response');

        if(!empty($decode_data['returncode']) && ($decode_data['code'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $decode_data['qrcode'],
            );
        }else {
            if(!empty($decode_data['retMsg'])) {
                $msg = $decode_data['retMsg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

    public function getOrderIdFromParameters($params) {
        $callbcack_order_sn = $params['order_sn'];
        $this->utils->debug_log("Callback returned order ID [$callbcack_order_sn]");
        $this->CI->load->model(array('sale_order'));
        $order = $this->CI->sale_order->getSaleOrderBySecureId($callbcack_order_sn);
        return $order->id;
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

        $this->CI->utils->debug_log("=====================macaubus callbackFrom $source params", $params);

        if($source == 'server'){
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['billno'], null, null, null, $response_result_id);
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
		$requiredFields = array(
			'app_id', 'order_sn','order_amount','order_pay','pay_status','sign'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================macaubus missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['pay_status'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['pay_status'];
			$this->writePaymentErrorLog("=====================macaubus Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['order_amount'] )
		) {
			$this->writePaymentErrorLog("=====================macaubus Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['order_sn'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================macaubus checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================macaubus checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	public function getBankListInfoFallback() {
		return array(
            array('label' => '工商银行', 'value' => 'mbICBC'),
            array('label' => '农业银行', 'value' => 'mbABC'),
            array('label' => '中国银行', 'value' => 'mbBOC'),
            array('label' => '建设银行', 'value' => 'mbCCB'),
            array('label' => '交通银行', 'value' => 'mbBCOMM'),
            array('label' => '中信银行', 'value' => 'mbCITIC'),
            array('label' => '光大银行', 'value' => 'mbCEB'),
            array('label' => '华夏银行', 'value' => 'mbHXB'),
            array('label' => '民生银行', 'value' => 'mbCMBC'),
            array('label' => '广发银行', 'value' => 'mbCGB'),
            array('label' => '平安银行', 'value' => 'mbPINGAN'),
            array('label' => '招商银行', 'value' => 'mbCMB'),
            array('label' => '兴业银行', 'value' => 'mbCIB'),
            array('label' => '浦发银行', 'value' => 'mbSPDB'),
            array('label' => '北京银行', 'value' => 'mbBOB'),
            array('label' => '上海银行', 'value' => 'mbBOS'),
            array('label' => '邮储银行', 'value' => 'mbPSBC')
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
        $params['app_secret'] = $this->getSystemInfo('secret');
        $params['auth_key'] = $this->getSystemInfo('key');

        ksort($params);
        $signStr = '';
        $params_len = count($params);
		$counter = 0;
		foreach($params as $key => $value) {
			++$counter;

			if($counter == $params_len){
				$signStr .= $key."=".$value;
				continue;
			}
			$signStr .= $key.'='.$value.'&';
		}
        $sign=strtoupper(md5($signStr));
		return $sign;
	}

    public function verifySignature($data) {
        $data['app_secret'] = $this->getSystemInfo('secret');
        $data['auth_key'] = $this->getSystemInfo('key');
	    $callback_sign = $data['sign'];
        unset($data['sign']);
        ksort($data);
        $signStr = '';
        $params_len = count($data);
		$counter = 0;
		foreach($data as $key => $value) {
			++$counter;

			if($counter == $params_len){
				$signStr .= $key."=".$value;
				continue;
			}
			$signStr .= $key.'='.$value.'&';
		}
        $sign = strtoupper(md5($signStr));
        return $sign == $callback_sign;
    }
}
