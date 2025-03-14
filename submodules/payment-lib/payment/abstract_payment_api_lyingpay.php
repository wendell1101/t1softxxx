<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * LYINGPAY 利盈支付
 *
 * * LYINGPAY_PAYMENT_API, ID: 464
 * * LYINGPAY_ALIPAY_PAYMENT_API, ID: 465
 * * LYINGPAY_WEIXIN_PAYMENT_API, ID: 466
 * * LYINGPAY_QQPAY_PAYMENT_API, ID: 467
 * * LYINGPAY_JDPAY_PAYMENT_API, ID: 468
 * * LYINGPAY_WEIXIN_WAP_PAYMENT_API, ID: 469
 * * LYINGPAY_QQPAY_WAP_PAYMENT_API, ID: 470
 *
 * Required Fields:
 *
 * * URL: https://api.lyingpay.com/v1/pay/unifiedorder
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_lyingpay extends Abstract_payment_api {
	const TRADETYPE_ALIPAY = '02';
	const TRADETYPE_WEIXIN = '01';
	const TRADETYPE_WEIXIN_WAP = '08';
	const TRADETYPE_QQPAY = '05';
	const TRADETYPE_QQPAY_WAP = '06';
	const TRADETYPE_JDPAY = '07';
	const TRADETYPE_BANK = '10';
	const QRCODE_REPONSE_CODE_SUCCESS = '0';
	const RESULT_CODE_SUCCESS = '0';
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const RETURN_FAILED_CODE = 'faile';

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

		$params['mch_id'] = $this->getSystemInfo("account");
		$params['out_trade_no'] = $order->secure_id;
		$params['body'] = 'Deposit';
		$params['attach'] = 'Deposit';
		$params['total_fee'] = $this->convertAmountToCurrency($amount);
		$params['bank_id'] = '';
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['return_url'] = $this->getReturnUrl($orderId);
		$params['time_start'] = date('YmdHis', time());
		$params['nonce_str'] = 'Deposit';
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================lyingpay generatePaymentUrlForm", $params);
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
	protected function processPaymentUrlQRCode($params) {
		$post_xml_data = $this->array2xml($params);
		$this->CI->utils->debug_log('=====================lyingpay post_xml_data', $post_xml_data);

		$curlConn = curl_init();
		$curlData = array();
		$curlData[CURLOPT_POST] = true;
		$curlData[CURLOPT_URL] = $this->getSystemInfo('url');
		$curlData[CURLOPT_RETURNTRANSFER] = true;
		$curlData[CURLOPT_TIMEOUT] = 120;
		$curlData[CURLOPT_POSTFIELDS] = $post_xml_data;
        $curlData[CURLOPT_HTTPHEADER] = [ "Content-type: text/xml;charset='utf-8'" ];
		curl_setopt_array($curlConn, $curlData);

		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYHOST, false);

		$response = curl_exec($curlConn);
		$errCode     = curl_errno($curlConn);
        $error       = curl_error($curlConn);
        $statusCode  = curl_getinfo($curlConn, CURLINFO_HTTP_CODE);

        $curlSuccess = ($errCode == 0);
        $response_result_id = $this->submitPreprocess($params, $response, $this->getSystemInfo('url'), $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['out_trade_no']);
		$response = $this->parseResultXML($response);
		$msg = lang('Invalidate API response');

		if($response['status'] == self::QRCODE_REPONSE_CODE_SUCCESS) {
			if($response['result_code'] == self::QRCODE_REPONSE_CODE_SUCCESS) {
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_URL,
					'url' => $response['code_img_url']
				);
			} else {
				$msg = 'Error Code: '.$response['err_code']. ', '.$response['err_msg'];
			}
		} else {
			if(isset($response['message'])) {
				$msg = $response['message'];
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
	public function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$this->utils->debug_log('=================lyingpay callbackFrom' . ucfirst($source) . ': [' . $orderId .'], params:', $params);

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		if($source == 'server' ){
			$raw_post_data = file_get_contents('php://input', 'r');
			$this->CI->utils->debug_log("=====================lyingpay raw_post_data", $raw_post_data);
			$params = $this->parseResultXML($raw_post_data);
			$this->CI->utils->debug_log("=====================lyingpay params", $params);


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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['out_trade_no'], null, null, null, $response_result_id);
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

	public function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array(
			'version', 'charset', 'status', 'sign_type', 'mch_id', 'out_trade_no', 'transaction_id', 'result_code', 'total_fee', 'nonce_str');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================lyingpay missing parameter: [$f]", $fields);
				return false;
			}
		}

		$callbackSign = $this->sign($fields, true);

		# is signature authentic?
		if ($fields['sign'] != $callbackSign) {
			$this->writePaymentErrorLog("=====================lyingpay check callback sign error, signature is [$callbackSign], match? ", $fields['sign']);
			return false;
		}

		if ($fields['result_code'] != self::RESULT_CODE_SUCCESS) {
			$resultCode = $fields['result_code'];
			$this->writePaymentErrorLog("=====================lyingpay Payment was not successful, resultCode is [$resultCode]", $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != floatval( $fields['total_fee']) ) {
			$this->writePaymentErrorLog("=====================lyingpay Payment amounts do not match, expected [$order->amount]", $fields);
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
			array('label' => '工商银行', 'value' => '01020000'),
			array('label' => '中国农业银行', 'value' => '01030000'),
			array('label' => '中国银行', 'value' => '01040000'),
			array('label' => '中国建设银行', 'value' => '01050000'),
			array('label' => '交通银行', 'value' => '03010000'),
			array('label' => '中信银行', 'value' => '03020000'),
			array('label' => '光大银行', 'value' => '03030000'),
			array('label' => '华夏银行', 'value' => '03040000'),
			array('label' => '民生银行', 'value' => '03050000'),
			array('label' => '广发银行', 'value' => '03060000'),
			array('label' => '平安银行', 'value' => '03070000'),
			array('label' => '招商银行', 'value' => '03080000'),
			array('label' => '兴业银行', 'value' => '03090000'),
			array('label' => '浦东发展银行', 'value' => '03100000'),
			array('label' => '北京银行', 'value' => '03130011'),
			array('label' => '中国邮政储蓄银行', 'value' => '04030000')
		);
	}

	# -- Private functions --
	/**
	 * detail: After payment is complete, the gateway will invoke this URL asynchronously
	 *
	 * @param int $orderId
	 * @return void
	 */
	public function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: After payment is complete, the gateway will send redirect back to this URL
	 *
	 * @param int $orderId
	 * @return void
	 */
	public function getReturnUrl($orderId) {
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

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params, $callback = false) {
		unset($params['attach']);
		$md5key = "key=".$this->getSystemInfo('key');

		ksort($params);

		$signStr = '';
        foreach($params as $key => $value) {
            if($value == null || $key == 'sign' || $key == 'sign_type') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
	    $signStr .= $md5key;

	    $sign = strtoupper(md5($signStr));
		return $sign;
	}

	public function array2xml($values){
		if (!is_array($values) || count($values) <= 0) {
		    return false;
		}

		$xml = "<xml>";
		foreach ($values as $key => $val) {
			if (is_numeric($val)) {
				$xml .= "<" . $key . ">" . $val . "</" . $key . ">";
			} else {
				$xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
			}
		}
		$xml .= "</xml>";
		return $xml;
	}

	public function parseResultXML($resultXml) {
		$result = NULL;
		$obj=simplexml_load_string($resultXml);
		$arr=$this->CI->utils->xmlToArray($obj);
		$this->CI->utils->debug_log(' =========================lyingpay parseResultXML', $arr);
		$result = $arr;

		return $result;
	}
}
