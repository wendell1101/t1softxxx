<?php
require_once dirname(__FILE__) . '/abstract_payment_api_duopay.php';

/**
 * DUOPAY 多付
 *
 * * DUOPAY_WITHDRAWAL_PAYMENT_API, ID: 5400
 *
 * Required Fields:
 * * URL:https://withdraw.duopay.net/gateway/transferPay
 * * Account
 * * Key
 * * Secret
 *
 * Field Values:
 * * URL: 
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_duopay_withdrawal extends Abstract_payment_api_duopay {
    const CALLBACK_MSG_SUCCESS = 'SUCCESS';
    const RETURN_SUCCESS = 'SUCCESS';
	const RETURN_FAILED  = 'FAILURE';

    public function getPlatformCode() {
        return DUOPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'duopay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

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
            $this->utils->error_log("========================duopay withdrawal bank whose bankTypeId=[$bank] is not supported by duopay");
            return array('success' => false, 'message' => 'Bank not supported by duopay');
        }

        $params = array();
		$params['version'] = 'V1.0.5';
        $params['serviceName'] = 'openTransferPay';
		$params['reqTime'] = date('Y-m-d H:i:s');
		$params['merchantId'] = $this->getSystemInfo("account");
		$params['busType'] = "PRV";
		$params['merOrderNo'] = $transId;
        $params['orderAmount'] = $this->convertAmountToCurrency($amount); //元
        $params['bankCode'] = $bankInfo[$bank]['code'];  //銀行編號
		$params['accountName'] = $name;  //收款人
        $params['accountCardNo'] = $accNum;  //銀行卡卡號
        $params['clientReqIP'] = $this->getClientIp();
		$params['notifyUrl'] = $this->getNotifyUrl($transId);
		$params['signType'] = 'MD5';
        $params['sign'] = $this->sign($params);
        
        $this->CI->utils->debug_log('=========================duopay getWithdrawParams params', $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');		
        
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $this->CI->utils->debug_log('======================================duopay submitWithdrawRequest params:', $params);
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
        $this->CI->utils->debug_log('=========================duopay submitWithdrawRequest decoded Result', $decodedResult);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;

	}

    public function decodeResult($resultString, $queryAPI = false) {
        $this->utils->debug_log("=========================duopay decodeResult resultString", $resultString);

		$result = json_decode($resultString, true);
		$this->utils->debug_log("=========================duopay json_decode result", $result);

		$respCode = $result['respCode'];
		$resultMsg = $result['respDesc'];
		$this->utils->debug_log("=========================duopay withdrawal resultMsg", $resultMsg);
		
		if($queryAPI){
			if($respCode == self::RETURN_SUCCESS) {
				if($result['status_code'] == self::RETURN_SUCCESS){
					$message = 'Duopay payment response successful, result Code:'.$respCode.", Msg: ".$resultMsg;
					return array('success' => true, 'message' => $message);
				}else{
					$message = "Duopay payment failed for Code:".$respCode.", Msg: ".$resultMsg;
					$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $message);
					return array('success' => false, 'message' => $message);
				}
			}else{
				$message = "Duopay payment  result_code is Query failed".$resultMsg;
				return array('success' => false, 'message' => $message);
			}
		}
		else{ 
			if($respCode == self::RETURN_SUCCESS) {
	            $message = "Duopay request successful. [".$respCode."]: ".$resultMsg;
	            return array('success' => true, 'message' => $message);
            } 
            else {
				if($resultMsg == '' || $resultMsg == false) {
						$this->utils->error_log("========================Duopay return UNKNOWN ERROR!");
						$resultMsg = "未知错误";
				}

				$message = "Duopay withdrawal response, Code: ".$respCode.", Msg: ".$resultMsg;
				return array('success' => false, 'message' => $message);
			}	
		}

	}

	public function checkWithdrawStatus($transId) {
        $params = array();
		$params['version']     = 'V1.0.5';
        $params['serviceName'] = 'openTransferQuery';
		$params['reqTime']     = date('Y-m-d H:i:s');
		$params['merchantId']  = $this->getSystemInfo("account");
		$params['merOrderNo']  = $transId;
		$params['signType']    = 'MD5';
		$params['sign']        = $this->sign($params);

		$url = $this->getSystemInfo('check_withdraw_status_url', 'https://query.duopay.net/gateway/transferQuery');
		$response = $this->submitPostForm($url, $params, true, $transId);
		$decodedResult = $this->decodeResult($response, true);

		$this->CI->utils->debug_log('======================================duopay checkWithdrawStatus params: ', $params);
		$this->CI->utils->debug_log('======================================duopay checkWithdrawStatus url: ', $url );
		$this->CI->utils->debug_log('======================================duopay checkWithdrawStatus result: ', $response );
		$this->CI->utils->debug_log('======================================duopay checkWithdrawStatus decoded Result', $decodedResult);

		return $decodedResult;
    }
    
    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);

        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->utils->debug_log("==========================duopay checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['transferStatus'] == self::RETURN_SUCCESS) {
            $this->utils->debug_log('==========================duopay withdrawal payment was successful: trade ID [%s]', $params['merOrderNo']);
            
            $msg = sprintf('duopay withdrawal was successful: trade ID [%s]',$params['merOrderNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::CALLBACK_MSG_SUCCESS;
            $result['success'] = true;

       }elseif($params['transferStatus'] == self::RETURN_FAILED){
            $this->utils->debug_log('==========================duopay withdrawal payment was failed: trade ID [%s]', $params['merOrderNo']);
            
            $msg = sprintf('duopay withdrawal was failed: trade ID [%s]',$params['merOrderNo']);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);

            $result['message'] = self::CALLBACK_MSG_SUCCESS;
            $result['success'] = true;

       }else{
            $msg = sprintf('duopay withdrawal payment was not successful  trade ID [%s] ',$params['merOrderNo']);
            $this->debug_log($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

 
    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array('merchantId', 'orderAmount', 'merOrderNo', 'transferStatus', 'sign');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================duopay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['sign'] != $this->validateSign($fields)) {
            $this->writePaymentErrorLog('==========================duopay withdrawal checkCallback signature Error',$fields);
            return false;
        }

		if ($fields['orderAmount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================duopay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
			return false;
		}

        if ($fields['merOrderNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================duopay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields); 
            return false;
        }

        # everything checked ok
        return true;
    }


    # -- signatures --
	public function sign($params) {
        $signStr = $this->createSignStr($params);
	    $sign = strtoupper(md5($signStr));

     
		return $sign;
	}


	private function validateSign($params){
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        
		if($params['sign'] == $sign){
			return true;
		}
        else{
        
            return false;
        }

	}

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if(($key == 'sign') || (empty($value)) || $value == '') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'key='. $this->getSystemInfo('key');
        return $signStr;
    }


    # -- bankinfo --
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("duopay_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting duopay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '1' => array('name' => '中国工商银行', 'code' => 'BANK_ICBC'),
                '2' => array('name' => '招商银行', 'code' => 'BANK_CMB'),
                '3' => array('name' => '中国建设银行', 'code' => 'BANK_CCB'),
                '4' => array('name' => '中国农业银行', 'code' => 'BANK_ABC'),
                '5' => array('name' => '交通银行', 'code' => 'BANK_BOCOM'),
                '6' => array('name' => '中国银行', 'code' => 'BANK_BOC'),
                // '7' => array('name' => '深圳发展银行', 'code' => ''),
                '8' => array('name' => '广发银行股份有限公司', 'code' => 'BANK_GDB'),
                '9' => array('name' => '东莞农村商业银行', 'code' => 'BANK_BRCB'),
                '10' => array('name' => '中信银行', 'code' => 'BANK_CITIC'),
                '11' => array('name' => '民生银行', 'code' => 'BANK_CMBC'),
                '12' => array('name' => '中国邮政储蓄银行', 'code' => 'BANK_PSBC'),
                '13' => array('name' => '兴业银行', 'code' => 'BANK_CIB'),
                '14' => array('name' => '华夏银行', 'code' => 'BANK_HXBC'),
                '15' => array('name' => '平安银行', 'code' => 'BANK_PAB'),
                '16' => array('name' => '广西农村信用社联合社	', 'code' => 'BANK_GXNXB'),
                '17' => array('name' => '广州银行股份有限公司', 'code' => 'BANK_GZCB'),
                '18' => array('name' => '南京银行', 'code' => 'BANK_BON'),
                // '19' => array('name' => '广州农商银行', 'code' => ''),
                '20' => array('name' => '光大银行', 'code' => 'BANK_CEB'),
                '24' => array('name' => '浦东发展银行', 'code' => 'BANK_SPDB')
            );
            $this->utils->debug_log("=======================getting duopay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}