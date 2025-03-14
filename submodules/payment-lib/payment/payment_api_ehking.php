<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * EHKING 易汇金
 * http://www.ehking.com
 *
 * EHKING_PAYMENT_API, ID: 87
 *
 * Required Fields:
 *
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * Live URL: https://api.ehking.com/onlinePay/order
 * * Sandbox URL: https://api.ehking.com/onlinePay/order
 * * Extra Info
 * > {
 * >     "ehking_merchantId" : "##Merchant ID##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ehking extends Abstract_payment_api {
	const DEFAULT_CURRENCY = 'CNY';
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const RETURN_FAILED_CODE='fail';

	private $info;

	public function __construct($params = null) {
		parent::__construct($params);

		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->info = $this->getInfoByEnv();
	}

	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return EHKING_PAYMENT_API;
	}

	public function getPrefix() {
		return 'ehking';
	}

	# -- override common API functions --
	## Constructs an URL so that the caller can redirect / invoke it to make payment through this API
	## See controllers/redirect.php for detail.
	##
	## Retuns a hash containing these fields:
	## array(
	##	'success' => true,
	##	'type' => self::REDIRECT_TYPE_FORM,  ## constants defined in abstract_payment_api.php
	##	'url' => $info['url'],
	##	'params' => $params,
	##	'post' => true
	## );
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		# Setup parameters. Reference: Documentation section 2.1.1

		# read some parameters from config
		$params['merchantId'] = $this->getSystemInfo('ehking_merchantId');

		# order-related params
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$params['orderAmount'] = $this->convertAmountToCurrency($amount);
		$params['orderCurrency'] = self::DEFAULT_CURRENCY;
		$params['requestId'] = $order->secure_id;
		$params['notifyUrl'] = $this->getNotifyUrl($orderId);
		$params['callbackUrl'] = $this->getReturnUrl($orderId);

		$params['clientIp'] = $this->getClientIP();

		$direct_pay_extra_info = $order->direct_pay_extra_info;
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['paymentModeCode'] = $extraInfo['bank'];
			}
		} else {
			$params['paymentModeCode'] = $this->getBankId();
		}

		# If visit using mobile, use 微信（手机端）
		if($params['paymentModeCode'] == 'SCANCODE-WEIXIN_PAY-P2P' && $this->utils->is_mobile()) {
			$params['paymentModeCode'] = 'WECHAT-OFFICIAL_PAY';
		}

		# product info. Reference: documentation section 2.1.1.1
		$params['productDetails'] = array(
			array(
				"name" => 'Deposit',
				"quantity" => "1",
				"amount" => $params['orderAmount'],
				"receiver" => "",
				"description" => "Deposit"
			)
		);

		$params['payer'] = (object)[];

		# sign param
		$params['hmac'] = $this->sign($params);

		$this->utils->debug_log('params', $params);

		# submit payment order
		$url=$this->getSystemInfo('url');
		$result = $this->postForm($url,$params);
		$result = json_decode(json_encode($result), true); # convert to assoc array

		if($this->validateOrderReturn($result)) {
			# There is QRCode data, redirect to QRCode result page
			if ($result['scanCode']) {
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_QRCODE,
					'base64' => $result['scanCode'],
				);
			}
			# redirect to the payment page
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_URL,
				'url' => $result['redirectUrl'],
			);
		} else {
			$this->utils->error_log('wrong result', $result);

			return array('success' => false);
		}
	}

	# Override by child class, payment_api_ehking_weixin
	protected function getBankId() {
		return '';
	}

	public function postForm($url, $params) {
		try {
			$curl = curl_init($url);
			curl_setopt($curl,CURLOPT_HEADER, 0 ); // 过滤HTTP头
			curl_setopt($curl,CURLOPT_HTTPHEADER,array(
				'Content-Type: application/vnd.ehking-v1.0+json'
			));
			curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
			curl_setopt($curl,CURLOPT_POST,true); // post传输数据
			curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($params));// post传输数据
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//SSL证书认证
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证

			$this->CI->utils->debug_log("POSTing fields", $params);

			$responseText = curl_exec($curl);
			if (curl_errno($curl) || $responseText === false) {
				$this->CI->utils->error_log("Curl error: ", curl_errno($curl));
				curl_close($curl);
			}
			curl_close($curl);

			$data = json_decode($responseText, true);
			$this->CI->utils->debug_log("Submit order response: ", $data);
			return $data;
		} catch (Exception $e) {
			$this->CI->utils->error_log('POST failed', $e);
			return '';
		}
	}

	private function validateOrderReturn($fields) {
		# does all required fields exist?
		$requiredFields = array(
			'merchantId', 'requestId', 'status', 'redirectUrl'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# does merchNo match?
		if ($fields['merchantId'] != $this->getSystemInfo('ehking_merchantId')) {
			$this->writePaymentErrorLog("Merchant codes do not match, expected [" . $this->getSystemInfo('ehking_merchantId') . "]", $fields);
			return false;
		}

		# is payment successful?
		if ($fields['status'] == 'FAILED' && $fields['status'] == 'ERROR') {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	public function callbackFromServer($orderId, $params) {
		//read json
        $raw_post_data = file_get_contents('php://input', 'r');
        $jsonData = json_decode($raw_post_data, true);
        $jsonData['_params']=$params;

		$response_result_id = parent::callbackFromServer($orderId, $jsonData);
		return $this->callbackFrom('server', $orderId, $jsonData, $response_result_id);
	}

	## This will be called when user redirects back to our page from payment API
	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}

	## $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		if($source == 'browser') {
			# Do not change payment status if callback from browser
			if (!$order || !$this->checkCallbackOrderBrowser($order, $params)) {
				return $result;
			}

			$result['success'] = true;
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
			return $result;
		}

		if (!$order || !$this->checkCallbackOrderServer($order, $params, $processed)) {
			return $result;
		}

		# Update order payment status and balance
		$success=true;
		// $this->CI->sale_order->startTrans();

		# Update player balance based on order status
		# if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
		$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
		if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
			$this->CI->utils->debug_log('CallbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
			if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
				$this->CI->sale_order->setStatusToSettled($orderId);
			}
		} else {
			# update player balance
			$this->CI->sale_order->updateExternalInfo($order->id,
				$params['serialNumber'], '',
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}
		// $success = $this->CI->sale_order->endTransWithSucc();

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

	## Validates whether the callback from API contains valid info and matches with the order
	private function checkCallbackOrderBrowser($order, $fields) {
		# does all required fields exist?
		$requiredFields = array(
			'merchantId', 'requestId'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# does merchNo match?
		if ($fields['merchantId'] != $this->getSystemInfo('ehking_merchantId')) {
			$this->writePaymentErrorLog("Merchant codes do not match, expected [" . $this->getSystemInfo('ehking_merchantId') . "]", $fields);
			return false;
		}

		# does order_no match?
		if ($fields['requestId'] != $order->secure_id) {
			$this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	private function checkCallbackOrderServer($order, $fields, &$processed = false) {
		if(!$this->checkCallbackOrderBrowser($order, $fields)) {
			return false;
		}

		# does all required fields exist?
		$requiredFields = array(
			'serialNumber', 'orderCurrency', 'orderAmount', 'status', 'completeDateTime', 'hmac'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->verify($fields, $fields['hmac'])) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		# is payment successful?
		if ($fields['status'] != 'SUCCESS') {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		# does amount match?
		if (
			$this->convertAmountToCurrency($order->amount) != $fields['orderAmount']
		) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- Functions to display bank dropdown --
	## Reference: Documentation, section 2.5.6
	public function getBankListInfo() {
		return array(
			array('label'=>'工商银行', 'value' => 'BANK_CARD-B2C-ICBC-P2P'),
			array('label'=>'中国银行', 'value' => 'BANK_CARD-B2C-BOC-P2P'),
			array('label'=>'交通银行', 'value' => 'BANK_CARD-B2C-BOCO-P2P'),
			array('label'=>'建设银行', 'value' => 'BANK_CARD-B2C-CCB-P2P'),
			array('label'=>'平安银行', 'value' => 'BANK_CARD-B2C-PINGANBANK-P2P'),
			array('label'=>'光大银行', 'value' => 'BANK_CARD-B2C-CEB-P2P'),
			array('label'=>'民生银行', 'value' => 'BANK_CARD-B2C-CMBC-P2P'),
			array('label'=>'农业银行', 'value' => 'BANK_CARD-B2C-ABC-P2P'),
			array('label'=>'广发银行', 'value' => 'BANK_CARD-B2C-GDB-P2P'),
			array('label'=>'招商银行', 'value' => 'BANK_CARD-B2C-CMBCHINA-P2P'),
			array('label'=>'中信银行', 'value' => 'BANK_CARD-B2C-ECITIC-P2P'),
			array('label'=>'邮政储蓄银行', 'value' => 'BANK_CARD-B2C-POST-P2P'),
			array('label'=>'深圳发展银行', 'value' => 'BANK_CARD-B2C-SDB-P2P'),
			array('label'=>'北京银行', 'value' => 'BANK_CARD-B2C-BCCB-P2P'),
			array('label'=>'上海银行', 'value' => 'BANK_CARD-B2C-SHB-P2P'),
			array('label'=>'浦发银行', 'value' => 'BANK_CARD-B2C-SPDB-P2P'),
			array('label'=>'兴业银行', 'value' => 'BANK_CARD-B2C-CIB-P2P'),
			array('label'=>'华夏银行', 'value' => 'BANK_CARD-B2C-HXB-P2P'),

			# WEIXIN
			array('label'=>'微信直连', 'value' => 'SCANCODE-WEIXIN_PAY-P2P'),

			# Other banklist
			// array('label' => '工商银行', 'value' => 'BANK_CARD-B2B-ICBC-P2P'),
			// array('label' => '建设银行', 'value' => 'BANK_CARD-B2B-CCB-P2P'),
			// array('label' => '招商银行', 'value' => 'BANK_CARD-B2B-CMBCHINA-P2P'),
			// array('label' => '中国银行', 'value' => 'BANK_CARD-B2B-BOC-P2P'),
			// array('label' => '中国农业银行', 'value' => 'BANK_CARD-B2B-ABC-P2P'),
			// array('label' => '光大银行', 'value' => 'BANK_CARD-B2B-CEB-P2P'),
			// array('label' => '交通银行', 'value' => 'BANK_CARD-B2B-BOCO-P2P'),
			// array('label' => '浦发银行', 'value' => 'BANK_CARD-B2B-SPDB-P2P'),
			// array('label' => '深圳发展银行', 'value' => 'BANK_CARD-B2B-SDB-P2P'),
			// array('label' => '民生银行', 'value' => 'BANK_CARD-B2B-CMBC-P2P'),
		);
	}
	/* TODO: English translation
	"bank_list" : {
		"BANK_CARD-B2B-ICBC-P2P" : "_json: { \"1\" : \"工商银行\", \"2\" : \"工商银行\" }",
		"BANK_CARD-B2B-CCB-P2P" : "_json: { \"1\" : \"建设银行\", \"2\" : \"建设银行\" }",
		"BANK_CARD-B2B-CMBCHINA-P2P" : "_json: { \"1\" : \"招商银行\", \"2\" : \"招商银行\" }",
		"BANK_CARD-B2B-BOC-P2P" : "_json: { \"1\" : \"中国银行\", \"2\" : \"中国银行\" }",
		"BANK_CARD-B2B-ABC-P2P" : "_json: { \"1\" : \"中国农业银行\", \"2\" : \"中国农业银行\" }",
		"BANK_CARD-B2B-CEB-P2P" : "_json: { \"1\" : \"光大银行\", \"2\" : \"光大银行\" }",
		"BANK_CARD-B2B-BOCO-P2P" : "_json: { \"1\" : \"交通银行\", \"2\" : \"交通银行\" }",
		"BANK_CARD-B2B-SPDB-P2P" : "_json: { \"1\" : \"浦发银行\", \"2\" : \"浦发银行\" }",
		"BANK_CARD-B2B-SDB-P2P" : "_json: { \"1\" : \"深圳发展银行\", \"2\" : \"深圳发展银行\" }",
		"BANK_CARD-B2B-CMBC-P2P" : "_json: { \"1\" : \"民生银行\", \"2\" : \"民生银行\" }"
	}
	*/

	# -- Private functions --
	## After payment is complete, the gateway will invoke this URL asynchronously
	private function getNotifyUrl($orderId) {
		// return 'http://www.smartbackend.com'.'/callback/process/' . $this->getPlatformCode() . '/' . $orderId;
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## After payment is complete, the gateway will send redirect back to this URL
	private function getReturnUrl($orderId) {
		// return 'http://www.smartbackend.com'.'/callback/browser/process/' . $this->getPlatformCode() . '/' . $orderId;
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## Format the amount value for the API
	protected function convertAmountToCurrency($amount) {
		return number_format($amount*100, 0, '', '');
	}

	# -- private helper functions --
	# Reference: Documentation 2.1.1
	public function sign($params) {
		$strb = "";
		$keySequence = array(
			# defines the order of the param value in the signing string
			//ignore payer, always empty
			'merchantId', 'orderAmount', 'orderCurrency', 'requestId', 'notifyUrl', 'callbackUrl', 'remark', 'paymentModeCode', 'productDetails', 'bankCard', 'cashierVersion', 'forUse', 'merchantUserId', 'bindCardId', 'clientIp', 'timeout'
		);
		foreach ($keySequence as $key) {
			if(!array_key_exists($key, $params)) {
				continue;
			}
			$val = $params[$key];
			if (empty($val)) {
				continue;
			}
			if (is_array($val)) {
				# the only sub-array we use is productDetail, and there is only one product
				$productDetailKeySequence = array('name', 'quantity', 'amount', 'receiver', 'description');
				$substrb = "";
				foreach($productDetailKeySequence as $subKey) {
					if(!empty($val[0][$subKey])) {
						$substrb .= $val[0][$subKey];
					}
				}
				$strb .= $substrb;
			}
			else {
				$strb .= $val;
			}
		}
		$this->utils->debug_log("Signing str", $strb);
		$this->utils->debug_log("Signing key", $this->getSystemInfo('key'));
		return hash_hmac("md5", $strb, $this->getSystemInfo('key'));
	}

	public function server_sign($params) {
		$strb = "";
		$keySequence = array(
			# defines the order of the param value in the signing string
			'merchantId', 'requestId', 'serialNumber', 'totalRefundCount', 'totalRefundAmount',
			'orderCurrency', 'orderAmount', 'status', 'completeDateTime', 'remark'
		);
		foreach ($keySequence as $key) {
			if(!array_key_exists($key, $params)) {
				continue;
			}
			$val = $params[$key];
			// if (empty($val)) {
			// 	continue;
			// }
			$strb .= $val;
		}
		// $strb .= $this->getSystemInfo('key');

		$this->utils->debug_log('original', $strb);

		return hash_hmac("md5", $strb, $this->getSystemInfo('key'));//md5($strb);
	}

	# Reference: Documentation 2.1.2
	public function verify($params, $sign) {
		$encrypted=$this->server_sign($params);
		$this->utils->debug_log('our encrypted', $encrypted,'sign param', $sign);
		return $encrypted == $sign;
	}
}


