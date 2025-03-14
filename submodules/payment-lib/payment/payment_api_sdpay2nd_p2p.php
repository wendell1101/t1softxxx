<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sdpay2nd.php';

/**
 * SDPay_2ND 速达支付 2ND
 * http://www.sdsystem.hk
 *
 * SDPAY2ND_P2P_PAYMENT_API, ID: 127
 *
 *
 * Required Fields:
 *
 * * URL
 * * Account
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: https://deposit2.sdapayapi.com/9001/ApplyForABank.asmx
 * * Account: The 'ID' value (e.g. twinbet), not the merchant code (e.g. RH000001).
 * * Extra Info
 * > {
 * >     "sdpay_key1": "## RSA key 1 ##",
 * >     "sdpay_key2": "## RSA key 2 ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sdpay2nd_p2p extends Abstract_payment_api_sdpay2nd {
	public function getPlatformCode() {
		return SDPAY2ND_P2P_PAYMENT_API;
	}

	public function getPrefix() {
		return 'sdpay2nd_p2p';
	}

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$req = array(); # Request parameters, needs to be converted into XML and encrypted

		# Prepare request data
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$direct_pay_extra_info = $order->direct_pay_extra_info;
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$req['sBank1'] = $extraInfo['banktype'];
			}
		}

		$req['storeOrderId'] = $order->secure_id;
		# query player name using player ID
		$this->CI->load->model('player');
		$player = $this->CI->player->getPlayerById($playerId);
		if($player) {
			if($player['language'] == 'Chinese'){ # temporarily determine full name using langauge
				$req['sName'] = $player['lastName'].$player['firstName'];
			} else {
				$req['sName'] = $player['firstName'] . ' ' . $player['lastName'];
			}
		}
		$req['sPrice'] = $this->convertAmountToCurrency($amount);
		$req['sPlayersId'] = $orderId;  # Note: We use this playerId field to store the $orderId

		$requestXml =
			"<t_savingApply>".
				"<id>$orderId</id>".
				"<storeOrderId>$req[storeOrderId]</storeOrderId>".
				"<sBank1>$req[sBank1]</sBank1>".
				"<sName>$req[sName]</sName>".
				"<sPrice>$req[sPrice]</sPrice>".
				"<sPlayersId>$req[sPlayersId]</sPlayersId>".
			"</t_savingApply>";
		$this->CI->utils->debug_log("Request XML", $requestXml);

		$params = array();
		$params['LoginAccount'] = $this->getSystemInfo("account");
		$params['GetFundInfo'] = $this->encrypt($requestXml);
		$this->CI->utils->debug_log("Submit params", $params);

		try {
			$soap = new SoapClient($this->getSystemInfo('url').'?wsdl', array('trace' => 1));
			$result = $soap->ApplyBank($params['LoginAccount'], $params['GetFundInfo']);
		} catch (SoapFault $exception) {
			$this->CI->utils->error_log("SoapFault", $exception);
			$success = false;
			$message = "SoapFault";
		}

		$this->CI->utils->debug_log("SoapResult", $result);

		#$result = $this->submitPostForm($this->getSystemInfo('url'), $params);
		#$this->CI->utils->debug_log("Get response XML", $result);

		# If the call fail, $result will be a number
		$message = $this->getErrorMsg($result);
		if($message){
			$success = false;
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR,
				'message' => lang($message),
			);
		} else {
			$resp = $this->decrypt($result['HiddenField1']);

			# $resp is now an array containing key-values
			$compareKeys = array('storeOrderId', 'sPrice', 'sPlayersId');
			$respValid = true;
			foreach($compareKeys as $key) {
				if($resp[$key] != $req[$key]) {
					$this->utils->error_log("Response error in [$key]: expected [$req[$key]], found [$resp[$key]]");
					$message = "[$key] mismatch";
					$respValid = false;
					$success = false;
					$data = array();
					break;
				}
			}

			if($respValid) {
				$bankName = $this->getBankName($resp['eBank']);
				$data['Beneficiary Bank'] = $bankName;
				# Reference: documentation 6.1
				if(strtoupper($resp['eBank']) == 'ABC') {
					$data['Beneficiary Bank Branch'] = $resp['eBank2'];
				}
				if(strtoupper($resp['eBank']) == 'CMB') {
					$data['Beneficiary Bank City'] = $resp['eBank2'];
				}
				#$data['Depositor Name'] = $resp['sName']; # To avoid confusion, this field is hidden from user
				$data['Beneficiary Account'] = @$resp['eBankAccount'];
				$data['Beneficiary Name'] = @$resp['eName'];
				$data['Deposit Amount'] = $resp['ePrice'];
				$data['Email'] = @$resp['email'];

				$paymentLink = $this->getPaymentLink($resp['eBank']);
				if(!empty($paymentLink)) {
					$data['Payment Link'] = "<a href='".$paymentLink."'>".lang('Login to ').$bankName.'</a>';
				}
			}
		}

		return array(
			'success' => $success,
			'type' => self::REDIRECT_TYPE_STATIC,
			'title' => lang('payment.type.'.$this->getPlatformCode()),
			'data' => $data,
		);
	}

	# Reference: Documentation section 3.3.4
	private function getErrorMsg($result){
		$errorMsg = array(
			'-1'  => "_json: { \"1\": \"Unknown case\", \"2\": \"未知原因\"}",
			'-10' => "_json: { \"1\": \"No beneficiary bank\", \"2\": \"无收款银行\"}",
			'-11' => "_json: { \"1\": \"No beneficiary card\", \"2\": \"无收款卡\"}",
			'-12' => "_json: { \"1\": \"Wrong password\", \"2\": \"密钥错误\"}",
			'-13' => "_json: { \"1\": \"Length of login account = 0\", \"2\": \"登录账号长度等于0\"}",
			'-14' => "_json: { \"1\": \"Login account is null\", \"2\": \"登录账号为null\"}",
			'-15' => "_json: { \"1\": \"Receive repeated request\", \"2\": \"申请的玩家同名\"}",
			'-16' => "_json: { \"1\": \"Deposit amount cannot be 0\", \"2\": \"存款金额不能小于等于0元\"}"
		);
		if(array_key_exists($result, $errorMsg)) {
			return $errorMsg[$result];
		}
	}

	public function getPlayerInputInfo() {
		return array(
			array('name' => 'banktype', 'type' => 'list', 'label_lang' => 'pay.bank',
				'list' => $this->getBankList(), 'list_tree' => $this->getBankListTree()),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	/*
	"bank_list": {
		"ABC" : "_json: { \"1\": \"Agricultural Bank of China\", \"2\": \"农业银行\" }",
		"ALIPAY" : "_json: { \"1\": \"Alipay\", \"2\": \"支付宝\" }",
		"BJRCB" : "_json: { \"1\": \"Beijing Rural Commercial Bank\", \"2\": \"北京农商银行\" }",
		"BOB" : "_json: { \"1\": \"Bank of Beijing\", \"2\": \"北京银行\" }",
		"BOC" : "_json: { \"1\": \"Bank of China\", \"2\": \"中国银行\" }",
		"BOD" : "_json: { \"1\": \"Bank of Dongguan\", \"2\": \"东莞银行\" }",
		"BOS" : "_json: { \"1\": \"Bank of Shanghai\", \"2\": \"上海银行\" }",
		"CBHB" : "_json: { \"1\": \"China Bohai Bank\", \"2\": \"渤海银行\" }",
		"CCB" : "_json: { \"1\": \"China Construction Bank\", \"2\": \"建设银行\" }",
		"CEB" : "_json: { \"1\": \"China Everbright Bank\", \"2\": \"光大银行\" }",
		"CIB" : "_json: { \"1\": \"Industrial Bank\", \"2\": \"兴业银行\" }",
		"CITIC" : "_json: { \"1\": \"China CITIC Bank\", \"2\": \"中信银行\" }",
		"CMB" : "_json: { \"1\": \"China Merchants Bank\", \"2\": \"招商银行\" }",
		"CMBC" : "_json: { \"1\": \"China Minsheng Bank\", \"2\": \"民生银行\" }",
		"COMM" : "_json: { \"1\": \"Bank of Communications\", \"2\": \"交通银行\" }",
		"CZB" : "_json: { \"1\": \"China Zheshang Bank\", \"2\": \"浙商银行\" }",
		"GDB" : "_json: { \"1\": \"Guangdong Development Bank\", \"2\": \"广东发展银行\" }",
		"GZB" : "_json: { \"1\": \"Bank of Guangzhou\", \"2\": \"广州银行\" }",
		"HXB" : "_json: { \"1\": \"Hua Xia Bank\", \"2\": \"华夏银行\" }",
		"HZB" : "_json: { \"1\": \"Bank of Hangzhou\", \"2\": \"杭州银行\" }",
		"ICBC" : "_json: { \"1\": \"Industrial and Commercial Bank of China\", \"2\": \"工商银行\" }",
		"NBCB" : "_json: { \"1\": \"Bank of Ningbo\", \"2\": \"宁波银行\" }",
		"PAB" : "_json: { \"1\": \"Ping An Bank\", \"2\": \"平安银行\" }",
		"PSBC" : "_json: { \"1\": \"Postal Savings Bank of China\", \"2\": \"中国邮政储蓄\" }",
		"SDB" : "_json: { \"1\": \"Shenzhen Development Bank\", \"2\": \"深圳发展银行\" }",
		"SPDB" : "_json: { \"1\": \"Shanghai Pudong Development Bank\", \"2\": \"浦发银行\" }",
		"WXPAY" : "_json: { \"1\": \"Wechat Pay\", \"2\": \"微信支付\" }"
	}
	*/

	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	## Reference: sample code, callback.php
	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	## This will be called when user redirects back to our page from payment API
	public function callbackFromBrowser($orderId, $params) {
		$this->CI->utils->error_log("Error: browser callback not supported");
		return;
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		# Decrypt callback info
		$encryptedData = $params['HiddenField1'];
		$params = $this->decrypt($encryptedData);

		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			return array('success' => false, 'return_error' => $processed ? self::RETURN_SUCCESS_CODE : '');
		}

		# Update order payment status and balance
		$success=true;
		// $this->CI->sale_order->startTrans();

		# Update player balance based on order status
		# if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
		$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
		if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
			$this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
			if ($order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
				$this->CI->sale_order->setStatusToSettled($orderId);
			}
		} else {
			# update player balance
			$this->CI->sale_order->updateExternalInfo($order->id,
				'', '',
				null, null, $response_result_id);
			$this->CI->sale_order->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
		}
		// $success = $this->CI->sale_order->endTransWithSucc();

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : '';
		}

		return $result;
	}

	# Returns the link that is used to directly go to the bank's webpage
	# Configure in extra_info, e.g. "icbc_url" : "http://www.icbc.com"
	private function getPaymentLink($bankCode) {
		return $this->getSystemInfo(strtolower($bankCode).'_url');
	}
}
