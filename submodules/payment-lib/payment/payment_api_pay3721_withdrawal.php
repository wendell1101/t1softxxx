<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pay3721.php';

/**
 * PAY3721  恒久
 *
 * * PAY3721_WITHDRAWAL_PAYMENT_API, ID: 5291
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Secret
 *
 * Field Values:
 * * URL: http://pay3721.cn/pay/api/payother_api.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_pay3721_withdrawal extends Abstract_payment_api_pay3721 {

    const RETURN_SUCCESS_CODE_0 = "0000";
    const RETURN_SUCCESS_CODE_1 = "1001";
    const RETURN_SUCCESS = 'success';

    public function getPlatformCode() {
        return PAY3721_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'pay3721_withdrawal';
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
            $this->utils->error_log("========================pay3721 withdrawal bank whose bankTypeId=[$bank] is not supported by pay3721");
            return array('success' => false, 'message' => 'Bank not supported by pay3721');
        }


        $params = array();
		$params['channel'] = $this->getSystemInfo("account");
        $params['callback'] = $this->getNotifyUrl($transId);
		$params['orderid'] = $transId;
		$params['txnAmt'] = $this->convertAmountToCurrency($amount); //元
		$params['paytype'] = 'weixin';
		$params['ip'] = $this->getClientIP();
        $params['sign'] = $this->sign($params);
        
		$params['certify_id'] = $this->randomNum(11);
		$params['iss_ins_name'] = $bankInfo[$bank]['name'];
		$params['bankcardowner'] = $name;
		$params['depositbank'] = $bankBranch;
		$params['bankId'] = $bankInfo[$bank]['bankId'];
		$params['bankno'] = $accNum;
        $params['phonenumber'] = $this->randomNum(10);
        
        $this->CI->utils->debug_log('=========================pay3721 getWithdrawParams params', $params);
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
		$this->CI->utils->debug_log('======================================pay3721 submitWithdrawRequest params:', $params);
        $url = $this->getSystemInfo('url');	
        
        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);
        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('=========================pay3721 submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;

	}

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $this->utils->debug_log("=========================pay3721 decodeResult resultString", $resultString);

		$result = json_decode($resultString, true);
		$this->utils->debug_log("=========================pay3721 json_decode result", $result);

		$respCode = $result['resultCode'];
		$resultMsg = $result['resultMsg'];
		$this->utils->debug_log("=========================pay3721 withdrawal resultMsg", $resultMsg);
		
		if($queryAPI){ 
			if($respCode == self::RETURN_SUCCESS_CODE_0 || $respCode == self::RETURN_SUCCESS_CODE_1) {
				if($result['resultCode'] == self::RETURN_SUCCESS_CODE_0){
					$message = 'Pay3721 payment response successful, result Code:'.$respCode.", Msg: ".$resultMsg;
					return array('success' => true, 'message' => $message);
				}else{
					$message = "Pay3721 payment failed for Code:".$respCode.", Msg: ".$resultMsg;
					$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $message);
					return array('success' => false, 'message' => $message);
				}
			}else{
				$message = "Pay3721 payment  result_code is Query failed".$resultMsg;
				return array('success' => false, 'message' => $message);
			}
		}
		else{ 
			if($respCode == self::RETURN_SUCCESS_CODE_0 || $respCode == self::RETURN_SUCCESS_CODE_1) {
	            $message = "Pay3721 request successful. [".$respCode."]: ".$resultMsg;
	            return array('success' => true, 'message' => $message);
            } 
            else {
				if($resultMsg == '' || $resultMsg == false) {
						$this->utils->error_log("========================pay3721 return UNKNOWN ERROR!");
						$resultMsg = "未知错误";
				}

				$message = "Pay3721 withdrawal response, Code: ".$respCode.", Msg: ".$resultMsg;
				return array('success' => false, 'message' => $message);
			}	
		}
    }

	public function checkWithdrawStatus($transId) {
        $params = array();
		$params['channel'] = $this->getSystemInfo("account"); 
		$params['orderid'] = $transId;
		$params['sign'] = $this->sign($params);
		$params['query_type'] = 'otherpay';

		$url = $this->getSystemInfo('check_withdraw_status_url', 'http://pay.buyustar.com/pay/api/query.php');
		$response = $this->submitPostForm($url, $params, false, $transId);
		$decodedResult = $this->decodeResult($response, true);

		$this->CI->utils->debug_log('======================================pay3721 checkWithdrawStatus params: ', $params);
		$this->CI->utils->debug_log('======================================pay3721 checkWithdrawStatus url: ', $url );
		$this->CI->utils->debug_log('======================================pay3721 checkWithdrawStatus result: ', $response );
		$this->CI->utils->debug_log('======================================pay3721 checkWithdrawStatus decoded Result', $decodedResult);

		return $decodedResult;
    }
    

    public function callbackFromServer($transId, $params) {
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->utils->debug_log('==========================pay3721 process withdrawalResult order id', $transId);
       
        $result = $params;

        $this->utils->debug_log("==========================pay3721 checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['respCode'] == self::RETURN_SUCCESS_CODE_0 || $params['respCode'] == self::RETURN_SUCCESS_CODE_1) {
            $this->utils->debug_log('==========================pay3721 withdrawal payment was successful: trade ID [%s]', $params['merOrderId']);
            
            $msg = sprintf('=pay3721 withdrawal was successful: trade ID [%s]',$params['merOrderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS;
            $result['success'] = true;
       }else {
            $realStateDesc = $params['respMsg'];
            $this->errMsg = '['.$realStateDesc.']';
            $msg = sprintf('=pay3721 withdrawal payment was not successful: '.$this->errMsg);
            $this->writePaymentErrorLog($msg, $params);
       
            $result['message'] = $msg;
        }

        return $result;
    }

 
     public function checkCallbackOrder($order, $fields) { 
        $requiredFields = array('respCode', 'merOrderId', 'txnAmt');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================pay3721 withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['sign']!=$this->validateSign($fields)) {
            $this->writePaymentErrorLog('==========================pay3721 withdrawal checkCallback signature Error',$fields);
            return false;
        }

        if ($fields['respCode'] != self::RETURN_SUCCESS_CODE_0 && $fields['respCode'] != self::RETURN_SUCCESS_CODE_1) {
            $this->writePaymentErrorLog("=======================pay3721 checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

		if ($fields['txnAmt'] != $this->convertAmountToCent($this->convertAmountToCurrency($order['amount']))) {
            $this->writePaymentErrorLog('=========================pay3721 withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
			return false;
		}

        if ($fields['merOrderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================pay3721 withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }


    # -- signatures --
	public function sign($params) {
        $signStr =  $this->createSignStr($params);
     $sign = strtolower(md5($signStr));
     
     return $sign;
 }

 private function createSignStr($params) {
    $params = array('channel'=>$params['channel'],'callback'=>$params['callback'],'orderid'=>$params['orderid'],'txnAmt'=>$params['txnAmt'],'paytype'=>$params['paytype'],'ip'=>$params['ip']);
     ksort($params);
     $signStr = '';
     foreach ($params as $key => $value) {

         $signStr .= $key."=".$value."&";
     }
     $signStr .= 'key='. $this->getSystemInfo('key');
     return $signStr;
 }

 public function validateSign($params) {
     $keys = array('respCode'=>$params['respCode'],'merOrderId'=>$params['merOrderId'],'txnAmt'=>$params['txnAmt']);
     ksort($keys);
     $signStr = '';
     foreach ($keys as $key => $value) {

         $signStr .= $key."=".$value."&";
     }
     $signStr .= 'key='. $this->getSystemInfo('key');
     $sign = strtolower(md5($signStr));
     if($params['sign'] == $sign){
         return true;
     }
     else{
         
         return false;
     }
 }


     # -- bankinfo --
     public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("pay3721_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting pay3721 bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
				'1' => array('name' => '工商银行', 'code' => 'ICBC', 'bankId' => '01020000'),
				'2' => array('name' => '招商银行', 'code' => 'CMB', 'bankId' => '03080000'),
				'3' => array('name' => '建设银行', 'code' => 'CCB', 'bankId' => '01050000'),
				'4' => array('name' => '农业银行', 'code' => 'ABC', 'bankId' => '01030000'),
				'5' => array('name' => '交通银行', 'code' => 'COMM', 'bankId' => '03010000'),
				'6' => array('name' => '中国银行', 'code' => 'BOC', 'bankId' => '01040000'),
				'7' => array('name' => '深圳发展银行', 'code' => 'SDB', 'bankId' => '03070000'),
				'8' => array('name' => '广东发展银行', 'code' => 'GDB', 'bankId' => '03060000'),
				// '9' => array('name' => '东莞农商银行', 'code' => 'DRCBANK', 'bankId' => '402602000018'),
				'10' => array('name' => '中信银行', 'code' => 'CITIC', 'bankId' => '03020000'),
				'11' => array('name' => '民生银行', 'code' => 'CMBC', 'bankId' => '03050000'),
				'12' => array('name' => '中国邮政储蓄银行', 'code' => 'PSBC', 'bankId' => '01000000'),
				'13' => array('name' => '兴业银行', 'code' => 'CIB', 'bankId' => '03090000'),
				'14' => array('name' => '华夏银行', 'code' => 'HXB', 'bankId' => '03040000'),
				'15' => array('name' => '平安银行', 'code' => 'SZPAB', 'bankId' => '04100000'),
				'16' => array('name' => '广西农村信用社', 'code' => 'GX966888', 'bankId' => '14436100'),
				'17' => array('name' => '广州银行', 'code' => 'GZCB', 'bankId' => '04135810'),
				'18' => array('name' => '南京银行', 'code' => 'NJCB', 'bankId' => '04243010'),
                // '19' => array('name' => '广州农商银行', 'code' => 'GRCB', 'bankId' => '314581000011'),
				'20' => array('name' => '光大银行', 'code' => 'CEB', 'bankId' => '03030000'),
				'88' => array('name' => '北京银行', 'code' => 'BCCB', 'bankId' => '04031000'),
            );
            $this->utils->debug_log("=======================getting pay3721 bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    public function randomNum($length) {
	    $str="12345678901234567890";
	    $result=substr(str_shuffle($str),0,$length); 
	    return $result;
    }

    
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
    
}