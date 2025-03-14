<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * IPS 环迅支付 - 2016新版 （V0.3.13）
 * http://www.ips.com.cn/
 *
 * * IPS2016_PAYMENT_API - 184
 * * IPS2016_ALIPAY_PAYMENT_API - 185
 * * IPS2016_WEIXIN_PAYMENT_API - 186
 *
 * Required Fields:
 *
 * * URL
 * * Key - Merchant ID
 * * Account - Merchant Account Number
 * * Secret - MD5 key
 *
 * Field Values:
 *
 * * URL
 * 		- Bank: https://newpay.ips.com.cn/psfp-entry/gateway/payment.do
 * 		- ScanPay: https://thumbpay.e-years.com/psfp-webscan/services/scan?wsdl
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_ips2016 extends Abstract_payment_api {
	const GATEWAY_TYPE_DEBIT_CARD = '01';
	const GATEWAY_TYPE_ALIPAY = '11';
	const GATEWAY_TYPE_WEIXIN = '10';

	const CURR_TYPE_RMB = '156';
	const LANG_CHINESE = 'GB';

	const ORDER_ENCODE_TYPE_MD5 = '5';
	const RET_ENCODE_TYPE_MD5 = '17';
	const RET_TYPE_SERVER = '1';

	const ORDER_EXPIRE_HOURS = 1;

	const PAYMENT_STATUS_SUCCESS = 'Y';

	# Ref: Documentation section 4.13
	const RETURN_SUCCESS_CODE = 'ipscheckok';
	const RSP_CODE_SUCCESS = '000000';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implemented by sub-class for payment method specific params
	protected abstract function configParams(&$params, $orderId, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($data);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$orderDate = $orderDateTime->format('Ymd');
		$curr = 'RMB';
		$currType = '156';	# code for RMB
		$gatewayType = '01'; //china card
		$lang = 'GB'; // chinese
		$merchantUrl = $this->getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
		$failedUrl = $this->getCallbackUrl('/callback/browser/failed/' . $this->getPlatformCode() . '/' . $orderId);
		//FailUrl is not work
		$callbackUrl = $this->getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);

		$billno = $orderId;
		if ($orderId) {
			$ord = $this->CI->sale_order->getSaleOrderById($orderId);
			$billno = $ord->secure_id;
		}

		# Prepare params, ref: IPS(v0.3.4).pdf, section 4.2.3
		$params['MerBillNo'] = $order->secure_id;
		$params['GatewayType'] = self::GATEWAY_TYPE_DEBIT_CARD;
		$params['Date'] = $orderDateTime->format('Ymd');
		$params['CurrencyType'] = self::CURR_TYPE_RMB;
		$params['Amount'] = $this->convertAmountToCurrency($amount);
		$params['Lang'] = self::LANG_CHINESE;
		$params['RetEncodeType'] = self::RET_ENCODE_TYPE_MD5;
		$params['ServerUrl'] = $this->getNotifyUrl($orderId);
		$params['BillEXP'] = self::ORDER_EXPIRE_HOURS;
		$params['GoodsName'] = 'Deposit';

		$this->configParams($params, $orderId, $order->direct_pay_extra_info);

		# build the prepared params into XML
		$xmlDoc = new DOMDocument('1.0', 'UTF-8');
		$ips = $xmlDoc->appendChild($xmlDoc->createElement("Ips"));
		$gatewayReq = $ips->appendChild($xmlDoc->createElement("GateWayReq"));
		$body = $this->createXmlReqBody($xmlDoc, $params);
		$head = $this->createXmlHeader($xmlDoc, $this->getSignMD5($xmlDoc->saveXML($body)));
		$gatewayReq->appendChild($head);
		$gatewayReq->appendChild($body);

		return $this->processPaymentUrlForm($xmlDoc->saveXML());
	}

	protected function processPaymentUrlPost($xml){
		$postParams['pGateWayReq'] = str_replace("\n", "", $xml);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $postParams,
			'post' => true
		);
	}

	protected function processPaymentUrlQRCode($xml) {
		$xml = str_replace("\n", "", $xml);
		$soapRet = $this->soapCallScanPay($xml);
		if(!$soapRet) {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR,
				'message' => lang("Payment failed - SOAP call failed"),
			);
		}

		$soapRetResult = $this->verifySoapReturn($soapRet);

		if($soapRetResult['success']) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $soapRetResult['message'],
			);
		} else {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR,
				'message' => $soapRetResult['message'],
			);
		}
	}

	# Ref: Demo code, IpsPayRequest.class.php
	private function soapCallScanPay($data) {
        try {
            $this->utils->debug_log("Invoking SoapClient, url, data:", $this->getSystemInfo('url'), $data);
            $client = new SoapClient($this->getSystemInfo('url'));
            $sReqXml = $client->scanPay($data);
            $this->utils->debug_log("Soap call returns ", $sReqXml);
            return $sReqXml;
        } catch (Exception $e) {
            $this->utils->error_log("Exception in soap call", $e->getMessage());
        }
       return null;
    }

	private function createXmlHeader($xmlDoc, $signature) {
		# List out parameters for header
		$version = "v1.0.0";
		$merCode = $this->getSystemInfo('key');
		$account = $this->getSystemInfo('account');
		$msgId = time(); # unique identifier for this request XML
		$reqDate = date('YmdHis'); # Format: yyyyMMddHHmmss

		$xmlHead = $xmlDoc->createElement("head");
		$xmlHead->appendChild($xmlDoc->createElement("Version", $version));
		$xmlHead->appendChild($xmlDoc->createElement("MerCode", $merCode));
		#$xmlHead->appendChild($xmlDoc->createElement("MerName", '')); # empty and not needed
		$xmlHead->appendChild($xmlDoc->createElement("Account", $account));
		$xmlHead->appendChild($xmlDoc->createElement("MsgId", $msgId));
		$xmlHead->appendChild($xmlDoc->createElement("ReqDate", $reqDate));
		$xmlHead->appendChild($xmlDoc->createElement("Signature", $signature));
		return $xmlHead;
	}

	private function createXmlReqBody($xmlDoc, $params) {
		$xmlBody = $xmlDoc->createElement("body");
		foreach($params as $name => $val) {
			# For tags whose name contains 'url', we use CDATA as its text value
			if(strpos(strtolower($name), 'url') !== false) {
				$xmlBody->appendChild($cdataNode = $xmlDoc->createElement($name));
				$cdataNode->appendChild($xmlDoc->createCDATASection($val));
			} else {
				$xmlBody->appendChild($xmlDoc->createElement($name, $val));
			}
		}
		return $xmlBody;
	}


	public function callbackFromServer($orderId, $params) {
		$callbackExtraInfo = $this->decodeNewpayCallbackExtraInfo($callbackExtraInfo);
		if(!$callbackExtraInfo) {
			return array('success' => false, 'message' => lang('Invalid callback received'));
		}

		$response_result_id = parent::callbackFromServer($orderId, $callbackExtraInfo);
		return $this->callbackFrom('server', $orderId, $callbackExtraInfo, $response_result_id);
	}

	public function callbackFromBrowser($orderId, $params) {
		$callbackExtraInfo = $this->decodeNewpayCallbackExtraInfo($callbackExtraInfo);
		if(!$callbackExtraInfo) {
			return array('success' => false, 'message' => lang('Invalid callback received'));
		}

		$response_result_id = parent::callbackFromBrowser($orderId, $callbackExtraInfo);
		return $this->callbackFrom('browser', $orderId, $callbackExtraInfo, $response_result_id);
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
		$paymentSuccessful = $this->checkCallbackOrder($order, $params); # $callbackValid is also assigned

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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['IpsBillNo'], $params['BankBillNo'], null, null, $response_result_id);
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

	# Convert the returned info from newpay (in XML) into the format supported by original workflow
	# ref: IPS(v0.3.4).pdf, section 4.3.2
	public function decodeNewpayCallbackExtraInfo($param) {
		# Get the return XML string
		$xml = $param['paymentResult'];
		$this->utils->debug_log("Decoding return XML", $xml);

		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML($xml);

		$head = $xmlDoc->getElementsByTagName('head')[0];
		$body = $xmlDoc->getElementsByTagName('body')[0];

		$retParam = array();

		# Parse the return info in XML into array
		# From head
		$retParam['RspCode'] = $head->getElementsByTagName("RspCode")->item(0)->nodeValue;
		$retParam['RspMsg'] = $head->getElementsByTagName("RspMsg")->item(0)->nodeValue;
		$retParam['signature'] = $head->getElementsByTagName("Signature")->item(0)->nodeValue;

		if($retParam['RspCode'] != self::RSP_CODE_SUCCESS) {
			$this->writePaymentErrorLog('payment is not successful', $retParam);
			return false;
		}

		# From body
		$retParam['MerBillNo'] = $body->getElementsByTagName("MerBillNo")->item(0)->nodeValue;
		$retParam['CurrencyType'] = $body->getElementsByTagName("CurrencyType")->item(0)->nodeValue;
		$retParam['Amount'] = $body->getElementsByTagName("Amount")->item(0)->nodeValue;
		$retParam['Date'] = $body->getElementsByTagName("Date")->item(0)->nodeValue;
		$retParam['Status'] = $body->getElementsByTagName("Status")->item(0)->nodeValue;
		$retParam['IpsBillNo'] = $body->getElementsByTagName("IpsBillNo")->item(0)->nodeValue;
		$retParam['BankBillNo'] = $body->getElementsByTagName("BankBillNo")->item(0)->nodeValue;
		$retParam['RetEncodeType'] = $body->getElementsByTagName("RetEncodeType")->item(0)->nodeValue;

		# Verify signature
		$sign = $this->getSignMD5($xmlDoc->saveXML($body));
		if(strcasecmp($sign, $retParam['signature']) === 0) {
			return $retParam;
		} else {
			$this->writePaymentErrorLog('return param signaure is wrong', $retParam);
			return false;
		}
	}

	# Verifies Soap response, and returns ['success' => x, 'message' => x]
	private function verifySoapReturn($xml) {
		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML($xml);

		$head = $xmlDoc->getElementsByTagName('head')[0];
		$body = $xmlDoc->getElementsByTagName('body')[0];

		$retParam = array();

		# Parse the return info in XML into array
		# From head
		$retParam['RspCode'] = $head->getElementsByTagName("RspCode")->item(0)->nodeValue;
		$retParam['RspMsg'] = $head->getElementsByTagName("RspMsg")->item(0)->nodeValue;
		$retParam['signature'] = $head->getElementsByTagName("Signature")->item(0)->nodeValue;

		if($retParam['RspCode'] != self::RSP_CODE_SUCCESS) {
			$this->writePaymentErrorLog('payment is not successful', $retParam);
			return array('success' => false, 'message' => $retParam['RspMsg']);
		}

		# From body
		$retParam['QrCode'] = $body->getElementsByTagName("QrCode")->item(0)->nodeValue;

		# Verify signature
		$sign = $this->getSignMD5($xmlDoc->saveXML($body));
		if(strcasecmp($sign, $retParam['signature']) === 0) {
			$this->utils->debug_log('SoapReturn verified', $retParam);
			return array('success' => true, 'message' => $retParam['QrCode']);
		} else {
			$this->writePaymentErrorLog('SoapReturn signaure is wrong', $retParam);
			return array('success' => false, 'message' => 'Invalid response - wrong signature');
		}
	}

	private function checkCallbackOrder($order, $params) {
		# does all required fields exist?
		$requiredFields = array(
			'Status', 'Amount', 'MerBillNo'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $params)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $params);
				return false;
			}
		}

		## Signature already verified
		$callbackValid = true;

		if ($params['Status'] != self::PAYMENT_STATUS_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $params);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $params['Amount']) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $params);
			return false;
		}

		if ($params['MerBillNo'] != $order->secure_id) {
			$this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $params);
			return false;
		}

		# everything checked ok
		return true;
	}

	protected function getBankListInfoFallback() {
		return array(
			array('label' => '工商银行', 'value' => '1100'),
			array('label' => '农业银行', 'value' => '1101'),
			array('label' => '招商银行', 'value' => '1102'),
			array('label' => '兴业银行', 'value' => '1103'),
			array('label' => '中信银行', 'value' => '1104'),
			array('label' => '建设银行', 'value' => '1106'),
			array('label' => '中国银行', 'value' => '1107'),
			array('label' => '交通银行', 'value' => '1108'),
			array('label' => '浦发银行', 'value' => '1109'),
			array('label' => '民生银行', 'value' => '1110'),
			array('label' => '华夏银行', 'value' => '1111'),
			array('label' => '光大银行', 'value' => '1112'),
			array('label' => '北京银行', 'value' => '1113'),
			array('label' => '广发银行', 'value' => '1114'),
			array('label' => '南京邮政', 'value' => '1115'),
			array('label' => '上海银行', 'value' => '1116'),
			array('label' => '杭州银行', 'value' => '1117'),
			array('label' => '宁波银行', 'value' => '1118'),
			array('label' => '邮储银行', 'value' => '1119'),
			array('label' => '浙商银行', 'value' => '1120'),
			array('label' => '平安银行', 'value' => '1121'),
			array('label' => '东亚银行', 'value' => '1122'),
			array('label' => '渤海银行', 'value' => '1123'),
			array('label' => '北京农商银行', 'value' => '1124'),
			array('label' => '浙江泰隆商业银行', 'value' => '1127'),
		);
	}

	public function directPay($order) {
		//no direct pay
		return array('success' => false);
	}

	# After payment is complete, the gateway will invoke this URL asynchronously
	protected function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## After payment is complete, the gateway will send redirect back to this URL
	protected function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## If payment fail, the gateway will send redirect back to this URL
	protected function getReturnUrlFail($orderId) {
		return parent::getCallbackUrl('/callback/browser/fail/' . $this->getPlatformCode() . '/' . $orderId);
	}

	# -- Private functions --
	# 	# Ref: Documentation section 4.1.3
	# 数字签名验证（<body>节点的 xml 文本值+商户号+商户数字证书）
	private function getSignMD5($bodyXml) {
		$signStr = $bodyXml.$this->getSystemInfo("key").$this->getSystemInfo("secret");
		return strtolower(md5($signStr));
	}

	private function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

}
