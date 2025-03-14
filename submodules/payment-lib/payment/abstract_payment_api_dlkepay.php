<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * dlkepay 联科支付 / 畅付(支付寶)
 *
 * *'DLKEPAY_PAYMENT_API',ID 826;
 * *'DLKEPAY_ALIPAY_PAYMENT_API',ID 827;
 * *'DLKEPAY_WEIXIN_PAYMENT_API',ID 828;
 * *'DLKEPAY_QQPAY_PAYMENT_API',ID 829;
 * *'DLKEPAY_JDPAY_PAYMENT_API',ID 830;
 * *'DLKEPAY_UNIONPAY_PAYMENT_API',ID 831;
 * *'DLKEPAY_ALIPAY_H5_PAYMENT_API',ID 5058;
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
abstract class Abstract_payment_api_dlkepay extends Abstract_payment_api {

    const SCANTYPE_ALIPAY= '1009'; //支付宝
    const SCANTYPE_ALIPAY_H5= '994'; //支付宝 H5
    const SCANTYPE_WEIXIN= '1004'; //微信
    const SCANTYPE_WEIXIN_H5= '1007'; //微信 H5
    const SCANTYPE_QQPAY = '1009';    //QQ扫码
    const SCANTYPE_QQPAY_H5= '1008'; //QQ H5
    const SCANTYPE_JDPAY = '1010';    //京东扫码
    const SCANTYPE_UNIONPAY = '2000'; //银联扫码
    const SCANTYPE_ONLINEBANK= '998'; //網銀

	const RETURN_SUCCESS_CODE = '0';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = 'SUCCESS';
	const PAY_RESULT_SUCCESS = '0';

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
		$random_number = $this->uuid();
		$params['parter'] = $this->getSystemInfo("account");
		$params['value'] = $this->convertAmountToCurrency($amount);
		$params['orderid'] = $order->secure_id.$random_number;
		$params['callbackurl'] = $this->getNotifyUrl($orderId);

		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);
		$params['attach'] = 'Deposit';
		$this->CI->utils->debug_log("=====================dlkepay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	public function uuid(){
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s', str_split(bin2hex($data), 4));
	}

    # Submit POST form
    protected function processPaymentUrlFormPost($params) {
        $url = $this->getSystemInfo('url');
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $url,
            'params' => $params,
            'post' => false,
        );
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
        $response = $this->submitGetForm($this->getSystemInfo('url'), $params, false, $params['orderid']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================dlkepay processPaymentUrlFormQRcode response json to array', $decode_data);

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
            $this->CI->utils->debug_log('=======================dlkepay callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['sysorderid'], null, null, null, $response_result_id);
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

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array(
			'orderid', 'opstate','ovalue','sign','sysorderid','systime','attach','msg'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================dlkepay missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['opstate'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['opstate'];
			$this->writePaymentErrorLog("=====================dlkepay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['ovalue'] ) ) {
			$this->writePaymentErrorLog("=====================dlkepay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		$orderid = substr($fields['orderid'], 0, 13);
        if ($orderid != $order->secure_id) {
            $this->writePaymentErrorLog("========================dlkepay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields ,$orderid );
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================dlkepay checkCallbackOrder verify signature Error', $fields);
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
            array('label' => '工商银行', 'value' => '967'),
            array('label' => '农业银行', 'value' => '964'),
            array('label' => '中国银行', 'value' => '963'),
            array('label' => '建设银行', 'value' => '965'),
            array('label' => '交通银行', 'value' => '981'),
            array('label' => '中信银行', 'value' => '962'),
            array('label' => '华夏银行', 'value' => '982'),
            array('label' => '民生银行', 'value' => '980'),
            array('label' => '平安银行', 'value' => '978'),
            array('label' => '招商银行', 'value' => '970'),
            array('label' => '兴业银行', 'value' => '972'),
            array('label' => '浦发银行', 'value' => '977'),
            array('label' => '上海银行', 'value' => '975'),
            array('label' => '邮储银行', 'value' => '971')
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
	public function sign($params) {
        $signStr = $this->createSignStr($params);
		$sign = $this->hamacmd5($signStr);
		$this->CI->utils->debug_log('==============================dlkepay sign: ', $sign, $signStr);
		return $sign;
	}

    public function verifySignature($data) {

	    $callback_sign = $data['sign'];
        unset($data['sign']);
       	$signStr = '';
       	$data_array = array('orderid','opstate','ovalue');

		foreach($data_array as $key => $value) {

			$signStr .= $value.'='.$data[$value].'&';
		}
		$signStr = rtrim($signStr,"&");
        $signStr .= $this->getSystemInfo('key');
        $sign= $this->hamacmd5($signStr);
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }

    public function hamacmd5($data){
		// RFC 2104 HMAC implementation for php.
		// Creates an md5 HMAC.
		// Eliminates the need to install mhash to compute a HMAC
		// Hacked by Lance Rushing(NOTE: Hacked means written)

		//需要配置环境支持iconv，否则中文参数不能正常处理
		// $key = iconv("GB2312","UTF-8",$key);
		// $key = iconv("gb2312","utf-8//IGNORE",$key);
		// $data = iconv("GB2312","UTF-8",$data);
		$data = iconv("gb2312","utf-8//IGNORE",$data);
		$this->CI->utils->debug_log("=====================dlkepay hamacmd5 data ", $data);

		$sign=md5($data);

		return $sign;
	}

    public function createSignStr($params) {
		$date = array('parter','type','value','orderid','callbackurl');
		$signStr = '';
		foreach ($date as $Key => $value) {
			$signStr .= $value .'='. $params[$value].'&';
		}
		$signStr = rtrim($signStr,"&");
		$signStr .= $this->getSystemInfo('key');
		return $signStr;
	}
}
