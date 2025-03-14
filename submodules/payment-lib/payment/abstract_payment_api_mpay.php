<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * MPAY 咪付
 *
 * * MPAY_PAYMENT_API,        ID: 647
 * * MPAY_WEIXIN_PAYMENT_API, ID: 648
 * * MPAY_ALIPAY_PAYMENT_API, ID: 649
 * * MPAY_QQPAY_PAYMENT_API,  ID: 650
 * * MPAY_UNIONPAY_PAYMENT_API, ID: 666
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
abstract class Abstract_payment_api_mpay extends Abstract_payment_api {

    const PAYTYPE_WEIXIN = 'WECHAT';         //微信
    const PAYTYPE_WEIXIN_WAP = 'WAP_WECHAT'; //WAP微信
    const PAYTYPE_ALIPAY = 'ALIPAY';         //支付宝
    const PAYTYPE_ALIPAY_H5 = 'H5_ALIPAY';   //支付宝H5
    const PAYTYPE_QQPAY = 'QQ';              //QQ
    const PAYTYPE_UNIONPAY = 'UNION';        //银联扫码

	const RETURN_SUCCESS_CODE = 'SUCCESS';
    const RETURN_FAILED_CODE = 'FAIL';
	const REQUEST_SUCCESS = '1000';
	const PAY_RESULT = '110';

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

        $params['merchant'] = $this->getSystemInfo("account");
        $params['billno'] = $order->secure_id;
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['notify_url'] = $this->getNotifyUrl($orderId);
        $params['variables'] = "Deposit";
        $params['sign_type'] = "MD5";
        $params['pay_time'] = $orderDateTime->format('Ymdhis');

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================mpay generatePaymentUrlForm", $params);

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
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['billno']);
        $decode_data = json_decode($response, true);

        if(!empty($decode_data['qrCode']) && ($decode_data['code'] == self::REQUEST_SUCCESS)) {
            if($decode_data['type'] == self::PAYTYPE_ALIPAY_H5){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $decode_data['qrCode'],
                );
            }
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'base64_url' => $decode_data['qrCode'],
            );
        }elseif(isset($decode_data['code']) && ($decode_data['code'] != self::REQUEST_SUCCESS)){
            $err_msg = $decode_data['msg'];
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $err_msg,
            );
        }else{
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
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================mpay callbackFrom $source params", $params);

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
			'code', 'merchant', 'bank', 'status', 'billno', 'amount', 'pay_time' , 'msg', 'sign_type', 'sign'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================mpay missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['status'] != self::PAY_RESULT) {
			$payStatus = $fields['status'];
			$this->writePaymentErrorLog("=====================mpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['amount'] )
		) {
			$this->writePaymentErrorLog("=====================mpay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['billno'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================mpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================mpay checkCallbackOrder verify signature Error', $fields);
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
            array('label' => '招商银行', 'value' => 'CMB'),
            array('label' => '中国工商银行', 'value' => 'ICBC'),
            array('label' => '中国建设银行', 'value' => 'CCB'),
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '中国农业银行', 'value' => 'ABOC'),
            array('label' => '交通银行', 'value' => 'BOCOM'),
            array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '广发银行', 'value' => 'CGB'),
            array('label' => '中信银行', 'value' => 'ECITIC'),
            array('label' => '中国光大银行', 'value' => 'CEB'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '平安银行', 'value' => 'SDB'),
            array('label' => '民生银行', 'value' => 'CMBC'),
            array('label' => '华夏银行', 'value' => 'HXB'),
            array('label' => '中国邮政银行', 'value' => 'PSBC'),
            array('label' => '北京银行', 'value' => 'BOBJ'),
            array('label' => '上海银行', 'value' => 'BOS')
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
        unset($params['notify_url']);
        unset($params['device']);
        unset($params['variables']);
        $signStr = '';
		foreach($params as $key => $value) {
			$signStr .= $key.'='.$value.'&';
		}
		$signStr .= 'key='.$this->getSystemInfo('key');
		$sign = md5($signStr);
		return $sign;

	}

    public function verifySignature($data) {
	    $callback_sign = $data['sign'];
        ksort($data);
        unset($data['variables']);
        unset($data['sign']);
        $signStr = '';
        foreach($data as $key => $value) {
            $signStr .= $key.'='.$value.'&';
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        $sign = md5($signStr);
        if (strcasecmp($sign, $callback_sign) !== 0) {
            return false;
        }else{
            return true;
        }

    }
}
