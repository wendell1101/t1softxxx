<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paysec.php';

/**
 * PAYSEC
 *
 *
 * * PAYSEC_WITHDRAWAL_V2_PAYMENT_API, ID: 559
 *
 * Required Fields:
 *
 * * URL
 * * Account
 * * Extra Info
 *
 * Field Values:
 *
 * * URL: https://service.paysec.com/api/quickdraw
 * * Account: ## partner ID ##
 * * Extra Info:
 * > {
 * > 	"paysec_priv_key" : "## merchant private key (pem formatted, escaped, no start/end tag) ##",
 * > 	"paysec_pub_key" : "## API public key (pem formatted, escaped, no start/end tag) ##",
 * > 	"callback_host" : ""
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_paysec_withdrawal_v2 extends Abstract_payment_api_paysec {
	const CALLBACK_STATUS_SUCCESS = 'SUCCESS';
	const CALLBACK_STATUS_FAILED = 'FAILED';

	public function getPlatformCode() {
		return PAYSEC_WITHDRAWAL_V2_PAYMENT_API;
	}

	public function getPrefix() {
		return 'paysec_withdrawal_v2';
	}

	# Implement abstract function but do nothing
	protected function configParams(&$params, $direct_pay_extra_info) {}
	protected function processPaymentUrlForm($params) {}

	/**
	 * detail: override common API functions
	 *
	 * @return void
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		return $this->returnUnimplemented();
	}

	# APIs with withdraw function need to implement these methods
	## This function returns the URL to submit withdraw request to
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url');
	}

	## This function returns the params to be submitted to the withdraw URL
	## Note that $bank param is the bank_type ID in database, we compare it with the supported bank_codes by this API
	private $errMsg = 'Payment failed'; # This variable is used to store error message that's available upon submit
	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$paramsBasic = array();
		$params = array();
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

		# Get player contact number
		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
		$playerId = $order['playerId'];
		$player = $this->CI->player->getPlayerById($playerId);
		$username = $player['username'];

		$params['merchantCode'] = $this->getSystemInfo("account");
		$params['amount'] = $this->convertAmountToCurrency($amount);

        if($this->getSystemInfo('use_usd_currency')) {
            $params['amount'] = $this->convertAmountToCurrency($this->gameAmountToDBByCurrency($params['amount'], $this->utils->getTodayForMysql(),'USD','CNY') );
        }

		$params['currency'] = $this->getSystemInfo("currency") ? $this->getSystemInfo("currency") : 'CNY';

		# look up bank code
		$bankInfo = $this->getPaysecBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================paysec withdraw bank whose bankTypeId=[$bank] is not supported by paysec");
			return array('success' => false, 'message' => 'Bank not supported by paysec');
		}

		$params['bankCode'] = empty($this->getSystemInfo("bankCode")) ? $bankInfo[$bank]['code'] : $this->getSystemInfo("bankCode");# bank SN mapping
		$params['bankName'] = empty($this->getSystemInfo("bankName")) ? $bankInfo[$bank]['name'] : $this->getSystemInfo("bankName");# bank SN mapping
		$params['customerName'] = $username;
		$params['bankAccountName'] = $name;
		$params['bankAccountNumber'] = $accNum;
		$params['cartId'] = $transId;
		$params['notifyURL'] = $this->getNotifyUrl($transId); # Invokes callBackFromServer
		$params['version'] = '3.0';

		if($params['currency'] == 'CNY' || $params['currency'] == 'IDR' || $params['currency'] == 'THB') {
			# look up bank detail from playerbankdetails table, using bank_type ID and accountNumber
			# but if we cannot look up those info, will leave the fields blank
			$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
			$this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
			if(!empty($playerBankDetails)){
				$params['province'] = empty($this->getSystemInfo("province")) ? $playerBankDetails['province'] : $this->getSystemInfo("province");
				$params['city'] = empty($this->getSystemInfo("city")) ? $playerBankDetails['city'] : $this->getSystemInfo("city");
				$params["bankBranch"] = empty($this->getSystemInfo("bankBranch")) ? $playerBankDetails['branch'] : $this->getSystemInfo("bankBranch");
			} else {
				$params['province'] = '无';
				$params['city'] = '无';
				$params["bankBranch"] = '无';
			}

			$params['province'] = empty($params['province']) ? "无" : $params['province'];
			$params['city'] = empty($params['city']) ? "无" : $params['city'];
			$params['bankBranch'] = empty($params['bankBranch']) ? "无" : $params['bankBranch'];
		}

        $this->CI->utils->debug_log('========================paysec withdrawal paramStr before sign', $params);

        $params["signature"] = $this->sign($params);

		$this->utils->debug_log("========================paysec submit withdrawal order Params: ", $params);

		return $params;
	}

	// --------- For Withdrawal ------------
	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false);
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		$fullParams = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        if(isset($fullParams['success'])) {
            if($fullParams['success'] == false) {
                $result['message'] = $fullParams['message'];
                $this->utils->debug_log($result);
                return $result;
            }
        }
		$url = $this->getWithdrawUrl();
		$postString = is_array($fullParams) ? $this->CI->utils->encodeJson($fullParams) : $fullParams;
		$curlConn = curl_init($url);
		curl_setopt($curlConn, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curlConn, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlConn, CURLOPT_HTTPHEADER, array('Content-Type:application/json') );
		curl_setopt($curlConn, CURLOPT_POSTFIELDS, $postString);

		$response = curl_exec($curlConn);
		$errCode = curl_errno($curlConn);
		$error = curl_error($curlConn);
		$header_size = curl_getinfo($curlConn, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$content = substr($response, $header_size);
		$statusCode = curl_getinfo($curlConn, CURLINFO_HTTP_CODE);
		curl_close($curlConn);

		$response_result_id = $this->submitPreprocess($fullParams, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $fullParams['cartId']);

		$result['result']  = $response;
		$result['success'] = ($errCode == 0);
		$result['message'] = $error;
		$this->utils->debug_log("========================paysec withdrawal Post json", $postString, "Result", $result);

		$decodedResult = $this->decodeResult($result['result']);
		$this->utils->debug_log("Decoded Result", $decodedResult);
		return $decodedResult;
	}

	## This function takes in the return value of the URL and translate it to the following structure
	## array('success' => false, 'message' => 'Error message')
	public function decodeResult($resultString) {
		$result = json_decode($resultString, true);
		$this->utils->debug_log("=========================paysec decoded result string", $result);

		if($result['status'] == 'PENDING') {
			$message = "Status: [" . $result['status'] . "], paysec withdrawal request acknowledgement. ".$result['message'];
			return array('success' => true, 'message' => $message);
		}
		else if($result['status'] == 'FAILURE') {
			$this->errMsg = 'Status: [' . $result['status']. '], paysec withdrawal error on request. '.$result['message'];
		}
		else {
			$this->errMsg = 'paysec payment failed for unknown reason';
		}

		return array('success' => false, 'message' => $this->errMsg);
	}
	## This function provides a way to manually check withdraw status. Useful when API does not provide a callback.
	## Returns array('success' => false, 'payment_fail' => false, 'message' => 'Error message')
	## 'success' means whether payment is successful, 'payment_fail' means if payment is not successful, shall we mark it as failed or shall we wait
	public function checkWithdrawStatus($orderId) {}

	/**
	 * detail: Help2Pay withdraw callback implementation
	 *
	 * @param int $transId transaction id
	 * @param int $paramsRaw
	 * @return array
	 */
	public function callbackFromServer($transId, $params) {
		$result = array('success' => false, 'message' => 'Payment failed');

		$this->CI->utils->debug_log('process withdrawalResult order id', $transId);
		$this->CI->utils->debug_log('=========================paysec process withdrawalResult order id', $transId);
		$this->CI->utils->debug_log("=========================paysec checkCallback params", $params);

		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		if (!$this->checkCallbackOrder($order, $params)) {
			return $result;
		}

		if($params['status'] == self::CALLBACK_STATUS_SUCCESS) {
			$msg = sprintf('Payment was successful: trade ID [%s]', $params['cartId']);
			$this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
			$result['success'] = true;
			$result['message'] = $msg;
		}
		else if($params['status'] == self::CALLBACK_STATUS_FAILED) {
			$msg = sprintf('======================paysec withdrawal payment was failed: trade ID [%s]', $params['cartId']);
			$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
			$this->writePaymentErrorLog($msg, $fields);
			$result['message'] = $msg;
		}
		else{
			$msg = sprintf('======================paysec withdrawal payment was not successful: status code [%s]. '.$params['statusMessage'], $params['status']);
			$this->writePaymentErrorLog($msg, $fields);
			$result['message'] = $msg;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields) {
		# does all required fields exist in the header?
		$requiredFields = array(
			'transactionReference', 'cartId', 'currency', 'orderAmount', 'orderTime', 'completedTime', 'status', 'statusMessage', 'version'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("======================paysec withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		if (!$this->verifySign($fields)) {
			$this->writePaymentErrorLog('=========================paysec withdrawal checkCallback signature Error', $fields);
			return false;
		}

		$check_amount = $this->convertAmountToCurrency($order['amount']);
		if($this->getSystemInfo('use_usd_currency')) {
			$check_amount = $this->convertAmountToCurrency($this->gameAmountToDBByCurrency($order['amount'], $this->utils->getTodayForMysql(),'USD','CNY') );
        }

		if ($fields['orderAmount'] != $check_amount) {
			$this->writePaymentErrorLog("======================paysec withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $check_amount, $fields);
			return false;
		}

		if ($fields['cartId'] != $order['transactionCode']) {
			$this->writePaymentErrorLog("======================paysec withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function callbackFromBrowser($transId, $params) {
		return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
	}

	public function getPaysecBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("paysec_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
				$bankInfo[$system_bank_type_id] = array('name' => $bankInfoItem['name'], 'code' => $bankInfoItem['code']);
			}
			$this->utils->debug_log("================== getting paysec bank info from extra_info: ", $bankInfo);
		} else {
			if($this->getSystemInfo("currency") == 'IDR') {
				$bankInfo = array(
					'27' => array('name' => 'Bank Rakyat Indonesia', 	  'code' => 'BRI_IDR'),
					'28' => array('name' => 'Mandiri Bank', 		 	  'code' => 'MDR_IDR'),
					'29' => array('name' => 'Bank Central Asia', 	 	  'code' => 'BCA_IDR'),
					'30' => array('name' => 'Permata Bank', 		 	  'code' => 'PMB_IDR'),
					//'31' => array('name' => 'Bank BJB/Bank JABAR', 	 	  'code' => '110'),
					//'32' => array('name' => 'Bank Bukopin', 		 	  'code' => '441'),
					//'33' => array('name' => 'Bank Syariah Mandiri',  	  'code' => '451'),
					//'34' => array('name' => 'Maybank', 				 	  'code' => '016'),
					//'35' => array('name' => 'Bank BRI Syariah', 	 	  'code' => '422'),
					'36' => array('name' => 'CIMB Clicks Indonesia', 	  'code' => 'CIMB_IDR'),
					'37' => array('name' => 'BTN Bank',                   'code' => 'BTN_IDR'),
					//'38' => array('name' => 'Bank BCA Syariah', 		  'code' => '536'),
					'39' => array('name' => 'Bank Negara Indonesia', 	  'code' => 'BNI_IDR'),
					'40' => array('name' => 'Danamon Bank', 	          'code' => 'DMN_IDR')
					//'41' => array('name' => 'Bank Mestika', 			  'code' => '151'),
					//'42' => array('name' => 'Bank Panin', 			  'code' => '019')
				);
			}elseif($this->getSystemInfo("currency") == 'THB'){
                $bankInfo = array(
                    '26' => array('name' => 'Siam Commercial Bank', 'code' => 'SCB_THB'),
                    '27' => array('name' => 'Krungthai Bank', 		'code' => 'KTB_THB'),
                    '28' => array('name' => 'Krungsri Online Bank', 'code' => 'BAY_THB'),
                    '29' => array('name' => 'Bangkok Bank', 		'code' => 'BBL_THB'),
                    '30' => array('name' => 'United Overseas Bank', 'code' => 'UOB_THB'),
                    '31' => array('name' => 'Kasikorn Bank', 		'code' => 'KKB_THB')
                );
            }else {
				$bankInfo = array(
					'1' => array('name' => '中国工商银行', 'code' => 'ICBC'),
					'2' => array('name' => '招商银行', 'code' => 'CMB'),
					'3' => array('name' => '中国建设银行', 'code' => 'CCB'),
					'4' => array('name' => '中国农业银行', 'code' => 'ABC'),
					'5' => array('name' => '交通银行', 'code' => 'BCOM'),
					'6' => array('name' => '中国银行', 'code' => 'BOC'),
					'7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
					//'8' => array('name' => '广东发展银行', 'code' => 'CGB'),
					'10' => array('name' => '中信银行', 'code' => 'CITIC'),
					'11' => array('name' => '民生银行', 'code' => 'CMBC'),
					'12' => array('name' => '中国邮政储蓄', 'code' => 'PSBC'),
					'13' => array('name' => '兴业银行', 'code' => 'CIB'),
					'14' => array('name' => '华夏银行', 'code' => 'HXB'),
					'15' => array('name' => '平安银行', 'code' => 'PAB'),
					'17' => array('name' => '广州银行', 'code' => 'GZCB'),
					'18' => array('name' => '南京银行', 'code' => 'NJCB'),
					'20' => array('name' => '光大银行', 'code' => 'CEB')
				);
			}

			$this->utils->debug_log("=======================getting paysec bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function sign($params, $action = ''){
		if(isset($params['status'])) {
			$params['merchantCode'] = $this->getSystemInfo('account');

			$data = array(
				"cartId", "orderAmount", "currency", "merchantCode", "version", "status"	//callback params
			);
		}
		else {
			$data = array(
				"cartId", "amount", "currency", "merchantCode", "version"
			);
		}

	    $arr = array();
	    for($i = 0; $i< count($data); $i++){
			if (array_key_exists($data[$i], $params)) {
				$arr[$i] = $params[$data[$i]];
			}
	    }
	    $preEncodeStr = implode(';', $arr);
        $salt = str_replace ("$2a$12$","", $this->getSystemInfo('secret'));
        $hashVal = hash('sha256', $preEncodeStr);
        $signature = $this->passwordHashToCrypt($hashVal,$this->getSystemInfo('secret'));
        $loc = $this->strposX($signature, "$", 3);
        $sdata = str_replace($salt, "", substr($signature, $loc));

        return $sdata;
	}

    private function strposX($haystack, $needle, $n = 0) {
        $offset = 0;

        for ($i = 0; $i < $n; $i++) {
            $pos = strpos($haystack, $needle, $offset);

            if ($pos !== false) {
                $offset = $pos + strlen($needle);
            } else {
                return false;
            }
        }

        return $offset;
    }

	public function verifySign($params){
		if($this->sign($params) == $params["signature"]){
			return true;
		} else {
			return false;
		}
	}
}
