<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * aobangpay  奥邦
 *
 * * AOBANGPAY_PAYMENT_API, ID: 5048
 * * AOBANGPAY_ALIPAY_PAYMENT_API, ID: 5049
 * * AOBANGPAY_ALIPAY_H5_PAYMENT_API, ID: 5050
 * * AOBANGPAY_WEIXIN_PAYMENT_API, ID: 5051
 * * AOBANGPAY_WEIXIN_H5_PAYMENT_API, ID: 5052
 * * AOBANGPAY_UNIONPAY_PAYMENT_API, ID: 5053
 * * AOBANGPAY_QUICKPAY_PAYMENT_API, ID: 5054
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.aobang2pay.com/pay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_aobangpay extends Abstract_payment_api {

	const PRODUCT_TYPE_ONLINEBANK = '50103'; //网关支付
    const PRODUCT_TYPE_ALIPAY	  = '20303'; //支付宝
    const PRODUCT_TYPE_ALIPAY_H5  = '20203'; //支付宝H5
    const PRODUCT_TYPE_WEIXIN	  = '10103'; //微信
    const PRODUCT_TYPE_WEIXIN_H5  = '10203'; //微信H5
    const PRODUCT_TYPE_UNIONPAY   = '60103'; //银联扫码支付
    const PRODUCT_TYPE_QUICKPAY	  = '40103'; //快捷支付

	const RETURN_SUCCESS_CODE = 'SUCCESS';
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

        $params = array();
        $params['trx_key']      = $this->getSystemInfo("account");
        $params['ord_amount']   = $this->convertAmountToCurrency($amount);
        $params['request_id']   = $order->secure_id;
        $params['request_ip']   = $this->getClientIp();
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['request_time'] = date('Ymdhis');
        $params['goods_name']   = 'Deposit';
        $params['return_url']   = $this->getReturnUrl($orderId);
        $params['callback_url'] = $this->getNotifyUrl($orderId);
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================aobangpay generatePaymentUrlForm", $params);

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
        $response = $this->submitPostForm($url, $params, false, $params['request_id']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('======================aobangpay processPaymentUrlFormQRcode response json to array', $decode_data);
        $msg = lang('Invalidte API response');

		if(!empty($decode_data['data']) && ($decode_data['rsp_code'] == self::REQUEST_SUCCESS)) {
	   		if($params['product_type'] == self::PRODUCT_TYPE_ALIPAY_H5){
  	 	    	return array(
	                'success' => true,
	                'type' => self::REDIRECT_TYPE_URL,
	                'url' => $decode_data['data'],
                );
    	     }else{
    	     	return array(
	                'success' => true,
	                'type' => self::REDIRECT_TYPE_QRCODE,
	                'image_url' => $decode_data['data'],
            	);
            }
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
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        if($source == 'server'){
            $this->CI->utils->debug_log('=======================aobangpay callbackFromServer server callbackFrom', $params);
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
                $params['request_id'], 'Third Party Payment (No Bank Order Number)', # no info available
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
		$requiredFields = array('trx_key', 'ord_amount','request_id','trx_status','product_type','request_time','goods_name','trx_time','pay_request_id','sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================aobangpay missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['trx_status'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['trx_status'];
			$this->writePaymentErrorLog("=====================aobangpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval($fields['ord_amount']) ) {
			$this->writePaymentErrorLog("=====================aobangpay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['request_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================aobangpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================aobangpay checkCallbackOrder verify signature Error', $fields);
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
            array('label' => '建设银行', 'value' => '1105'),
            array('label' => '交通银行', 'value' => '1301'),
            array('label' => '光大银行', 'value' => '1311'),
            array('label' => '华夏银行', 'value' => '1304'),
            array('label' => '民生银行', 'value' => '1305'),
            array('label' => '广发银行', 'value' => '1460'),
            array('label' => '招商银行', 'value' => '1308'),
            array('label' => '北京银行', 'value' => '1313'),
            array('label' => '上海银行', 'value' => '1310'),
            array('label' => '邮政储蓄银行', 'value' => '1312'),
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
        $sign = strtoupper(md5($signStr));
		return $sign;
	}

    public function verifySignature($data) {
	    $callback_sign = $data['sign'];
        unset($data['sign']);
        unset($data['remark']);
        $signStr = $this->createSignStr($data);
        $sign = md5($signStr);
        return $sign == $callback_sign;
    }

    private function createSignStr($params) {
    	ksort($params);
       	$signStr='';
		foreach ($params as $key => $value) {

			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'secret_key='. $this->getSystemInfo('key');
		return $signStr;
	}
}
