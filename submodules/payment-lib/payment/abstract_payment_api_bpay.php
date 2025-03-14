<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * BPay
 *
 * * 'BPAY_ALIPAY_PAYMENT_API', ID 944
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
abstract class Abstract_payment_api_bpay extends Abstract_payment_api {

    const SCANTYPE_ALIPAY= 'WAY_TYPE_ALIPAY'; //  支付宝
    const SCANTYPE_ALIPAY_H5= 'WAY_TYPE_ALIPAY_PHONE'; //  支付宝 H5
    const SCANTYPE_WEIXIN= 'WAY_TYPE_WEBCAT'; //微信
    const SCANTYPE_BANK= 'WAY_TYPE_BANK'; //網銀支付

	const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '0';
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

		$params['p_name'] = $this->getSystemInfo("account");
		$params['p_oid'] = $order->secure_id;
		$params['p_money'] = $this->convertAmountToCurrency($amount); //元
		$params['p_url'] = $this->getNotifyUrl($orderId);
		$params['p_surl'] = $this->getReturnUrl($orderId);
		$params['p_remarks'] = 'Deposit';
		$params['uname'] = $this->getSystemInfo("account");
		$params['p_syspwd'] = $this->sign();
		$this->configParams($params, $order->direct_pay_extra_info);
		$this->CI->utils->debug_log("=====================bpay generatePaymentUrlForm", $params);
		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {

		$desKey = $this->getSystemInfo('deskey');
	    $url = $this->getSystemInfo('url');
	    $uname = $params['uname'];
	    $query_string='';
		foreach($params as $key=>$value) {

			$query_string .= $key.'='.$value.'!';
		}
		$query_string = urlencode(rtrim($query_string, '!'));
		$desStr =  $this->encrypt($query_string, $desKey);
		$urlStr = $url . '?params=' . $desStr . '&uname=' . $uname;
		$this->CI->utils->debug_log("=====================bpay processPaymentUrlFormPost URL", $urlStr);
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_URL,
			'url' => $urlStr,
		);
	}

	public function encrypt($params, $deskey)
	{
	    $size = @mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_CBC);
	    $str =  $this->pkcs5Pad($params, $size);
	    $data = openssl_encrypt($str, 'des-cbc', $deskey, 1, $deskey);
	    $data = substr($data, 0, strlen($data) - strlen($deskey));
	    $data = base64_encode($data);
	    return $data;
	}

	public function pkcs5Pad($text, $blocksize)
	{
	    $pad = $blocksize - (strlen($text) % $blocksize);
	    return $text . str_repeat(chr($pad), $pad);
	}

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
    	$url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, false, $params['CustomerId']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('=============================bpay processPaymentUrlFormQRcode response json to array', $decode_data);
        $msg = lang('Invalidte API response');

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

        $this->CI->utils->debug_log("=====================bpay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id,
                $params['p_oid'], 'Third Party Payment (No Bank Order Number)', # no info available
                null, null, $response_result_id);
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

		$requiredFields = array('p_name', 'p_oid','p_money','p_code','p_remarks','p_syspwd','p_md5');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================bpay missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['p_code'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['p_code'];
			$this->writePaymentErrorLog("=====================bpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['p_money'] )
		) {
			$this->writePaymentErrorLog("=====================bpay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['p_oid'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================bpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================bpay checkCallbackOrder verify signature Error', $fields);
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
		return number_format($amount, 2, '.', '');
	}

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign() {
		$syspwd = $this->getSystemInfo('syspwd');
		$md5key = $this->getSystemInfo('key');
		$sigStr = md5($syspwd.$md5key);
		return $sigStr;
	}

    public function verifySignature($data) {
	    $callback_sign = $data['p_md5'];
        $keys = array('p_name', 'p_oid', 'p_money');
        $signStr = "";
        foreach($keys as $key) {
            $signStr .= $data[$key];
        }
		$signStr .= $this->getSystemInfo('syspwd');
		$md5key = $this->getSystemInfo('key');
        $sign=md5($signStr.$md5key);
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }
}
