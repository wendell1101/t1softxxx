<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * Vicus
 *
 *
 * * VICUS_PAYMENT_API, ID: 255
 * * VICUS_ALIPAY_PAYMENT_API, ID: 256
 * * VICUS_WEIXIN_PAYMENT_API, ID: 257
 * * VICUS_QQPAY_PAYMENT_API, ID: 703
 * * VICUS_JDPAY_PAYMENT_API, ID: 704
 * * VICUS_UNIONPAY_PAYMENT_API, ID: 705
 * * VICUS_QUICKPAY_PAYMENT_API, ID: 706
 * * VICUS_WITHDRAWAL_PAYMENT_API, ID: 810
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.vicussolutions.net/Payapi_Index_Pay.html
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_vicus extends Abstract_payment_api {
	const PAYTYPE_ONLINE_BANK = 'b';
	const PAYTYPE_WEIXIN = 'wx';
	const PAYTYPE_ALIPAY = 'zfb';
	const PAYTYPE_QQPAY = 'qqpay';
	const PAYTYPE_JDPAY = 'jdpay';
	const PAYTYPE_UNIONPAY = 'unionpay';
	const PAYTYPE_QUICKPAY = 'quickpay';
	const RETURN_SUCCESS_CODE = 'ok';
	const ORDER_STATUS_SUCCESS = 1;

	public function __construct($params = null) {
		parent::__construct($params);
	}

	public function getBankType($direct_pay_extra_info) {
		return ''; # Default return empty banktype, redirect to bank selection page
	}
	protected abstract function configParams(&$params, $direct_pay_extra_info);

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

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params = array();
		$params['p0_Cmd'] = 'Buy'; # fixed value
		$params['p1_MerId'] = $this->getSystemInfo('account');
		$params['p2_Order'] = $order->secure_id;
		$params['p3_Amt'] = $this->convertAmountToCurrency($amount);
		$params['p4_Cur'] = 'CNY'; # fixed value
		$params['p5_Pid'] = 'Topup';
		$params['p6_Pcat'] = 'Topup';
		$params['p7_Pdesc'] = 'Topup';
		$params['p8_Url'] = $this->getReturnUrl($orderId);
		$params['p9_SAF'] = '0'; # fixed value
		$params['p10_beUrl'] = $this->getNotifyUrl($orderId);
		$params['pa_MP'] = '0'; # fixed value
		$params['pd_FrpId'] = $this->getBankType($order->direct_pay_extra_info);
		$params['pr_NeedResponse'] = '1'; # fixed value

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['hmac'] = $this->getHmac($params);

		$this->CI->utils->debug_log('=====================vicus generatePaymentUrlForm params', $params);

		return $this->processPaymentUrlForm($params);
	}

	# QRCode implementation can overwrite this function to supply QRCode page
	protected function processPaymentUrlForm($params) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true,
		);
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
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;
		if($source == 'server' ){
			if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
				return $result;
			}
		}

		# Update order payment status and balance
		$success = true;

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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['Vicus_TransID'], '', null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($processed) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = 'Error';
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	## Validates whether the callback from API contains valid info and matches with the order
	## Reference: code sample, callback.php
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array(
			'Vicus_MerchantID', 'Vicus_TransID', 'Vicus_OrderID', 'Vicus_Return', 'Vicus_Error', 'Vicus_factMoney', 'Vicus_SuccTime', 'Vicus_BType', 'Vicus_Sign'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================vicus Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->validateHmac($fields)) {
			$this->writePaymentErrorLog('=====================vicus Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['Vicus_Return'] != self::ORDER_STATUS_SUCCESS) {
			$this->writePaymentErrorLog('=====================vicus Payment was not successful', $fields);
			return false;
		}

		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['Vicus_factMoney']))
		) {
			$this->writePaymentErrorLog("=====================vicus Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# Config in extra_info will overwrite this one
	public function getBankListInfoFallback() {
		return array(
			array('label' => '招商银行', 'value' => 'zsyh'),
			array('label' => '工商银行', 'value' => 'gsyh'),
			array('label' => '建设银行', 'value' => 'jsyh'),
			array('label' => '上海浦发银行', 'value' => 'shpdfzyh'),
			array('label' => '农业银行', 'value' => 'nyyh'),
			array('label' => '民生银行', 'value' => 'msyh'),
			array('label' => '兴业银行', 'value' => 'xyyh'),
			array('label' => '交通银行', 'value' => 'jtyh'),
			array('label' => '光大银行', 'value' => 'gdyh'),
			array('label' => '中国银行', 'value' => 'zgyh'),
			array('label' => '平安银行', 'value' => 'payh'),
			array('label' => '广发银行', 'value' => 'gfyh'),
			array('label' => '中信银行', 'value' => 'zxyh'),
			array('label' => '中国邮政储蓄银行', 'value' => 'zgyzcxyh'),
			array('label' => '微信', 'value' => self::PAYTYPE_WEIXIN),
			array('label' => '支付宝', 'value' => self::PAYTYPE_ALIPAY),
			array('label' => 'QQ 钱包', 'value' => self::PAYTYPE_QQPAY),
			array('label' => '京东支付', 'value' => self::PAYTYPE_JDPAY),
			array('label' => '银联扫码', 'value' => self::PAYTYPE_UNIONPAY),
			array('label' => '银联无卡支付', 'value' => self::PAYTYPE_QUICKPAY)
		);
	}

	# -- Private functions --
	# After payment is complete, the gateway will invoke this URL asynchronously
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## After payment is complete, the gateway will send redirect back to this URL
	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## Format the amount value for the API
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- signatures --
	# Reference: PHP Demo
	private function getHmac($params) {
		$this->utils->debug_log("=======================vicus Getting HMAC for request", $params);
		$keys = array('p0_Cmd', 'p1_MerId', 'p2_Order', 'p3_Amt', 'p4_Cur', 'p5_Pid', 'p6_Pcat', 'p7_Pdesc', 'p8_Url', 'p9_SAF', 'p10_beUrl', 'pa_MP', 'pd_FrpId', 'pr_NeedResponse', '$pe_PayerBankCardNo');
		$signStr = "";
		foreach($keys as $key) {
			if(array_key_exists($key, $params)) {
				$signStr .= $params[$key];
			}
		}
		$hmac = md5($signStr.$this->getSystemInfo('key'));

		//$hmac = $this->HmacMd5($signStr, $this->getSystemInfo('key'));
		
		return $hmac;
	}

	private function validateHmac($params) {
		$this->utils->debug_log("=======================vicus Validating HMAC for response", $params);
		$keys = array('Vicus_MerchantID', 'Vicus_Username', 'Vicus_TransID', 'Vicus_OrderID', 'Vicus_Return', 'Vicus_Error', 'Vicus_factMoney', 'Vicus_SuccTime', 'Vicus_BType');
		$signStr = "";
		foreach($keys as $key) {
			if(array_key_exists($key, $params)) {
				$signStr .= $params[$key];
			}
		}
		$hmac = md5($signStr.$this->getSystemInfo('key'));
		//$hmac = $this->HmacMd5($signStr, $this->getSystemInfo('key'));
		if($params['Vicus_Sign'] == $hmac){
			return true;
		}
		else{
			return false;
		}

		return strcasecmp($params['hmac'], $hmac) === 0;
	}

	/*private function HmacMd5($data,$key) {
		// RFC 2104 HMAC implementation for php.
		// Creates an md5 HMAC.
		// Eliminates the need to install mhash to compute a HMAC
		// Hacked by Lance Rushing(NOTE: Hacked means written)

		//需要配置环境支持iconv，否则中文参数不能正常处理
		$key = iconv("GB2312","UTF-8",$key);
		$data = iconv("GB2312","UTF-8",$data);

		$b = 64; // byte length for md5
		if (strlen($key) > $b) {
			$key = pack("H*",md5($key));
		}
		$key = str_pad($key, $b, chr(0x00));
		$ipad = str_pad('', $b, chr(0x36));
		$opad = str_pad('', $b, chr(0x5c));
		$k_ipad = $key ^ $ipad ;
		$k_opad = $key ^ $opad;

		return md5($k_opad . pack("H*",md5($k_ipad . $data)));
	}*/
}

