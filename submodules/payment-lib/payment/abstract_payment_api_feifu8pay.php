<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * feifu8  菲付吧
 *
 * * ('FEIFU8PAY_ALIPAY_PAYMENT_API', 754);
 * * ('FEIFU8PAY_WEIXIN_PAYMENT_API', 755);
 * * ('FEIFU8PAY_QQPAY_PAYMENT_API', 756);
 * * ('FEIFU8PAY_ONLINEBANK_PAYMENT_API', 757);
 * * ('FEIFU8PAY_BAIDUPAY_PAYMENT_API', 758);
 * * ('FEIFU8PAY_JDPAY_PAYMENT_API', 759);
 * * ('FEIFU8PAY_ALIPAY_H5_PAYMENT_API', 5101);
 * * ('FEIFU8PAY_UNIONPAY_PAYMENT_API', 5289);
 * * ('FEIFU8PAY_UNIONPAY_H5_PAYMENT_API', 5290);
 * * ('ABCPAY_QUICKPAY_PAYMENT_API', 5424);
 * * ('ABCPAY_PAYMENT_API', 5451);
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
abstract class Abstract_payment_api_feifu8pay extends Abstract_payment_api {
    const SCANTYPE_WEIXIN    = '902'; //微信 h5
    const SCANTYPE_ALIPAY    = '903'; //支付宝
    const SCANTYPE_ALIPAY_H5 = '904'; //支付宝 h5
    const SCANTYPE_QQPAY_H5  = '905'; //QQh5
    const SCANTYPE_ONLINE_BANK = '907'; //網銀支付
    const SCANTYPE_QQPAY = '908';//QQ扫码
	const SCANTYPE_BAIDU = '909';//百度扫码
    const SCANTYPE_JDPAY = '910';//京东扫码

    const SCANTYPE_JDPAY_WAP  = '00046';//京东 wap
    const SCANTYPE_UNIONPAY   = '924';//银联扫码

	const RETURN_SUCCESS_CODE = 'OK';
    const RETURN_FAILED_CODE  = 'FAIL';
    const REQUEST_SUCCESS     = 'SUCCESS';
	const PAY_RESULT_SUCCESS  = '00';

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
		$params['pay_applydate']   = $orderDateTime->format('Y-m-d h:i:s');
		$params['pay_callbackurl'] = $this->getReturnUrl($orderId);
		$params['pay_notifyurl']   = $this->getNotifyUrl($orderId);
		$params['pay_amount']      = $this->convertAmountToCurrency($amount); //分
		$params['pay_productname'] = 'Deposit';

		$this->configParams($params, $order->direct_pay_extra_info);
		$params['pay_md5sign']     = $this->sign($params);
		$this->CI->utils->debug_log("=====================feifu8pay generatePaymentUrlForm", $params);

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
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['pay_orderid']);
        $decode_data = json_decode($response, true);
        $this->CI->utils->debug_log('========================================feifu8pay processPaymentUrlFormQRcode response json to array', $decode_data);

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
            $this->CI->utils->debug_log('=======================feifu8pay callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderid'], null, null, null, $response_result_id);
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
			'memberid', 'orderid','amount','transaction_id','datetime','returncode','attach','sign'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================feifu8pay missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['returncode'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['returncode'];
			$this->writePaymentErrorLog("=====================feifu8pay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['amount'] )
		) {
			$this->writePaymentErrorLog("=====================feifu8pay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['orderid'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================feifu8pay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================feifu8pay checkCallbackOrder verify signature Error', $fields);
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
        unset($params['pay_productname']);
        $signStr = $this->createSignStr($params);
        $sign=strtoupper(md5($signStr));
		return $sign;

	}

    public function verifySignature($data) {
	    $callback_sign = $data['sign'];
        unset($data['sign']);
        unset($data['attach']);
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
