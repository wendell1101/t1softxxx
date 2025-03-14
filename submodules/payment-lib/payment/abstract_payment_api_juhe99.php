<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * *  JUHE99  聚合99
 *
 * * 'JUHE99_UNIONPAY_PAYMENT_API', ID 917
 * * 'JUHE99_QQPAY_PAYMENT_API', ID 918
 * * 'JUHE99_JDPAY_PAYMENT_API', ID 919
 * * 'JUHE99_UNIONPAY_WAP_PAYMENT_API', ID 920
 * * 'JUHE99_QQPAY_WAP_PAYMENT_API', ID 921
 * * 'JUHE99_JDPAY_WAP_PAYMENT_API', ID 922
 * *  Required Fields:
 *
 * * URL  http://t.9556182.com/trx-service/appPay/api.action
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_juhe99 extends Abstract_payment_api {

    const SCANTYPE_QQPAY= 'QQPAY'; // QQ 钱包
    const SCANTYPE_JDPAY= 'JDPAY'; // 京东支付
    const SCANTYPE_UNIONPAY = 'UNIONPAY';  //银联钱包

    const PAYTYPE_SCAN  = 'SCAN';
    const PAYTYPE_WAP = 'WAP';
    const AUTHCODE = '0';

	const RETURN_SUCCESS_CODE = 'SUCCESS';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '0000';
	const PAY_RESULT_SUCCESS = 'SUCCESS';

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

		$params['trxType'] = 'AppPay';
		$params['r1_merchantNo'] = $this->getSystemInfo("account");
		$params['r2_orderNumber'] = $order->secure_id;
		$params['r4_amount'] = $this->convertAmountToCurrency($amount); //元
		$params['r5_currency'] = 'CNY';
		$params['r6_authcode'] = self::AUTHCODE;
		$params['r8_callbackUrl'] = $this->getNotifyUrl($orderId);
		$params['r10_orderIp'] = $this->getClientIp();
		$params['r11_itemname'] = 'Deposit';

		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================juhe99 generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
	    $url = $this->getSystemInfo('url');
	    $interface_name = 'fuseJumpProxy';
	   	$new_url = $url.$interface_name;
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $new_url,
			'params' => $params,
			'post' => true,
		);
	}

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
    	$url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, false, $params['r2_orderNumber']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================juhe99 processPaymentUrlFormQRcode response json to array', $decode_data);

        $msg = lang('Invalidate API response');
        if(!empty($decode_data['r5_qrcode']) && ($decode_data['retCode'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $decode_data['r5_qrcode'],
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

        $this->CI->utils->debug_log("=====================juhe99 callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['r2_orderNumber'], null, null, null, $response_result_id);
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
			'r1_merchantNo', 'r2_orderNumber','r3_serialNumber','r4_orderStatus','r5_amount','r6_currency','r7_timestamp','r8_desc','sign'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================juhe99 missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['r4_orderStatus'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['r4_orderStatus'];
			$this->writePaymentErrorLog("=====================juhe99 Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['r5_amount'] )
		) {
			$this->writePaymentErrorLog("=====================juhe99 Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['r2_orderNumber'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================juhe99 checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================juhe99 checkCallbackOrder verify signature Error', $fields);
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
            array('label' => '交通银行', 'value' => 'COMM'),
            array('label' => '中信银行', 'value' => 'CITIC'),
            array('label' => '光大银行', 'value' => 'CEB'),
            array('label' => '华夏银行', 'value' => 'HXBANK'),
            array('label' => '民生银行', 'value' => 'CMBC'),
            array('label' => '广发银行', 'value' => 'GDB'),
            array('label' => '平安银行', 'value' => 'SPABANK'),
            array('label' => '招商银行', 'value' => 'CMB'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '北京银行', 'value' => 'BJBANK'),
            array('label' => '渤海银行', 'value' => 'BHB'),
            array('label' => '上海银行', 'value' => 'SHBANK'),
            array('label' => '邮储银行', 'value' => 'PSBC'),
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
	public function sign($params) {
		$keys = array('trxType', 'r1_merchantNo', 'r2_orderNumber', 'r3_payType', 'r4_amount', 'r5_currency', 'r7_appPayType', 'r8_callbackUrl','r10_orderIp');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= '#'.$params[$key];
            }
        }
        $signStr .= '#'.$this->getSystemInfo('key');
        $sign = md5($signStr);
        return $sign;
	}

    public function verifySignature($data) {
	    $callback_sign = $data['sign'];
       	$keys = array('r1_merchantNo', 'r2_orderNumber', 'r3_serialNumber', 'r4_orderStatus', 'r5_amount', 'r6_currency', 'r7_timestamp');
        $signStr = "";
        foreach($keys as $key) {
            $signStr .= '#'.$data[$key];
        }
        $signStr .= '#'.$this->getSystemInfo('key');
        $sign = md5($signStr);
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }
}
