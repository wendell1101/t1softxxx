<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * DADA 达达
 *
 * * 'DADA_QUICKPAY_PAYMENT_API', ID 5347
 * * 'DADA_ALIPAY_PAYMENT_API', ID 5350
 * * 'DADA_ALIPAY_H5_PAYMENT_API', ID 5351
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
abstract class Abstract_payment_api_dada extends Abstract_payment_api {

	const PAYTYPE_ALIPAY = 'XINSHIJI_SCANPAY_ALIPAY';
	const PAYTYPE_QUICKPAY = 'YUNSHANFU_SM';


	const RETURN_SUCCESS_CODE = '0';
	const RETURN_SUCCESS = 'SUCCESS';
	public function __construct($params = null) {
		parent::__construct($params);
		$this->_custom_curl_header = ["Content-Type: application/json"];
	}

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
		$params['businessId'] = (int)$this->getSystemInfo("account");
		$params['money'] = (int)$this->convertAmountToCurrency($amount); //分
		$params['orderNo'] = $order->secure_id;
		$this->configParams($params, $order->direct_pay_extra_info); //$params['method']
		$params['notifyUrl'] = $this->getNotifyUrl($orderId);
        $params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================dada generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}


	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
    	$url = $this->getSystemInfo('url').$this->getSystemInfo('qucode_url');
		$response = $this->submitPostForm($url, $params, true, $params['orderNo']);
		$decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================dada processPaymentUrlFormQRcode response json to array', $decode_data);
		$msg = lang('Invalidate API response');

		if(isset($decode_data['code'])){
			if($decode_data['code'] == self::RETURN_SUCCESS_CODE) {
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_URL,
					'url' => $decode_data['data']['qrcodeUrl'],
				);
			}elseif($decode_data['code'] != self::RETURN_SUCCESS_CODE){
				return array(
					'success' => false,
					'type' => self::REDIRECT_TYPE_ERROR,
					'message' => $decode_data['code'].":".$decode_data['msg']
				);
			}
		}
		else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }


    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
    	$url = $this->getSystemInfo('url').$this->getSystemInfo('qucode_url');
		$response = $this->submitPostForm($url, $params, true, $params['orderNo']);
		$decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================dada processPaymentUrlFormQRcode response json to array', $decode_data);
		$msg = lang('Invalidate API response');

		if(isset($decode_data['code'])){
			if($decode_data['code'] == self::RETURN_SUCCESS_CODE) {
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_QRCODE,
					'url' => $decode_data['data']['qrcodeUrl'],
				);
			}elseif($decode_data['code'] != self::RETURN_SUCCESS_CODE){
				return array(
					'success' => false,
					'type' => self::REDIRECT_TYPE_ERROR,
					'message' => $decode_data['code'].":".$decode_data['msg']
				);

			}
		}
		else {
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
            $this->CI->utils->debug_log('=======================dada callbackFromServer server callbackFrom', $params);
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
				'', '', # no info available
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
			$result['message'] = $processed ? self::RETURN_SUCCESS : 'failed';
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

		$requiredFields = array('businessOrderNo','money','orderStatus');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================dada missing parameter: [$f]", $fields);
				return false;
			}
		}
		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================dada checkCallbackOrder Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass


		if ($this->convertAmountToCurrency($order->amount) != $fields['money']) {
			$this->writePaymentErrorLog("=====================dada Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
        }

		if ($fields['orderStatus'] != self::RETURN_SUCCESS) {
			$payStatus = $fields['orderStatus'];
			$this->writePaymentErrorLog("=====================dada Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

        if ($fields['businessOrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================dada checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}


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
		return number_format($amount *100 , 0, '.', '');
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

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
				if(empty($value) || $key == 'sign') {
                continue;
            }
			$signStr .= $value;
        }
        $signStr .= $this->getSystemInfo('key');

        return $signStr;
    }

	private function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
		if($params['sign'] == $sign){
			return true;
		}
		else{
			return false;
		}
	}
}


