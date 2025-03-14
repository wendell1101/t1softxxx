<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * CFUPAY_ 创富
 *
 * * CFUPAY_PAYMENT_API,          ID:658
 * * CFUPAY_QUICKPAY_PAYMENT_API, ID:659
 * * CFUPAY_WEIXIN_PAYMENT_API,   ID:660
 * * CFUPAY_ALIPAY_PAYMENT_API,   ID:661
 * * CFUPAY_QQPAY_PAYMENT_API,    ID:662
 * * CFUPAY_JDPAY_PAYMENT_API,    ID:663
 * * CFUPAY_UNIONPAY_PAYMENT_API, ID:664
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
abstract class Abstract_payment_api_cfupay extends Abstract_payment_api {

    const PAYTYPE_QUICKPAY = 'QUICKPAY';//快捷支付
    const PAYTYPE_WEIXIN = 'WXPAY';     //微信扫码
    const PAYTYPE_ALIPAY = 'ALIPAY';    //支付宝扫码
    const PAYTYPE_QQPAY = 'QQPAY';      //QQ扫码
    const PAYTYPE_JDPAY = 'JDPAY';      //京东扫码
    const PAYTYPE_UNIONQRPAY = 'UNIONQRPAY';//银联扫码

    //directPay:直连(傳值), bankPay:收银台(空值)
    const PAYMETHOD_DIRECTPAY = 'directPay';
    const PAYMETHOD_BANKPAY = 'bankPay';

    //app:返回二维码地址，需商户自行 ; web:直接在收银台页面上显示二维码 ; H5:会在手机端唤醒支付
    const ISAPP_WEB = 'web';
    const ISAPP_APP = 'app';
    const ISAPP_H5 = 'h5';

	const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE = 'failed';
	const TRADE_STATUS_SUCCESS = 'TRADE_FINISHED';

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

        $params['body'] = "Deposit";
        $params['charset'] = "utf-8";
        $params['merchantId'] = $this->getSystemInfo("account");
        $params['notifyUrl'] = $this->getNotifyUrl($orderId);
        $params['orderNo'] = $order->secure_id;
        $params['paymentType'] = "1";
        $params['returnUrl'] = $this->getReturnUrl($orderId);
        $params['service'] = "online_pay";
        $params['title'] = $params['body'];
        $params['totalFee'] = $this->convertAmountToCurrency($amount);//订单金额，单位为RMB元（Order Amount, unit is RMB)
        $params['paymethod'] = self::PAYMETHOD_DIRECTPAY;
        $params['isApp'] = self::ISAPP_WEB;

		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);
        $params['signType'] = "SHA";

		$this->CI->utils->debug_log("=====================cfupay generatePaymentUrlForm", $params);
		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
	    $url = $this->getSystemInfo('url').$params['merchantId'].'-'.$params['orderNo'];
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
        $url = $this->getSystemInfo('url').$params['merchantId'].'-'.$params['orderNo'];
        $response = $this->submitPostForm($url, $params, false, $params['orderNo']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================cfupay processPaymentUrlFormQRcode response json to array', $decode_data);

        $msg = lang('Invalidate API response');
        if(!empty($decode_data['codeUrl'])) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $decode_data['codeUrl'],
            );
        }else{
            if($decode_data['respMessage']) {
                $msg = $decode_data['respMessage'];
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
            $raw_post_data = file_get_contents('php://input');
            $flds = $raw_post_data;
            $this->CI->utils->debug_log('=======================cfupay callbackFromServer server callbackFrom', $flds);
            $params = array_merge( $params, $flds );

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
            'body', 'gmt_create', 'gmt_logistics_modify', 'gmt_payment', 'is_success', 'is_total_fee_adjust', 'notify_id', 'notify_time', 'notify_type', 'order_no', 'payment_type', 'price', 'quantity', 'seller_actions', 'seller_email', 'seller_id', 'title', 'total_fee', 'trade_no', 'trade_status', 'sign', 'signType'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================cfupay missing parameter: [$f]", $fields);
				return false;
			}
		}

        # is signature authentic?
        if($this->ignore_callback_sign){
            $this->CI->utils->debug_log('=======================cfupay checkCallbackOrder ignore callback sign', $fields, $order, $this->validateSign($fields));
        }else{
            # is signature authentic?
            if (!$this->validateSign($fields)) {
                $this->writePaymentErrorLog('=======================cfupay checkCallbackOrder signature Error', $fields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass

        if($fields['order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================cfupay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        if($fields['trade_status'] != self::TRADE_STATUS_SUCCESS) {
			$payStatus = $fields['trade_status'];
			$this->writePaymentErrorLog("=====================cfupay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if($this->convertAmountToCurrency($order->amount) != floatval( $fields['total_fee'])){
			$this->writePaymentErrorLog("=====================cfupay Payment amounts do not match, expected [$order->amount]", $fields);
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
            array('label' => '招商银行', 'value' => 'CMB'),
            array('label' => '工商银行', 'value' => 'ICBC'),
            array('label' => '建设银行', 'value' => 'CCB'),
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '农业银行', 'value' => 'ABC'),
            array('label' => '交通银行', 'value' => 'BOCM'),
            array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '广发银行', 'value' => 'CGB'),
            array('label' => '中信银行', 'value' => 'CITIC'),
            array('label' => '光大银行', 'value' => 'CEB'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '平安银行', 'value' => 'PAYH'),
            array('label' => '民生银行', 'value' => 'CMBC'),
            array('label' => '华夏银行', 'value' => 'HXB'),
            array('label' => '邮储银行', 'value' => 'PSBC'),
            array('label' => '北京银行', 'value' => 'BCCB'),
            array('label' => '上海银行', 'value' => 'SHBANK')
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
		foreach($params as $key => $value) {
			$signStr .= $key.'='.$value.'&';
		}
        $signStr = substr($signStr, 0, strlen($signStr) -1 );
		$signStr .= $this->getSystemInfo('key');
		$sign = strtoupper(sha1($signStr));
		return $sign;

	}

    public function validateSign($data) {
	    $callback_sign = $data['sign'];
        ksort($data);
        unset($data['signType']);
        unset($data['sign']);
        $signStr = '';
        foreach($data as $key => $value) {
            $signStr .= $key.'='.$value.'&';
        }
        $signStr = substr($signStr, 0, strlen($signStr) -1 );
        $signStr .= $this->getSystemInfo('key');
        $sign = strtoupper(sha1($signStr));
        if (strcasecmp($sign, $callback_sign) !== 0) {
            return false;
        }else{
            return true;
        }

    }
}
