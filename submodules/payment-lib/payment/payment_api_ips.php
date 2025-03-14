<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * IPS 环迅支付
 * http://www.ips.com.cn/
 *
 * IPS_PAYMENT_API, ID: 4
 *
 * Required Fields:
 *
 * * URL
 * * Key
 * * Account (for newpay)
 * * Secret
 * * Extra Info
 *
 * Field Values:
 *
 * * Sandbox URL: http://pay.ips.net.cn/ipayment.aspx
 * * Live URL: https://pay.ips.com.cn/ipayment.aspx
 * * Extra Info
 * > {
 * >     "ips_use_newpay" : "1",
 * >     "ips_newpay_url" : "https://newpay.ips.com.cn/psfp-entry/gateway/payment.do",
 * >     "ips_expiry_hours" : 2
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ips extends Abstract_payment_api {

	const RET_ENCODE_TYPE_MD5 = '17';
	const ORDER_ENCODE_TYPE_MD5 = '5';
	const RET_TYPE_SERVER = '1';

	const CALLBACK_FIELD_EXTERNAL_ORDER_ID = 'ipsbillno';
	const CALLBACK_FIELD_BANK_ORDER_ID = 'bankbillno';

	private $isNewpay = false;

	public function __construct($params = null) {
		parent::__construct($params);

		$this->isNewpay = ($this->getSystemInfo("ips_use_newpay") == "1");
	}

	public function getPlatformCode() {
		return IPS_PAYMENT_API;
	}

	public function getPrefix() {
		return 'ips';
	}

	protected function getDateNow() {
		return date('Ymd');
	}

	protected function getSignMD5($str) {
		return strtolower(md5($str));
	}

	# Ref: Documentation section 4.1.3
	# 数字签名验证（<body>节点的 xml 文本值+商户号+商户数字证书）
	protected function getNewpaySignMD5($bodyXml) {
		$signStr = $bodyXml.$this->getSystemInfo("key").$this->getSystemInfo("secret");
		return strtolower(md5($signStr));
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	//====implements Payment_api_interface start===================================
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		$amountNum = $amount;
		$amount = $this->convertAmountToCurrency($amount);
		$info = $this->getInfoByEnv();
		$this->CI->utils->debug_log('info', $info);
		if ($this->shouldRedirect($enabledSecondUrl)) {
			//disable second url
			$url = $this->CI->utils->getPaymentUrl($info['second_url'], $this->getPlatformCode(), $amountNum, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$this->CI->load->model(array('bank_list', 'sale_order'));
		$bankCode = $this->CI->bank_list->getBankTypeCodeById($bankId);
		$this->CI->utils->debug_log('bankId', $bankId, 'bankCode', $bankCode);
		// $bankCode = null; // '00124';

		$orderDate = $orderDateTime->format('Ymd'); //$this->getDateNow();
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

		$orderEncodeType = self::ORDER_ENCODE_TYPE_MD5;
		$retEncodeType = self::RET_ENCODE_TYPE_MD5;
		$retType = self::RET_TYPE_SERVER;

		$billExp = $this->getSystemInfo('ips_expiry_hours');
		$goodsName = lang('pay.deposit');

		if (!$this->isNewpay) {
			$params = array('Billno' => $billno, "Mer_code" => $info['key'], "Amount" => $amount, 'Date' => $orderDate,
				'Currency_Type' => $curr, 'Gateway_Type' => $gatewayType, 'Lang' => $lang,
				'Merchanturl' => $merchantUrl, 'FailUrl' => $failedUrl, 'ErrorUrl' => $failedUrl, 'Attach' => $playerId,
				'OrderEncodeType' => $orderEncodeType, 'RetEncodeType' => $retEncodeType,
				'Rettype' => $retType, 'ServerUrl' => $callbackUrl);

			if (!empty($bankCode)) {
				$params['DoCredit'] = '1';
				$params['Bankco'] = $bankCode;
			}

			// $orge = 'billno'.$Billno.'currencytype'.$Currency_Type.'amount'.$Amount.'date'.$Date.'orderencodetype'.$OrderEncodeType.$Mer_key ;
			// string SignMD5 = System.Web.Security.FormsAuthentication.HashPasswordForStoringInConfigFile(
			// "billno" + Billno + "currencytype" + Currency_Type + "amount" + Amount + "date" + BillDate + "orderencodetype" + OrderEncodeType + Mer_key, "MD5").ToLower();
			$org = 'billno' . $billno . 'currencytype' . $curr . 'amount' . $amount . 'date' . $orderDate . 'orderencodetype' . $orderEncodeType . $info['secret'];
			// log_message('debug', 'org:' . $org);
			$this->CI->utils->debug_log('org', $org, 'params', $params);
			$signMD5 = $this->getSignMD5($org);
			$params['SignMD5'] = $signMD5;
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_FORM, 'url' => $info['url'], 'params' => $params, 'post' => true);
			return $result;
		}
		else {
			# Prepare params, ref: IPS(v0.3.4).pdf, section 4.2.3
			$newpayParams['MerBillNo'] = $billno;
			$newpayParams['GatewayType'] = $gatewayType;
			$newpayParams['Date'] = $orderDate;
			$newpayParams['CurrencyType'] = $currType;
			$newpayParams['Amount'] = $amount;
			$newpayParams['Lang'] = $lang;
			$newpayParams['Merchanturl'] = $merchantUrl;
			$newpayParams['FailUrl'] = $failedUrl;
			$newpayParams['OrderEncodeType'] = $orderEncodeType;
			$newpayParams['RetEncodeType'] = $retEncodeType;
			$newpayParams['RetType'] = $retType;
			$newpayParams['ServerUrl'] = $callbackUrl;
			$newpayParams['BillEXP'] = $billExp;
			$newpayParams['GoodsName'] = $goodsName;

			if (!empty($bankCode)) {
				$newpayParams['IsCredit'] = '0'; # Set to 1 will get an error: 该产品不支持直连！
				$newpayParams['BankCode'] = $bankCode;
				$newpayParams['ProductType'] = '1';
			}

			# Newpay uses XML. build the prepared params into XML
			$xmlDoc = new DOMDocument('1.0', 'UTF-8');
			$ips = $xmlDoc->appendChild($xmlDoc->createElement("Ips"));
			$gatewayReq = $ips->appendChild($xmlDoc->createElement("GateWayReq"));
			$body = $this->createXmlReqBody($xmlDoc, $newpayParams);
			$head = $this->createXmlHeader($xmlDoc, $this->getNewpaySignMD5($xmlDoc->saveXML($body)));
			$gatewayReq->appendChild($head);
			$gatewayReq->appendChild($body);

			$params['pGateWayReq'] = str_replace("\n", "", $xmlDoc->saveXML());

			$this->utils->debug_log("Params: ", $params);
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_FORM,
				'url' => $this->getSystemInfo('ips_newpay_url'),
				'params' => $params,
				'post' => true
			);
		}

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

	public function callbackFromServer($orderId, $callbackExtraInfo) {
		if ($this->isNewpay) {
			$callbackExtraInfo = $this->decodeNewpayCallbackExtraInfo($callbackExtraInfo);
			if(!$callbackExtraInfo) {
				return array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
			}
		}

		//must call
		$response_result_id = parent::callbackFromServer($orderId, $callbackExtraInfo);

		$rlt = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		//query order
		$ord = $this->CI->sale_order->getSaleOrderById($orderId);
		if ($ord) {
			if ($this->checkCallbackOrder($ord, $callbackExtraInfo)) {
				$success = true;
				$extra_info=null;

				// $this->CI->sale_order->startTrans();
				//save to player balance
				//check order status, if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
				$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
				if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
					$this->CI->utils->debug_log('callbackFromServer, already get callback for order:' . $ord->id, $callbackExtraInfo);
					if ($ord->status == Sale_order::STATUS_BROWSER_CALLBACK) {
						$this->CI->sale_order->setStatusToSettled($orderId);
					}
				} else {
					// $this->CI->sale_order->setStatusToSettled($orderId);
					//update balance once
					$ord->external_order_id = $callbackExtraInfo[self::CALLBACK_FIELD_EXTERNAL_ORDER_ID];
					$ord->response_result_id = $response_result_id;
					$this->CI->sale_order->updateExternalInfo($ord->id, $callbackExtraInfo[self::CALLBACK_FIELD_EXTERNAL_ORDER_ID],
						$callbackExtraInfo[self::CALLBACK_FIELD_BANK_ORDER_ID], null, null, $response_result_id);

					// $locked=$this->CI->lockActionById($orderId, 'approve');
					// if($locked){
					$success = $this->approveSaleOrder($ord->id, 'auto server callback ' . $this->getPlatformCode(), false, $extra_info);
					// }

				}
				// $success = $this->CI->sale_order->endTransWithSucc() && $success;

				$this->processStandaloneTrans($extra_info);

				// $rlt['message'] = $this->CI->load->view('payment/ips/success', ['transaction' => $transaction, 'callbackExtraInfo' => $callbackExtraInfo, 'promo' => $promo_transaction], true);
				$rlt['success'] = $success;
				$rlt['next_url'] = $this->getPlayerBackUrl();
			}
		}
		return $rlt;
	}

	public function callbackFromBrowser($orderId, $callbackExtraInfo) {
		if ($this->isNewpay) {
			$callbackExtraInfo = $this->decodeNewpayCallbackExtraInfo($callbackExtraInfo);
			if(!$callbackExtraInfo) {
				return array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
			}
		}

		//must call
		$response_result_id = parent::callbackFromBrowser($orderId, $callbackExtraInfo);

		$rlt = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		//query order
		$ord = $this->CI->sale_order->getSaleOrderById($orderId);
		if ($ord) {
			if ($this->checkCallbackOrder($ord, $callbackExtraInfo)) {
				$success = true;

				// $this->CI->sale_order->startTrans();

				$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
				//save to player balance
				//check order status, if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
				if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
					$this->CI->utils->debug_log('callbackFromBrowser, already get callback for order:' . $ord->id, $callbackExtraInfo);
				} else {
					// $this->CI->sale_order->setStatusToBrowserCallback($orderId);
					//update balance once
					$ord->external_order_id = $callbackExtraInfo['ipsbillno'];
					$ord->response_result_id = $response_result_id;
					$this->CI->sale_order->updateExternalInfo($ord->id, $callbackExtraInfo['ipsbillno'], $callbackExtraInfo['bankbillno'], null, null, $response_result_id);
					$success = $this->CI->sale_order->browserCallbackSaleOrder($ord->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
				}

				// $success = $this->CI->sale_order->endTransWithSucc();

				// $rlt['message'] = $this->CI->load->view('payment/ips/success', ['transaction' => $transaction, 'callbackExtraInfo' => $callbackExtraInfo, 'promo' => $promo_transaction], true);
				$rlt['order_id'] = $ord->id;
				$rlt['success'] = $success;
				$rlt['next_url'] = $this->getPlayerBackUrl();
			}
		}
		return $rlt;
	}

	# Convert the returned info from newpay (in XML) into the format supported by original workflow
	# ref: IPS(v0.3.4).pdf, section 4.3.2
	public function decodeNewpayCallbackExtraInfo($param) {
		$info = $this->getInfoByEnv();
		# Get the return XML string
		$xml = $param['paymentResult'];
		$this->utils->debug_log("Decoding return XML", $xml);

		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML($xml);

		$head = $xmlDoc->getElementsByTagName('head')[0];
		$body = $xmlDoc->getElementsByTagName('body')[0];

		$retParam = array();
		# Convert the return info in XML to the following params:
		# ipsbillno, bankbillno, succ, billno, Currency_type, amount, date, retencodetype, signature, mercode
		# RspCode, RspMsg

		# From head
		$retParam['RspCode'] = $head->getElementsByTagName("RspCode")->item(0)->nodeValue;
		$retParam['RspMsg'] = $head->getElementsByTagName("RspMsg")->item(0)->nodeValue;
		$retParam['signature'] = $head->getElementsByTagName("Signature")->item(0)->nodeValue;

		# From body
		$retParam['billno'] = $body->getElementsByTagName("MerBillNo")->item(0)->nodeValue; # billno = order->secure_id
		$retParam['Currency_type'] = $body->getElementsByTagName("CurrencyType")->item(0)->nodeValue;
		$retParam['amount'] = $body->getElementsByTagName("Amount")->item(0)->nodeValue;
		$retParam['date'] = $body->getElementsByTagName("Date")->item(0)->nodeValue;
		$retParam['succ'] = $body->getElementsByTagName("Status")->item(0)->nodeValue; # Y-交易成功；N-交易失败；P-交易处理中
		$retParam['ipsbillno'] = $body->getElementsByTagName("IpsBillNo")->item(0)->nodeValue;
		$retParam['bankbillno'] = $body->getElementsByTagName("BankBillNo")->item(0)->nodeValue;
		$retParam['retencodetype'] = $body->getElementsByTagName("RetEncodeType")->item(0)->nodeValue;
		$retParam['mercode'] = $info['key'];

		# Verify signature
		$sign = $this->getNewpaySignMD5($xmlDoc->saveXML($body));
		if(strcmp($sign, $retParam['signature']) === 0) {
			return $retParam;
		} else {
			$this->writePaymentErrorLog('signaure is wrong', $retParam);
			return false;
		}
	}

	//====implements Payment_api_interface end===================================

	/**
	 *
	 *
	 *
	 * @return array (success=>boolean, message=>string)
	 */
	private function checkCallbackOrder($ord, $flds) {
		//check succ='n'
		$success = strtolower(@$flds['succ']) != 'n';
		if (!$success) {
			$this->writePaymentErrorLog('succ is n', $flds);
		}

		$info = $this->getInfoByEnv();

		if ($success && !$this->isNewpay) { # For newpay, signature is validated differently in decodeNewpayCallbackExtraInfo
			//check signature
			$signStr = 'billno' . $flds['billno'] . 'currencytype' . $flds['Currency_type'] . 'amount' . $flds['amount'] . 'date' . $flds['date'] . 'succ' . $flds['succ'] . 'ipsbillno' . $flds['ipsbillno'] . 'retencodetype' . $flds['retencodetype'] . $info['secret'];
			$signature = $flds['signature'];
			log_message('debug', 'signStr:' . $signStr . ' md5:' . $signature . ' new md5:' . $this->getSignMD5($signStr));
			$success = $this->getSignMD5($signStr) == $signature;
			if (!$success) {
				$this->writePaymentErrorLog('signaure is wrong', $flds);
			}
		}
		if ($success) {
			//check amount, order id, mercode
			$success = $ord->amount == floatval($flds['amount']);
			if ($success) {
				$success = $ord->secure_id == $flds['billno'];
				if ($success) {
					$success = $info['key'] == $flds['mercode'];
					if ($success) {
					} else {
						$this->writePaymentErrorLog('mercode is wrong', $flds);
					}
				} else {
					$this->writePaymentErrorLog('order id is wrong', $flds);
				}

			} else {
				$this->writePaymentErrorLog('amount is wrong', $flds);
			}
		}
		return $success;
	}

	public function directPay($order) {
		//no direct pay
		return array('success' => false);
	}
}

////END OF FILE//////////////////