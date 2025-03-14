<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * MOBAO 摩宝(新付)
 * https://xp.7xinpay.com/
 *
 * MOBAO_PAYMENT_API,            ID: 40
 * MOBAO_ALIPAY_PAYMENT_API,     ID: 245
 * MOBAO_ALIPAY_H5_PAYMENT_API,  ID: 773
 * MOBAO_WEIXIN_PAYMENT_API,     ID: 246
 * MOBAO_WEIXIN_H5_PAYMENT_API,  ID: 775
 * MOBAO_QQPAY_PAYMENT_API,      ID: 769
 * MOBAO_QQPAY_H5_PAYMENT_API,   ID: 771
 * MOBAO_UNIONPAY_PAYMENT_API,   ID: 770
 * MOBAO_UNIONPAY_H5_PAYMENT_API,ID: 776
 * MOBAO_QUICKPAY_PAYMENT_API,   ID: 772
 * MOBAO_JDPAY_PAYMENT_API,      ID: 774
 *
 * General behavior includes :
 * * Recieving callbacks
 * * Generate payment forms
 * * Checking of callback orders
 * * Get bank details
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
 * * Live URL: http://trade.7xinpay.com/cgi-bin/netpayment/pay_gate.cgi
 * * Sandbox URL: http://trade.7xinpay.com/cgi-bin/netpayment/pay_gate.cgi
 * * Extra Info
 * > {
 * >     "mobao_apiVersion": "1.0.0.0",
 * >     "mobao_platformID": "##platform ID##",
 * >     "mobao_merchNo": "##merchant ID##",
 * >     "only_MOBOACC": false,
 * >     "callback_host" : ""
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

abstract class Abstract_payment_api_mobao extends Abstract_payment_api {

	const MOBAO_APINAME_PAY          = "WEB_PAY_B2C"; #網關支付 MOBAO_PAYMENT_API
	const MOBAO_APINAME_QQPAY_WAP    = "QQ_APP_PAY";  #QQ H5 直連 MOBAO_QQPAY_H5_PAYMENT_API
	const MOBAO_APINAME_QUICKPAY     = "MOBI_PAY_B2C";#WAP快捷支付 MOBAO_QUICKPAY_PAYMENT_API
	const MOBAO_APINAME_UNIONPAY_WAP = "MOBI_YL_B2C"; #銀聯H5
	#以下需傳 $params['customerIP'] = $this->getClientIp();
	const MOBAO_APINAME_ALIPAY     = "ALIPY_PAY";     #支付寶直連 MOBAO_ALIPAY_PAYMENT_API
	const MOBAO_APINAME_ALIPAY_WAP = "WAP_ALIPY_PAY"; #支付寶直連H5 MOBAO_ALIPAY_H5_PAYMENT_API
	const MOBAO_APINAME_WEIXIN     = "WECHAT_PAY";    #微信支付* MOBAO_WEIXIN_PAYMENT_API
	const MOBAO_APINAME_WEIXIN_WAP = "WAP_WECHAT_PAY";#微信直連H5 MOBAO_WEIXIN_H5_PAYMENT_API
	const MOBAO_APINAME_QQPAY      = "QQ_PAY";        #QQ直連 MOBAO_QQPAY_PAYMENT_API
	const MOBAO_APINAME_UNIONPAY   = "YL_PAY";        #銀聯掃碼* MOBAO_UNIONPAY_PAYMENT_API
	const MOBAO_APINAME_JDPAY      = "JD_PAY";        #京東錢包掃碼* MOBAO_JDPAY_PAYMENT_API

	const MOBAO_CALLBACK = "PAY_RESULT_NOTIFY";
	const QRCODE_REPONSE_CODE_SUCCESS = '00';
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const RETURN_FAILED_CODE = 'FAILED';

	private $info;
	public function __construct($params = null) {
		parent::__construct($params);
		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->info = $this->getInfoByEnv();
	}

	# Implement these to specify pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

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
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url, 'use_second_url' => true);
			return $result;
		}
		# read some parameters from config
		$paramNames = array('apiVersion', 'platformID', 'merchNo');
		$params = array();
		foreach ($paramNames as $p) {
			$params[$p] = $this->getSystemInfo("mobao_$p");
		}
		# other parameters
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$params['orderNo'] = $order->secure_id;
		$params['tradeDate'] = $orderDateTime->format('Ymd'); # test shows API only allows this format, no time info
		$params['amt'] = $this->convertAmountToCurrency($amount);
		$params['merchUrl'] = $this->getNotifyUrl($orderId);
		$params['merchParam'] = ''; # No parameter needed
		$params['tradeSummary'] = lang('pay.deposit'); # this will be displayed on the payment page
		$this->configParams($params, $order->direct_pay_extra_info);

		# sign param
		$params['signMsg'] = $this->sign($params);

		$this->CI->utils->debug_log('=====================mobao generatePaymentUrlForm params', $params);

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
	protected function processPaymentUrlFormQRCode($params) {
		$response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['orderNo']);
		$response = $this->parseResultXML($response);
		$this->CI->utils->debug_log('=====================mobao response', $response);

		$msg = lang('Invalidate API response');

		if(isset($response['respData'])) {
			if($response['respData']['respCode'] == self::QRCODE_REPONSE_CODE_SUCCESS) {
				$qrcode_url = base64_decode($response['respData']['codeUrl']);
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_QRCODE,
					'url' => $qrcode_url
				);
			}
			else {
				if($response['respData']['respDesc']) {
					$msg = $response['respData']['respCode'].': '.$response['respData']['respDesc'];
				}
			}
		}

		return array(
			'success' => false,
			'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
			'message' => $msg
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['accNo'], null, null, null, $response_result_id);
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
			$result['next_url'] = '/iframe_module/iframe_viewCashier';
			$result['go_success_page'] = true;
		}
		return $result;
	}
	## Validates whether the callback from API contains valid info and matches with the order
	## Reference: code sample, callback.php
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		# does all required fields exist?
		$requiredFields = array(
			'apiName', 'notifyTime', 'tradeAmt', 'merchNo', 'orderNo',
			'tradeDate', 'accNo', 'accDate', 'orderStatus', 'signMsg',
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================mobao Missing parameter: [$f]", $fields);
				return false;
			}
		}
		# is signature authentic?
		if (!$this->verify($fields, $fields['signMsg'])) {
			$this->writePaymentErrorLog('=====================mobao Signature Error', $fields);
			return false;
		}
		$processed = true; # processed is set to true once the signature verification pass
		# check parameter values: orderStatus, tradeAmt, orderNo, merchNo
		# is payment successful?
		if ($fields['orderStatus'] !== '1') {
			$this->writePaymentErrorLog('=====================mobao Payment was not successful', $fields);
			return false;
		}
		# does amount match?
		if (
			$this->convertAmountToCurrency($order->amount) !==
			$this->convertAmountToCurrency(floatval($fields['tradeAmt']))
		) {
			$this->writePaymentErrorLog("=====================mobao Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}
		# does merchNo match?
		if ($fields['merchNo'] !== $this->getSystemInfo('mobao_merchNo')) {
			$this->writePaymentErrorLog("=====================mobao Merchant codes do not match, expected [" . $this->getSystemInfo('mobao_merchNo') . "]", $fields);
			return false;
		}
		# does order_no match?
		if ($fields['orderNo'] !== $order->secure_id) {
			$this->writePaymentErrorLog("=====================mobao Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}
		# everything checked ok
		return true;
	}
	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}
	## functions to display banks etc on the cashier page
    public function getBankListInfo() {
        $result = parent::getBankListInfo();
        if ($this->getSystemInfo('only_MOBOACC')) {
            $result = array();
            array_unshift( $result, array('label' => '摩宝收银台', 'value' => 'MOBOACC') );
        } else {
            array_unshift( $result, array('label' => '摩宝收银台', 'value' => 'MOBOACC') );
        }

        return $result;
    }

	public function getBankListInfoFallback() {
        return array(
                array("label"=>"农业银行借记卡"    ,"value"=>"J_ABC"),
                array("label"=>"北京银行借记卡"    ,"value"=>"J_BOBJ"),
                array("label"=>"中国银行借记卡"    ,"value"=>"J_BOC"),
                array("label"=>"宁波银行借记卡"    ,"value"=>"J_BONB"),
                array("label"=>"建设银行借记卡"    ,"value"=>"J_CCB"),
                array("label"=>"光大银行借记卡"    ,"value"=>"J_CEB"),
                array("label"=>"兴业银行借记卡"    ,"value"=>"J_CIB"),
                array("label"=>"招商银行借记卡"    ,"value"=>"J_CMB"),
                array("label"=>"民生银行借记卡"    ,"value"=>"J_CMBC"),
                array("label"=>"中信银行借记卡"    ,"value"=>"J_CNCB"),
                array("label"=>"交通银行借记卡"    ,"value"=>"J_COMM"),
                array("label"=>"广发银行借记卡"    ,"value"=>"J_GDB"),
                array("label"=>"华夏银行借记卡"    ,"value"=>"J_HXB"),
                array("label"=>"工商银行借记卡"    ,"value"=>"J_ICBC"),
                array("label"=>"邮政银行借记卡"    ,"value"=>"J_PSBC"),
                array("label"=>"浦发银行借记卡"    ,"value"=>"J_SPDB"),
                array("label"=>"北京农商银行借记卡","value"=>"J_BJRCB"),
                );
	}

	# -- Private functions --
	## After payment is complete, the gateway will invoke this URL asynchronously
	protected function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}
	## After payment is complete, the gateway will send redirect back to this URL
	protected function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}
	## Format the amount value for the API
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	public function sign($data) {
		# made it public for testing purpose
		$dataStr = $this->prepareSign($data);
		$signature = strtoupper(MD5($dataStr . $this->info['key']));
		return $signature;
	}

	# -- private helper functions --
	private function prepareSign($data) {
		if ($data['apiName'] == self::MOBAO_APINAME_PAY         ||
			$data['apiName'] == self::MOBAO_APINAME_QUICKPAY    ||
			$data['apiName'] == self::MOBAO_APINAME_QQPAY_WAP   ||
			$data['apiName'] == self::MOBAO_APINAME_UNIONPAY_WAP||
			$data['apiName'] == self::MOBAO_APINAME_ALIPAY      ||
			$data['apiName'] == self::MOBAO_APINAME_ALIPAY_WAP  ||
			$data['apiName'] == self::MOBAO_APINAME_WEIXIN      ||
			$data['apiName'] == self::MOBAO_APINAME_WEIXIN_WAP  ||
			$data['apiName'] == self::MOBAO_APINAME_QQPAY       ||
			$data['apiName'] == self::MOBAO_APINAME_UNIONPAY    ||
			$data['apiName'] == self::MOBAO_APINAME_JDPAY
			) {
			$result = sprintf(
				"apiName=%s&apiVersion=%s&platformID=%s&merchNo=%s&orderNo=%s&tradeDate=%s&amt=%s&merchUrl=%s&merchParam=%s&tradeSummary=%s",
				$data['apiName'], $data['apiVersion'], $data['platformID'], $data['merchNo'], $data['orderNo'], $data['tradeDate'], $data['amt'], $data['merchUrl'], $data['merchParam'], $data['tradeSummary']
			);

			if(isset($data['authCode'])) {
				$result .= sprintf(
					"&authCode=%s", $data['authCode']
				);
			}

			if(isset($data['customerIP'])) {
				$result .= sprintf(
					"&customerIP=%s", $data['customerIP']
				);
			}
			return $result;
		} else if ($data['apiName'] == 'PAY_RESULT_NOTIFY') {
			$result = sprintf(
				"apiName=%s&notifyTime=%s&tradeAmt=%s&merchNo=%s&merchParam=%s&orderNo=%s&tradeDate=%s&accNo=%s&accDate=%s&orderStatus=%s",
				$data['apiName'], $data['notifyTime'], $data['tradeAmt'], $data['merchNo'], $data['merchParam'], $data['orderNo'], $data['tradeDate'], $data['accNo'], $data['accDate'], $data['orderStatus']
			);
			return $result;
		}
		$array = array();
		foreach ($data as $key => $value) {
			array_push($array, $key . '=' . $value);
		}
		return implode($array, '&');
	}

	private function verify($data, $signature) {
		$mySign = $this->sign($data);
		if (strcasecmp($mySign, $signature) === 0) {
			return true;
		} else {
			return false;
		}
	}

	public function parseResultXML($resultXml) {
		$result = NULL;
		$obj = simplexml_load_string($resultXml);
		$result = $this->CI->utils->xmlToArray($obj);
		return $result;
	}
}