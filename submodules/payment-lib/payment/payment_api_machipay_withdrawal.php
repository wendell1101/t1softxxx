<?php
require_once dirname(__FILE__) . '/abstract_payment_api_machipay.php';

/**
 * MACHIPAY 麻吉支付
 *
 * * MACHIPAY_WITHDRAWAL_PAYMENT_API, ID: 5525
 * * MACHIPAY_WITHDRAWAL_2_PAYMENT_API, ID: 5531
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Secret
 *
 * Field Values:
 * * URL: http://paygate.machi-tech.com:9090/gateway-onl/txn
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_machipay_withdrawal extends Abstract_payment_api_machipay {

    const RETURN_SUCCESS = '0000';
    const RETURN_PROCESSING = '1101';

    const CALLBACK_SUCCESS = '10';
    const CALLBACK_PROCESSING = '01';
    const CALLBACK_FAILED = '20';

    const CALLBACK_SUCCESS_MSG = 'success';

    public function getPlatformCode() {
        return MACHIPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'machipay_withdrawal';
    }

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('application/x-www-form-urlencoded');
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

		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $orderDateTime = $order['dwDateTime'];

        # look up bank code
        $bankInfo = $this->getBankInfo();

        $params = array();
		$params['txnType'] = "52";
        $params['txnSubType'] = "10";
        $params['secpVer'] = "icp3-1.1";
        $params['secpMode'] = "perm";
        $params['macKeyId'] = $this->getSystemInfo('account');
        $params['orderDate'] = str_replace( '-' , "" , substr($orderDateTime,0,10));
        $params['orderTime'] = str_replace( ':' , "" , substr($orderDateTime,11,8));
        $params['merId'] = $this->getSystemInfo('account');
        $params['orderId'] = $transId;
        $params['txnAmt'] = $this->convertAmountToCurrency($amount); //分
        $params['currencyCode']  = "156";
        $params['accName'] = $name;  //收款人
		$params['accNum'] = $accNum;
        $params['bankNum'] = $bankInfo[$bank]['code'];
        $params['bankName'] = $bankInfo[$bank]['name'];
		$params['phoneNumber'] = $this->randomNum(11);
        $params['notifyUrl'] = $this->getNotifyUrl($transId);
        $params['timeStamp'] = $params['orderDate'].$params['orderTime'];
        $params['mac'] = $this->sign($params);

        $this->CI->utils->debug_log('=========================machipay getWithdrawParams params', $params);
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
            $this->utils->error_log("========================machipay withdrawal bank whose bankTypeId=[$bank] is not supported by machipay");
            return array('success' => false, 'message' => 'Bank not supported by machipay');
        }

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        $url = $this->getSystemInfo('url');

        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $this->CI->utils->debug_log('=========================machipay submitWithdrawRequest decoded Result', $decodedResult);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;

	}

    public function decodeResult($resultString, $queryAPI = false) {
		$result = json_decode($resultString, true);
		$this->utils->debug_log("=========================machipay json_decode result", $result);

        $respCode = $result['respCode'];
		$resultMsg = $result['respMsg'];
		$this->utils->debug_log("=========================machipay withdrawal resultMsg", $resultMsg);

        if($respCode == self::RETURN_SUCCESS) {
            $message = "machipay request successful.";
            return array('success' => true, 'message' => $message);
        }
        else {
            if($resultMsg == '' || $resultMsg == false) {
                    $this->utils->error_log("========================machipay return UNKNOWN ERROR!");
                    $resultMsg = "未知错误";
            }
            $message = "machipay withdrawal response, Code: [ ".$respCode." ] , Msg: ".$resultMsg;
            return array('success' => false, 'message' => $message);
        }
	}

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);

        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->utils->debug_log("==========================machipay checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['txnStatus'] == self::CALLBACK_SUCCESS) {
            $this->utils->debug_log('==========================machipay withdrawal payment was successful: trade ID [%s]', $params['orderId']);

            $msg = sprintf('machipay withdrawal was successful: trade ID [%s]',$params['orderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::CALLBACK_SUCCESS_MSG;
            $result['success'] = true;

       }elseif($params['txnStatus'] == self::CALLBACK_FAILED){
            $this->utils->debug_log('==========================machipay withdrawal payment was failed: trade ID [%s]', $params['orderId']);

            $msg = sprintf('machipay withdrawal payment was failed: trade ID [%s] ',$params['orderId']);
            $this->writePaymentErrorLog($msg, $params);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = self::CALLBACK_SUCCESS_MSG;
            $result['success'] = true;

       }else {
            $msg = sprintf('machipay withdrawal payment was not successful  trade ID [%s] ',$params['orderId']);
            $this->debug_log($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }


    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array('orderId', 'txnStatus', 'respCode', 'txnAmt', 'mac');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================machipay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['mac'] != $this->validateSign($fields)) {
            $this->writePaymentErrorLog('==========================machipay withdrawal checkCallback signature Error',$fields);
            return false;
        }

		if ($fields['respCode'] != self::RETURN_SUCCESS || $fields['respCode'] != self::RETURN_PROCESSING) {
			$this->writePaymentErrorLog("==========================machipay checkCallbackOrder Payment status is not success", $fields);
			return false;
		}

		if ($fields['txnAmt'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================machipay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
			return false;
		}

        if ($fields['orderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================machipay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }


    # -- signatures --
    protected function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
    
        return $sign;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'mac') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return $signStr.'k='.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        if($params['mac'] == $sign){
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
            $this->utils->debug_log("==================getting machipay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '1' => array('name' => '工商银行', 'code' => '01020000'),
                '2' => array('name' => '招商银行', 'code' => '03080000'),
                '3' => array('name' => '建设银行', 'code' => '01050000'),
                '4' => array('name' => '农业银行', 'code' => '01030000'),
                '5' => array('name' => '交通银行', 'code' => '03010000'),
                '6' => array('name' => '中国银行', 'code' => '01040000'),
                '8' => array('name' => '广发银行', 'code' => '03060000'),
                '10' => array('name' => '中信银行', 'code' => '03020000'),
                '11' => array('name' => '民生银行', 'code' => '03050000'),
                '12' => array('name' => '邮储银行', 'code' => '04030000'),
                '13' => array('name' => '兴业银行', 'code' => '03090000'),
                '14' => array('name' => '华夏银行', 'code' => '03040000'),
                '15' => array('name' => '平安银行', 'code' => '03070000'),
                '17' => array('name' => '广州银行', 'code' => '04135810'),
                '20' => array('name' => '光大银行', 'code' => '03030000'),
                '24' => array('name' => '浦发银行', 'code' => '03100000'),
                '32' => array('name' => '江西银行', 'code' => '04484210'),
                '33' => array('name' => '厦门银行', 'code' => '04023930'),
                '65' => array('name' => '北京银行', 'code' => '03131000'),
            );
            $this->utils->debug_log("=======================getting machipay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }


	protected function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

	public function randomNum($length) {
	    $str="12345678901234567890";
	    $result=substr(str_shuffle($str),0,$length);
	    return $result;
	}
}