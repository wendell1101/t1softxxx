<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ADPAY 安達支付
 *
 * * ADPAY_ALIPAY_PAYMENT_API, ID: 5575
 * * ADPAY_ALIPAY_H5_PAYMENT_API, ID: 5580
 * * ADPAY_WEIXIN_PAYMENT_API, ID: 5603
 * * ADPAY_WEIXIN_H5_PAYMENT_API, ID: 5604
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_adpay extends Abstract_payment_api {
    const GATEWAY_ALIPAY = 'alipay';
    const GATEWAY_WEIXIN = 'weixin';
    const GATEWAY_MOBILE_ALIPAY = 'mobile_alipay';    
    const GATEWAY_MOBILE_WEIXIN = 'mobile_weixin';
    const SUCCESS_METHOD_POST = "post";
    const SUCCESS_METHOD_QRCODE = "qrcode";
    const SUCCESS_METHOD_GET = "get";

	const QRCODE_REPONSE_CODE_SUCCESS = '000000';
	const ORDER_STATUS_SUCCESS = '2';
	const RETURN_SUCCESS_CODE = 'OK';
	const RETURN_FAILED_CODE = 'faile';


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
		$params['order_no'] = $order->secure_id;
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['return_url'] = $this->getReturnUrl($orderId);
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['ip'] = $this->getClientIp();
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================adpay generatePaymentUrlForm", $params);
		return $this->processPaymentUrlForm($params);
	}


	# Display QRCode get from curl
	protected function processPaymentUrlFormQRCode($params) {

		$originResponse = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['order_no']);
		$response = json_decode($originResponse, true);

		$msg = lang('Invalidate API response');

		if($response['ok'] == true) {
		    if($response['data']['method'] == self::SUCCESS_METHOD_POST) {
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_FORM,
                    'url' => $response['data']['url'],
                    'params' => $response['data']['form'],
                    'post' => true,
                );
            }
            if($response['data']['method'] == self::SUCCESS_METHOD_QRCODE) {
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_QRCODE,
                    'url' => $response['data']['url']
                );
            }
            if($response['data']['method'] == self::SUCCESS_METHOD_GET) {
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['data']['url']
                );
            }
		}
		else {
			if($response['error']) {
				$msg = $response['error'];
			}

			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $msg
			);
		}
	}

	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;
		$decodeData = $this->decrypt($params['content']);
		$params = json_decode($decodeData,true);

		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['tx_no'], null, null, null, $response_result_id);
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
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'order_no', 'tx_no', 'amount', 'actual_amount', 'status', 'extra'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================adpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # Have confirmed with client, this payment do not provide signatur in callback and do not need to verify signature
        # https://talk.chatchat365.com/smartbackend/pl/kamxdadrfjn9bpfeuuw78y19uw

        $processed = true; # processed is set to true once the signature verification pass


        $check_amount = $this->convertAmountToCurrency($order->amount);
        if ($fields['amount'] != $check_amount) {
            $this->writePaymentErrorLog("======================adpay checkCallbackOrder Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        }

        if ($fields['order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================adpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}


	# -- Private functions --
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- private helper functions --
	public function sign($params)
	{
        ksort($params);
        $string = json_encode($params,JSON_UNESCAPED_SLASHES);
        $localIV = $this->getSystemInfo('vector');
        $encryptKey = $this->getSystemInfo('key');

        $block = 16;
        $pad = $block - (strlen($string) % $block);
        $string .= str_repeat(chr($pad), $pad);
	    $encrypted = openssl_encrypt($string, 'AES-256-CBC', $encryptKey, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $localIV);

	    $sign = base64_encode($encrypted);
        return $sign;
	}

    public function decrypt($encryptedData)
    {
        $localIV = $this->getSystemInfo('vector');
        $encryptKey = $this->getSystemInfo('key');
	    $decodeData = openssl_decrypt(base64_decode($encryptedData), 'AES-256-CBC', $encryptKey, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $localIV);
	    $unPaddingData = $this->unPaddingPKCS7($decodeData);
        return $unPaddingData;
    }

    private function unPaddingPKCS7($text)
    {
		$pad = ord($text[strlen($text) - 1]);
		if ($pad > strlen($text)) {
			return false;
		}
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
			return false;
		}
		return substr($text, 0, - 1 * $pad);
    }
}
