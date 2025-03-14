<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * TONGTAI 通泰
 *
 * * TONGTAI_PAYMENT_API, ID: 5132
 * * TONGTAI_QUICKPAY_PAYMENT_API, ID: 5133
 *
 * Required Fields:
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 * Field Values:
 * * URL:
 *   網銀 http://69.172.75.141:7802/api.php/wgpay/wap_pay
 *   快捷 http://69.172.75.141:7802/api.php/quickpay/wap_pay
 *   支付寶掃碼 http://69.172.75.141:7802/api.php/dlipay/wap_pay
 *   支付寶H5 http://69.172.75.141:7802/api.php/alipay/wap_pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_tongtai extends Abstract_payment_api {

    const RESULT_CODE_SUCCESS = '00';
	const RETURN_SUCCESS_CODE = 'OK';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '00';
	const PAY_RESULT_SUCCESS = '00';

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

        $params['branchId']  = $this->getSystemInfo("branchId");
        $params['merCode']   = $this->getSystemInfo("account");
        $params['settType']  = 'T0';
        $params['userIp']    = $this->getClientIp();
        $params['orderId']   = $order->secure_id;
        $params['transAmt']  = $this->convertAmountToCurrency($amount); //元
        $params['returnUrl'] = $this->getNotifyUrl($orderId);
        $params['notifyUrl'] = $this->getReturnUrl($orderId);

		$this->configParams($params, $order->direct_pay_extra_info);
		$params['signature'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================tongtai generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}


    protected function processPaymentUrlFormPost($params) {
        $data['data'] = urlencode(urlencode($this->json_encode_ex($params)));
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $this->getSystemInfo('url'),
            'params' => $data,
            'post' => true,
        );
    }

    protected function processPaymentUrlFormRedirect($params) {
        $data = array();
        $data['data'] = urlencode(urlencode($this->json_encode_ex($params)));
        $response = $this->submitPostForm($this->getSystemInfo('url'), $data, false, $params['orderId']);
        $this->CI->utils->debug_log('=====================tongtai processPaymentUrlFormPost received response', $response);
        $response = json_decode($this->checkBOM($response), JSON_UNESCAPED_UNICODE);
        $this->CI->utils->debug_log('=====================tongtai processPaymentUrlFormPost decoded response', $response);

        if(isset($response['respCode']) && $response['respCode'] == self::RESULT_CODE_SUCCESS) {
            if($this->CI->utils->is_mobile()){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['qrcodeUrl'],
                );
            }
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['payUrl'],
            );
        }
        else if($response['result']['msg']) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => '['.$response['result']['code'].']: '.$response['result']['msg'],
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
		if(empty($params) || is_null($params)){
			$raw_post_data = file_get_contents('php://input', 'r');
        	$params = json_decode($raw_post_data, true);
		}
        $this->CI->utils->debug_log('=======================tongtai callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================tongtai callbackFromServer server callbackFrom', $params);
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
                $params['orderId'], 'Third Party Payment (No Bank Order Number)', # no info available
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

		$requiredFields = array('flowId','orderId','returnUrl','transAmt','transCode','transMsg','signature');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================tongtai missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['transCode'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['transCode'];
			$this->writePaymentErrorLog("=====================tongtai Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['transAmt']) {
			$this->writePaymentErrorLog("=====================tongtai Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['orderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================tongtai checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================tongtai checkCallbackOrder verify signature Error', $fields);
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
            array('label' => '工商银行', 'value' => 'ICBC'),
            array('label' => '农业银行', 'value' => 'ABC'),
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '建设银行', 'value' => 'CCB'),
            // array('label' => '农业发展银行', 'value' => '203'),
            array('label' => '交通银行', 'value' => 'BOCOM'),
            array('label' => '中信银行', 'value' => 'CNCB'),
            array('label' => '光大银行', 'value' => 'CEB'),
            array('label' => '华夏银行', 'value' => 'HXB'),
            array('label' => '民生银行', 'value' => 'CMBC'),
            array('label' => '广发银行', 'value' => 'GDB'),
            array('label' => '平安银行', 'value' => 'PAB'),
            array('label' => '招商银行', 'value' => 'CMB'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '北京银行', 'value' => 'BCCB'),
            // array('label' => '南京银行', 'value' => 'NJCB'),
            // array('label' => '恒丰银行', 'value' => '315'),
            // array('label' => '浙商银行', 'value' => 'CZ'),
            // array('label' => '杭州银行', 'value' => 'HZBANK'),
            // array('label' => '上海农村商业银行', 'value' => 'SHB'),
            // array('label' => '河北银行', 'value' => 'BOHB'),
            // array('label' => '泰隆银行', 'value' => 'ZJTLCB'),
            // array('label' => '成都银行', 'value' => 'BOCDBANK'),
            // array('label' => '渤海银行', 'value' => 'CBHB'),
            // array('label' => '东亚银行', 'value' => 'HKBEA'),
            // array('label' => '宁波银行', 'value' => 'NBCB'),
            // array('label' => '上海银行', 'value' => 'SHB'),
            // array('label' => '上海浦东发展银行', 'value' => 'SPDB'),
            array('label' => '邮政储蓄银行', 'value' => 'PSBC'),
            // array('label' => '北京农村商业银行', 'value' => 'BJRCB'),
            // array('label' => '徽商银行', 'value' => '440'),
            // array('label' => '广州市商业银行', 'value' => 'GRCBANK')
		);
	}

	# -- Private functions --
	/**
	 * detail: After payment is complete, the gateway will invoke this URL asynchronously
	 *
	 * @param int $orderId
	 * @return void
	 */
	public function getNotifyUrl($orderId) {
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
    public function json_encode_ex($value) {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $str = json_encode($value);
            $str = preg_replace_callback(
                "#\\\u([0-9a-f]{4})#i",
                function ($matchs) {
                    return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
                },
                $str
            );
            return $str;
        } else {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
    }

    public function checkBOM($contents){
        $charset [1] = substr($contents, 0, 1);
        $charset [2] = substr($contents, 1, 1);
        $charset [3] = substr($contents, 2, 1);
        if(ord($charset[1]) == 239 && ord($charset [2]) == 187 && ord($charset [3]) == 191) {
            $contents = substr($contents, 3);
            return $this->checkBOM($contents);
        }
        return $contents;
    }

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
		$signStr =  $this->createSignStr($params);
        $sign=md5($signStr);
		return $sign;
	}

    public function verifySignature($data) {
	    $callback_sign = $data['signature'];
        $signStr =  $this->createSignStr($data);
        $sign=md5($signStr);
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }

    private function createSignStr($params) {
    	ksort($params);
       	$signStr='';
		foreach ($params as $key => $value) {
			if(is_null($value) || $key == 'signature' || $key == ''){
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		$signStr .= $this->getSystemInfo('key');
		return $signStr;
	}
}
