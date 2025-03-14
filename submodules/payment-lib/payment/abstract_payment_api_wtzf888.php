<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * wtzf168  万通
 *
 * * 'WTZF888_WEIXIN_PAYMENT_API', 5228
 * * 'WTZF888_WEIXIN_H5_PAYMENT_API', 5229
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
abstract class Abstract_payment_api_wtzf888 extends Abstract_payment_api {
    const PAY_BANKCODE_WEIXIN_H5   = '901'; //微信 h5
    const PAY_BANKCODE_WEIXIN      = '902'; //微信 
    const PAY_BANKCODE_ALIPAY_H5   = '903'; //支付宝 h5
    const PAY_BANKCODE_ALIPAY      = '904'; //支付宝
    const PAY_BANKCODE_QQPAY_H5    = '905'; //QQh5
    const PAY_BANKCODE_QQPAY       = '906';    //QQ扫码
    const PAY_BANKCODE_ONLINE_BANK = '907'; //網銀支付
	const PAY_BANKCODE_QUICKPAY    = '908';    //快捷支付
    const PAY_BANKCODE_UNIONPAY    = '910'; //银联扫码

    const PAY_FORMAT_PAY_STR   = 'pay_str'; //二维码生成字符
    const PAY_FORMAT_JUMP_PAGE = 'jump_page'; //支付页面跳转

	const RETURN_SUCCESS_CODE = 'SUCCESS';
    const RETURN_FAILED_CODE  = 'FAIL';
    const REQUEST_SUCCESS     = '0000';
	const PAY_RESULT_SUCCESS  = '0000';

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

		$params['pay_memberid']    = $this->getSystemInfo("account");
		$params['pay_orderid']     = $order->secure_id;
		$params['pay_amount']      = $this->convertAmountToCurrency($amount); //元
		$params['pay_applydate']   = $orderDateTime->format('Y-m-d h:i:s');
		$params['pay_notifyurl']   = $this->getNotifyUrl($orderId);
		$params['pay_callbackurl'] = $this->getReturnUrl($orderId);
		$params['pay_format']      = self::PAY_FORMAT_JUMP_PAGE;
		$params['pay_clientip']    = $this->getClientIp();
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['pay_md5sign']     = $this->sign($params);
		$this->CI->utils->debug_log("=====================================wtzf888 generatePaymentUrlForm", $params);

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

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
        $this->CI->utils->debug_log('=====================================wtzf888 processPaymentUrlFormQRcode scan url', $this->getSystemInfo('url'));
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['pay_orderid']);
        $this->CI->utils->debug_log('=====================================wtzf888 processPaymentUrlFormQRcode received response', $response);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('=====================================wtzf888 processPaymentUrlFormQRcode response[1] json to array', $decode_data);
        $msg = lang('Invalidte API response');

        if(!empty($decode_data['qrcode']) && ($decode_data['code'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $decode_data['qrcode'],
            );
        }else {
            if(!empty($decode_data['msg'])) {
                $msg = 'msg:'.$decode_data['msg'].' ,code:'.$decode_data['code'];
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
        $this->CI->utils->debug_log('=====================================wtzf888 callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
            $this->CI->utils->debug_log('=====================================wtzf888 callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderid'], '', null, null, $response_result_id);
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
		$requiredFields = array('memberid', 'orderid','transaction_id','amount','datetime','returncode','sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================wtzf888 missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['returncode'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['returncode'];
			$this->writePaymentErrorLog("=====================wtzf888 Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['amount'] )
		) {
			$this->writePaymentErrorLog("=====================wtzf888 Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['orderid'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================wtzf888 checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================wtzf888 checkCallbackOrder verify signature Error', $fields);
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
            array('label' => '工商银行', 'value' => '102'),
            array('label' => '农业银行', 'value' => '103'),
            array('label' => '中国银行', 'value' => '104'),
            array('label' => '建设银行', 'value' => '105'),
            array('label' => '农业发展银行', 'value' => '203'),
            array('label' => '交通银行', 'value' => '301'),
            array('label' => '中信银行', 'value' => '302'),
            array('label' => '光大银行', 'value' => '303'),
            array('label' => '华夏银行', 'value' => '304'),
            array('label' => '民生银行', 'value' => '305'),
            array('label' => '广发银行', 'value' => '306'),
            array('label' => '平安银行', 'value' => '307'),
            array('label' => '招商银行', 'value' => '308'),
            array('label' => '兴业银行', 'value' => '309'),
            array('label' => '浦发银行', 'value' => '310'),
            array('label' => '北京银行', 'value' => '313'),
            array('label' => '恒丰银行', 'value' => '315'),
            array('label' => '浙商银行', 'value' => '316'),
            array('label' => '渤海银行', 'value' => '318'),
            array('label' => '上海银行', 'value' => '325'),
            array('label' => '邮储银行', 'value' => '403'),
            array('label' => '徽商银行', 'value' => '440'),
            array('label' => '广州市商业银行', 'value' => '441')
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
        $signStr = $this->createSignStr($params);
        $sign=strtoupper(md5($signStr));

		return $sign;

	}

    public function verifySignature($data) {
	    $callback_sign = $data['sign'];
        unset($data['sign']);
        $signStr = $this->createSignStr($data);
        $sign=strtoupper(md5($signStr));
    
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }

    public function createSignStr($params) {
    	ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            $signStr .= $key.'='.$value.'&';
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
    	return $signStr;
	}
}
