<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * FFPAY
 *
 * * FFPAY_PAYMENT_API, ID: 5102
 * * FFPAY_ALIPAY_PAYMENT_API, ID: 5103
 * * FFPAY_ALIPAY_H5_PAYMENT_API, ID: 5104
 * * FFPAY_WEIXIN_PAYMENT_API, ID: 5105
 * * FFPAY_WEIXIN_H5_PAYMENT_API, ID: 5106
 * * FFPAY_WITHDRAWAL_PAYMENT_API, ID: 5131
 * * FFPAY_QUICKPAY_PAYMENT_API, ID: 5140
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.ffpay.net/api/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_ffpay extends Abstract_payment_api {

	const BANKCODE_ONLINEBANK = 'MOBILEBANK'; //网关支付
    const BANKCODE_ALIPAY	  = 'ALIPAY'; //支付宝
    const BANKCODE_ALIPAY_H5  = 'ALIPAY_WAP'; //支付宝H5
	const BANKCODE_WEIXIN     = 'WECHAT'; //微信
	const BANKCODE_WEIXIN_H5  = 'WECHAT_WAP'; //微信H5
	const BANKCODE_QUICKPAY   = 'EXPRESS'; //银联快捷

	const RETURN_SUCCESS_CODE = 'OK';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '200';
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

		$params['MerchantCode'] = $this->getSystemInfo("account");
		$params['Amount']       = $this->convertAmountToCurrency($amount); //元
		$params['OrderId']      = $order->secure_id;
		$params['NotifyUrl']    = $this->getNotifyUrl($orderId);
        $params['ReturnUrl']    = $this->getReturnUrl($orderId);
		$params['OrderDate']    = $this->getMillisecond();
		$params['Ip']           = $this->getClientIp();
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['Sign']         = $this->sign($params);
		$this->CI->utils->debug_log("=====================ffpay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

    protected function processPaymentUrlFormRedirect($params) {
    	$url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, false, $params['OrderId']);
        $decoded = json_decode($response, true);

        $this->CI->utils->debug_log('=====================ffpay processPaymentUrlFormQRcode received response', $response);
        $this->CI->utils->debug_log('=====================ffpay processPaymentUrlFormQRcode response json to array', $decoded);

		if(isset($decoded['success']) && $decoded['success']) {
			switch ($decoded['data']['data']['type']) {
				case 'url':
					return array(
		                'success' => true,
		                'type' => self::REDIRECT_TYPE_URL,
		                'url' => $decoded['data']['data']['info'],
		        	);
					break;
				case 'img':
					return array(
		                'success' => true,
		                'type' => self::REDIRECT_TYPE_QRCODE,
		                'image_url' => $decoded['data']['data']['info'],
		        	);
					break;
				case 'string':
                    return array(
                        'success' => true,
                        'type' => self::REDIRECT_TYPE_HTML,
                        'html' => $decoded['data']['data']['info'],
                    );
					break;
				default:
		            return array(
		                'success' => false,
		                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
		                'message' => 'Unknown type: '.$decoded['data']['data']['type']
		            );
					break;
			}
        } elseif(isset($decoded['success']) && !$decoded['success']) {
            $resultMsg = json_decode($decoded['resultMsg'], true);
            if(!is_null($resultMsg)){
                $error_msg = "[".$resultMsg['code']."]".$resultMsg['msg'];
            } else {
                $error_msg = $decoded['resultMsg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $error_msg
            );
        } else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
            );
        }
    }

    protected function processPaymentUrlFormQRCode($params) {
    	$url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, false, $params['OrderId']);
        $decoded = json_decode($response, true);

        $this->CI->utils->debug_log('=====================ffpay processPaymentUrlFormQRcode received response', $response);
        $this->CI->utils->debug_log('=====================ffpay processPaymentUrlFormQRcode response json to array', $decoded);

		if(isset($decoded['success']) && $decoded['success']) {
			switch ($decoded['data']['data']['type']) {
				case 'url':
					return array(
		                'success' => true,
		                'type' => self::REDIRECT_TYPE_QRCODE,
		                'url' => $decoded['data']['data']['info'],
		        	);
					break;
				case 'img':
					return array(
		                'success' => true,
		                'type' => self::REDIRECT_TYPE_QRCODE,
		                'image_url' => $decoded['data']['data']['info'],
		        	);
					break;
                case 'string':
                    return array(
                        'success' => true,
                        'type' => self::REDIRECT_TYPE_HTML,
                        'html' => $decoded['data']['data']['info'],
                    );
                    break;
				default:
		            return array(
		                'success' => false,
		                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
		                'message' => 'Unknown type: '.$decoded['data']['data']['type']
		            );
					break;
			}
        } elseif(isset($decoded['success']) && !$decoded['success']) {
            $resultMsg = json_decode($decoded['resultMsg'], true);
            if(!is_null($resultMsg)){
                $error_msg = "[".$resultMsg['code']."]".$resultMsg['msg'];
            } else {
                $error_msg = $decoded['resultMsg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $error_msg
            );
        } else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
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

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================ffpay callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['OrderId'], null, null, null, $response_result_id);
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

		$requiredFields = array('MerchantCode','OrderId','OrderDate','Amount','OutTradeNo','BankCode','Time','Remark','Status','Sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================ffpay missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['Status'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['Status'];
			$this->writePaymentErrorLog("=====================ffpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['Amount'] )
		) {
			$this->writePaymentErrorLog("=====================ffpay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['OrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================ffpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================ffpay checkCallbackOrder verify signature Error', $fields);
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
            array('label' => '交通银行', 'value' => 'BOCM'),
            array('label' => '中信银行', 'value' => 'ECITIC'),
            array('label' => '光大银行', 'value' => 'CEB'),
            array('label' => '华夏银行', 'value' => 'HXB'),
            array('label' => '民生银行', 'value' => 'CMBC'),
            array('label' => '广发银行', 'value' => 'GDB'),
            array('label' => '平安银行', 'value' => 'PAB'),
            array('label' => '招商银行', 'value' => 'CMB'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '北京银行', 'value' => 'BOB'),
            array('label' => '南京银行', 'value' => 'NJCB'),
            array('label' => '上海农村商业银行', 'value' => 'SHB'),
            array('label' => '邮政储蓄银行', 'value' => 'PSBC'),
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
		return number_format($amount, 2, '.', '');
	}

	protected function getMillisecond() {
	    list($s1, $s2) = explode(' ', microtime());
	    return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
	}

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
		$signStr = $this->createSignStr($params);
        $sign = md5($signStr);
		return $sign;
	}

    public function verifySignature($data) {
	    $callback_sign = $data['Sign'];
        $signStr =  $this->createSignStr($data);
        $sign = md5($signStr);
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }

    private function createSignStr($params) {
        unset($params['ReturnUrl']);
    	ksort($params);
       	$signStr='';
		foreach ($params as $key => $value) {
			if(is_null($value) || $key == 'Sign' || $key == 'Remark'){
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'Key='.$this->getSystemInfo('key');
		return $signStr;
	}
}
