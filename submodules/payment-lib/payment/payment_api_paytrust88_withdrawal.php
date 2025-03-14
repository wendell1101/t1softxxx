<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paytrust88.php';

/**
 * paytrust88 
 *
 * * PAYTRUST88_WITHDRAWAL_PAYMENT_API, ID: 882
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://paytrust88.com/trade/api/
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_paytrust88_withdrawal extends Abstract_payment_api_paytrust88 {
	const CALLBACK_STATUS_SUCCESS = 1;
	const ORDER_STATUS_SUCCESS = '2';
	const RETURN_SUCCESS_CODE = '';
	const RETURN_FAILED_CODE = 'faile';

	public function getPlatformCode() {
		return PAYTRUST88_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'paytrust88_withdrawal';
	}

	# Implement abstract function but do nothing
	protected function configParams(&$params, $direct_pay_extra_info) {}

	/**
	 * detail: override common API functionsh
	 *
	 * @return void
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		return $this->returnUnimplemented();
	}

	public function processPaymentUrlForm($params) {
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
		# look up bank code
		$bankInfo = $this->getBankInfo();
        $bankno = $bankInfo[$bank]['code'];

		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
		$playerId = $order['playerId'];
		$playerDetails = $this->getPlayerDetails($playerId);
		$username =  (isset($playerDetails[0]) && !empty($playerDetails[0]['username'])) ? $playerDetails[0]['username'] : '';

		$params = array();
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['currency'] = $this->getSystemInfo('currency');
		$params['name'] = $name;
		$params['bank_code'] = $bankno;
		$params['iban'] = $accNum;
		$params['http_post_url'] = $this->getNotifyUrl($transId);
		$params['item_id'] = $transId;
		$params['item_description'] = $username;
	
		$this->CI->utils->debug_log('=========================paytrust88 withdrawal paramStr before sign', $params);
		return $params;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false, 'message' => 'payment failed');		
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		$bankInfo = $this->getBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================paytrust88 withdrawal bank whose bankTypeId=[$bank] is not supported by paytrust88");
			return array('success' => false, 'message' => 'Bank not supported by paytrust88');
		}
		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

		$queryString = http_build_query($params);
		$url = $this->getSystemInfo('url'). '?' . $queryString;
		list($content, $response_result) = $this->processCurl($url, $params, $transId, true);
		$this->CI->utils->debug_log('======================================paytrust88 list content', $content);
		$this->CI->utils->debug_log('======================================paytrust88 list response_result', $response_result);
		
		$decodedResult = $this->decodeResult($content);
		$this->CI->utils->debug_log('======================================paytrust88 submitWithdrawRequest decodedResult', $decodedResult);
		$decodedResult['response_result'] = $response_result; 
		return $decodedResult;
	}

	protected function processCurl($url, $params, $transId=NULL, $return_all=false){

		$this->CI->utils->debug_log('======================================paytrust88 processCurl params: ', $params);
		$this->CI->utils->debug_log('======================================paytrust88 processCurl url: ', $url );
	
		$apiKey = $this->getSystemInfo('key');
		$username = $apiKey;
		$password = $this->getSystemInfo('api_password') ? $this->getSystemInfo('api_password') : '';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeoutSecond());
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());

		$fullResponse = curl_exec($ch);
		$errCode = curl_errno($ch);
		$error = curl_error($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($fullResponse, 0, $header_size);
		$responseStr = substr($fullResponse, $header_size);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		#save response result
		$response_result_id = $this->submitPreprocess($params, $responseStr, $url, $responseStr, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $transId);
		
		if($return_all){
			$response_result = [
				$params, $responseStr, $url, $responseStr, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $transId
			];
			return array($responseStr, $response_result);
		}

		return $fullResponse;
	}

	public function decodeResult($resultString, $queryAPI = false) {
        $result_array = json_decode($resultString, true);
		$this->utils->debug_log("=========================paytrust88 withdrawal decoded result string", $result_array);

		if(array_key_exists("status",$result_array)) {
			$returnCode = $result_array['status'];
			$payoutID = $result_array['payout'];

			if ($returnCode == '0'){
				$message = 'paytrust88 payment response successful! Payout ID: '.$payoutID;
				return array('success' => true, 'message' => $message);

			}else{
				$returnDesc = $result_array['decline_reason'];
				$message = "paytrust88 payment failed for [" .$returnCode. "] , Desc: ".$returnDesc;
				return array('success' => false, 'message' => $message);
			} 
			
		}
		elseif(array_key_exists("error",$result_array)){
			$returnError = $result_array['error'];
			$message = "paytrust88 payment failed for [" .$returnError. "]";
			return array('success' => false, 'message' => $message);
		} 
		else{
			$message = 'paytrust88 payment decoded failed';
			return array('success' => false, 'message' => $message);
		} 

	}	


	public function getBankInfo() {
			$bankInfo = array();
			$bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
			if(!empty($bankInfoArr)) {
				foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
					if(isset($bankInfoItem['name'])){
						$bankInfo[$system_bank_type_id]['name'] = $bankInfoItem['name'];
					}
					if(isset($bankInfoItem['code'])){
						$bankInfo[$system_bank_type_id]['code'] = $bankInfoItem['code'];
					}
				}
				$this->utils->debug_log("=========================ysydai bank info from extra_info: ", $bankInfo);
			} else {
				$currency  = $this->getSystemInfo('currency');
				switch ($currency) {
					case 'MYR':
						$bankInfo = array(
							'' => array('name' => 'Hong Leong', 'code' => '59f413ca94c67'), 
							'' => array('name' => 'Maybank', 'code' => '59f413e3ac31f'), 
							'' => array('name' => 'CIMB', 'code' => '59f413f699727'),
							'' => array('name' => 'Public Bank', 'code' => '59f414215b156'), 
							'' => array('name' => 'RHB', 'code' => '5b3558e1ad70d')
						);
						break;
					case 'IDR':
						$bankInfo = array(
							'29' => array('name' => 'Bank BCA', 'code' => '59f4149441ddc'),  //Bank Central asia
							'35' => array('name' => 'Bank BRI', 'code' => '59f414875bddc'),  //Bank Rakyat Indonesia
							'39' => array('name' => 'Bank BNI', 'code' => '59f414a0180a8')  //Bank Negara Indonesia
						);
						break;
					case 'THB':
						$bankInfo = array(
							'26' => array('name' => 'SCB Easy', 'code' => '59f414509ca5d'), 
							'27' => array('name' => 'KTB NetBank', 'code' => '59f414434c28e'), 
							'29' => array('name' => 'Bangkok Bank', 'code' => '59f4143921ba5'), 
							'31' => array('name' => 'Kasikorn Bank', 'code' => '59f414091aeb1')
						);
						break;
					case 'VND':
						$bankInfo = array(
							'' => array('name' => 'Vietin', 'code' => '5a8d9b3432bc7'), 
							'' => array('name' => 'VietCom', 'code' => '5a8dbfef271b0'), 
							'' => array('name' => 'BIDV', 'code' => '5a8dc25912217'), 
							'' => array('name' => 'TechCom', 'code' => '5a8ee643945a3'), 
							'' => array('name' => 'SacomBank', 'code' => '5a8eec3fc74e6'), 
							'' => array('name' => 'DongaBank', 'code' => '5a904bc3775ba'),
							'' => array('name' => 'ACB', 'code' => '5a900eca03af6') 
						);
						break;

					default:
						$bankInfo = array();
						break;
				}
			$this->utils->debug_log("=======================getting paytrust88 bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}
    
	public function callbackFromServer($transId, $params) {
		$response_result_id = parent::callbackFromServer($transId, $params);
		if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->utils->debug_log('=========================paytrust88 process withdrawalResult order id', $transId);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
		
        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
		}
		
		if ($params['status'] == self::ORDER_STATUS_SUCCESS) {
			$this->utils->debug_log('==========================paytrust88 withdrawal payment was successful: trade ID [%s]', $params['item_id']);
			
			$msg = sprintf('paytrust88 withdrawal was successful: trade ID [%s]', $params['item_id']);
			$this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

			$result['message'] = $msg;
		    $result['success'] = true; 
		} elseif($this->getSystemInfo('auto_to_decline') == true) {
			if($params['status'] == '-1' || $params['status'] == '-3' ||$params['status'] == '-2'){
				$this->utils->debug_log('==========================paytrust88 withdrawal payment was failed: trade ID [%s]', $params['item_id']);
				
				$returnCode = $params['status'];
				$returnDesc = $this->getStatusErrorMsg($params['status']);
	
				$msg = sprintf('paytrust88 withdrawal was failed: trade ID [%s], status code is [%s]: [%s]', $params['item_id'],$returnCode,$returnDesc);
				$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
	
				$result['message'] = $msg ;
				$result['success'] = true; 
			}
		}else{
			if(isset($params['status'])){
				$returnCode = $params['status'];
				$returnDesc = $this->getStatusErrorMsg($params['status']);
				$msg = sprintf('paytrust88 withdrawal payment was not successful trade ID [%s], status code is [%s]: [%s]', $params['item_id'],$returnCode,$returnDesc);
			}else{
				$msg = sprintf('paytrust88 withdrawal payment was not successful trade ID [%s] ',$params['item_id']);
			}
			
			if($this->getSystemInfo('auto_to_decline') == true){
				$this->debug_log($msg, $params); 
			}else{
				$this->writePaymentErrorLog($msg, $params);
			}

		    $result['message'] = $msg;
		}
        return $result;
    }

    private function getStatusErrorMsg($status) {
		$msg = "";
		switch ($status) {
			case '0':
				$msg = "Requested";
				break;

			case '1':
				$msg = "Authorized";
				break;

			case '-1':
				$msg = "Rejected";
				break;			

			case '3':
				$msg = "Confirmed";
				break;

			case '-3':
				$msg = "Invalid";
				break;

			case '-2':
				$msg = "Cancelled";
				break;

			default:
				$msg = "";
				break;
		}
		return $msg;
	}

    public function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array('apikey', 'item_id', 'token', 'amount','currency','status','signature','created_at');

        foreach ($requiredFields as $f) {
        	if (!array_key_exists($f, $fields)) {
        		$this->writePaymentErrorLog("======================paytrust88 withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
        		return false;
        	}
        }

        $callbackSign = $this->sign($fields);

		# is signature authentic?
		if ($fields['signature'] != $callbackSign) {
			$this->writePaymentErrorLog("======================paytrust88 check callback sign error, signature is [$callbackSign], match? ", $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order['amount']) != number_format($fields['amount'], 2, '.', '') ) {
            $this->writePaymentErrorLog('======================paytrust88 withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
			return false;
		}

		if ($fields['item_id'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('======================paytrust88 withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields); 
			return false;
		}

        # everything checked ok
        return true;
    }
	
	# -- signing --
	public function sign($params) {
	    $apiKey = $this->getSystemInfo('key');

		$data = array("payout", "amount", "currency", "status", "created_at");
	    
	    $arr = array();
	    for($i = 0; $i< count($data); $i++){
			if (array_key_exists($data[$i], $params)) {
				$arr[$i] = $params[$data[$i]];
			}
	    }
	    $signStr = implode('', $arr);

	    $sign = hash_hmac('sha256', $signStr, $apiKey);

		return $sign;
	}
}
