<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * payplus
 *
 * * 'PAYPLUS_PAYMENT_API', ID 6033
 * * 'PAYPLUS_WITHDRAWAL_PAYMENT_API', ID 6034
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
abstract class Abstract_payment_api_payplus extends Abstract_payment_api {

    const PAYWAY_BANK	   = 'IND0';
	const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '1';
    const ORDER_STATUS_SUCCESS = '1';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function __construct($params = null) {
		parent::__construct($params);
		$this->_custom_curl_header = array('Content-Type:application/json');
	}

	public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'payplus_pub_key', 'payplus_priv_key');
        return $secretsInfo;
    }

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$this->CI->load->model('player');
		$params['merId'] = $this->getSystemInfo("account");
		$params['orderId'] = $order->secure_id;
		$params['orderAmt'] = $this->convertAmountToCurrency($amount); //元
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['desc'] = 'deposit';
		$params['ip'] = $this->getClientIP();
		$params['notifyUrl'] = $this->getNotifyUrl($orderId);
		$params['returnUrl'] = $this->getReturnUrl($orderId);
		$params['nonceStr'] = $this->getNonce();
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================payplus generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['orderId']);
        $this->CI->utils->debug_log('=====================payplus processPaymentUrlFormURL received response', $response);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('=====================payplus processPaymentUrlFormURL json to array', $response);

        $msg = lang('Invalidte API response');

		if(isset($response['code']) && ($response['code'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data']['payurl']
            );
        }
        else {
            if(isset($response['msg']) && !empty($response['msg'])) {
                $msg = $response['msg'];
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

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================payplus callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderId'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				if($params['status'] == self::ORDER_STATUS_SUCCESS){
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
			}
		}

		$result['success'] = $success;
		$result['message'] = self::RETURN_SUCCESS_CODE;

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields, &$processed = false) {

		$requiredFields = array('orderId','sysOrderId','orderAmt', 'status','sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================payplus missing parameter: [$f]", $fields);
				return false;
			}
		}
		 # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================payplus checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		if ($this->convertAmountToCurrency($order->amount) != $fields['orderAmt']) {
			$this->writePaymentErrorLog("=====================payplus Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['orderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================payplus checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

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

	# -- signing --
    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        $signature = false;
        openssl_sign($sign, $signature, $this->getPrivKey(), OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    public function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign' || empty($value)) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return $signStr."key=".$this->getSystemInfo('key');
    }

    public function verifySignature($params) {
        $signStr =  $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        $flg = openssl_verify($sign, base64_decode($params['sign']), $this->getPubKey(), OPENSSL_ALGO_SHA256);
        return $flg;
    }

    public function getNonce($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }

        return $str;
    }

    # Returns public key given by gateway
	public function getPubKey() {
		$payplus_pub_key = $this->getSystemInfo('payplus_pub_key');
		$pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($payplus_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
		return openssl_get_publickey($pub_key);
	}

	# Returns the private key generated by merchant
	public function getPrivKey() {
		$payplus_priv_key = $this->getSystemInfo('payplus_priv_key');
		$priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($payplus_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
		return openssl_get_privatekey($priv_key);
	}

}
