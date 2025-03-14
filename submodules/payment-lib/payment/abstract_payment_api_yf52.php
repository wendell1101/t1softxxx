<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * YF52 貝富
 *
 * * YF52_PAYMENT_API, ID: 340
 * * YF52_ALIPAY_PAYMENT_API, ID: 341
 * * YF52_WEIXIN_PAYMENT_API, ID: 342
 *
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
abstract class Abstract_payment_api_yf52 extends Abstract_payment_api {
	const PAYTYPE_BANK = 'WAY_TYPE_BANK';
	const PAYTYPE_ALIPAY = 'WAY_TYPE_ALIPAY';
	const PAYTYPE_ALIPAY_WAP = 'WAY_TYPE_ALIPAY_PHONE';
	const PAYTYPE_WEIXIN = 'WAY_TYPE_WEBCAT';
	const PAYTYPE_WEIXIN_WAP = 'WAY_TYPE_WEBCAT_PHONE';
	const PAYTYPE_QQPAY = 'WAY_TYPE_QQ';
	const PAYTYPE_QQPAY_WAP = 'WAY_TYPE_QQ_PHONE';
	const PAYTYPE_QUICKPAY = 'WAY_TYPE_BANK_FAST';
	const QRCODE_REPONSE_CODE_SUCCESS = '000000';
	const ORDER_STATUS_SUCCESS = '1';
	const RETURN_SUCCESS_CODE = 'success';
	const RETURN_FAILED_CODE = 'false';

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

		$params['p_name'] = $this->getSystemInfo("account");
		$params['p_oid'] = $order->secure_id;
		$params['p_money'] = $this->convertAmountToCurrency($amount);
		$params['p_url'] = $this->getNotifyUrl($orderId);
		$params['p_remarks'] = 'Deposit';
		$params['p_syspwd'] = $this->getSystemInfo("syspwd");
		$params['uname'] = $this->getSystemInfo("account");

		$this->configParams($params, $order->direct_pay_extra_info);

		$paramStr = $this->createParamStr($params);
		$encryptStr = $this->desEncrypt( urlencode($paramStr) );

		$this->CI->utils->debug_log("=====================yf52 generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($encryptStr);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($encryptStr) {
		$url = $this->getSystemInfo('url').'?params='.$encryptStr.'&uname='.$this->getSystemInfo("account");
		$this->CI->utils->debug_log('=====================yf52 post url', $url);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $url,
			// 'params' => $params,
			'post' => false,
		);
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
			$this->CI->sale_order->updateExternalInfo($order->id,
				$params['p_sysid'], 'Third Party Payment (No Bank Order Number)',
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
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
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
		$requiredFields = array(
			'p_oid', 'p_money', 'p_code', 'p_remarks', 'p_sysid', 'p_syspwd'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================yf52 missing parameter: [$f]", $fields);
				return false;
			}
		}

		$callbackSign = $this->createParamStr($fields);

		# is signature authentic?
		if ($fields['p_md5'] != $callbackSign) {
			$this->writePaymentErrorLog("=====================yf52 check callback sign error, signature is [$callbackSign], match? ", $fields);
			return false;
		}

		if ($fields['p_code'] != self::ORDER_STATUS_SUCCESS) {
			$payStatus = $fields['p_code'];
			$this->writePaymentErrorLog("=====================yf52 Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['p_money'] ) ) {
			$this->writePaymentErrorLog("=====================yf52 Payment amounts do not match, expected [$order->amount]", $fields);
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
			array('label' => '中国工商银行', 'value' => '10010'),
			array('label' => '中国农业银行', 'value' => '10002'),
			array('label' => '招商银行', 'value' => '10003'),
			array('label' => '中国银行', 'value' => '10004'),
			array('label' => '中国建设银行', 'value' => '10005'),
			array('label' => '中国民生银行', 'value' => '10006'),
			array('label' => '中信银行', 'value' => '10007'),
			array('label' => '交通银行', 'value' => '10008'),
			array('label' => '兴业银行', 'value' => '10009'),
			array('label' => '光大银行', 'value' => '10010'),
			array('label' => '深圳发展银行', 'value' => '10011'),
			array('label' => '中国邮政', 'value' => '10012'),
			array('label' => '北京银行', 'value' => '10013'),
			array('label' => '平安银行', 'value' => '10014'),
			array('label' => '上海浦东发展银行', 'value' => '10015'),
			array('label' => '广东发展银行', 'value' => '10016'),
			array('label' => '渤海银行', 'value' => '10017'),
			array('label' => '东亚银行', 'value' => '10018'),
			array('label' => '宁波银行', 'value' => '10019'),
			array('label' => '北京农村商业银行', 'value' => '10020'),
			array('label' => '南京银行', 'value' => '10021'),
			array('label' => '浙商银行', 'value' => '10022'),
			array('label' => '上海银行', 'value' => '10023')
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

	public function createParamStr($params) {
		$md5key = $this->getSystemInfo('key');
		$callbackFlag = false;

		if($params['p_code']) {
			$callbackFlag = true;
			$params['p_name'] = $this->getSystemInfo("account");
			$params['p_syspwd'] = $this->getSystemInfo("syspwd");
			$data = array(
				"p_name", "p_oid", "p_money", "p_syspwd"	//callback params
			);
		}
		else {
			$data = array(
				"p_name", "p_type", "p_oid", "p_money", "p_bank", "p_url", "p_remarks", "p_syspwd"
			);
		}

	    $arr = array();
	    for($i = 0; $i< count($data); $i++){
			if (array_key_exists($data[$i], $params)) {
				if($callbackFlag) {
					$arr[$i] = $params[$data[$i]];
				}
				else {
					if($data[$i] == 'p_syspwd') {
						$arr[$i] = $data[$i].'='. md5($params[$data[$i]].$md5key);
					}
					else {
						$arr[$i] = $data[$i].'='.$params[$data[$i]];
					}
				}
			}
	    }

	    $finalStr = '';

	    if(!$callbackFlag) {
			$finalStr = implode('!', $arr);
			$this->CI->utils->debug_log('==============================yf52 createParamStr: ', $finalStr);
	    }
	    else {
			$signStr = implode('', $arr);
			$finalStr = md5($signStr.$md5key);
	
		}

		return $finalStr;
	}

	public function desEncrypt($params) {
		$deskey = $this->getSystemInfo('deskey');

		$size = @mcrypt_get_block_size ( MCRYPT_DES, MCRYPT_MODE_CBC );
		$str = $this->pkcs5Pad ( $params, $size );
		$data = @mcrypt_cbc ( MCRYPT_DES, $deskey, $str, MCRYPT_ENCRYPT, $deskey );
		$base64Data = base64_encode ( $data );
	

		return $base64Data;
	}

	public function pkcs5Pad($text, $blocksize) {
		$pad = $blocksize - (strlen ( $text ) % $blocksize);
		$padStr = $text . str_repeat ( chr ( $pad ), $pad );

		return $padStr;
	}
}
