<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * TUBEIPAY 途贝支付
 *
 * * TUBEIPAY_ALIPAY_PAYMENT_API, ID: 419
 * * TUBEIPAY_WEIXIN_PAYMENT_API, ID: 420
 * * TUBEIPAY_WEIXIN_H5_PAYMENT_API, ID: 421
 * * TUBEIPAY_QQPAY_PAYMENT_API, ID: 422
 * * TUBEIPAY_QQPAY_H5_PAYMENT_API, ID: 423
 * * TUBEIPAY_JDPAY_PAYMENT_API, ID: 424
 * * TUBEIPAY_JDPAY_H5_PAYMENT_API, ID: 425
 * * TUBEIPAY_BDPAY_PAYMENT_API, ID: 426
 * * LPAY_QUICKPAY_PAYMENT_API, ID: 5395
 * * LPAY_ALIPAY_H5_PAYMENT_API, ID: 5396
 *
 * Required Fields:
 *
 * * URL: http://lpay.9cp7c.com/pay/gateway
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_tubeipay extends Abstract_payment_api {
	const PAYTYPE_ALIPAY = 'pay.alipay.trade.precreate';
	const PAYTYPE_ALIPAY_H5 = 'pay.alipay.trade.precreate';
	const PAYTYPE_WEIXIN = 'pay.weixin.scan.trade.precreate';
	const PAYTYPE_WEIXIN_H5 = 'pay.weixin.scan.trade.precreate';
	const PAYTYPE_QUICKPAY = 'pay.quick.trade.precreate';


	const RESULT_CODE_SUCCESS = '0';
	const RETURN_SUCCESS = 'success';
	const RETURN_FAILED = 'failed';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	/**
	 * detail: Constructs an URL so that the caller can redirect / invoke it to make payment through this API, See controllers/redirect.php for detail.
	 *
	 * @param int $orderId order id
	 * @param int $playerId player id
	 * @param float $amount amount
	 * @param string $orderDateTime
	 * @param int $playerPromoId
	 * @param string $enabledSecondUrl
	 * @param int $bankId
	 * @return array
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$this->configParams($params, $order->direct_pay_extra_info); //$params['service']
		$params['mch_id'] = $this->getSystemInfo("account");
		$params['nonce_str'] = 'Deposit';
		$params['out_trade_no'] = $order->secure_id;
		$params['body'] = 'Deposit';
		$params['total_fee'] = $this->convertAmountToCurrency($amount); //分
		$params['spbill_create_ip'] = $this->getClientIp();
		$params['return_url'] = $this->getReturnUrl($orderId);
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['sign'] = $this->sign($params);



		$this->CI->utils->debug_log("=====================tubeipay generatePaymentUrlForm", $params);

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
        $this->CI->utils->debug_log('=====================tubeipay processPaymentUrlFormQRcode scan url',$url);
        $response = $this->submitPostForm($url, $params, false, $params['out_trade_no']);
        $this->CI->utils->debug_log('========================================tubeipay processPaymentUrlFormQRcode received response', $response);

        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================tubeipay processPaymentUrlFormQRcode response[1] json to array', $decode_data);
        $msg = lang('Invalidte API response');

    	if($decode_data['code'] == self::RESULT_CODE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decode_data['qr_code'],
            );
        }else {
            if(!empty($decode_data['message'])) {
                $msg = $decode_data['code'].": ".$decode_data['message'];
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
        $this->CI->utils->debug_log('=======================tubeipay callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================tubeipay callbackFromServer server callbackFrom', $params);
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
                $params['out_trade_no'], 'Third Party Payment (No Bank Order Number)', # no info available
                null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS;
		} else {
			$result['return_error'] = $processed ? self::RETURN_SUCCESS : self::RETURN_FAILED;
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
		$requiredFields = array('mch_id', 'out_trade_no', 'total_fee');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================tubeipay missing parameter: [$f]", $fields);
				return false;
			}
		}

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=======================tubeipay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass


		if ($fields['total_fee'] != $this->convertAmountToCurrency($order->amount)) {
			$this->writePaymentErrorLog("=====================tubeipay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['out_trade_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================tubeipay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
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
		return number_format($amount * 100, 2, '.', '');
	}


    # -- signatures --
	private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if(empty($value) || $key == 'key') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return $signStr.'key='.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if( ($key == 'sign') || (empty($value)) ) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        $sign = strtoupper(md5($signStr));
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }
}
