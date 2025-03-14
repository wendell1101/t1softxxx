<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * XiaoXioBao 小熊宝
 *
 * * 'XIAOXIOBAO_ALIPAY_PAYMENT_API', ID 5063
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
abstract class Abstract_payment_api_xiaoxiobao extends Abstract_payment_api {

    const PAYTYPE_ALIPAY = 'ALIPAY'; //支付宝

	const RETURN_SUCCESS_CODE = '200';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '0000';
	const PAY_RESULT_SUCCESS = '2';

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

		$params['type'] = 'form';
		$params['merchantId'] = $this->getSystemInfo("account");
		$params['money'] = $this->convertAmountToCurrency($amount); //元
		$params['timestamp'] = date('Ymdhis');
		$params['notifyURL'] = $this->getNotifyUrl($orderId);
		$params['returnURL'] = $this->getReturnUrl($orderId);
		$params['merchantOrderId'] = $order->secure_id;		
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);
		
		$this->CI->utils->debug_log("=====================xiaoxiobao generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {

	    $url = $this->getSystemInfo('url');
	    $this->CI->utils->debug_log("=====================xiaoxiobao processPaymentUrlFormPost URL", $url);
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
        $this->CI->utils->debug_log('=====================xiaoxiobao processPaymentUrlFormQRcode scan url',$url);
        $response = $this->submitPostForm($url, $params, false, $params['request_id']);
        $this->CI->utils->debug_log('======================xiaoxiobao processPaymentUrlFormQRcode received response', $response);

        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('======================xiaoxiobao processPaymentUrlFormQRcode response[1] json to array', $decode_data);
        $msg = lang('Invalidte API response');

		if(!empty($decode_data['data']) && ($decode_data['rsp_code'] == self::REQUEST_SUCCESS)) {
			return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'image_url' => $decode_data['data'],
            );
        }else {
            if(!empty($decode_data['rsp_msg'])) {
                $msg = $decode_data['rsp_msg'];
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
        $this->CI->utils->debug_log('=======================xiaoxiobao callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================xiaoxiobao callbackFromServer server callbackFrom', $params);
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
                $params['merchantOrderNo'], 'Third Party Payment (No Bank Order Number)', # no info available
                null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {

				$amount=floatval($params['payAmount']);
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

		$requiredFields = array('orderNo', 'merchantOrderNo','money','payAmount','sign','payType');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================xiaoxiobao missing parameter: [$f]", $fields);
				return false;
			}
		}

		// if ($fields['trx_status'] != self::PAY_RESULT_SUCCESS) {
		// 	$payStatus = $fields['trx_status'];
		// 	$this->writePaymentErrorLog("=====================xiaoxiobao Payment was not successful, payStatus is [$payStatus]", $fields);
		// 	return false;
		// }

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['money'] )
		) {
			$this->writePaymentErrorLog("=====================xiaoxiobao Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		$lastAmount = abs($this->convertAmountToCurrency($order->amount) - floatval( $fields['payAmount']));
		if ( $lastAmount >= 1) {
			$this->writePaymentErrorLog("=====================xiaoxiobao Payment payAmount do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['merchantOrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================xiaoxiobao checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================xiaoxiobao checkCallbackOrder verify signature Error', $fields);
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
            array('label' => '工商银行', 'value' => '1102'),
            array('label' => '农业银行', 'value' => '1103'),
            // array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '建设银行', 'value' => '1105'),
            // array('label' => '农业发展银行', 'value' => '203'),
            array('label' => '交通银行', 'value' => '1301'),
            // array('label' => '中信银行', 'value' => 'ECITIC'),
            array('label' => '光大银行', 'value' => '1311'),
            array('label' => '华夏银行', 'value' => '1304'),
            array('label' => '民生银行', 'value' => '1305'),
            array('label' => '广发银行', 'value' => '1460'),
            // array('label' => '平安银行', 'value' => 'PINGAN'),
            array('label' => '招商银行', 'value' => '1308'),
            // array('label' => '兴业银行', 'value' => 'CIB'),
            // array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '北京银行', 'value' => '1313'),
            // array('label' => '恒丰银行', 'value' => '315'),
            // array('label' => '浙商银行', 'value' => '316'),
            // array('label' => '渤海银行', 'value' => 'BHB'),
            array('label' => '上海银行', 'value' => '1310'),
            array('label' => '邮政储蓄银行', 'value' => '1312'),
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
		return number_format($amount, 0, '.', '');
	}

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
		$params_keys = array('money','merchantId','notifyURL','returnURL','merchantOrderId','timestamp');
		$signStr =  $this->createSignStr($params_keys,$params);
        $sign=md5($signStr);
		
		return $sign;
	}

    public function verifySignature($data) {
	    $callback_sign = $data['sign'];
	    $data_keys = array('orderNo','merchantOrderNo','money','payAmount');
        $signStr =  $this->createSignStr($data_keys,$data);
        $sign=md5($signStr);
    
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }

    private function createSignStr($params,$val) {
       	$signStr='';
		foreach ($params as $key => $value) {

			$signStr .= $val[$value]."&";
		}
		$signStr .= $this->getSystemInfo('key');
		return $signStr;
	}
}
