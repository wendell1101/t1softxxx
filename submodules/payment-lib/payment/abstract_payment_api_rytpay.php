<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * Payment API implementation rytpay
 */
abstract class Abstract_payment_api_rytpay extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const RETURN_FAILED_CODE = 'FAILED';
	private $info;
	public function __construct($params = null) {
		parent::__construct($params);
		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->info = $this->getInfoByEnv();
	}

	protected abstract function configParams(&$params, $direct_pay_extra_info);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'rytpay_priv_key');
        return $secretsInfo;
    }

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        // For second url redirection
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$direct_pay_extra_info = $order->direct_pay_extra_info;
        $sysinfo = $this->getAllSystemInfo();

		# read some parameters from config
		$params['orgId'] = $this->getSystemInfo("orgId");
		$params['source'] = '0';
		$params['settleAmt'] = '0';
		$params['account'] = $this->getSystemInfo("account");
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['notifyUrl'] = $this->getNotifyUrl($orderId);
		$params['tranTp'] = "0";
		$params['orgOrderNo'] = $order->secure_id;

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['signature'] = $this->sign($params);

		$this->CI->utils->debug_log('=========================rytpay generatePaymentUrlForm', $params);

		return $this->processPaymentUrlFormQRCode($params);
	}

	# Display QRCode get from curl
	protected function processPaymentUrlFormQRCode($params) {
		# CURL post the data to Dinpay

		$curlConn = curl_init($this->getSystemInfo('url'));
		curl_setopt($curlConn, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curlConn, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curlConn, CURLOPT_POSTFIELDS, $this->CI->utils->encodeJson($params));
		curl_setopt($curlConn, CURLOPT_HEADER, true);
		curl_setopt($curlConn, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

		# Need to specify the referer when doing CURL submit. since we use redirect 2nd url, we can take the HTTP_HOST
		curl_setopt($curlConn, CURLOPT_REFERER, "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

		$curlResult = curl_exec($curlConn);
		$curlSuccess = (curl_errno($curlConn) == 0);

		$header_size = curl_getinfo($curlConn, CURLINFO_HEADER_SIZE);
		$header = substr($curlResult, 0, $header_size);
		$curlResultContent = substr($curlResult, $header_size);

		$curlResultContentDecode = json_decode($curlResultContent, true);

		$this->CI->utils->debug_log('=====================rytpay curlSuccess', $curlSuccess, $curlResultContentDecode);

		$errorMsg = "Payment failed";

		if(!$curlSuccess ) {
			$errorMsg = curl_error($curlConn);
		}

		curl_close($curlConn);

		if($curlSuccess && $curlResultContentDecode['respCode'] == '200') {
			$qrCodeUrl = $curlResultContentDecode['qrcode'];
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $qrCodeUrl,
			);
		} else {
			$errorMsg = $curlResultContentDecode['respMsg'];

			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $errorMsg
			);
		}
	}

	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	## Reference: sample code, callback.php
	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}
	## This will be called when user redirects back to our page from payment API
	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}
	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$postdata = file_get_contents("php://input");
		$params = $this->CI->utils->decodeJson($postdata);

		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;
		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			return $result;
		}
		# Update order payment status and balance
		$this->CI->sale_order->startTrans();
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
				$params['accNo'], '', # only platform order id exist. Reference: documentation section 2.4.2
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}
		$success = $this->CI->sale_order->endTransWithSucc();
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
			'amount', 'orderDt', 'orderNo', 'orgOrderNo', 'orgId',
			'paySt', 'fee', 'signature', 'account', 'respCode'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("========================rytpay missing parameter: [$f]", $fields);
				return false;
			}
		}
		# is signature authentic?
		/*if (!$this->verify($fields, $fields['signMsg'])) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}*/
		$processed = true; # processed is set to true once the signature verification pass
		# check parameter values: orderStatus, tradeAmt, orderNo, merchNo
		# is payment successful?
		if ($fields['paySt'] !== '2') {
			$this->writePaymentErrorLog('========================rytpay payment was not successful', $fields);
			return false;
		}
		# does amount match?
		if (
			$this->convertAmountToCurrency($order->amount) != $fields['amount']
		) {
			$this->writePaymentErrorLog("========================rytpay payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}
		# does merchNo match?
		if ($fields['account'] !== $this->getSystemInfo('account')) {
			$this->writePaymentErrorLog("========================rytpay merchant codes do not match, expected [" . $this->getSystemInfo('account') . "]", $fields);
			return false;
		}
		# does order_no match?
		if ($fields['orgOrderNo'] !== $order->secure_id) {
			$this->writePaymentErrorLog("========================rytpay order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}
		# everything checked ok
		return true;
	}
	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}
	## Format the amount value for the API
	protected function convertAmountToCurrency($amount) {
		return $amount * 100;
	}
	# -- private helper functions --
	/**
	 * @name	生成签名
	 * @param	sourceData
	 * @return	签名数据
	 */
	# -- signing --
	private function sign($params) {
		$signStr = $this->createSignStr($params);

		openssl_sign($signStr, $sign_info, $this->getPrivKey(), OPENSSL_ALGO_MD5);
		$sign = base64_encode($sign_info);
		return $sign;
	}

	private function createSignStr($params) {
		ksort($params);
		$signStr = '';
		foreach($params as $key => $value) {
			if($key == 'signature') {
				continue;
			}
			$signStr .= "$key=$value&";
		}
		return rtrim($signStr, '&');
	}

	/*
		 * @name	验证签名
		 * @param	data 原数据
		 * @param	signature 签名数据
		 * @return
	*/
	private function verify($data, $signature) {
		$mySign = $this->sign($data);
		if (strcasecmp($mySign, $signature) === 0) {
			return true;
		} else {
			return false;
		}
	}

	# Returns the private key generated by merchant
	private function getPrivKey() {
		$rytpay_priv_key = $this->getSystemInfo('rytpay_priv_key');

		$priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($rytpay_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
		return openssl_get_privatekey($priv_key);
	}

}