<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * MBPAY
 *
 *
 * * MBPAY_WITHDRAWAL_PAYMENT_API, ID: 5413
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra_info
 *
 * Field Values:
 * * URL: https://www.mbpay.cc/api/1/order/withdraw
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_mbpay_withdrawal extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 200;
    const CALLBACK_STATUS_SUCCESS = '3';
    const RETURN_SUCCESS = 'success';

	public function getPlatformCode() {
		return MBPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'mbpay_withdrawal';
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
		$this->CI->utils->debug_log('======================================mbpay submitWithdrawRequest params:', $params);
		
		$url = $this->getSystemInfo('url').'?sn='.$this->getSystemInfo('sn');
        list($content, $response_result) = $this->processCurl($url, $params, $transId, true);
		$this->CI->utils->debug_log('====================================== mbpay list content', $content);
		$this->CI->utils->debug_log('====================================== mbpay list response_result', $response_result);

		$decodedResult = $this->decodeResult($content);
		$this->CI->utils->debug_log('====================================== mbpay submitWithdrawRequest decoded Result', $decodedResult);
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
		$params['BankName'] = $bankInfo[$bank]['name'];
		$params['AccountNumber'] = $accNum;
        $params['AccountName'] = $name;
        $params['TransactionAmount'] = $this->convertAmountToCurrency($amount);
        $params['TransactionCode'] = $transId;
        $params['Callback'] = $this->getNotifyUrl($transId);

		return $params;
	}	

	public function decodeResult($resultString, $queryAPI = false) {
        $this->utils->debug_log("=========================mbpay decodeResult resultString", $resultString);

		$result = json_decode($resultString, true);
		$this->utils->debug_log("=========================mbpay json_decode result", $result);

		$respCode = $result['status'];
		$resultMsg = "未知错误";

		if($queryAPI){ 
			if($respCode == true) {
				if($result['status'] == true){
					$message = 'mbpay payment response successful, result Code:'.$respCode;
					return array('success' => true, 'message' => $message);
				}else{
					if(isset($result['error']) && $respCode == false) {
						$resultMsg = $result['error'];
					}
					$message = "mbpay payment failed for Code: ".$respCode.", Msg: ".$resultMsg;
					$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $message);
					return array('success' => false, 'message' => $message);
				}
			}else{
				if(isset($result['error']) && $respCode == false) {
					$resultMsg = $result['error'];
					$message = "mbpay withdrawal response, Msg: ".$resultMsg;
				}
				$message = "mbpay payment  result_code is Query failed ".$resultMsg;
				return array('success' => false, 'message' => $message);
			}
		}
		else{ 
			if($result['status'] == true) {
	            $message = "mbpay request successful.";
	            return array('success' => true, 'message' => $message);
            } 
            else {
				if(isset($result['error']) && $respCode == false) {
					$resultMsg = $result['error'];
					$message = "mbpay withdrawal response, Msg: ".$resultMsg;
				}
				
				$this->utils->error_log("========================mbpay return UNKNOWN ERROR!");
				$message = "mbpay withdrawal response, Msg: ".$resultMsg;
				return array('success' => false, 'message' => $message);
			}	
		}
	}



	public function checkWithdrawStatus($transId) {

        $params = array();
		$params['OrderType'] = 'withdraw';
		$params['TransactionCode'] = $transId;

		$queryurl = $this->getSystemInfo('check_status_url','https://www.mbpay.cc/api/1/order/query');
		$url = $queryurl.'?sn='.$this->getSystemInfo('sn');
		
		$response = $this->processCurl($url, $params, $transId, false);
		$decodedResult = $this->decodeResult($response, true);

		$this->CI->utils->debug_log('======================================mbpay checkWithdrawStatus params: ', $params);
		$this->CI->utils->debug_log('======================================mbpay checkWithdrawStatus url: ', $url );
		$this->CI->utils->debug_log('======================================mbpay checkWithdrawStatus response: ', $response);
		$this->CI->utils->debug_log('======================================mbpay checkWithdrawStatus decodedResult: ', $decodedResult);
		return $decodedResult;
    }


    public function callbackFromServer($transId, $params) {
		$response_result_id = parent::callbackFromServer($transId, $params);
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->utils->debug_log('==========================mbpay process withdrawalResult order id', $transId);
       
        $result = $params;

        $this->utils->debug_log("==========================mbpay checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

	    if($params['Status'] == self::CALLBACK_STATUS_SUCCESS) {
            $this->utils->debug_log('==========================mbpay withdrawal payment was successful: trade ID [%s]', $params['TransactionCode']); 
            
            $msg = sprintf('=mbpay withdrawal was successful: trade ID [%s]',$params['TransactionCode']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS;
			$result['success'] = true;
			
		}else {
			$returnCode = $params['Status'];
			$returnDesc = $this->getStatusErrorMsg($params['Status']);
			$msg = sprintf('=mbpay withdrawal was not successful: trade ID [%s], status code is [%s]: [%s]', $params['TransactionCode'],$returnCode,$returnDesc);
			$result['message'] = $msg;
			$this->writePaymentErrorLog($msg, $params);
			
		}
		return $result;
	}

    private function getStatusErrorMsg($status) {
		$msg = "";
		switch ($status) {
			case '1':
				$msg = "處理中";
				break;		

			case '2':
				$msg = "交易進行中";
				break;

			case '4':
				$msg = "交易取消";
				break;

			case '5':
				$msg = "提交完成";
				break;

			default:
				$msg = "";
				break;
		}
		return $msg;
	}

	private function checkCallbackOrder($order, $fields) {

        $requiredFields = array('TransactionCode','TransactionAmount','Status');
        
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("======================mbpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['TransactionAmount'] != $order['amount']) {
			$this->writePaymentErrorLog('=========================mbpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
			return false;
		}

		if ($fields['TransactionCode'] != $order['transactionCode']) {
			$this->writePaymentErrorLog('=========================mbpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
			return false;
		}

		# everything checked ok
		return true;
	}
    

	# -- amount --
	protected function convertAmountToCurrency($amount) {
        return number_format($amount, 0); 
	}


    # -- bankinfo --
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("mbpay_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting mbpay bank info from extra_info: ", $bankInfo);
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
            $this->utils->debug_log("=======================getting mbpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
    

    protected function processCurl($url, $params, $orderSecureId=NULL, $return_all=false){
		try {
			$ch = curl_init();
			$token = $this->getSystemInfo('key');

			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/x-www-form-urlencoded',
				'Authorization: '.$token)
			);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
			curl_setopt($ch, CURLOPT_POST, TRUE); 
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  false);
			curl_setopt($ch, CURLOPT_SSLVERSION, 6); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params) );


			$this->setCurlProxyOptions($ch);

			$response    = curl_exec($ch);
			$errCode     = curl_errno($ch);
			$error       = curl_error($ch);
			$statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			curl_close($ch);

			$this->CI->utils->debug_log('==============================mbpay processCurl','url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
			#save response result
        	$response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $orderSecureId);
			
			if($return_all){
				$response_result = [
					$params, $response, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $orderSecureId
				];
				$this->CI->utils->debug_log('=========================processCurl return_all response_result', $response_result);
				return array($response, $response_result);
			}
			return $response;
		} catch (Exception $e) {
			$this->CI->utils->error_log('POST failed', $e);
		}
	}

    
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}
	
}