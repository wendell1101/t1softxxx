<?php
require_once dirname(__FILE__) . '/abstract_payment_api_largepay.php';

/**
 * LARGEPAY
 *
 * * LARGEPAY_WITHDRAWAL_PAYMENT_API, ID: 5540
 *
 * Required Fields:
 * * URL:https://pay.hongzhong777.com/withdraw/singleWithdraw
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
class Payment_api_largepay_withdrawal extends Abstract_payment_api_largepay {
    const CALLBACK_MSG_SUCCESS = 'SUCCESS';
    const RETURN_SUCCESS = 'S';
    const RETURN_FAILED  = 'F';
    
    const RETURN_SUCCESS_CODE = '000000';

    public function getPlatformCode() {
        return LARGEPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'largepay_withdrawal';
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

        $params = array();
		$params['merchant_no'] = $this->getSystemInfo("account");
		$params['order_no'] = $transId;
        $params['card_no'] = $accNum;  //銀行卡卡號
		$params['account_name'] =  base64_encode($name);  //收款人
		$params['bank_branch'] =  base64_encode($bankBranch);  
		$params['cnaps_no'] = '';  
        $params['bank_code'] = $bankInfo[$bank]['code'];  //銀行編號
		$params['bank_name'] =  base64_encode($bankInfo[$bank]['name']);
        $params['amount'] = $this->convertAmountToCurrency($amount); //元
        $params['sign'] = $this->sign($params);
        
        $this->CI->utils->debug_log('=========================largepay getWithdrawParams params', $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');		
        
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}
        # look up bank code
        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================largepay withdrawal bank whose bankTypeId=[$bank] is not supported by largepay");
            return array('success' => false, 'message' => 'Bank not supported by largepay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);        
        $url = $this->getSystemInfo('url');	
        
        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $this->CI->utils->debug_log('=========================largepay submitWithdrawRequest decoded Result', $decodedResult);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;

	}

    public function decodeResult($resultString, $queryAPI = false) {
        $this->utils->debug_log("=========================largepay decodeResult resultString", $resultString);

		$result = json_decode($resultString, true);
		$this->utils->debug_log("=========================largepay json_decode result", $result);

		$respCode = $result['result_code'];
		$resultMsg = $result['result_msg'];
		$this->utils->debug_log("=========================largepay withdrawal resultMsg", $resultMsg);

        if($respCode == self::RETURN_SUCCESS_CODE) {
            $message = "Largepay request successful. [".$respCode."]: ".$resultMsg;
            return array('success' => true, 'message' => $message);
        } 
        else {
            if($resultMsg == '' || $resultMsg == false) {
                    $this->utils->error_log("========================largepay return UNKNOWN ERROR!");
                    $resultMsg = "未知错误";
            }

            $message = "Largepay withdrawal response, Code: ".$respCode.", Msg: ".$resultMsg;
            return array('success' => false, 'message' => $message);
        }	
    }
    
    # Callback URI: /callback/fixed_process/<payment_id>
	public function getOrderIdFromParameters($params) {
        $raw_post_data = file_get_contents('php://input', 'r');
        $params = json_decode($raw_post_data, true);
		$this->utils->debug_log('====================================largepay getOrderIdFromParameters params', $params);
		
		$transId = null;
		//for fixed return url on browser
        if(isset($params['orders'][0]['mer_order_no'])) {
            $transId = $params['orders'][0]['mer_order_no'];
            $this->CI->utils->debug_log('================largepay getOrderIdFromParameters transId: ',$transId);
            return $transId;
        }
        else {
            $this->CI->utils->debug_log('================largepay getOrderIdFromParameters cannot get transId', $params);
        }
		return $transId;
	}
    
    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);

        $raw_post_data = file_get_contents('php://input', 'r');
        $params = json_decode($raw_post_data, true);
        
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->utils->debug_log("==========================largepay checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        $returnCallbackOrderId = $params['orders'][0]['mer_order_no'];
        $statusCode = $params['orders'][0]['result'];
        if($statusCode == self::RETURN_SUCCESS) {
            $this->utils->debug_log('==========================largepay withdrawal payment was successful: trade ID [%s]', $returnCallbackOrderId);
            
            $msg = sprintf('largepay withdrawal was successful: trade ID [%s]',$returnCallbackOrderId);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::CALLBACK_MSG_SUCCESS;
            $result['success'] = true;
       }else {
            $msg = sprintf('largepay withdrawal payment was not successful  trade ID [%s] ',$returnCallbackOrderId);
            $this->writePaymentErrorLog($msg, $params);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $msg;
        }

        return $result;
    }

 
    public function checkCallbackOrder($order, $fields) {
    	$validateSign = $fields;
    	$params = $fields['orders'][0];
    	unset($fields['orders'][0]);
    	$fields = array_merge($fields,$params);
        # does all required fields exist in the header?
        $requiredFields = array('mer_order_no', 'result', 'amount','withdraw_fee','sign');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================largepay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['sign'] != $this->validateSign($validateSign)) {
            $this->writePaymentErrorLog('==========================largepay withdrawal checkCallback signature Error',$fields);
            return false;
        }

        if ($fields['result'] == self::RETURN_FAILED) {
            $this->writePaymentErrorLog("=======================largepay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        $newAmount = (($fields['amount']) - ($fields['withdraw_fee']));
		if ($newAmount != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================largepay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
			return false;
		}

        if ($fields['mer_order_no'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================largepay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields); 
            return false;
        }

        # everything checked ok
        return true;
    }


    # -- signatures --
	public function sign($params) {
        $signStr = $this->createSignStr($params);
	    $sign = md5($signStr);

    
		return $sign;
	}

    private function createSignStr($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            $signStr .= "$key=$value&";
        }
        $signStr .= 'pay_pwd='. $this->getSystemInfo('pay_pwd').'&key='. $this->getSystemInfo('key');
        return $signStr;
    }


    public function validateSign($data) {
        $post=array(
		"merchant_no"=>$data['merchant_no'],
		"orders"=>$data['orders']
		);
    	$src = json_encode($post);
        $signStr =  $src .$this->getSystemInfo('key');
        $sign=strtolower(md5($signStr));
      
		return $sign;
	}

    # -- bankinfo --
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
            $this->utils->debug_log("=========================daddypay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                "1" => array('name' => '中国工商银行', 'code' => 'ICBC'),
                "2" => array('name' => '招商银行', 'code' => 'CMB'),
                "3" => array('name' => '中国建设银行', 'code' => 'CCB'),
                "4" => array('name' => '中国农业银行', 'code' => 'ABC'),
                "5" => array('name' => '交通银行', 'code' => 'BOCOM'),
                "6" => array('name' => '中国银行', 'code' => 'BOC'),
                "8" => array('name' => '广发银行', 'code' => 'GDB'),
                "10" => array('name' => '中信银行', 'code' => 'CNCB'),
                "11" => array('name' => '中国民生银行', 'code' => 'CMBC'),
                "12" => array('name' => '中国邮政储蓄银行', 'code' => 'PSBC'),
                "13" => array('name' => '兴业银行', 'code' => 'CIB'),
                "14" => array('name' => '华夏银行', 'code' => 'HXB'),
                "15" => array('name' => '平安银行', 'code' => 'PAB'),
                "18" => array('name' => '南京银行', 'code' => 'NJB'),
                "20" => array('name' => '中国光大银行', 'code' => 'CEB'),
                "24" => array('name' => '上海银行', 'code' => 'BOS'),
                "32" => array('name' => '浦发银行', 'code' => 'SPDB'),
                "119" => array('name' => '北京银行', 'code' => 'BCCB'),
                // "" => array('name' => '杭州银行', 'code' => 'HZB'),
                // "" => array('name' => '宁波银行', 'code' => 'NBB')
            );
            $this->utils->debug_log("=======================getting largepay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
}