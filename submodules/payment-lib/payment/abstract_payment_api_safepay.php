<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * SAFEPAY
 *
 * * SAFEPAY_PAYMENT_API, ID: 362
 * * SAFEPAY_ALIPAY_PAYMENT_API, ID: 363
 * * SAFEPAY_WEIXIN_PAYMENT_API, ID: 364
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
abstract class Abstract_payment_api_safepay extends Abstract_payment_api {
	const BANK_ALIPAY = '04';
	const BANK_WEIXIN = '00';
	const ORDER_STATUS_SUCCESS = 'Y';
	const RETURN_SUCCESS_CODE = 'success';
	const RETURN_FAILED_CODE = 'failed';

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
        $this->CI->load->model(array('player'));
		$player = $this->CI->player->getPlayerById($playerId);

		$params['userID'] = $this->getSystemInfo("account");
		$params['orderId'] = $order->secure_id;
		$params['amt'] = $this->convertAmountToCurrency($amount);
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['name'] = $player['username'];
		$params['cur'] = '1';
		$params['userip'] = $this->getClientIP();
		$params['agent'] = urlencode($_SERVER["HTTP_USER_AGENT"]);
		$params['hmac'] = $this->sign($params);
		$params['url'] = $this->getNotifyUrl($orderId);
		$this->CI->utils->debug_log("=====================safepay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {

		$ch = curl_init();
		$url = $this->getSystemInfo('url');
		$url = $url.'?'.http_build_query($params);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $params['agent']);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeoutSecond());
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_FAILONERROR, 0);
        $this->setCurlProxyOptions($ch);


		$response    = curl_exec($ch);
		$response    = iconv("gb2312","UTF-8//TRANSLIT//IGNORE",$response);
		$statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header      = substr($response, 0, $header_size);
		$content     = substr($response, $header_size);

		$errCode    = curl_errno($ch);
		$error      = curl_error($ch);
		$statusText = $errCode . ':' . $error;
		curl_close($ch);

		$response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['orderId']);
		$this->CI->utils->debug_log("=====================safepay getPageContent response", $response);

		if($response == "" || strpos($response,"error:") > 0){
			if($response == ""){
				$err_msg = lang('timeout');
			}else{
				$err_msg = $response;
			}

			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $err_msg
			);
		}else{
			$redirect =  substr($response, strpos($response,"[")+1, strpos($response,"]") - 5);
			$this->CI->utils->debug_log("=====================safepay redirect url", $redirect);
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_URL,
				'url' => $redirect
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

        $this->CI->utils->debug_log('=====================safepay process entry ===' );
        $this->CI->utils->debug_log('=====================safepay callback process params', $params );

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
			/*$this->CI->sale_order->updateExternalInfo($order->id,
				$params['OrdNo'], '',
				null, null, $response_result_id);*/
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
			"userID", "r_orderId", "orderId", "amt", "cur", "succ", "time", "sid", "paytype", "userOrderID"
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================safepay missing parameter: [$f]", $fields);
				return false;
			}
		}

		$callbackSign = $this->sign($fields, false, false);

		# is signature authentic?
		if ($fields['hmac2'] != $callbackSign) {
			$this->writePaymentErrorLog("=====================safepay check callback sign error, signature is [$callbackSign], match? ", $fields['sign']);
			return false;
		}

		if ($fields['succ'] != self::ORDER_STATUS_SUCCESS) {
			$payStatus = $fields['succ'];
			$this->writePaymentErrorLog("=====================safepay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $this->convertAmountToCurrency(floatval( $fields['amt'] )) ) {
			$this->writePaymentErrorLog("=====================safepay Payment amounts do not match, expected [$order->amount]", $fields);
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
			array('label' => '北京银行', 'value' => 'A'),
			array('label' => '广东发展银行', 'value' => 'B'),
			// array('label' => '广州市农村信用社 ', 'value' => 'C'),
			// array('label' => '广州市商业银行 ', 'value' => 'D'),
			// array('label' => '华夏银行', 'value' => 'E'),
			array('label' => '交通银行', 'value' => 'F'),
			array('label' => '民生银行', 'value' => 'G'),
			array('label' => '平安银行', 'value' => 'H'),
			array('label' => '浦东发展银行', 'value' => 'I'),
			// array('label' => '上海农村商业银行', 'value' => 'J'),
			// array('label' => '深圳发展银行', 'value' => 'K'),
			// array('label' => '深圳市农村商业银行', 'value' => 'L'),
			// array('label' => '顺德农村信用合作社', 'value' => 'M'),
			array('label' => '兴业银行', 'value' => 'N'),
			array('label' => '邮政储蓄', 'value' => 'O'),
			array('label' => '招商银行', 'value' => 'P'),
			array('label' => '中国工商银行', 'value' => 'R'),
			array('label' => '中国光大银行', 'value' => 'S'),
			array('label' => '中国建设银行', 'value' => 'T'),
			array('label' => '中国农业银行', 'value' => 'U'),
			array('label' => '中国银行', 'value' => 'V'),
			array('label' => '中信银行', 'value' => 'W'),
			array('label' => '北京农村商业银行', 'value' => 'X'),
			// array('label' => '浙商银行', 'value' => 'Y'),
			array('label' => '上海银行', 'value' => 'Z'),
			// array('label' => '宁波银行', 'value' => 'AA'),
			// array('label' => '东亚银行', 'value' => 'AB'),
			array('label' => '渤海银行', 'value' => 'AC'),
			// array('label' => '南京银行', 'value' => 'AD'),
			// array('label' => '杭州银行', 'value' => 'AE'),
			// array('label' => '天津银行', 'value' => 'AF')
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
		return number_format($amount, 0, '.', '');
	}

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
		$md5key = $this->getSystemInfo('key');

		if(isset($params['hmac2'])) {
			$data = array(
				"userID", "orderId", "amt", "succ"	//deposit callback params
			);
		}
		else {
			$data = array(
				"userID", "orderId", "amt"
			);
		}

	    $arr = array();
	    for($i = 0; $i< count($data); $i++){
			if (array_key_exists($data[$i], $params)) {
				$arr[$i] = $params[$data[$i]];
			}
	    }
	    $signStr = implode('&', $arr);
	    $signStr .= '&'.$md5key;

		$sign = md5($signStr);

		return $sign;
	}
}