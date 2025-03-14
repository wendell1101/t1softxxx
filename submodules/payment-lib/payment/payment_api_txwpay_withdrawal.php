<?php
require_once dirname(__FILE__) . '/abstract_payment_api_txwpay.php';

/**
 * TXWPAY  同兴旺
 *
 * * TXWPAY_WITHDRAWAL_PAYMENT_API, ID: 5341
 *
 * Required Fields:
 * * URL:http://27.124.8.30/Pay/GateWayPement.aspx
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
class Payment_api_txwpay_withdrawal extends Abstract_payment_api_txwpay {

    const PAYTYPE_T1 = '1';
    const PAYTYPE_D0 = '3';
    const PAYTYPE_AUTO = '0';
    const RETURN_SUCCESS_CODE = "0";
    const RETURN_FAILED_CODE = "2";
    const CALLBACK_PAYRESULT_SUCCESS = '20';
    const CALLBACK_PAYRESULT_FAILED = '30';
    const RETURN_SUCCESS = 'ok';

    public function getPlatformCode() {
        return TXWPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'txwpay_withdrawal';
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

        $bankInfo = $this->getBankInfo();

        $params = array();
        $params['mer_id'] = $this->getSystemInfo("account");
        $params['pay_type'] = $this->getSystemInfo("pay_type", self::PAYTYPE_AUTO);
		$params['order_id'] = $transId;
		$params['order_amt'] = $this->convertAmountToCurrency($amount); //元
		$params['notify_url'] = urlencode($this->getNotifyUrl($transId));
		$params['acct_name'] = urlencode($name);  //收款人
        $params['acct_id'] = $accNum;  //銀行卡卡號
        $params['acct_type'] = '0';  //0-借记卡
		$params['bank_code'] = $bankInfo[$bank]['code'];  //銀行編號
		$params['bank_branch'] = $bankBranch;  //開戶支行 //開戶分行
		$params['time_stamp'] = $this->getTimestamp();
        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log('=========================txwpay getWithdrawParams params', $params);
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
            $this->utils->error_log("========================txwpay withdrawal bank whose bankTypeId=[$bank] is not supported by txwpay");
            return array('success' => false, 'message' => 'Bank not supported by txwpay');
        }

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);


        $url = $this->getSystemInfo('url');

        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('=========================txwpay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;

	}

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }

		$result = json_decode($resultString, true);

		$respCode = $result['status_code'];
		$resultMsg = $result['status_msg'];

		if($queryAPI){
			if($respCode == self::RETURN_SUCCESS_CODE) {
				if($result['status_code'] == self::RETURN_SUCCESS_CODE){
					$message = 'Txwpay payment response successful, result Code: ['.$respCode."], Msg: ".$resultMsg;
					return array('success' => true, 'message' => $message);
				}elseif($respCode == self::RETURN_FAILED_CODE){
					$message = "Txwpay payment failed for Code: [".$respCode."], Msg: ".$resultMsg;
					$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $message);
					return array('success' => false, 'message' => $message);
				}else{
					$message = "Txwpay payment was not successful for Code: [".$respCode."], Msg: ".$resultMsg;
					return array('success' => false, 'message' => $message);
				}
			}else{
				$message = "Txwpay payment  result_code is Query failed".$resultMsg;
				return array('success' => false, 'message' => $message);
			}
		} else {
			if($respCode == self::RETURN_SUCCESS_CODE) {
	            $message = "Txwpay request successful. [".$respCode."]: ".$resultMsg;
	            return array('success' => true, 'message' => $message);
            }
            else {
				if($resultMsg == '' || $resultMsg == false) {
					$resultMsg = "未知错误";
				}

				$message = "Txwpay withdrawal response, Code: [".$respCode."], Msg: ".$resultMsg;
				return array('success' => false, 'message' => $message);
			}
		}

	}

	public function checkWithdrawStatus($transId) {
        $params = array();
		$params['mer_id'] = $this->getSystemInfo("account");
		$params['orderid'] = $transId;
		$params['time_stamp'] = $this->getTimestamp();
		$params['sign'] = $this->sign($params);

		$url = $this->getSystemInfo('check_withdraw_status_url', 'http://27.124.8.30/Pay/PementQuery.aspx');
		$response = $this->submitPostForm($url, $params, true, $transId);
		$decodedResult = $this->decodeResult($response, true);

		return $decodedResult;
    }

    public function callbackFromServer($transId, $params) {
		$response_result_id = parent::callbackFromServer($transId, $params);
        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $result = array('success' => false, 'message' => 'Payment failed');


        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['draw_result'] == self::CALLBACK_PAYRESULT_SUCCESS) {
            $msg = sprintf('txwpay withdrawal was successful: trade ID [%s]',$params['order_id']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS;
            $result['success'] = true;
        } elseif($params['draw_result'] == self::CALLBACK_PAYRESULT_FAILED) {
            $realStateDesc = $params['result_desc'];
            $msg = sprintf('txwpay withdrawal was failed: trade ID ['.$params['order_id'].'] ,Desc: '.$realStateDesc);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS;
            $result['success'] = true;
        } else {
            if(isset($params['result_desc'])){
                $realStateDesc = $params['result_desc'];
                $msg = sprintf('txwpay withdrawal was not successful: trade ID ['.$params['order_id'].'] ,Desc: '.$realStateDesc);
            }else{
                $msg = sprintf('txwpay withdrawal was not successful: trade ID [%s] ',$params['order_id']);
            }

            $this->debug_log($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }


    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array('order_id', 'pay_seq', 'draw_amt', 'draw_result', 'sign');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================txwpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['sign']!=$this->validateSign($fields)) {
            $this->writePaymentErrorLog('==========================txwpay withdrawal checkCallback signature Error',$fields);
            return false;
        }

		if ($fields['draw_amt'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================txwpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
			return false;
		}

        if ($fields['order_id'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================txwpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }


    # -- signatures --
	public function sign($params) {
        $signStr =  $this->createSignStr($params);
        $sign = strtolower(trim(md5($signStr)));
        return $sign;
    }

    private function createSignStr($params) {
        $params = array('mer_id'=>$params['mer_id'],'pay_type'=>$params['pay_type'],'order_id'=>$params['order_id'],'order_amt'=>$params['order_amt'],'acct_name'=>urldecode($params['acct_name']),'acct_id'=>$params['acct_id'],'time_stamp'=>$params['time_stamp']);
        $signStr = '';
        foreach ($params as $key => $value) {
            $signStr .= $key."=".$value."&";
        }
        $signStr .= 'key='. md5($this->getSystemInfo('key'));
        return $signStr;
    }

    public function validateSign($params) {
        $keys = array('mer_id'=>$params['mer_id'],'order_id'=>$params['order_id'],'pay_seq'=>$params['pay_seq'],'draw_amt'=>$params['draw_amt'],'draw_result'=>$params['draw_result']);
        $signStr = '';
        foreach ($keys as $key => $value) {
            $signStr .= $key."=".$value."&";
        }
        $signStr .= 'key='. md5($this->getSystemInfo('key'));
        $sign = strtolower(trim(md5($signStr)));
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
            $this->utils->debug_log("=========================txwpay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
				'1' => array('name' => '工商银行', 'code' => 'ICBC'),
				'2' => array('name' => '招商银行', 'code' => 'CMBCHINA'),
				'3' => array('name' => '建设银行', 'code' => 'CCB'),
				'4' => array('name' => '农业银行', 'code' => 'ABC'),
				'6' => array('name' => '中国银行', 'code' => 'BOC'),
				'10' => array('name' => '中信银行', 'code' => 'ECITIC'),
				'11' => array('name' => '民生银行', 'code' => 'CMBC'),
				'12' => array('name' => '中国邮政储蓄银行', 'code' => 'POST'),
				'13' => array('name' => '兴业银行', 'code' => 'CIB'),
				'14' => array('name' => '华夏银行', 'code' => 'HXB'),
				'15' => array('name' => '平安银行', 'code' => 'PINGAN'),
				'18' => array('name' => '南京银行', 'code' => 'NJCB'),
				'20' => array('name' => '光大银行', 'code' => 'CEB'),
				'88' => array('name' => '北京银行', 'code' => 'BCCB'),
            );
            $this->utils->debug_log("=======================getting txwpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }


	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}
}