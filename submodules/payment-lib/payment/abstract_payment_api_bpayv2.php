<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * *  bpayv2
 * *
 * * 'BPAYV2_PAYMENT_API', ID 6150
 * * 'BPAYV2_WITHDRAWAL_PAYMENT_API', ID: 6151
 * *
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
abstract class Abstract_payment_api_bpayv2 extends Abstract_payment_api {
	const CHANNEL_CODE_PIX = '900410282001';
	const CHANNEL_CODE_PIX_WITHDRAWAL = '900410285001';

    const REQUEST_SUCCESS = '200';
	const CALLBACK_SUCCESS = 'SUCCESS';
	const RETURN_SUCCESS_CODE = 'SUCCESS';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
        $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName'] : 'none';
        $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName'] : 'none';
        $phone      = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : 'none';
        $email      = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email'] : 'none';
        $pixNumber  = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))    ? $playerDetails[0]['pix_number'] : 'none';

		$params['merchantNo'] 	 	 = $this->getSystemInfo("account");
		$params['merchantOrderNo'] 	 = $order->secure_id;
		$params['countryCode'] 	 	 = $this->getSystemInfo("countryCode");
		$params['currencyCode'] 	 = $this->getSystemInfo("currencyCode");
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['paymentAmount'] 	 = $this->convertAmountToCurrency($amount); //元
		$params['goods'] 			 = 'deposit';
		$params['extendedParams']    = 'payerFirstName^'.$firstname.'|'.'payerLastName^'.$lastname.'|'.'payerEmail^'.$email.'|'.'payerPhone^'.$phone.'|'.'payerCPF^'.$pixNumber;
		$params['pageUrl'] 		 	 = $this->getReturnUrl($orderId);
		$params['notifyUrl'] 		 = $this->getNotifyUrl($orderId);
		$params['sign'] 			 = $this->sign($params);

		$this->CI->utils->debug_log("=====================bpayv2 generatePaymentUrlForm", $params);
		return $this->processPaymentUrlForm($params);
	}

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
    	$url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, true, $params['merchantOrderNo']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log("=====================bpayv2 response", $response);
        $this->CI->utils->debug_log("=====================bpayv2 decode_data", $decode_data);

        $msg = lang('Invalidate API response');
    	if(!empty($decode_data['data']['paymentUrl']) && ($decode_data['code'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decode_data['data']['paymentUrl'],
            );
        }else {
            if(!empty($decode_data['message'])) {
                $msg = $decode_data['message'];
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
            $this->CI->utils->debug_log('=======================bpayv2 callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['merchantOrderNo'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				if (!empty($params)){
                    if ($params['paymentStatus'] == self::CALLBACK_SUCCESS) {
                        $this->CI->sale_order->updateExternalInfo($order->id, $params['merchantOrderNo'], '', null, null, $response_result_id);
                        $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                    } else {
                        $this->CI->utils->debug_log("=====================checkCallbackOrder Payment status is not success", $params);
                    }
                }
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
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

		$requiredFields = array('orderNo','orderAmount', 'paymentStatus', 'merchantOrderNo','sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================bpayv2 missing parameter: [$f]", $fields);
				return false;
			}
		}

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================bpayv2 checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass
		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['orderAmount'] )
		) {
			$this->writePaymentErrorLog("=====================bpayv2 Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['merchantOrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================bpayv2 checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		# everything checked ok
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
		$merchant_private_key = $this->getPrivKey();
		$signStr = $this->createSignStr($params);
		openssl_sign($signStr, $sign_info, $merchant_private_key, OPENSSL_ALGO_MD5);
		$sign = base64_encode($sign_info);
		return $sign;
	}

    public function verifySignature($data) {
	    $callback_sign = $data['sign'];
	    $pay_public_key = $this->getPubKey();
        $signStr =  $this->createSignStr($data);
		$flag = openssl_verify($signStr, base64_decode($callback_sign), $pay_public_key, OPENSSL_ALGO_MD5);
        return $flag;
    }

    public function createSignStr($params) {
    	ksort($params);
       	$signStr='';
		foreach ($params as $key => $value) {
			if($key == 'sign'){
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		return rtrim($signStr, '&');
	}

	public function getPubKey() {
        $yingsheng_pub_key = $this->getSystemInfo('bpayv2_pub_key');
        $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($yingsheng_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($pub_key);
    }

    public function getPrivKey() {
        $yingsheng_priv_key = $this->getSystemInfo('bpayv2_priv_key');
        $priv_key = '-----BEGIN PRIVATE KEY-----' . PHP_EOL . chunk_split($yingsheng_priv_key, 64, PHP_EOL) . '-----END PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($priv_key);
    }

}
