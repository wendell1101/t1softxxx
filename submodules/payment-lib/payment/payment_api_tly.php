<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * 同略云
 *
 * TLY_PAYMENT_API, ID: 82
 *
 * Required Fields:
 *
 * * URL
 * * Key - apikey
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: https://s01.tonglueyun.com/authority/system/api/place_order/ ; https://s02.tonglueyun.com/authority/system/api/place_order/
 * * Extra Info:
 * 	{
 * 		"bank_info_list": [
	        {
	            "bank_name": "_json: { \"1\" : \"ABC\", \"2\" : \"农行\" }",
	            "db_bank_id": 4,
	            "enabled": true,
	            "bank_code": "ABC",
	            "card_number": "xxxxxx",
	            "name": "chinese name",
	            "address" : "Bank address"
	        },
	        {
	            "bank_name": "_json: { \"1\" : \"ICBC\", \"2\" : \"工行\" }",
	            "db_bank_id": 1,
	            "enabled": true,
	            "bank_code": "ICBC",
	            "card_number": "xxxxx",
	            "name": "xxxx",
	            "address" : "Bank address"
	        }
	    ],
 *	}
 *
 * Important Note: TLY Deposit need special view support. Use v8_v3 template.
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tly extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = '{"success":true}';
	const RETURN_FAILED_CODE = '{"success":false}';

	public function __construct($params = null) {
		parent::__construct($params);

	}

	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return TLY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'tly';
	}

	# -- Helper functions --
	public function postForm($url, $params) {
		try {

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);

			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->utils->encodeJson($params) );

			//set timeout
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeoutSecond());
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());


			$response = curl_exec($ch);
			$errCode = curl_errno($ch);
			$error = curl_error($ch);
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$content = substr($response, $header_size);

			$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
			// var_dump($url);
			// var_dump($response);

			$statusText = $errCode . ':' . $error;
			// var_dump($statusText);
			curl_close($ch);

			$this->CI->utils->debug_log('response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

			return $this->CI->utils->decodeJson($content);

			// $response = \Httpful\Request::post($this->getSystemInfo('url'))
			// 	->method(\Httpful\Http::POST)
			// 	->expectsText()
			// 	// ->expectsJson()
			// 	->body(json_encode($params))
			// 	->sendsType(\Httpful\Mime::FORM)
			// 	->send();
			// $this->CI->utils->debug_log('response', $response->body);
			// return $response->body;
		} catch (Exception $e) {
			$this->CI->utils->error_log('POST failed', $e);
		}
	}

	// public $playerId;

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

		$this->CI->utils->debug_log('generate url for order id:'.$orderId);

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		// $this->playerId=$playerId;

		# Parameters used by response (the static page)
		$respData = array();

		# Setup parameters. Reference: Documentation section 2.1
		$params['apikey'] = $this->getSystemInfo('key');
		$params['order_id'] = $order->secure_id;

		$bankInfo = $this->getBankInfoList();
		$direct_pay_extra_info = $order->direct_pay_extra_info;
		$this->CI->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		$depositDetail=null;
		$respData=null;
		$success=false;
		$hide_system_confirmation=false;
		$player_bank_info=null;
		if (!empty($direct_pay_extra_info) && !empty($bankInfo)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				// $bankId = $extraInfo['bank'];
				// $respData['Beneficiary Bank'] = $this->findBankName($bankId);

				# Get info from deposit_detail section
				$depositDetail = $this->getDepositDetail($extraInfo, $playerId);
				$params['pay_username'] = $depositDetail['name'];
				$params['pay_card_number'] = $depositDetail['account'];
				//update playerBankDetailsId to order
				if(!empty($depositDetail['playerBankDetailsId'])){

					$this->CI->utils->debug_log('update orderId', $orderId, 'playerBankDetailsId', $depositDetail['playerBankDetailsId']);

					$rlt_save=$this->CI->sale_order->updatePlayerBankDetailsId($orderId, $depositDetail['playerBankDetailsId']);
					if(!$rlt_save){
						$this->utils->error_log('updatePlayerBankDetailsId failed');
					}
				}

				$hide_system_confirmation=true;
				$player_bank_info=$depositDetail;

				$db_bank_id=$depositDetail['db_bank_id'];
				//map bank_id to
				// $bank_label = $this->getSystemInfo('default_bank');
				$bank_label=null;
				$i=0;
				$rand_idx=rand(0,count($bankInfo)-1);
 				foreach ($bankInfo as $bankIndex => $bank) {
					if(empty($bank_label)){
						//random
						if($rand_idx==$i){
							$bank_label=$bankIndex;
							break;
						}
					}else{
						//match bank info
						//search bankId
						if(isset($bank['db_bank_id']) && $db_bank_id==$bank['db_bank_id']){
							$bank_label=$bank_key;
							break;
						}
 					}
					$i++;
				}

				if($bank_label!==null){
					$bank=$bankInfo[$bank_label];
					//set
					$params['card_number'] = $bank['card_number'];
					$respData['Beneficiary Account'] = $bank['card_number'];
					$respData['Beneficiary Bank'] = lang($bank['bank_name']);
					if(array_key_exists('address', $bank)) {
						$respData['Beneficiary Bank Address'] = $bank['address'];
					}
					if(isset($bank['name'])) {
						$respData['Beneficiary Name'] = $bank['name'];
					}
					$params['bank_flag'] = $bank['bank_code'];
				}

				$this->CI->utils->debug_log('bank_label', $bank_label, 'db_bank_id', $db_bank_id,
					'orderId', $orderId);

				// if(array_key_exists($bankId, $bankInfo)) {
				// 	$params['card_number'] = $bankInfo[$bankId]['card_number'];
				// 	$respData['Beneficiary Account'] = $bankInfo[$bankId]['card_number'];
				// 	$respData['Beneficiary Bank'] = $bankInfo[$bankId]['bank_name'];

				// 	if(array_key_exists('name', $bankInfo[$bankId])) {
				// 		$respData['Beneficiary Name'] = $bankInfo[$bankId]['name'];
				// 	}
				// }

				$params['comment'] = $extraInfo['comment'];
				if(!empty($params['comment'])){
					//lang key
					$respData['cashier.deposit.comment']=$params['comment'];
					//write to notes
					$rlt_add_comment=$this->CI->sale_order->appendReason($orderId, lang('cashier.deposit.comment').': '.$params['comment']);
					if(!$rlt_add_comment){
						$this->utils->error_log('add comment to sale order failed');
					}
				}

				$params['amount'] = $this->convertAmountToCurrency($amount);
				$respData['Deposit Amount'] = $params['amount'];
				$params['create_time'] = time(); # current unix time
				$params['meta_data']['client_ip'] = $this->getClientIP();

				$url=$this->getSystemInfo('url');
				$resp = $this->postForm($params); # Response rf: Documentation section 2.1.2

				$this->CI->utils->debug_log("params", $params);
				$this->CI->utils->debug_log("respData", $respData);
				$this->CI->utils->debug_log("resp", $resp);

				if($resp && $resp['success']) {
					$success = true;
					# other $respData fields are already prepared
				} else {
					$success = false;
				}
			}
		}else{
			$this->CI->utils->error_log('missing info direct_pay_extra_info', $direct_pay_extra_info, 'bankInfo', $bankInfo);
		}

		return array(
			'success' => $success,
			'type' => self::REDIRECT_TYPE_STATIC,
			'title' => lang('payment.type.'.$this->getPlatformCode()),
			'data' => $respData,
			'hide_system_confirmation' => $hide_system_confirmation,
			'player_bank_info' => $player_bank_info,
		);
	}

	public function isAvailable() {
		$bankInfoList = $this->getBankInfoList();
		return !empty($bankInfoList);
	}

	private function getBankInfoList() {
		$this->CI->load->library(['authentication']);
		$this->CI->load->model(['player_model']);
		$rawList = $this->getSystemInfo('bank_info_list');
		$bankInfoList = array();

		$playerId = $this->CI->authentication->getPlayerId();
		$player = $this->CI->player_model->getPlayerById($playerId);

		foreach($rawList as $bankInfo){
			# Decide whether the bank info is enabled
			if(array_key_exists('enabled', $bankInfo) && !$bankInfo['enabled']){
				continue;
			}

			# Decide whether the current player level is allowed to use this bankInfo
			if(array_key_exists('playerLevels', $bankInfo)) {
				$playerLevels = $bankInfo['playerLevels'];
				$this->utils->debug_log("Checking whether the player level is configured in bank info", $player->levelId, $bankInfo['playerLevels']);
				if(!in_array($player->levelId, $playerLevels)) {
					continue;
				}
			}

			$bankInfoList[] = $bankInfo;
		}
		return $bankInfoList;
	}

	private function getDepositDetail($param, $playerId) {
		$this->CI->load->model(['playerbankdetails', 'banktype']);

		// $playerId = $this->CI->authentication->getPlayerId();
		$itemAccount = $param['itemAccount'];

		if ($itemAccount == 'new') {
			$na_bankName = $param['na_bankName'];
			$fullName = $param['fullName'];
			$depositAccountNo = $param['depositAccountNo'];

			$data = array(
				'playerId' => $playerId,
				'bankTypeId' => $na_bankName,
				'bankAccountNumber' => $depositAccountNo,
				'bankAccountFullName' => $fullName,
				'dwBank' => '0', //0 is deposit
				'isRemember' => '1', //1 is default
				'status' => '0', //0 is active
			);
			$this->CI->utils->debug_log('Saving bank detail', $data);
			$playerBankDetailsId = $this->CI->playerbankdetails->addBankDetailsByDeposit($data);
			return array('name' => $fullName, 'db_bank_id'=>$data['bankTypeId'], 'account' => $depositAccountNo, 'playerBankDetailsId'=>$playerBankDetailsId);

		} else {
			$playerBankDetailsId = $param['pa_bankName'];
			$playerBankDetail = $this->CI->playerbankdetails->getBankDetailsById($playerBankDetailsId);

			$this->CI->utils->debug_log('Loaded bank detail', $playerBankDetail);
			$fullName = $playerBankDetail['bankAccountFullName'];
			$depositAccountNo = $playerBankDetail['bankAccountNumber'];

			$banktype=$this->CI->banktype->getBankTypeById($playerBankDetail['bankTypeId']);

			return array('name' => $fullName, 'db_bank_id'=>$playerBankDetail['bankTypeId'],
				'bank_name'=>$banktype->bankName,
				'account' => $depositAccountNo, 'playerBankDetailsId'=>$playerBankDetailsId);
		}
	}

	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	public function callbackFromServer($orderId, $params) {
        // $raw_post_data = file_get_contents('php://input', 'r');
        // $jsonData = json_decode($raw_post_data, true);
        // $jsonData['_params']=$params;

		$this->CI->load->model(['sale_order']);
		$source = 'server';

		$response_result_id = parent::callbackFromServer($orderId, $params);

		// $this->returnUnimplemented();

		//ip white list
		//check order id and time
		$success=true;
		$processed=true;
		$ip=$this->CI->utils->getIP();

		$ip_whitelist=$this->getSystemInfo('ip_whitelist');
		if(!empty($ip_whitelist)){
			$success=in_array($ip, $ip_whitelist);
		}

		$this->CI->utils->debug_log('ip', $ip, 'orderId', $orderId, 'ip_whitelist', $ip_whitelist);

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
		if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
			$this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $orderId, $params);
			if ($source == 'server' && $orderStatus == Sale_order::STATUS_BROWSER_CALLBACK) {
				$this->CI->sale_order->setStatusToSettled($orderId);
			}
		} else {
			# update player balance
			$this->CI->sale_order->updateExternalInfo($orderId,
				@$params['oid'], '', # neither of payment gateway order id or bank order id exist. Reference: documentation section (2)
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($orderId, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->CI->sale_order->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
		}

		return $result;

	}

	## This will be called when user redirects back to our page from payment API
	public function callbackFromBrowser($orderId, $params) {
		return $this->returnUnimplemented();
	}

	## $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$this->returnUnimplemented();
	}

	## Validates whether the callback from API contains valid info and matches with the order
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$this->returnUnimplemented();
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- Functions to display bank dropdown --
	public function getPlayerInputInfo() {
		$playerInputInfo = //parent::getPlayerInputInfo();
				[ ['name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'Amount']];

		# Add an additional section of player input. This section is implemented in auto_3rdparty_deposit.php view of v8_v3 template
		$this->CI->load->model(['banktype','playerbankdetails']);
		$this->CI->load->library(array('authentication'));
		$playerId = $this->CI->authentication->getPlayerId();
		$playerBanks = $this->CI->playerbankdetails->getDepositBankDetails($playerId);
		$banks = $this->CI->banktype->getAllActiveBankType();
		$playerInputInfo[] = array('type' => 'deposit_detail', 'banks' => $banks, 'player_banks' => $playerBanks, 'comment' => $this->generateRandomString());
		return $playerInputInfo;
	}


	// private function findBankName($bankId) {
	// 	$bankList = array(
	// 		array('label' => '农行', 'value' => 'ABC'),
	// 		array('label' => '中行', 'value' => 'BOC'),
	// 		array('label' => '工行', 'value' => 'ICBC'),
	// 		array('label' => '交行', 'value' => 'BCM'),
	// 		array('label' => '建行', 'value' => 'CCB'),
	// 		array('label' => '招行', 'value' => 'CMB'),
	// 		array('label' => '民生', 'value' => 'CMBC'),
	// 		array('label' => '华夏', 'value' => 'HXB'),
	// 		array('label' => '邮政', 'value' => 'PSBC'),
	// 		array('label' => '微信', 'value' => 'WebMM'),
	// 		array('label' => '支付宝', 'value' => 'ALIPAY'),
	// 	);

	// 	foreach($bankList as $bank) {
	// 		if($bank['value'] == $bankId) {
	// 			return $bank['label'];
	// 		}
	// 	}
	// 	return '';
	// }
	# Overwritten by the bankList defined in extra_info, as the bank_list needs to match the bank_info
	/* public function getBankListInfo() {
		return array(
			array('label' => '农行', 'value' => 'ABC'),
			array('label' => '中行', 'value' => 'BOC'),
			array('label' => '工行', 'value' => 'ICBC'),
			array('label' => '交行', 'value' => 'BCM'),
			array('label' => '建行', 'value' => 'CCB'),
			array('label' => '招行', 'value' => 'CMB'),
			array('label' => '民生', 'value' => 'CMBC'),
			array('label' => '华夏', 'value' => 'HXB'),
			array('label' => '邮政', 'value' => 'PSBC'),
			array('label' => '微信', 'value' => 'WebMM'),
			array('label' => '支付宝', 'value' => 'ALIPAY')
		);
	} */

	# -- Private functions --
	## After payment is complete, the gateway will invoke this URL asynchronously
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

	## Generate random string for the deposit comment
	private function generateRandomString($length = 4) {
		$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	public function isPopupNewWindowOnDeposit(){
		return false;
	}

	/**
	 * detail: find the order id from the given paramater
	 *
	 * @param array $flds
	 * @return int
	 */
	public function getOrderIdFromParameters(&$flds) {

        $raw_post_data = file_get_contents('php://input', 'r');
        $flds = json_decode($raw_post_data, true);

		$orderId = null;
		//for fixed return url on browser
		if (isset($flds['order_id'])) {
			$secure_id = $flds['order_id'];

			$this->CI->load->model(array('sale_order'));
			$order = $this->CI->sale_order->getSaleOrderBySecureId($secure_id);

			$orderId = $order->id;
		}

		return $orderId;
	}

}
