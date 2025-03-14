<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * FNZHIFU  蜂鸟
 *
 * * 'FNZHIFU_WEIXIN_PAYMENT_API', ID 5293
 * * 'FNZHIFU_WEIXIN_H5_PAYMENT_API', ID 5294
 *
 * Required Fields:
 * * Account
 * * Extra Info
 *
 *
 * Field Values:
 * * URL: https://api.fnzhifu.com/orderSubmit
 * * Extra Info:
 * * {
 * *    "fnzhifu_server_pub_key":
 * * }
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_fnzhifu extends Abstract_payment_api {
    const PAYTYPE_WEIXIN = '2';

    const RETURN_SUCCESS_CODE = '0000';
	const RETURN_SUCCESS = 'SUCCESS';
	const RETURN_FAILED = 'FAIL';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'fnzhifu_server_pub_key');
        return $secretsInfo;
    }

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
		$this->configParams($params, $order->direct_pay_extra_info);

		$params['orderNo'] = $order->secure_id;
		$params['notifyUrl'] = $this->getNotifyUrl($orderId);
		$params['amount'] = $this->convertAmountToCurrency($amount);//單位:分
		$params['payType'] = self::PAYTYPE_WEIXIN;
		$params['buyerId'] = $this->getSystemInfo("account");
		$params['timestamp'] = $this->getMillisecond();
		$params['appId'] = $this->getSystemInfo("account");
		$this->CI->utils->debug_log("=====================fnzhifu generatePaymentUrlForm notifyUrl", $params['notifyUrl']);

		return $this->processPaymentUrlForm($params);
	}


	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
        $encryption = array(
            'orderNo' => $params['orderNo'],
            'notifyUrl' => $params['notifyUrl'],
            'amount' => $params['amount'],
            'payType' => $params['payType'],
            'buyerId' => $params['buyerId'],
            'timestamp' => $params['timestamp']
        );
		$sendparams['appId'] = $params['appId'];
        $sendparams['encryption'] = $this->encrypt($encryption);
		$this->CI->utils->debug_log("=====================fnzhifu generatePaymentUrlForm", $sendparams);

        $response = $this->submitPostForm($this->getSystemInfo('url'), $sendparams, false, $params['orderNo']);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================fnzhifu processPaymentUrlFormPost response json to array', $response);

        $msg = lang('Invalidate API response');
    	if(!empty($response['data']) && ($response['code'] == self::RETURN_SUCCESS_CODE)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data'],
            );
        }else {
            if(!empty($response['message'])) {
                $msg = $response['message'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
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
            $this->CI->utils->debug_log('=======================fnzhifu callbackFromServer server callbackFrom', $params);
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
                $params['orderNo'], 'Third Party Payment (No Bank Order Number)', # no info available
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
			$result['message'] = $processed ? self::RETURN_SUCCESS : self::RETURN_FAILED;
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

		$requiredFields = array('order_no','trade_no','amount','trade_status');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================fnzhifu missing parameter: [$f]", $fields);
				return false;
			}
		}
        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=======================fnzhifu checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['trade_status'] != self::RETURN_SUCCESS) {
			$payStatus = $fields['trade_status'];
			$this->writePaymentErrorLog("=====================hbepay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
			$this->writePaymentErrorLog("=====================hbepay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================hbepay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}


	# -- notifyUrl --
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}


	# -- returnUrl --
	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}



	# -- amount --
	protected function convertAmountToCurrency($amount) {
		return number_format($amount*100, 0, '.', '');
	}

    # -- timestamp --
	private function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    # -- 公鑰 RSA加密 --
    public function encrypt($params){
        ksort($params);

        $signStr = '';
        foreach ($params as $key => $value) {
            $signStr .= $key."=".$value."&";
        }
        $signStr = rtrim($signStr, '&');
        $split = str_split($signStr, 64);

        $pubkey = openssl_get_publickey($this->getServerPubKeyStr());
        $encParam_encrypted = '';
		foreach($split as $part) {
			openssl_public_encrypt($part,$partialData,$pubkey);
			$t = strlen($partialData);
			$encParam_encrypted .= $partialData;
        }

		$encrpt = base64_encode(($encParam_encrypted));
		return $encrpt;
    }

    # -- validateSign --
    public function validateSign($params) {
        $keys = array(
            'order_no'     => $params['order_no'],
            'trade_no'     => $params['trade_no'],
            'amount'       => $params['amount'],
            'trade_status' => $params['trade_status']
        );
        ksort($keys);

        $strData = '';
        foreach ($keys as $key => $value) {
            $strData .= $key."=".$value."&";
        }
        $strData = rtrim($strData, '&');

		$base64Signature = base64_decode($params['sign']);
        $pubkey = openssl_get_publickey($this->getServerPubKeyStr());

        if (!openssl_verify($strData, $base64Signature, $pubkey, OPENSSL_ALGO_SHA256)) {
            return false;
        }
        return true;
	}

    # -- public key --
    public function getServerPubKeyStr() {
        $fnzhifu_pub_key = $this->getSystemInfo('fnzhifu_server_pub_key');
        $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($fnzhifu_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return $pub_key;
    }
}
