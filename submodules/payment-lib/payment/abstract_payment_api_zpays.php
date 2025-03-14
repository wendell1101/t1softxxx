<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * zpays
 *
 * * 'ZPAYS_PAYMENT_API', ID 6199
 * *
 * Required Fields:
 *
 * * URL https://api.zm-pay.com
 * * Account - Merchant ID
 * * Key - Secret key
 * * Extra Info
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_zpays extends Abstract_payment_api {

	const CURRENCY_USDT = 'usdt';
   	const CHANNEL_TYPE_PIXPAY = 8008;
   	const CHANNEL_TYPE_ASPIX  = 8010;
	const CHANNEL_TYPE_USDT  = 8001;
	const RETURN_SUCCESS_CODE = "SUCCESS";
	const CALLBACK_SUCCESS_CODE = 2;
	const CALLBACK_STATUS_FAILED   = -2;
    const CALLBACK_RETURN_SUCCESS = 'success';
    const RETURN_FAILED = 'ERROR';

	public function __construct($params = null) {
        parent::__construct($params);
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
		$params['mchId']      = $this->getSystemInfo("account");
		$params['appId']      = $this->getSystemInfo("appId",'61d0f05c841f4ddfbe70b00f6409f890');
        $params['mchOrderNo'] = $order->secure_id;
        $params['currency']   = $this->getSystemInfo("currency", 'BRL');
        $params['amount'] 	  = (int)$this->convertAmountToCurrency($amount);
		$this->configParams($params, $order->direct_pay_extra_info);
        $params['notifyUrl']  = $this->getNotifyUrl($orderId);
        $params['subject']    = $this->getSystemInfo("subject", 'payment');
        $params['body'] 	  = $this->getSystemInfo("body", 'deposit');
        $params['sign'] 	  = $this->sign($params);
		$this->CI->utils->debug_log("=====================zpays generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
    }

	# Submit URL form
	protected function processPaymentUrlFormURL($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['mchOrderNo']);
        $decode_data = json_decode($response, true);
        $this->CI->utils->debug_log('=====================zpays processPaymentUrlFormURL response json to array', $decode_data);
		$msg = lang('Invalidate API response');


		if(isset($decode_data['retCode']) && ($decode_data['retCode'] == self::RETURN_SUCCESS_CODE)) {
			$url = $decode_data['redirect'];
			return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $url,
        	);
        }else {
			$msg = empty($decode_data['retMsg']) ? $msg: $decode_data['retMsg'];
            if( $decode_data['retCode'] != self::RETURN_SUCCESS_CODE && !empty($decode_data['retMsg'])) {
                $msg = $decode_data['retMsg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

    # Display QRCode get from curl
	protected function processPaymentUrlFormQRCode($params) {
		$response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['mchOrderNo']);
        $decode_data = json_decode($response, true);
        $this->CI->utils->debug_log('=====================zpays processPaymentUrlFormURL response json to array', $decode_data);
		$msg = lang('Invalidate API response');

		if(isset($decode_data['retCode']) && ($decode_data['retCode'] == self::RETURN_SUCCESS_CODE)) {
			$qr_code = $decode_data['encodedImage'];
			if(isset($decode_data['pixPayCode']) && !empty($decode_data['pixPayCode'])){
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_QRCODE,
					'qrcode_img_copy_text' => $decode_data['pixPayCode'],
					'image_url' => $qr_code,
				);
			}else{
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_QRCODE,
					'base64' => $qr_code
				);
			}
        }else {
			$msg = empty($decode_data['retMsg']) ? $msg: $decode_data['retMsg'];
            if( $decode_data['retCode'] != self::RETURN_SUCCESS_CODE && !empty($decode_data['retMsg'])) {
                $msg = $decode_data['retMsg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
	}

	protected function processPaymentUrlFormForRedirect($params) {

		$response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['mchOrderNo']);
        $decode_data = json_decode($response, true);
        $this->CI->utils->debug_log('=====================zpays processPaymentUrlFormForRedirect response json to array', $decode_data);
		$msg = lang('Invalidate API response');

		$isCrypto = ($this->getSystemInfo("currency") == self::CURRENCY_USDT);

		if ($isCrypto) {
			$cryptoAmount = $params['amount'] / 100;
			list($crypto, $cryptoRate) = $this->CI->utils->convertCryptoCurrency($cryptoAmount, 'USDT', 'USDT', 'deposit');
        	$this->CI->utils->debug_log('=====================zpays crypto', $crypto);
		}

		if(isset($decode_data['retCode']) && ($decode_data['retCode'] == self::RETURN_SUCCESS_CODE) && !empty($decode_data['channelMchId'])) {

			if ($isCrypto) {
				$order = $this->CI->sale_order->getSaleOrderBySecureId($params['mchOrderNo']);
           		 $this->CI->sale_order->createCryptoDepositOrder($order->id, $cryptoAmount , $cryptoRate, null, null, 'USDT');
            	$deposit_notes = 'cryptoRate'. $cryptoRate .'USDTcoin: ' . $cryptoAmount;
            	$this->CI->sale_order->appendNotes($order->id, $deposit_notes);
			}

			$data = array();
			$data['Payment Order ID'] = $decode_data['payOrderId'];
        	$data['Network']          = $decode_data['passageName'];
        	$data['Wallet Address']   = $decode_data['channelMchId'];
			$data['Amount']   = $decode_data['amount'];

       		$this->CI->utils->debug_log("=====================zpays processPaymentUrlFormForRedirect data", $data);

        	$collection_text_transfer = '';
       	 	$collection_text = $this->getSystemInfo("collection_text_transfer", array(''));
       		if(is_array($collection_text)){
          	  $collection_text_transfer = $collection_text;
       		}
        	$is_not_display_recharge_instructions = $this->getSystemInfo('is_not_display_recharge_instructions');

        	return array(
           		'success' => true,
            	'type' => self::REDIRECT_TYPE_STATIC,
            	'data' => $data,
            	'collection_text_transfer' => $collection_text_transfer,
            	'is_not_display_recharge_instructions' => $is_not_display_recharge_instructions
        	);

        }else {
			$msg = empty($decode_data['retMsg']) ? $msg: $decode_data['retMsg'];
            if( $decode_data['retCode'] != self::RETURN_SUCCESS_CODE && !empty($decode_data['retMsg'])) {
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

        if(empty($params)){
			$raw_post_data = file_get_contents('php://input', 'r');
			$params = json_decode($raw_post_data, true);
        }
        if($source == 'server'){
            $this->CI->utils->debug_log('=======================zpays callbackFromServer server callbackFrom', $params);
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

		if ($this->getSystemInfo("allow_auto_decline") == true && $params['status'] == self::CALLBACK_STATUS_FAILED) {
			$this->CI->sale_order->setStatusToDeclined($orderId);
			$this->writePaymentErrorLog("=====================zpays callbackFromServer status is failed. set to decline", $params);
            $this->CI->utils->debug_log("=========================zpays callbackFromServer status is failed. set to decline");
			$result['return_error_msg'] = self::CALLBACK_RETURN_SUCCESS;
			return $result;
        } 

		if ($params['status'] != self::CALLBACK_SUCCESS_CODE) {
			$payStatus = $params['status'];
			$this->writePaymentErrorLog("=====================zpays checkCallbackOrder Payment status was not successful, status is [$payStatus]", $params);
			return $result;
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
			$this->CI->sale_order->updateExternalInfo($order->id,'', '', null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::CALLBACK_RETURN_SUCCESS;
		} else {
			$result['return_error'] = $processed ? self::CALLBACK_RETURN_SUCCESS : self::RETURN_FAILED;
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
		$requiredFields = array('mchOrderNo','amount','sign','status');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================zpays checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================zpays checkCallbackOrder signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		// if ($fields['status'] != self::CALLBACK_SUCCESS_CODE) {
		// 	$payStatus = $fields['status'];
		// 	$this->writePaymentErrorLog("=====================zpays checkCallbackOrder Payment status was not successful, status is [$payStatus]", $fields);
		// 	return false;
		// }

		if ($this->getSystemInfo("currency") == self::CURRENCY_USDT) {
		 	//USDT
			$crypto_amount = $this->convertAmountToCrypto($order->id);
			if ($crypto_amount != ($fields['amount'] / 100)) {
				$this->writePaymentErrorLog("=====================zpays_usdt Payment amounts do not match, expected ", $crypto_amount);
				return false;
			}

		} else {
			//PIX
			if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
				if ($this->getSystemInfo('allow_callback_amount_diff')) {
					$diffAmount = abs($this->convertAmountToCurrency($order->amount) - floatval($fields['amount']));
					if ($diffAmount >= 1) {
						$this->writePaymentErrorLog("=====================zpays checkCallbackOrder Payment amounts ordAmt - payAmount > 1, expected [$order->amount]", $fields, $diffAmount);
						return false;
					}
				}else {
					$this->writePaymentErrorLog("=====================zpays checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields);
					return false;
				}
			}
		}

        if ($fields['mchOrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================zpays checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
		}

		# everything checked ok
		return true;
	}

	protected function convertAmountToCrypto($orderId) {
        $cryptoOrder = $this->CI->sale_order->getCryptoDepositOrderBySaleOrderId($orderId);
        $cryptoAmount = $cryptoOrder->received_crypto;
        $this->CI->utils->debug_log("=======================zpays_usdt convertAmountToCrypto,orderId",$cryptoAmount,$orderId);
        return number_format($cryptoAmount, 2, '.', '');
    }

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
    }

	protected function convertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 100);
        return number_format($amount * $convert_multiplier, 0, '.', '') ;
    }

	# -- notifyURL --
	public function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

    # -- returnURL --
	public function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

    public function sign($params) {
		$signStr = $this->createSignStr($params);
		$sign = strtoupper(md5($signStr));
		return $sign;
	}

    public function createSignStr($params) {
		$signStr = '';
		ksort($params);
        foreach($params as $key => $value) {
			if( ($key == 'sign') || empty($value)) {
				continue;
			}
			$signStr.=$key."=".$value."&";
		}
		$signStr .= "key=".$this->getSystemInfo('key');
		return $signStr;
    }

    public function validateSign($params) {
		$signStr = $this->createSignStr($params);
		$sign = strtoupper(md5($signStr));
		if($params['sign'] == $sign){
			return true;
		}
		else{
			return false;
		}
	}
}