<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * Anxinpay 安心支付
 *
 *
 * * ANXINPAY_WITHDRAWAL_PAYMENT_API, ID: 5435
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra_info
 *
 * Field Values:
 * * URL: http://pay.aixinyu.cn/dfPayOut
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_anxinpay_withdrawal extends Abstract_payment_api {
    const STATUS_SUCCESS = '1';
    const STATUS_FAILED = '5';
    const RETURN_SUCCESS = 'success';

	public function getPlatformCode() {
		return ANXINPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'anxinpay_withdrawal';
	}

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = NULL, $enabledSecondUrl = true, $bankId = NULL) {}
    public function directPay($order) {}

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');		
        
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $this->CI->utils->debug_log('======================================anxinpay submitWithdrawRequest params:', $params);
        if(isset($params['success'])) {
			if($params['success'] == false) {
				$result['message'] = $params['message'];
				$this->utils->debug_log($result);
				return $result;
			}
        }
        
        $url = $this->getSystemInfo('url');	
        
        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $this->CI->utils->debug_log('=========================anxinpay submitWithdrawRequest decoded Result', $decodedResult);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;

	}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $province    = empty($playerBankDetails['province'])    ? "无" : $playerBankDetails['province'];
            $city        = empty($playerBankDetails['city'])        ? "无" : $playerBankDetails['city'];
            $bankBranch  = empty($playerBankDetails['branch'])      ? "无" : $playerBankDetails['branch'];
            $bankAddress = empty($playerBankDetails['bankAddress']) ? "无" : $playerBankDetails['bankAddress'];
        } else {
            $bankBranch  = '无';
            $province    = '无';
            $city        = '无';
            $bankAddress = '无';
        }
        # look up bank code
        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================pay3721 withdrawal bank whose bankTypeId=[$bank] is not supported by pay3721");
            return array('success' => false, 'message' => 'Bank not supported by pay3721');
        }

        $params = array();

		$params['Version'] = '1.3';
		$params['Memid'] = $this->getSystemInfo("account");
        $params['Amount'] = $this->convertAmountToCurrency($amount); //元
        $params['Realname'] = $name; //收款人
        $params['Cardno'] = $accNum; //銀行卡卡號
        $params['Province'] = $province;
        $params['City'] = $city;
        $params['Branch'] = $bankBranch;
        $params['Type'] = "bank";
		$params['outTradeNo'] = $transId.'000';
        $params['tradeTime'] = date("YmdHis");
        $params['sign'] = $this->sign($params);
		$params['notify_url'] = $this->getNotifyUrl($transId);

		return $params;
	}
		

	public function decodeResult($resultString, $queryAPI = false) {
        $this->utils->debug_log("=========================anxinpay decodeResult resultString", $resultString);

		$result = json_decode($resultString, true);
		$this->utils->debug_log("=========================anxinpay json_decode result", $result);

		$respCode = $result['status'];
		$resultMsg = "未知错误";

		if($queryAPI){ 
			if($respCode == self::STATUS_SUCCESS) {
                $message = 'mbpay payment response successful, result Code:'.$respCode;
                return array('success' => true, 'message' => $message);
			}else{
				if($respCode != self::STATUS_SUCCESS && isset($result['msg'])) {
					$resultMsg = $result['msg'];
					$message = "mbpay withdrawal response, Msg: ".$resultMsg;
				}
				$message = "mbpay payment  result_code is Query failed ".$resultMsg;
				return array('success' => false, 'message' => $message);
			}
		}
		else{ 
			if($result['status'] == self::STATUS_SUCCESS) {
				$message = "anxinpay request successful.";
				return array('success' => true, 'message' => $message);
			} 
			else {
				if($respCode != self::STATUS_SUCCESS && isset($result['msg'])) {
					$resultMsg = $result['msg'];
					$message = "anxinpay withdrawal response, Msg: ".$resultMsg;
				}
				
				$this->utils->error_log("========================anxinpay return UNKNOWN ERROR!");
				$message = "anxinpay withdrawal response, Msg: ".$resultMsg;
				return array('success' => false, 'message' => $message);
			}		
		}
	}



	// public function checkWithdrawStatus($transId) {
    //     $params = array();
	// 	$params['Version'] = '1.3';
    //     $params['Memid'] = $this->getSystemInfo("account");
	// 	$params['outTradeNo'] = $transId;
    //     $params['querytime'] = date("YmdHis");
	// 	$params['Type'] = 'dfOrder';
	// 	$params['sign'] = $this->sign($params);

	// 	$url = $this->getSystemInfo('check_withdraw_status_url', ' http://pay.aixinyu.cn/dfQuery');
	// 	$response = $this->submitPostForm($url, $params, false, $transId);
	// 	$decodedResult = $this->decodeResult($response, true);

	// 	$this->CI->utils->debug_log('======================================anxinpay checkWithdrawStatus params: ', $params);
	// 	$this->CI->utils->debug_log('======================================anxinpay checkWithdrawStatus url: ', $url );
	// 	$this->CI->utils->debug_log('======================================anxinpay checkWithdrawStatus result: ', $response );
	// 	$this->CI->utils->debug_log('======================================anxinpay checkWithdrawStatus decoded Result', $decodedResult);

	// 	return $decodedResult;
    // }
    
    public function callbackFromServer($transId, $params) {
		$response_result_id = parent::callbackFromServer($transId, $params);  //這句一定要寫!!!! ==> 將callback訊息寫進 後台的 response result
		if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->utils->debug_log('==========================anxinpay process withdrawalResult order id', $transId);
       
        $result = $params;

        $this->utils->debug_log("==========================anxinpay checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

	    if($params['Status'] == self::STATUS_SUCCESS) {
            $this->utils->debug_log('==========================anxinpay withdrawal payment was successful: trade ID [%s]', rtrim($params['Sn'],'000')); 
            
            $msg = sprintf('=anxinpay withdrawal was successful: trade ID [%s]',rtrim($params['Sn'],'1'));
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS;
			$result['success'] = true;
			
		}else {
			$returnCode = $params['Status'];
			$returnDesc = $this->getStatusErrorMsg($params['Status']);
			$msg = sprintf('=anxinpay withdrawal was not successful: trade ID [%s], status code is [%s]: [%s]', rtrim($params['Sn'],'000'),$returnCode,$returnDesc);
			$result['message'] = $msg;
			$this->writePaymentErrorLog($msg, $params);
			
		}
		return $result;
	}

    private function getStatusErrorMsg($status) {
		$msg = "";
		switch ($status) {	

			case '4':
				$msg = "支付中";
				break;
                
            case '6':
                $msg = ":已退款";
                break;	

			default:
				$msg = "";
				break;
		}
		return $msg;
	}
	

    // public function callbackFromServer($transId, $params) {
	// 	$response_result_id = parent::callbackFromServer($orderId, $params);
    //     if(empty($params) || is_null($params)){
    //         $raw_post_data = file_get_contents('php://input', 'r');
    //         $params = json_decode($raw_post_data, true);
    //     }
    //     $result = array('success' => false, 'message' => 'Payment failed');

    //     $this->utils->debug_log('==========================anxinpay process withdrawalResult order id', $transId);
       
    //     $result = $params;

    //     $this->utils->debug_log("==========================anxinpay checkCallback params", $params);

    //     $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

    //     if (!$this->checkCallbackOrder($order, $params)) {
    //         return $result;
    //     }

    //     if($params['Status'] == self::STATUS_SUCCESS) {
    //         $this->utils->debug_log('==========================anxinpay withdrawal payment was successful: trade ID [%s]', $params['TransactionCode']);  // 看log ============anxinpay checkCallback params的回調參數是什麼格式,就怎麼取參數
            
    //         $msg = sprintf('=anxinpay withdrawal was successful: trade ID [%s]',$params['TransactionCode']);
    //         $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

    //         $result['message'] = self::RETURN_SUCCESS;
    //         $result['success'] = true;
    //    }else {
    //         $realStateDesc = $params['error'];
    //         $this->errMsg = '['.$realStateDesc.']';
    //         $msg = sprintf('=anxinpay withdrawal payment was not successful: '.$this->errMsg);
    //         $this->writePaymentErrorLog($msg, $params);
       
    //         $result['message'] = $msg;
    //     }

    //     return $result;
    // }

	private function checkCallbackOrder($order, $fields) {

        $requiredFields = array('Memid','Fee','Status','FinishTime','Amount','TradeType','Sn','Sign');
        
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("======================anxinpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

        if ($fields['sign']!=$this->validateSign($fields)) {
        	$this->writePaymentErrorLog('=========================anxinpay withdrawal checkCallback signature Error',$fields);
        	return false;
        }

    	if ($fields['Status'] == self::STATUS_FAILED) {
            $this->writePaymentErrorLog("=========================anxinpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

		if ($fields['Amount'] != $order['amount']) {
			$this->writePaymentErrorLog('=========================anxinpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
			return false;
		}

		if (rtrim($fields['Sn'],'000') != $order['transactionCode']) {
			$this->writePaymentErrorLog('=========================anxinpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
			return false;
		}

		# everything checked ok
		return true;
	}
    

    # -- bankinfo --
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("anxinpay_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting anxinpay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                "1" => array('name' => '工商银行', 'code' => 'ICBC'),
                "2" => array('name' => '招商银行', 'code' => 'CMB'),
                "3" => array('name' => '建设银行', 'code' => 'CCB'),
                "4" => array('name' => '农业银行', 'code' => 'ABC'),
                "5" => array('name' => '交通银行', 'code' => 'BOCOM'),
                "6" => array('name' => '中国银行', 'code' => 'BOC'),
                "8" => array('name' => '广发银行', 'code' => 'GDB'),
                // "10" => array('name' => '中信银行', 'code' => 'CITIC'),
                "11" => array('name' => '民生银行', 'code' => 'CMBC'),
                "12" => array('name' => '邮储银行', 'code' => 'PSBC'),
                "13" => array('name' => '兴业银行', 'code' => 'CIB'),
                // "14" => array('name' => '华夏银行', 'code' => 'HXB'),
                "15" => array('name' => '平安银行', 'code' => 'PAB'),
                "20" => array('name' => '光大银行', 'code' => 'CEB'),
                "32" => array('name' => '浦发银行', 'code' => 'SPDB')
            );
            $this->utils->debug_log("=======================getting anxinpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

	# -- Private functions --
	/**
	 * detail: After payment is complete, the gateway will invoke this URL asynchronously
	 *
	 * @param int $orderId
	 * @return void
	 */
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: Format the amount value for the API
	 *
	 * @param float $amount
	 * @return float
	 */
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}
		
    
    # -- signatures --
     private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtolower(md5($signStr)); 
        return $sign;
    }

    private function createSignStr($params) {
        $params = array(
            'Amount' => $params['Amount'],
            'Branch' => urlencode($params['Branch']),
            'Cardno' => $params['Cardno'],
            'City' => urlencode($params['City']),
            'Memid' => $params['Memid'],
            'outTradeNo' => $params['outTradeNo'],
            'Province' => urlencode($params['Province']),
            'Realname' => urlencode($params['Realname']),
            'tradeTime' => $params['tradeTime'],
            'Type' => $params['Type'],
            'Version' => $params['Version']
        );
		$signStr = '';
        foreach($params as $key => $value) {
            $signStr.=$key."=".$value."&";
        }  
        $signStr = $signStr.$this->getSystemInfo('key');
		return $signStr;
    }

    private function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if(empty($value) || $key == 'sign'){
                continue;
            }
            $signStr.=$key."=".$value."&";
        }     
        $signStr = $signStr.$this->getSystemInfo('key');
		$sign = strtolower(md5($signStr));
		if($params['sign'] == $sign){
			return true;
		}
		else{
			return false;
		}
	}
    
}