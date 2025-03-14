<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * 新乐付 LEPAYLE
 * https://cms.lepayle.com/
 *
 * * LEPAYLE_WEIXIN_PAYMENT_API, ID: 254
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

abstract class Abstract_payment_api_lepayle extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const TRADE_STATUS_SUCCESS = 1;
	const RESP_CODE_SUCCESS = 'SUCCESS';
	const QRCODE_RESULT_CODE_SUCCESS = 0;

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these to specify pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'lepayle_pub_key', 'lepayle_priv_key');
        return $secretsInfo;
    }

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$paramsBasic = array();
		$params = array();

		$paramsBasic['input_charset'] = 'UTF-8';
		$paramsBasic['partner'] = $this->getSystemInfo("account");

		$params['service'] = 'wx_pay';
		$params['sign_type'] = 'SHA1WITHRSA';
		$params['return_url'] = $this->getNotifyUrl($orderId);
		$params['redirect_url'] = $this->getReturnUrl($orderId);
		$params['request_time'] = date('YmdHis'); # 时间格式：yyyy-MM-dd HH:mm:ss
		$params['out_trade_no'] = $order->secure_id;
		$params['amount_str'] = $this->convertAmountToCurrency($amount);
		$params['wx_pay_type'] = 'wx_sm';
		$params['subject'] = 'Deposit';
		$params['sub_body'] = 'Deposit';

		$this->configParams($params, $order->direct_pay_extra_info);

		$params = array_merge($params,$paramsBasic);

        $paramStr = $this->arrayToUrl($params);

        $this->CI->utils->debug_log('=========================lepayle paramStr before sign and encrypt', $paramStr);

        $postData = [];

        if($params['service'] == 'gateway_pay') {
			$params['sign_type'] = 'MD5';
			$params['sign'] = $this->signMd5($params);

        	return $this->processPaymentUrlForm($params);
        }
        else {
        if(($content = $this->encrypt($paramStr)) && ($sign = $this->sign($paramStr))) {
            $postData = [
                'sign_type'     => 'SHA1WITHRSA',
                'content'       => $content,
                'sign'          => $sign,
            ];
            $postData = array_merge($postData, $paramsBasic);
        }

        	return $this->processPaymentUrlForm($postData);
        }
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
	protected function processPaymentUrlFormQRCode($postData = []) {
		$paramStr = $this->arrayToUrl($postData, true);

		$curlConn = curl_init();
        curl_setopt($curlConn , CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlConn , CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curlConn , CURLOPT_URL, $this->getSystemInfo('url'));
        curl_setopt($curlConn , CURLOPT_HTTPHEADER, array());
        curl_setopt($curlConn , CURLOPT_TIMEOUT, 20);
        curl_setopt($curlConn , CURLOPT_MAXREDIRS, 4);
        curl_setopt($curlConn , CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlConn , CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlConn , CURLOPT_ENCODING, 'gzip');
        curl_setopt($curlConn, CURLOPT_POST, true);
        curl_setopt($curlConn, CURLOPT_POSTFIELDS, $paramStr);

        $curlResult = curl_exec($curlConn);
		$curlSuccess = (curl_errno($curlConn) == 0);

		$this->CI->utils->debug_log('=====================lepayle curlSuccess', $curlSuccess, $curlResult);

		$errorMsg = 'Invalid API Response';

		if($curlSuccess) {
			$result = json_decode($curlResult, true);

			## Validate result data
			$curlSuccess = true;

			if ($curlSuccess) {
				## All good, return with qrcode link
				$qrCodeUrl = $result['base64QRCode'];

				if(!$qrCodeUrl) {
					$curlSuccess = false;
				}
			}

			if($result['is_succ'] == 'F') {
				$errorMsg = 'error code: '.$result['fault_code'].', '.$result['fault_reason'];

				return array(
					'success' => false,
					'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
					'message' => $errorMsg
				);
			}
			else if($result['is_succ'] == 'T') {
				$decryptResponse = $this->decrypt($result['response']);
				$decryptResult = json_decode($decryptResponse, true);

				if(array_key_exists('sign', $result)) {
					$validateSign = $this->verify($decryptResponse, $result['sign']);
				}

				if($validateSign == false) {
					return array(
						'success' => false,
						'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
						'message' => 'validate api response sign error'
					);
				}

				if(array_key_exists('base64QRCode', $decryptResult)) {
					return array(
						'success' => true,
						'type' => self::REDIRECT_TYPE_QRCODE,
						'base64' => $decryptResult['base64QRCode']
					);
				}
				else if(array_key_exists('wx_pay_sm_url', $decryptResult)) {
					return array(
						'success' => true,
						'type' => self::REDIRECT_TYPE_QRCODE,
						'url' => $decryptResult['wx_pay_sm_url']
					);
				}
				else if(array_key_exists('ali_pay_sm_url', $decryptResult)) {
					return array(
						'success' => true,
						'type' => self::REDIRECT_TYPE_QRCODE,
						'url' => $decryptResult['ali_pay_sm_url']
					);
				}
				else if(array_key_exists('qq_pay_sm_ur', $decryptResult)) {
					return array(
						'success' => true,
						'type' => self::REDIRECT_TYPE_QRCODE,
						'url' => $decryptResult['qq_pay_sm_ur']
					);
				}
				else {
					return array(
						'success' => false,
						'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
						'message' => 'response not decrypt failed, reponse is \n'.$result['response']
					);
				}
			}
			else {
				return array(
					'success' => false,
					'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
					'message' => "API didn't return is_succ which should be T or F"
				);
			}
		} else {
			# curl error
			$errorMsg = curl_error($curlConn);
		}

		curl_close($curlConn);
	}

	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	## Reference: sample code, callback.php
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
		$this->utils->debug_log('callbackFrom' . ucfirst($source) . ': [' . $orderId .'], params:', $params);

		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		if (!$order) {
			$this->utils->error_log("Order ID [$orderId] not found.");
			return $result;
		}

		$callbackValid = false;
		$paymentSuccessful = $this->checkCallbackOrder($order, $params, $callbackValid); # $callbackValid is also assigned

		# Do not print success msg if callback fails integrity check
		if(!$callbackValid) {
			return $result;
		}

		# Do not proceed to update order status if payment failed, but still print success msg as callback response
		if(!$paymentSuccessful) {
			$result['return_error'] = self::RETURN_SUCCESS_CODE;
			return $result;
		}

		# We can respond with ack to callback now
		$success = true;
		$result['message'] = self::RETURN_SUCCESS_CODE;

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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['trade_no'], $params['bank_seq_no'], null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$success = $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		# This $success marks whether the order status update is successful
		$result['success'] = $success;

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	# returns true if callback is valid and payment is successful
	# sets the $callbackValid parameter if callback is valid
	private function checkCallbackOrder($order, $fields, &$callbackValid) {
		# does all required fields exist?
		$requiredFields = array(
			'input_charset', 'sign_type', 'sign', 'request_time', 'content', 'out_trade_no', 'status'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("======================lepayle check callback Missing parameter: [$f]", $fields);
				return false;
			}
		}

		$decryptCallbackContent = $this->decrypt(urldecode($fields['content']));
		$decryptResult = json_decode($decryptCallbackContent, true);

		$decryptResultrequiredFields = array(
			'trade_id', 'out_trade_no', 'amount_str', 'amount_fee', 'status', 'business_type', 'create_time'
		);
		foreach ($decryptResultrequiredFields as $f) {
			if (!array_key_exists($f, $decryptResult)) {
				$this->writePaymentErrorLog("======================lepayle check callback decryptResult Missing parameter: [$f]", $decryptResult);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->verify($decryptCallbackContent, urldecode($fields['sign']))) {
			$this->writePaymentErrorLog('========================lepayle checkcallback Signature Error', $fields);
			return false;
		}

		$callbackValid = true; # callbackValid is set to true once the signature verification pass

		if ($fields['status'] != self::TRADE_STATUS_SUCCESS) {
			$this->writePaymentErrorLog('========================lepayle Payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $decryptResult['amount_str']) {
			$this->writePaymentErrorLog("========================lepayle Payment amounts do not match, expected [$order->amount]", $decryptResult);
			return false;
		}

		if ($decryptResult['out_trade_no'] != $order->secure_id) {
			$this->writePaymentErrorLog("========================lepayle Order IDs do not match, expected [$order->secure_id]", $decryptResult);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- private helper functions --
	protected function getBankListInfoFallback() {
		return array(
			array('label' => '工商银行', 'value' => 'ICBC'),
			array('label' => '农业银行', 'value' => 'ABC'),
			array('label' => '建设银行', 'value' => 'CCB'),
			array('label' => '交通银行', 'value' => 'BOCM'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '邮政储蓄银行', 'value' => 'PSBC'),
			array('label' => '华夏银行', 'value' => 'HXB'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '广发银行', 'value' => 'CGB'),
			array('label' => '中信银行', 'value' => 'CITIC'),
		);
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	protected function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

    /**
     *
     * @param array $array
     * @param bool $encode
     * @return string
     */
    public function arrayToUrl($array = [], $encode = false)
    {
        $paramStr = '';
        ksort($array);
        foreach ($array as $k => $v)
        {
            if (empty($k) || empty($v)) continue;

            !$encode or $v = urlencode($v);

            $paramStr .= "$k" . "=" . $v. '&';
        }
        return trim($paramStr, '&');
    }

    /**
     * 平台公钥加密
     * @param $paramStr
     * @return string
     */
    public function encrypt($paramStr) {
        $encryptData = '';

        foreach (str_split($paramStr, 117) as $chunk){
            if (openssl_public_encrypt($chunk, $encrypted, $this->getPubKey())) {
                $encryptData .= $encrypted;
            }
        }
        return base64_encode($encryptData);
    }

    /**
     * @param $response
     * @return bool|string
     */
    public function decrypt($response) {
        $crypto = '';
        foreach (str_split(base64_decode($response), 128) as $chunk) {

            openssl_private_decrypt($chunk, $decryptData, $this->getPrivKey());

            $crypto .= $decryptData;
        }

        return $crypto;
    }

    /**
     * RSA加签
     * @param $paramStr
     * @return string
     */

    public function sign($paramStr) {
        openssl_sign($paramStr, $sign_info, $this->getPrivKey());
        $sign = base64_encode($sign_info);
        return $sign;

    }

    /**
     * MD5加签
     * @param $paramStr
     * @return string
     */

    public function signMd5($params) {
    	$requreParams = ['partner', 'service', 'out_trade_no', 'amount_str', 'tran_ip', 'good_name', 'request_time', 'return_url'];

        $md5src = '';
        foreach ($requreParams as $paramKey){
            $md5src .= $paramKey.'='.$params[$paramKey].'&';
        }
        $md5src .= 'verfication_code=' . $this->getSystemInfo('key');
        $sign = md5($md5src);

        return $sign;
    }

    /**
     * 验签
     * @param $data
     * @param $sign
     * @return int
     */
    public function verify($data, $sign) {
        $publicKey = openssl_get_publickey($this->getPubKey());
        $sign = base64_decode($sign);
        return (bool)openssl_verify($data, $sign, $publicKey);
    }

	private function createSignStr($params) {
		ksort($params);
		$signStr = '';
		foreach($params as $key => $value) {
			if(empty($value) || $key == 'sign' || $key == 'sign_type') {
				continue;
			}
			$signStr .= "$key=$value&";
		}
		return rtrim($signStr, '&');
	}

	# Returns public key given by gateway
	private function getPubKey() {
		$lepayle_pub_key = $this->getSystemInfo('lepayle_pub_key');
		$pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($lepayle_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
		return $pub_key;
	}

	# Returns the private key generated by merchant
	private function getPrivKey() {
		$lepayle_priv_key = $this->getSystemInfo('lepayle_priv_key');
		$priv_key = '-----BEGIN PRIVATE KEY-----' . PHP_EOL . chunk_split($lepayle_priv_key, 64, PHP_EOL) . '-----END PRIVATE KEY-----' . PHP_EOL;
		return $priv_key;
	}
}