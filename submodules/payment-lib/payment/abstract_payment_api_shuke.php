<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * shuke  数科
 *
 * * 'SHUKE_ALIPAY_PAYMENT_API', ID 5010
 * * 'SHUKE_ALIPAY_H5_PAYMENT_API', ID 5011
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
abstract class Abstract_payment_api_shuke extends Abstract_payment_api {

    const SCANTYPE_WEIXIN     = '0'; // 微信
    const SCANTYPE_ALIPAY     = '1'; // 支付宝
    const SCANTYPE_QQPAY      = '2'; // qq支付
    const SCANTYPE_JDPAY      = '3'; // 京东支付
    const SCANTYPE_UNIONPAY   = '4'; // 银联支付
    const SCANTYPE_ONLINEBANK = '5'; // 银联网关

    const RETURN_TYPE_QRCODE  = '1'; // 扫码支付
    const RETURN_TYPE_H5      = '2'; // H5

	const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE  = 'FAIL';
    const REQUEST_SUCCESS     = '00';
	const PAY_RESULT_SUCCESS  = '1';

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
		$player = $this->CI->player->getPlayerById($playerId);        
		$username = $player['username'];

		$params['agent_code']   = $this->getSystemInfo("account");
		$params['order_no']     = $order->secure_id;
		$params['pay_money']    = $this->convertAmountToCurrency($amount); //元
		$params['notify_url']   = $this->getNotifyUrl($orderId);
		$params['client_ip']    = $this->getClientIp();
		$params['timestamp']    = $this->getMillisecond();
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================shuke generatePaymentUrlForm", $params);
		return $this->processPaymentUrlForm($params);
	}

	public function getMillisecond() {
    list($t1, $t2) = explode(' ', microtime());
    return $t2 .   ceil( ($t1 * 1000) );
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {

  	    $url = $this->getSystemInfo('url');
	    $this->CI->utils->debug_log("=====================fengyunpay processPaymentUrlFormPost URL", $url);
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
        $this->CI->utils->debug_log('=====================shuke processPaymentUrlFormQRcode scan url',$url);
        $response = $this->submitPostForm($url, $params, false, $params['order_no']);
        $this->CI->utils->debug_log('=====================shuke processPaymentUrlFormQRcode received response', $response);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('=====================shuke processPaymentUrlFormQRcode response[1] json to array', $decode_data);
        $msg = lang('Invalidte API response');

        if(!empty($decode_data['data']['pay_url']) && ($decode_data['code'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $decode_data['data']['pay_url'],
            );
        }else {
            if(!empty($decode_data['code'])) {
                $msg = 'code:'.$decode_data['code'].', msg:'.$decode_data['msg'];
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
		if(empty($params) || is_null($params)){
			$raw_post_data = file_get_contents('php://input', 'r');
        	$params = json_decode($raw_post_data, true);
		}
        $this->CI->utils->debug_log('=======================shuke callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================shuke callbackFromServer server callbackFrom', $params);
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
                $params['order_no'], 'Third Party Payment (No Bank Order Number)', # no info available
                null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {

				$amount=floatval($params['pay_money']);
                //update sale order number
                $notes = $order->notes . " diff amount, old amount is " . $order->amount;
                $success = $this->CI->sale_order->fixOrderAmount($order->id, $amount, $notes);

                if(!$success){

                    $respParams = array();
                    $respParams['status'] = 0;
                    $respParams['error_msg'] = 'Internet Error, change amount failed';

                    return ['success' => false, 'return_error' => json_encode($respParams)];
                }
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

		$requiredFields = array('agent_code', 'pay_order_sn', 'order_no', 'status', 'pay_money', 'pay_type', 'timestamp', 'sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================shuke missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['status'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['rescode'];
			$this->writePaymentErrorLog("=====================shuke Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		$lastAmount = abs($this->convertAmountToCurrency($order->amount) - floatval( $fields['pay_money']));
		if ( $lastAmount >= 1 ) {
			$this->writePaymentErrorLog("=====================shuke Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['order_no'] != $order->secure_id) {
	        $this->writePaymentErrorLog("=====================shuke checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
	        return false;
   		}
   		
        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================shuke checkCallbackOrder verify signature Error', $fields);
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
            // array('label' => '农业发展银行', 'value' => '203'),
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
            // array('label' => '恒丰银行', 'value' => '315'),
            // array('label' => '浙商银行', 'value' => '316'),
            array('label' => '渤海银行', 'value' => 'BHB'),
            array('label' => '上海银行', 'value' => 'SHBANK'),
            array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
            // array('label' => '徽商银行', 'value' => '440'),
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
		$signStr = $this->createSignStr($params);
        $sign=md5($signStr);
		return $sign;
	}

    public function verifySignature($data) {
	    $callback_sign = $data['sign'];
        $signStr = $this->createSignStr($data);
        $sign=md5($signStr);
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }

    private function createSignStr($params) {
    	ksort($params);
    	$signStr='';
		foreach ($params as $key => $value) {
			if($key=='sign'){
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		$signStr = rtrim($signStr, '&');
		$signStr .= $this->getSystemInfo('key');
		return $signStr;
	}
}
