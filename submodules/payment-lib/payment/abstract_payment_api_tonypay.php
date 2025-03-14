<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * TONYPAY  Tony支付
 *
 * * 'TONYPAY_PAYMENT_API', ID 5508
 * * 'TONYPAY_ALIPAY_PAYMENT_API', ID 5509
 * * 'TONYPAY_ALIPAY_H5_PAYMENT_API', ID 5510

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
abstract class Abstract_payment_api_tonypay extends Abstract_payment_api {

    const PAYTYPE_ALIPAY = 'QRALIPAY'; 
    const PAYTYPE_WEIXIN = 'QRWEIXIN'; 
	const PAYTYPE_UNIONAPAY = 'QRUNIONPAY';
    const PAYTYPE_ALIPAY_H5 = 'ALIPAY'; 
    const PAYTYPE_WEIXIN_H5 = 'WEIXIN'; 

    const SERVICETYPE_SCAN = 'scan_pay'; 
    const SERVICETYPE_H5 = 'h5_pay'; 
    const SERVICETYPE_BANK = 'netbank_pay'; 

	const RETURN_SUCCESS_CODE = 'OK';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = 'T';
	const PAY_RESULT_SUCCESS = '01';

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
		
		$this->configParams($params, $order->direct_pay_extra_info); //$params['service'] $params['pay_type']
		$params['version'] = $this->getSystemInfo("version","1.0");
        $params['request_time'] = $orderDateTime->format('Ymdhis');
		$params['partner_id'] = $this->getSystemInfo("account");
        $params['_input_charset'] = 'UTF-8';
		$params['out_order_no'] = $order->secure_id;
        $params['amount'] = $this->convertAmountToCurrency($amount); //元
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['return_url'] = $this->getReturnUrl($orderId);
        $params['sign'] = $this->sign($params);
		$params['sign_type'] = 'MD5';
        
		$this->CI->utils->debug_log("=====================tonypay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {

	    $url = $this->getSystemInfo('url');
	    $this->CI->utils->debug_log("=====================tonypay processPaymentUrlFormPost URL", $url);
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
        $this->CI->utils->debug_log('=====================tonypay processPaymentUrlFormQRcode scan url',$url);
        $response = $this->submitPostForm($url, $params, false, $params['out_order_no']);  
        $this->CI->utils->debug_log('========================================tonypay processPaymentUrlFormQRcode received response', $response);

        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================tonypay processPaymentUrlFormQRcode response[1] json to array', $decode_data);
        $msg = lang('Invalidte API response');

    	if($decode_data['is_success'] == self::REQUEST_SUCCESS) {
			$url = $decode_data['post_data'];
			$type = self::REDIRECT_TYPE_URL;

			if(empty($decode_data['post_data'])&&!empty($decode_data['scan2h5'])){
				$url = $decode_data['scan2h5'];
			}
			
			if($params['service'] == self::SERVICETYPE_SCAN){
				$type = self::REDIRECT_TYPE_QRCODE;
			}

            return array(
                'success' => true,
                'type' => $type,
                'url' => $url,
			);
			
        }else {
            if(!empty($decode_data['error_message'])) {
                $msg = $decode_data['error_code'].": ".$decode_data['error_message'];
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

        $this->CI->utils->debug_log("=====================tonypay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['outOrderNo'], null, null, null, $response_result_id);
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
			'order_no', 'status','sign'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================tonypay missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['status'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['status'];
			$this->writePaymentErrorLog("=====================tonypay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['amount'] )) {
			$this->writePaymentErrorLog("=====================tonypay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================tonypay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================tonypay checkCallbackOrder verify signature Error', $fields);
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
			"1" => array('label' => '工商银行', 'value' => 'ICBC'),
			"2" => array('label' => '招商银行', 'value' => 'CMB'),
			"3" => array('label' => '建设银行', 'value' => 'CCB'),
			"4" => array('label' => '农业银行', 'value' => 'ABC'),
			"5" => array('label' => '交通银行', 'value' => 'COMM'),
			"6" => array('label' => '中国银行', 'value' => 'BOC'),
			"8" => array('label' => '广发银行', 'value' => 'GDB'),
			"10" => array('label' => '中信银行', 'value' => 'CITIC'),
			"11" => array('label' => '民生银行', 'value' => 'CMBC'),
			"13" => array('label' => '兴业银行', 'value' => 'CIB'),
			"14" => array('label' => '华夏银行', 'value' => 'HXB'),
			"15" => array('label' => '平安银行', 'value' => 'SZPAB'),
			// "" => array('label' => '上海银行', 'value' => 'BOS'),
			"12" => array('label' => '邮政储蓄银行', 'value' => 'PSBC'),
			"20" => array('label' => '光大银行', 'value' => 'CEB'),
			"24" => array('label' => '浦发银行', 'value' => 'SPDB')
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
        $sign = md5($signStr);

		return $sign;
	}

	private function validateSign($params){
        $keys = array(
			'order_no' => $params['order_no'],
			'status' => $params['status']
		);

		$signStr = '';
        foreach($keys as $key => $value) {
            $signStr .= "$key=$value&";
        }
		$signStr = rtrim($signStr, '&');

        $sign = md5($signStr. $this->getSystemInfo('key'));
        
		if($params['sign'] == $sign){
			return true;
		}
        else{
            return false;
        }

	}

	private function createSignStr($params) {
        ksort($params);
		$signStr = '';
        foreach($params as $key => $value) {
            if(($key == 'sign') || (empty($value)) || $value == '') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
		$signStr = rtrim($signStr, '&');
        return $signStr. $this->getSystemInfo('key');
    }
	
	public function getPlayerDetails($playerId) {
        $this->CI->load->model(array('player_model'));
        $player = $this->CI->player_model->getPlayerDetails($playerId);

        return $player;
    }
}
