<?php
require_once dirname(__FILE__) . '/abstract_payment_api_aeepay.php';

/**
 * AEEPAY_WITHDRAWAL
 *
 * * AEEPAY_WITHDRAWAL_PAYMENT_API, ID: 6300
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_aeepay_withdrawal extends Abstract_payment_api_aeepay {

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/x-www-form-urlencoded');
	}

    public function getPlatformCode() {
        return AEEPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'aeepay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}
    protected function processPaymentUrlForm($params) {}
    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================aeepays submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by aeepay");
            return array('success' => false, 'message' => 'Bank not supported by aeepay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $bankInfo = $this->getBankInfo();
        $playerInfo = $this->getPlayerInfoByTransactionCode($transId);
        $realName = !empty($playerInfo['firstName']) ? $playerInfo['firstName'].$playerInfo['lastName'] : '';
        $bankCode = $bankInfo[$bank]['code'];

        $params = array();
        $params['merchantno'] = $this->getSystemInfo("account");
        $params['morderno']   = $transId;
        $params['type']       = '0';
        $params['money']      = $this->convertAmountToCurrency($amount);
        $params['bankcode']   = $bankCode;
        $params['realname']   = $realName;
        $params['cardno']     = $accNum;
        $params['sendtime']   = date("YmdHis");
        $params['notifyurl']  = $this->getNotifyUrl($transId);
        $params['buyerip']    = $this->utils->getIP();
        $params['sign']       = $this->sign($params);
        
        $this->CI->utils->debug_log('=========================aeepay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('Success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================aeepay json_decode result", $result);

        if (isset($result['resultCode'])&&isset($result['success'])) {
            if($result['resultCode'] == self::REQUEST_SUCCESS_CODE) {
                $message = "aeepay withdrawal response successful, TrackingNumber:".$result['orderno'];
                $this->utils->debug_log("=========================aeepay json_decode decodeResult", $message);
                return array('success' => true, 'message' => $message);
            }
            $message = "aeepay withdrawal response failed. ErrorMessage: ".$result['resultMsg'];
            $this->utils->debug_log("=========================aeepay json_decode decodeResult", $message);
            return array('success' => false, 'message' => $message);
        }
        elseif($result['resultCode']!=self::REQUEST_SUCCESS_CODE){
            $message = 'aeepay withdrawal response: '.$result['resultMsg'];
            $this->utils->debug_log("=========================aeepay json_decode decodeResult", $message);
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "aeepay decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================aeepay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================aeepay json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================aeepay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================aeepay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }
        
        if ($params['status'] == self::RESPONSE_WITHDRAWAL_SUCCESS_CODE) {
            $msg = sprintf('aeepay withdrawal success: trade ID [%s]', $params['morderno']);
            $this->withdrawalSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;

        }else {
            $msg = sprintf("aeepay withdrawal payment unsuccessful or pending: status=%s", $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
            if(null!==$this->getSystemInfo("allow_auto_decline")
            &&$this->getSystemInfo("allow_auto_decline") == true){
                $msg = sprintf("aeepay withdrawal payment unsuccessful auto decline: status=%s", $params['status']);
                $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            }
        }

        $this->CI->utils->debug_log("=========================aeepay callbackFromServer result", $result);

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'merchantno', 'morderno', 'tjmoney', 'money', 'status', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================aeepay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================aeepay withdrawal checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }

        if ($fields['tjmoney'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================aeepay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['morderno'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================aeepay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }
    protected function createSignStr($params) {
        $keys = array('merchantno','morderno','bankcode','type','realname','cardno','money','sendtime','buyerip');
        $signStr = "";
        foreach($keys as $key) {
            $signStr .= $params[$key].'|';
        }
        return $signStr.$this->getSystemInfo('key');
    }

    protected function sign($params) {	
		$signStr=$this->createSignStr($params);
		$sign = md5($signStr);

		return $sign;
	}
    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

	private function validateSign($params) {
        // merchantno+“|”+orderno+“|”+bankcode+“|”+morderno +“|”+cardno+“|”+tjmoney+“|”+money+“|”+status+“|”+md5key
        $keys = array('merchantno','orderno','bankcode','morderno','cardno','tjmoney','money','status');
		$signStr = "";
		$result=false;
		foreach($keys as $key) {
			$signStr .= $params[$key].'|';
		}
		$signStr .= $this->getSystemInfo('key');
		$sign = md5($signStr);
		if($sign === $params['sign']){
			$result=true;
		}
        $this->CI->utils->debug_log('=========================aeepay getWithdrawParams validateSign', $signStr);
        $this->CI->utils->debug_log('=========================aeepay getWithdrawParams validateSign', $sign);

		return $result;
	}

    # -- info --
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
            $this->utils->debug_log("==================aeepay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                "28" =>  array('name' => "Bangkok Bank", 'code' => 'THB_BBL'),
                "29" =>  array('name' => "Krung Thai Bank", 'code' => 'THB_KTB'),
                "30" =>  array('name' => "Siam Commercial Bank", 'code' => 'THB_SCB'),
                // "31" =>  array('name' => "Karsikorn Bank (K-Bank)", 'code' => ''),
                "34" =>  array('name' => "CIMB Thai", 'code' => 'THB_CIMB'),
                "35" =>  array('name' => "CITIBANK", 'code' => 'THB_CITI'),
                "37" =>  array('name' => "Kiatnakin Bank", 'code' => 'THB_KK'),
                "38" =>  array('name' => "STANDARD CHARTERED BANK", 'code' => 'THB_SCBT'),
                "39" =>  array('name' => "THANACHART BANK", 'code' => 'THB_TBANK'),
                "43" =>  array('name' => "Government Savings Bank", 'code' => 'THB_GSB'),
                "47" =>  array('name' => "GOVERNMENT HOUSING BAN", 'code' => 'THB_GHB'),
                "56" =>  array('name' => "SUMITOMO MITSUI BANGKING CORPORATION", 'code' => 'THB_SMBC'),
                "57" =>  array('name' => "UNITED OVERSEAS BANK", 'code' => 'THB_UOB'),
                "60" =>  array('name' => "HONGKONG AND SHANGHAI CORPORATION LTD", 'code' => 'THB_HSBC'),
                "61" =>  array('name' => "Bank for Agriculture and Agricultural Cooperatives", 'code' => 'THB_BAAC'),
                "62" =>  array('name' => "MIZUHO BANK", 'code' => 'THB_MHCB'),
                // "63" =>  array('name' => "ISLAMIC BANK", 'code' => ''),
                "64" =>  array('name' => "TISCO BANK", 'code' => 'THB_TSCO'),
                "66" =>  array('name' => "THAI CREDIT RETAIL BANK", 'code' => 'THB_TCRB'),
                "67" =>  array('name' => "LAND AND HOUSES RETAIL BANK", 'code' => 'THB_LHBANK'),
                // "73" =>  array('name' => "TMBThanachart Bank", 'code' => ''),
            );
            $this->utils->debug_log("=======================getting aeepay bank info from code: ", $bankInfo);
        }
        $this->utils->debug_log("==================aeepay xxxxxxxxxxxxxxx bankInfo: ", $bankInfo);

        return $bankInfo;
    }
}