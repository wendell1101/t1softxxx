<?php
require_once dirname(__FILE__) . '/abstract_payment_api_emerchantpay.php';

/**
 * Emerchant Payment
 *
 * * EMERCHANT_WITHDRAWAL_PAYMENT_API, ID: 5543
 *
 * Required Fields:
 *
 * * URL : https://gate.emerchantpay.net/process/
 *
 * Field Values:
 *
 * * Extra Info:
 * > {
 * >    "terminal_token" : "## Terminal Token ##",
 * >    "auth_username" : "## Username for http basic auth ##",
 * >    "auth_password" : "## Password for http basic auth ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_emerchantpay_withdrawal extends Abstract_payment_api_emerchantpay {

    const TRANS_TYPE_BANKPAYOUT = 'bank_payout';

    const RETURN_STATUS_SUCCESS = 'approved';
    const RETURN_STATUS_PENDING = 'pending';
    const RETURN_STATUS_PENDING_ASYNC = 'pending_async';

    const CALLBACK_SUCCESS = 'approved';
    const CALLBACK_DECLINED = 'declined';
    const CALLBACK_ERROR = 'error';

    public function getPlatformCode() {
        return EMERCHANT_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'emerchantpay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

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


		# Get player contact number
		$order         = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
		$playerId      = $order['playerId'];
		$playerDetails = $this->getPlayerDetails($playerId);
		$firstname     = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'no firstName';
		$lastname      = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))  	  ? $playerDetails[0]['lastName']	   : 'no lastName';
		$email         = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))     	  ? $playerDetails[0]['email'] 		   : 'mail@example.com';
        $telephone     = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '+12063582043';
        $address       = (isset($playerDetails[0]) && !empty($playerDetails[0]['address']))       ? $playerDetails[0]['address']       : 'no address';
        $city          = (isset($playerDetails[0]) && !empty($playerDetails[0]['city']))          ? $playerDetails[0]['city']          : 'no city';
        $zipcode       = (isset($playerDetails[0]) && !empty($playerDetails[0]['zipcode']))       ? $playerDetails[0]['zipcode']       : '1000';
        $country       = (isset($playerDetails[0]) && !empty($playerDetails[0]['country']))       ? $playerDetails[0]['country']       : 'CN';

        # look up bank code
        $bankInfo = $this->getBankInfo();

        $params = array();
        $params['transaction_type'] = self::TRANS_TYPE_BANKPAYOUT;
        $params['transaction_id'] = $transId;
        $params['usage'] = 'Withdrawal';
        $params['remote_ip'] = $this->getClientIp();
        $params['notification_url'] = $this->getNotifyUrl($transId);
        $params['return_success_url'] = $this->getReturnUrl($transId);
        $params['return_failure_url'] = $this->getReturnFailUrl($transId);
        $params['amount'] = (int)$this->convertAmountToCurrency($amount,$order['dwDateTime']);
        $params['currency'] = 'CNY';
        $params['customer_email'] = $email;
        $params['customer_phone'] = $telephone;
        $params['bank_name'] = $bankInfo[$bank]['name'];
        $params['bank_branch'] = $bankBranch;
        $params['bank_account_name'] = $name;
        $params['bank_account_number'] = $accNum;
        $params['id_card_number'] = $accNum;
        $params['payer_bank_phone_number'] = $this->randomNum(11);
        $params['billing_address']['first_name'] = $firstname;
        $params['billing_address']['last_name'] = $lastname;
        $params['billing_address']['address1'] = $address;
        $params['billing_address']['zip_code'] = $zipcode;
        $params['billing_address']['city'] = $city;
        $params['billing_address']['state'] = 'no state';
        $params['billing_address']['country'] = $country;

        $this->CI->utils->debug_log('=========================emerchantpay getWithdrawParams params', $params);
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
            $this->utils->error_log("========================emerchantpay withdrawal bank whose bankTypeId=[$bank] is not supported by emerchantpay");
            return array('success' => false, 'message' => 'Bank not supported by emerchantpay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        $xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><payment_transaction></payment_transaction>");

        $xml = $this->CI->utils->arrayToXml($params, $xml_object);
        $xml = strtr($xml, array("\n" => '',"\r" => ''));

        list($responseXml, $response_result) = $this->submitXml($xml, $transId, $return_all=true);

        $decodedResult = $this->decodeResult($responseXml);
        $decodedResult['response_result'] = $response_result;

        $this->utils->debug_log("=========================emerchantpay submitWithdrawRequest responseXml", $responseXml);
        $this->utils->debug_log("=========================emerchantpay submitWithdrawRequest decoded Result", $decodedResult);

        return $decodedResult;
	}

    public function decodeResult($resultString, $queryAPI = false) {
        $this->utils->debug_log("=========================emerchantpay decodeResult resultString", $resultString);

        $result = $this->parseResultXML($resultString);
        $this->utils->debug_log("=========================emerchantpay parseResultXML decoded", $result);

        $respCode = $result['status'];
        if(isset($result['technical_message'])){
            $resultMsg = $result['technical_message'];
            $this->utils->debug_log("=========================emerchantpay withdrawal resultMsg", $resultMsg);
        }

        if($respCode == self::RETURN_STATUS_SUCCESS || $respCode == self::RETURN_STATUS_PENDING || $respCode == self::RETURN_STATUS_PENDING_ASYNC) {
            $message = "emerchantpay request successful.";
            return array('success' => true, 'message' => $message);
        }
        else {
            if($resultMsg == '' || $resultMsg == false) {
                    $this->utils->error_log("========================emerchantpay return UNKNOWN ERROR!");
                    $resultMsg = "未知错误";
            }
            $message = "emerchantpay withdrawal response, State [ ".$respCode." ] : ".$resultMsg;
            return array('success' => false, 'message' => $message);
        }
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================emerchantpay raw_post_data", $raw_post_data);
            $params = $this->parseResultXML($raw_post_data);
            $this->CI->utils->debug_log("=====================emerchantpay parseResultXML params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        $xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><notification_echo></notification_echo>");
        $unique_id = array(
            'unique_id' => $params['unique_id']
        );
        $xml = $this->CI->utils->arrayToXml($unique_id, $xml_object);

        if($params['status'] == self::CALLBACK_SUCCESS) {
            $this->utils->debug_log('==========================emerchantpay withdrawal payment was successful: trade ID [%s]', $params['transaction_id']);

            $msg = sprintf('emerchantpay withdrawal was successful: trade ID [%s]',$params['transaction_id']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = $xml;
            $result['success'] = true;

       }elseif($params['status'] == self::CALLBACK_ERROR || $params['status'] == self::CALLBACK_DECLINED){
            $this->utils->debug_log('==========================emerchantpay withdrawal payment was failed: trade ID [%s]', $params['transaction_id']);

            $msg = sprintf('emerchantpay withdrawal payment was failed: trade ID [%s] ',$params['transaction_id']);
            $this->writePaymentErrorLog($msg, $params);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $xml;
            $result['success'] = true;

       }else{
            $msg = sprintf('emerchantpay withdrawal payment was not successful  trade ID [%s] ',$params['transaction_id']);
            $this->debug_log($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array('signature', 'status', 'transaction_id', 'amount','unique_id');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================emerchantpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['signature'] != $this->validateSign($fields)) {
            $this->writePaymentErrorLog('==========================emerchantpay withdrawal checkCallback signature Error',$fields);
            return false;
        }

        $amount = $this->convertAmountToCurrency($order['amount'], $order['dwDateTime']);
		if ($fields['amount'] != $amount) {
            $this->writePaymentErrorLog('=========================emerchantpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $amount, $fields);
			return false;
		}

        if ($fields['transaction_id'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================emerchantpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
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
            $this->utils->debug_log("==================getting emerchantpay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                "1" => array('name' => '中国工商银行', 'code' => 'ICBC'),
                "2" => array('name' => '招商银行', 'code' => 'CMB'),
                "3" => array('name' => '中国建设银行', 'code' => 'CCB'),
                "4" => array('name' => '中国农业银行', 'code' => 'ABC'),
                "5" => array('name' => '中国交通银行', 'code' => 'BOCOM'),
                "6" => array('name' => '中国银行', 'code' => 'BOC'),
                "7" => array('name' => '深圳发展银行', 'code' => ''),
                "8" => array('name' => '广发银行', 'code' => 'GDB'),
                "10" => array('name' => '中信银行', 'code' => 'CNCB'),
                "11" => array('name' => '民生银行', 'code' => 'CMBC'),
                "13" => array('name' => '中国兴业银行', 'code' => 'CIB'),
                "14" => array('name' => '华夏银行', 'code' => 'HXB'),
                "15" => array('name' => '平安银行', 'code' => 'PAB'),
                "17" => array('name' => '广州银行', 'code' => ''),
                "18" => array('name' => '南京银行', 'code' => 'NJB'),
                "20" => array('name' => '光大银行', 'code' => 'CEB'),
                "24" => array('name' => '上海浦发银行', 'code' => 'SPDB'),
                "79" => array('name' => '上海银行', 'code' => 'BOS'),
                "80" => array('name' => '北京银行', 'code' => 'BCCB')
            );
            $this->utils->debug_log("=======================getting emerchantpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

	public function getPlayerDetails($playerId) {
		$this->CI->load->model(array('player_model'));
		$player = $this->CI->player_model->getPlayerDetails($playerId);

		return $player;
	}

	public function randomNum($length) {
	    $str="12345678901234567890";
	    $result=substr(str_shuffle($str),0,$length);
	    return $result;
    }


    protected function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $sign = hash('sha1',$signStr);

		if($params['signature'] == $sign){
			return true;
		}
		else{
			return false;
		}
    }

    protected function createSignStr($params) {
        $signStr = '';
        $signStr = $params['unique_id'].$this->getSystemInfo('auth_password');
        return $signStr;
    }
}