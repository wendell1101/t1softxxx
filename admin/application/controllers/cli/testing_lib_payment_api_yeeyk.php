<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_yeeyk extends BaseTesting {

	private $platformCode = YEEYK_PAYMENT_API;
	private $api = null;

	# overload parent functions
	public function init() {
		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($this->platformCode);
		//$this->load->helper('date');
		//$this->load->model('wallet_model');
		$this->test($loaded, true, 'Is API class loaded. Expected: true');
		if (!$loaded) {
			$this->utils->debug_log("Error: API not loaded, platformCode = " . $this->platformCode);
			return;
		}

		$this->utils->debug_log("test apiClassName"  . $apiClassName);
		$this->test($apiClassName, 'payment_api_yeeyk', 'Test loaded API\'s class name. Expected: payment_api_yeeyk');
		$this->utils->debug_log("before api assign");
		$this->api = $this->$apiClassName;
		$this->utils->debug_log("after api assign");
		$this->test($this->api->getPlatformCode(), $this->platformCode, 'Test loaded API\'s platform code. Expected: ' . $this->platformCode);
		$this->test($this->api->getPrefix(), 'yeeyk', 'Test loaded API\'s prefix. Expected: yeeyk');
	}

	## all tests route through this function
	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	# Actual Tests
	## Invokes all tests defined below. A test function's name should begin with 'test'
	public function testAll() {
		$classMethods = get_class_methods($this);
		$excludeMethods = array('test', 'testTarget', 'testAll');
		foreach ($classMethods as $method) {
			if (strpos($method, 'test') !== 0 || in_array($method, $excludeMethods)) {
				continue;
			}

			$this->$method();
		}
	}

	private function testgeneratePaymentUrlForm() {
		
	}

	private function testCreateUrlAndCallback() {
		$this->CI = &get_instance();
		//ecoh 'test';
		//$player = $this->getFirstPlayer();
		//$playerId = $player->playerId;
		$playerId = 56964;
		$directpayextrainfo = '{"bankTypeId":"46","deposit_from":"169","cardCode":"MOBILE","cardAmt":"20.00","cardNo":"179551106316278030","cardPwd":"123","sub_wallet_id":"","promo_cms_id":"0"}';
		/*
$playerId, $amount, $player_promo_id = null, $directPayExtraInfo = null,
			$subWalletId=null, $group_level_id=null, $is_mobile = null,
			$player_deposit_reference_no = null, $deposit_time = null,
			$promo_info=null		
		 */
		$orderId = $this->api->createSaleOrder($playerId, 20.00, null, $directpayextrainfo, null, null, 0, null);
		$this->CI->utils->debug_log($orderId);
		$params = array(
			"bizType" => "PROFESSION",
			"result" => 'SUCCESS',
			"merchantNo" => '67001000024',
			"merchantOrderNo" => $orderId,
			"successAmount" => '20.00',
			"cardCode" => 'MOBILE',
			"noticeType" => "2",
			"extInfo" => 'player',
			"cardNo" => "179551106316278030",
			"cardStatus" => "0",
			"cardReturnInfo" => "",
			"cardIsbalance" => "true",
			"cardBalance" => "0.00",
			"cardSuccessAmount" => "20.00",
			"hmac" => ""
		);


		## Simulate the params returned from yeeyk gateway
		## Reference: documentation, section 3.3.2
		## $bizType, $result, $merchantNo, $merchantOrderNo, $successAmount, $cardCode, $noticeType, $extInfo,
		## $cardNo, $cardStatus, $cardReturnInfo, $cardIsbalance, $cardBalance, $cardSuccessAmount
		$params["hmac"] = $this->api->getCallbackHmacString($params["bizType"], $params["result"], $params["merchantNo"], $params["merchantOrderNo"], $params["successAmount"], $params["cardCode"], $params["noticeType"], $params["extInfo"], $params["cardNo"], $params["cardStatus"], $params["cardReturnInfo"], $params["cardIsbalance"], $params["cardBalance"], $params["cardSuccessAmount"]);
		$this->CI->utils->debug_log('params', var_export($params, true));
		$this->CI->utils->debug_log('start callback from server');
		$result = $this->api->callbackFromServer($orderId, $params);
		$this->CI->utils->debug_log('callbackFromServer result: ', json_encode($result));
		$this->CI->utils->debug_log('end callback from server');
		$this->test($result['success'], true, "Test API callbackFromServer function. Expected: success = true");
		/*
		$transactionCode = 'W0000009454855936'; # Enter a transactionCode form walletaccount, status must be wait_API
		$walletAccount = $this->wallet_model->getWalletAccountByTransactionCode($transactionCode);
		$transactionDetail = $this->payment->getWithdrawalTransactionDetail($walletAccount['walletAccountId']);

		# Payment fail
		$params = array(
			"orderNum" => "201001084",
			"merchantOrderNum" => $transactionCode,
			"merchantName" => "测试一",
			"inAcctNum" => $transactionDetail[0]['bankAccountNumber'],
			"inAcctName" => $transactionDetail[0]['bankAccountFullName'],
			"amount" => $transactionDetail[0]['amount'],
			"inBankName" => "中国兴业银行",
			"orderStatus" => "3",  # Payment failed status
			"createTime" => "1421934379000",
			"lastUpdateTime" => "1421934379000",
			"currentAmount" => "99.99",
			"currencyCode" => "RMB",
			"totalFee" => "6.00",
			"commission" => "5.00",
			"charges" => "1.00",
			"orderOrigin" => "1",
			"failReason" => ""
		);
		$paramsRaw = array(
			'callBackMsg' => json_encode($params)
		);

		$result = $this->api->callbackFromServer($transactionCode, $paramsRaw);
		$this->test($result['success'], true,
			"Test API callbackFromServer function. Expected: success = true. Manually check withdrawal status for [$transactionCode], expected: declined");
			*/
/*
		# Order Numbers do not match
		$params = array(
			"orderNum" => "201001084",
			"merchantOrderNum" => "xxx", # incorrect order number
			"merchantName" => "测试一",
			"inAcctNum" => "622908398189586615",
			"inAcctName" => "陈能亮",
			"amount" => $amount,
			"inBankName" => "中国兴业银行",
			"orderStatus" => "2",
			"createTime" => "1421934379000",
			"lastUpdateTime" => "1421934379000",
			"currentAmount" => "99.99",
			"currencyCode" => "RMB",
			"totalFee" => "6.00",
			"commission" => "5.00",
			"charges" => "1.00",
			"orderOrigin" => "1",
			"failReason" => ""
		);

		$result = $this->api->callbackFromServer($orderId, $params);
		$this->test($result['success'], false,
			'Test API callbackFromServer function with unmatching order numbers. Expected: success = false');

		# Payment amounts do not match
		$params = array(
			"orderNum" => "201001084",
			"merchantOrderNum" => $order->secure_id,
			"merchantName" => "测试一",
			"inAcctNum" => "622908398189586615",
			"inAcctName" => "陈能亮",
			"amount" =>  "1000.00",  # incorrect amount
			"inBankName" => "中国兴业银行",
			"orderStatus" => "2",
			"createTime" => "1421934379000",
			"lastUpdateTime" => "1421934379000",
			"currentAmount" => "99.99",
			"currencyCode" => "RMB",
			"totalFee" => "6.00",
			"commission" => "5.00",
			"charges" => "1.00",
			"orderOrigin" => "1",
			"failReason" => ""
		);

		$result = $this->api->callbackFromServer($orderId, $params);
		$this->test($result['success'], false,
			'Test API callbackFromServer function with unmatching payment amounts. Expected: success = false');*/
	}
}
