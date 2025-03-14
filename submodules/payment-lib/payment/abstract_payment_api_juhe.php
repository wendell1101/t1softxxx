<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * juhe  聚合
 *
 * * 'JUHE_ALIPAY_PAYMENT_API', ID 5013
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
abstract class Abstract_payment_api_juhe extends Abstract_payment_api {

    const SCANTYPE_ALIPAY= '20000'; // 银行编码 支付宝
    const SCANTYPE_WEIXIN= '30000'; //微信

    const CURRENCY = '156';
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

		$params['merchant_no'] = $this->getSystemInfo("account");
		$params['amount'] = $this->convertAmountToCurrency($amount); //元
		$params['currency'] = self::CURRENCY;
		$params['order_no'] = $order->secure_id;
		$params['pay_ip'] = $this->getClientIp();
		$params['request_time'] = date('Y-m-d h:i:s');
		$params['product_name'] = 'Deposit';
		$params['return_url'] = $this->getReturnUrl($orderId);
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log("=====================juhe generatePaymentUrlForm", $params);
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

    	$url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, false, $params['order_no']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================juhe processPaymentUrlFormQRcode response json to array', $decode_data);
        $msg = lang('Invalidte API response');

        if($this->CI->utils->is_mobile()){
        	if(!empty($decode_data['data']) && ($decode_data['resp_code'] == self::REQUEST_SUCCESS)) {
	            return array(
	                'success' => true,
	                'type' => self::REDIRECT_TYPE_URL,
	                'url' => $decode_data['data'],
	            );
	        }else {
	            if(!empty($decode_data['resp_msg'])) {
	                $msg = $decode_data['resp_msg'];
	            }
	            return array(
	                'success' => false,
	                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
	                'message' => $msg
	            );
	        }

        }else{
        	if(!empty($decode_data['data']) && ($decode_data['resp_code'] == self::REQUEST_SUCCESS)) {
	            return array(
	                'success' => true,
	                'type' => self::REDIRECT_TYPE_QRCODE,
	                'url' => $decode_data['data'],
	            );
	        }else {
	            if(!empty($decode_data['resp_msg'])) {
	                $msg = $decode_data['resp_msg'];
	            }
	            return array(
	                'success' => false,
	                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
	                'message' => $msg
	            );
	        }
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
            $this->CI->utils->debug_log('=======================juhe callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderNo'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {

				$amount = floatval($params['pay_amount']);
                //update sale order number
                $notes = 'Upper score pay_amount';
                if(floatval( $params['order_amount']) != floatval($params['pay_amount'])){

                	$notes = $order->notes . " Payment order_amount do not match callback pay_amount " . $order->amount;
				}

                $this->CI->sale_order->fixOrderAmount($order->id, $amount, $notes);

				$this->approveSaleOrder($order->id, 'auto server callback ' .$this->getPlatformCode().$notes.$amount, false);
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

		$requiredFields = array('merchant_no', 'pay_code','order_no','product_name','order_amount','currency','ord_status','complete_time','payment_trx_no','sign','pay_amount');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================juhe missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['ord_status'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['ord_status'];
			$this->writePaymentErrorLog("=====================juhe Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['order_amount'] )
		) {
			$this->writePaymentErrorLog("=====================juhe Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================juhe checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================juhe checkCallbackOrder verify signature Error', $fields);
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
	public function sign($params) {
        ksort($params);
       	$signStr='';
		foreach ($params as $key => $value) {
			//判斷如果=sing 或 空職 不加入簽名
			if(is_null($value) || empty($value)){
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'key='. $this->getSystemInfo('key');
        $sign=md5($signStr);
		return $sign;
	}

    public function verifySignature($data) {
	    $callback_sign = $data['sign'];
        unset($data['sign']);
        ksort($data);
        $signStr='';
        foreach ($data as $key => $value) {
			if(is_null($value) || empty($value)){
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'key='. $this->getSystemInfo('key');
        $sign=md5($signStr);
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }
}
