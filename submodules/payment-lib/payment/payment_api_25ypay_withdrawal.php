<?php
require_once dirname(__FILE__) . '/abstract_payment_api_25ypay.php';

/**
 * 25YPAY
 *
 * * _25YPAY_WITHDRAWAL_PAYMENT_API, ID: 5405
 *
 * Required Fields:
 * * URL:http://pay.25ypay.cn/transfer
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
class Payment_api_25ypay_withdrawal extends Abstract_payment_api_25ypay {

    const RETURN_SUCCESS_CODE = '0000';
    const RETURN_SUCCESS = 'SUCCESS';
    const RETURN_SUCCESS_CODE_1 = "1";
    const RETURN_PROCESSING_STATUS = "1001";
    const RETURN_INSUFFICIENT_BALANCE_STATUS = "1002";

    public function getPlatformCode() {
        return _25YPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return '25ypay_withdrawal';
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
            $this->utils->error_log("========================25ypay withdrawal bank whose bankTypeId=[$bank] is not supported by 25ypay");
            return array('success' => false, 'message' => 'Bank not supported by 25ypay');
        }


        $params = array();
        $params['version'] = '1.8';
		$params['merchantId'] = $this->getSystemInfo("account");
		$params['orderId'] = $transId;
		$params['amount'] = $this->convertAmountToCurrency($amount); //元
        $params['cardNo'] = $accNum;  //銀行卡卡號
		$params['cardName'] = $name;  //收款人
		$params['cardType'] = '0';
        $params['bankCode'] = $bankInfo[$bank]['code'];  //銀行編號
		$params['notifyUrl'] = $this->getNotifyUrl($transId);
		$params['signType'] = 'MD5';
        $params['sign'] = $this->sign($params);
        
        $this->CI->utils->debug_log('=========================25ypay getWithdrawParams params', $params);
        return $params;
    }



	public function getOrderIdFromParameters($params) {
		$this->utils->debug_log('====================================25ypay callbackOrder params', $params);
		if(empty($params) || is_null($params)){
			$raw_post_data = file_get_contents('php://input', 'r');
        	$params = json_decode($raw_post_data, true);
		}
		
		$transId = null;
		//for fixed return url on browser
		if (isset($params['orders'][0]['mer_order_no'])) {
			$trans_id = $params['orders'][0]['mer_order_no'];

			$this->CI->load->model(array('wallet_model'));
	        $walletAccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($trans_id);

			if(!empty($walletAccount)){
               	$transId = $walletAccount['transactionCode'];
            }else{
            	$this->utils->debug_log('====================================25ypay callbackOrder transId is empty when getOrderIdFromParameters', $params);
            }
		}
		else {
			$this->utils->debug_log('====================================25ypay callbackOrder cannot get any transId when getOrderIdFromParameters', $params);
		}
		return $transId;
	}



    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');		
        
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
		$this->CI->utils->debug_log('======================================25ypay submitWithdrawRequest params:', $params);
        $url = $this->getSystemInfo('url');	
        
        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $this->CI->utils->debug_log('=========================25ypay submitWithdrawRequest decoded Result', $decodedResult);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;

	}

    public function decodeResult($resultString, $queryAPI = false) {


		$result = json_decode($resultString, true);
		$this->utils->debug_log("=========================25ypay json_decode result", $result);

		$respCode = $result['respCode'];
		$resultMsg = $result['respDesc'];
	
		
		if($queryAPI){
			if($respCode == self::RETURN_SUCCESS_CODE) {
				if($result['status'] == self::RETURN_SUCCESS_CODE_1){
					$message = '25ypay payment response successful, result Code:'.$result['status'].", Msg:".$resultMsg;
					return array('success' => true, 'message' => $message);
                }else{
					$message = "25ypay payment failed for Code:".$result['status'].", Msg:".$resultMsg;
					$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $message);
					return array('success' => false, 'message' => $message);
				}
            }else{
				$message = "25ypay payment result_code is Query failed for Code:".$respCode.", Msg:".$resultMsg;
				return array('success' => false, 'message' => $message);
			}
		}
		else{ 
			if($respCode == self::RETURN_SUCCESS_CODE) {
                $this->utils->error_log("=========================9999999999999999999 result status: " , $result['status']);
                if($result['status'] == self::RETURN_INSUFFICIENT_BALANCE_STATUS){
                    $message = "25ypay withdrawal response, Code: ".$result['status'].", Msg: 金額不足";
                }elseif($result['status'] == self::RETURN_PROCESSING_STATUS){
                    $message = "25ypay withdrawal response, Code: ".$result['status'].", Msg: 處理中";
                }else{
                    $message = "25ypay request successful. [".$respCode."]: ".$resultMsg;
                }
                return array('success' => true, 'message' => $message);
            }else{
				if($resultMsg == '' || $resultMsg == false) {
						$this->utils->error_log("===============================25ypay return UNKNOWN ERROR!");
						$resultMsg = "未知错误";
				}

				$message = "25ypay withdrawal response, Code: ".$respCode.", Msg: ".$resultMsg;
				return array('success' => false, 'message' => $message);
			}	
		}

	}

	public function checkWithdrawStatus($transId) {
        $params = array();
		$params['version'] = '1.8';
		$params['merchantId'] = $this->getSystemInfo("account");
        $params['busType'] = '02';
        $params['orderId'] = $transId;
		$params['signType'] = 'MD5';
		$params['sign'] = $this->sign($params);

		$url = $this->getSystemInfo('check_withdraw_status_url', 'http://pay.25ypay.cn/query');
		$response = $this->submitPostForm($url, $params, false, $transId);
		$decodedResult = $this->decodeResult($response, true);

		$this->CI->utils->debug_log('======================================25ypay checkWithdrawStatus params: ', $params);
		$this->CI->utils->debug_log('======================================25ypay checkWithdrawStatus url: ', $url );
	

		return $decodedResult;
    }
    
    public function callbackFromServer($transId, $params) {
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================25ypay withdrawal callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("=====================25ypay withdrawal callbackFromServer json_decode params", $params);
        }
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->utils->debug_log('==========================25ypay process withdrawalResult order id', $transId);
       
        $result = $params;

        $this->utils->debug_log("==========================25ypay checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['status'] == self::RETURN_SUCCESS) {
            $this->utils->debug_log('==========================25ypay withdrawal payment was successful: trade ID [%s]', $params['orderId']);
            
            $msg = sprintf('=25ypay withdrawal was successful: trade ID [%s]',$params['orderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS; 
            $result['success'] = true;
       }else {
            $realStateDesc = $params['respDesc'];
            $this->errMsg = '['.$realStateDesc.']';
            $msg = sprintf('=25ypay withdrawal payment was not successful: '.$this->errMsg);
            $this->writePaymentErrorLog($msg, $params);
       
            $result['message'] = $msg;
        }

        return $result;
    }

 
    public function checkCallbackOrder($order, $fields) {
		$requiredFields = array('merchantId', 'orderId','amount','status','signType','orderTime','version');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================25ypay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['sign']!=$this->validateSign($fields)) {
            $this->writePaymentErrorLog('==========================25ypay withdrawal checkCallback signature Error',$fields);
            return false;
        }

        if ($fields['status'] != self::RETURN_SUCCESS) {
            $this->writePaymentErrorLog("=======================25ypay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

		if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================25ypay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
			return false;
		}

        if ($fields['orderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================25ypay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields); 
            return false;
        }

        # everything checked ok
        return true;
    }


    # -- signatures --
	public function sign($params) {
		ksort($params);
		$signStr = '';
        foreach($params as $key => $value) {
            $signStr .= "$key=$value&";
		}

		$signStr .= 'key='. $this->getSystemInfo('key');
	    $sign = strtoupper(md5($signStr));

		return $sign;
	}


	private function validateSign($params){
		ksort($params);
		$signStr = '';
        foreach($params as $key => $value) {
            if( ($key == 'sign') || (empty($value)) ) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        
		$signStr .= 'key='. $this->getSystemInfo('key');
        $sign = strtoupper(md5($signStr));
        
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
        $bankInfoArr = $this->getSystemInfo("25ypay_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting 25ypay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                "1" => array('name' => '工商银行', 'code' => 'ICBC'),
                "2" => array('name' => '招商银行', 'code' => 'CMB'),
                "3" => array('name' => '建设银行', 'code' => 'CCB'),
                "4" => array('name' => '农业银行', 'code' => 'ABC'),
                "5" => array('name' => '交通银行', 'code' => 'BCOM'),
                "6" => array('name' => '中国银行', 'code' => 'BOC'),
                "8" => array('name' => '广发银行', 'code' => 'GDB'),
                "10" => array('name' => '中信银行', 'code' => 'CITIC'),
                "11" => array('name' => '民生银行', 'code' => 'CMBC'),
                "12" => array('name' => '邮政储蓄银行', 'code' => 'PSBC'),
                "14" => array('name' => '华夏银行', 'code' => 'HXB'),
                "15" => array('name' => '平安银行', 'code' => 'PAB'),
                "20" => array('name' => '光大银行', 'code' => 'CEBB'),
                "32" => array('name' => '浦发银行', 'code' => 'SPDB')
            );
            $this->utils->debug_log("=======================getting 25ypay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}