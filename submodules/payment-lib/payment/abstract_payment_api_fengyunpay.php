<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * FENGYUNPAY  风云
 *
 * * FENGYUNPAY_PAYMENT_API, ID: 948
 * * FENGYUNPAY_ALIPAY_PAYMENT_API, ID: 949
 * * FENGYUNPAY_ALIPAY_H5_PAYMENT_API, ID: 950
 * * FENGYUNPAY_QQPAY_PAYMENT_API, ID: 951
 * * FENGYUNPAY_QQPAY_H5_PAYMENT_API, ID: 952
 * * FENGYUNPAY_UNIONPAY_PAYMENT_API, ID: 953
 * * FENGYUNPAY_QUICKPAY_PAYMENT_API, ID: 954
 * * FENGYUNPAY_WEIXIN_PAYMENT_API, ID: 961
 * * FENGYUNPAY_WEIXIN_H5_PAYMENT_API, ID: 962
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Secret
 *
 * Field Values:
 * * URL: https://www.fengyunpay.net/gateway/pay
 * * Account: ## MerId ##
 * * Key: ## APIKEY ##
 * * Secret: ## TerId ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_fengyunpay extends Abstract_payment_api {

    const PAYTYPE_ONLINEBANK = "1003"; // 罔銀
    const PAYTYPE_ALIPAY     = "1006"; // 支付寶
    const PAYTYPE_ALIPAY_H5  = "1011"; #1008
    const PAYTYPE_WEIXIN     = "1005"; // 微信
    const PAYTYPE_WEIXIN_H5  = "1010";
    const PAYTYPE_QQPAY      = "1013"; //QQ
    const PAYTYPE_QQPAY_H5   = "1014";
    const PAYTYPE_JDPAY      = "1017"; //京東
    const PAYTYPE_JDPAY_H5   = "1022";
    const PAYTYPE_UNIONPAY   = "1016"; //銀聯
    const PAYTYPE_QUICKPAY   = "1024"; //快捷

    const APPSENCE_PC = "1001"; //PC
    const APPSENCE_H5 = "1002"; //H5

	const RETURN_SUCCESS_CODE = 'SUCCESS';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '0';
	const PAY_RESULT_SUCCESS = '1003';

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
        $params['merId']         = $this->getSystemInfo('account');
        $params['terId']         = $this->getSystemInfo('secret');
        $params['businessOrdid'] = $order->secure_id;
        $params['orderName']     = 'Deposit';
        $params['tradeMoney']    = $this->convertAmountToCurrency($amount);
        $params['payType']       = "1000";
        $params['appSence']      = ($this->CI->utils->is_mobile()) ? self::APPSENCE_H5 : self::APPSENCE_PC;
        $params['syncURL']       = $this->getReturnUrl($orderId);
        $params['asynURL']       = $this->getNotifyUrl($orderId);

		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================fengyunpay generatePaymentUrlForm", $params);

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
    	$url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, false, $params['businessOrdid']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================fengyunpay processPaymentUrlFormQRcode response json to array', $decode_data);
        $msg = lang('Invalidate API response');

        if(!empty($decode_data['Data']['Url']) && ($decode_data['Code'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $decode_data['Data']['Url'],
            );
        }else {
            if(!empty($decode_data['Status'])) {
                $msg = $decode_data['Status'];
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

        $this->CI->utils->debug_log("=====================fengyunpay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderId'], null, null, null, $response_result_id);
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
		$requiredFields = array('merId', 'orderId','payOrderId','order_state','money','payReturnTime','payType','notifyType','sign','signType');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================fengyunpay missing parameter: [$f]", $fields);
				return false;
			}
		}

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================fengyunpay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

		if ($fields['order_state'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['order_state'];
			$this->writePaymentErrorLog("=====================fengyunpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($fields['money'] != $this->convertAmountToCurrency($order->amount)){
			$this->writePaymentErrorLog("=====================fengyunpay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['orderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================fengyunpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '中国建设银行', 'value' => 'CCB'),
            array('label' => '交通银行', 'value' => 'BOCOM'),
            array('label' => '中信银行', 'value' => 'ECITIC'),
            array('label' => '中国光大银行', 'value' => 'CEBB'),
            array('label' => '华夏银行', 'value' => 'CGB'),
            array('label' => '中国民生银行', 'value' => 'CMBC'),
            array('label' => '广发银行', 'value' => 'CGB'),
            array('label' => '平安银行', 'value' => 'PINGAN'),
            array('label' => '招商银行', 'value' => 'CMB'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '北京银行', 'value' => 'BJBANK'),
            array('label' => '渤海银行', 'value' => 'BHB'),
            array('label' => '上海银行', 'value' => 'SHBANK'),
            array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
            array('label' => '广州市商业银行', 'value' => 'GRCBANK')
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
        ksort($params);
       	$signStr='';
		foreach ($params as $key => $value) {
			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'key='. $this->getSystemInfo('key');
        $sign=strtolower(md5($signStr));

		return $sign;
	}

    protected function verifySignature($data) {
	    $callback_sign = $data['sign'];
        unset($data['sign']);
        unset($data['signType']);
        ksort($data);
        $signStr='';
        foreach ($data as $key => $value) {
			if(is_null($value) || empty($value) || $value == 'null'){
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'key='. $this->getSystemInfo('key');
        $sign=strtolower(md5($signStr));
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }
}
