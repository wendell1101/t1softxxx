<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wxpay.php';

/**
 * WXPAY 取款
 *
 * * WXPAY_WITHDRAWAL_PAYMENT_API, ID: 6008
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://tl.7xinpy.com/withdraw/singleOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##g
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_wxpay_withdrawal extends Abstract_payment_api_wxpay {

    public function getPlatformCode() {
        return WXPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'wxpay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}

    protected function processPaymentUrlForm($params) {}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $bankInfo = $this->getBankInfo();
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $playerDetails = $this->CI->player_model->getPlayerDetails($order['playerId']);
        $firstname = (!empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : 'none';
        $lastname  = (!empty($playerDetails[0]['lastName']))  ? $playerDetails[0]['lastName']  : 'none';

        $params = array();
        $params['uid'] = $this->getSystemInfo('account');
        $params['orderid'] = $transId;
        $params['channel'] = "714";
        $params['notify_url'] = $this->getNotifyUrl($transId);
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['userip'] = $this->getClientIP();
        $params['timestamp'] = time();
        $params['custom']     = 'Withdraw';
        $params['bank_no'] = $accNum;
        $params['bank_account'] = $name;
        $params['bank_id']  = $bankInfo[$bank]['code']; # bank SN mapping
        $params['user_name'] = $lastname.$firstname;
        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log('======================================wxpay getWithdrawParams :', $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');
        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log('======================================wxpay submitWithdrawRequest result: ',$result);
            return $result;
        }
        # look up bank code
        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================wxpay withdrawal bank whose bankTypeId=[$bank] is not supported by wxpay");
            return array('success' => false, 'message' => 'Bank not supported by wxpay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getSystemInfo('url');

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;
        return $decodedResult;
    }


    public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================wxpay json_decode result", $result);

        $resultMsg = "Unknow Error";
        $this->utils->debug_log("=========================wxpay withdrawal resultMsg", $result['status']);

        if(isset($result['status']) && $result['status'] == self::RESULT_CODE_SUCCESS) {
            $message = "wxpay request successful.";
            return array('success' => true, 'message' => $message);
        }
        else {
            $message = "wxpay withdrawal response, Code: [ ".$result['status']." ] , Msg: ".$resultMsg;
            return array('success' => false, 'message' => $message);
        }
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');
        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }
        $result_params = json_decode($params['result'], true);

        if($params['status'] == self::RESULT_CODE_SUCCESS) {
            $this->utils->debug_log('=========================wxpay withdrawal payment was successful: trade ID [%s]', $result_params['orderid']);

            $msg = sprintf('wxpay withdrawal was successful: trade ID [%s]',$result_params['orderid']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        } else {
            $msg = 'wxpay withdrawal payment was not successful';
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }


     public function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'status', 'result', 'sign'
        );

        $result_params = json_decode($fields['result'], true);

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================wxpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['sign']!=$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================wxpay withdrawal checkCallback signature Error',$fields);
            return false;
        }

        if ($result_params['orderid'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("========================wxpay checkCallbackOrder type2 order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        if ($result_params['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================wxpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['status'] != self::RESULT_CODE_SUCCESS) {
            $this->writePaymentErrorLog("======================wxpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }


    # -- info --
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("wxpay_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting wxpay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                "28" =>  array('name' => "Bangkok Bank", 'code' => '4002'),
                "29" =>  array('name' => "Krung Thai Bank", 'code' => '4006'),
                "30" =>  array('name' => "Siam Commercial Bank", 'code' => '4014'),
                "31" =>  array('name' => "Karsikorn Bank (K-Bank)", 'code' => '4004'),
                "34" =>  array('name' => "CIMB Thai", 'code' => '4022'),
                "35" =>  array('name' => "CITIBANK", 'code' => '4017'),
                "37" =>  array('name' => "Kiatnakin Bank", 'code' => '4069'),
                "38" =>  array('name' => "STANDARD CHARTERED BANK", 'code' => '4020'),
                "39" =>  array('name' => "THANACHART BANK", 'code' => '4065'),
                "43" =>  array('name' => "Government Savings Bank", 'code' => '4030'),
                "47" =>  array('name' => "GOVERNMENT HOUSING BAN", 'code' => '4033'),
                "56" =>  array('name' => "SUMITOMO MITSUI BANGKING CORPORATION", 'code' => '4018'),
                "57" =>  array('name' => "UNITED OVERSEAS BANK", 'code' => '4024'),
                "60" =>  array('name' => "HONGKONG AND SHANGHAI CORPORATION LTD", 'code' => '4031'),
                "61" =>  array('name' => "BANK FOR AGRICULTURE", 'code' => '4034'),
                "62" =>  array('name' => "MIZUHO BANK", 'code' => '4039'),
                "63" =>  array('name' => "ISLAMIC BANK", 'code' => '4066'),
                "64" =>  array('name' => "TISCO BANK", 'code' => '4067'),
                "66" =>  array('name' => "THAI CREDIT RETAIL BANK", 'code' => '4071'),
                "67" =>  array('name' => "LAND AND HOUSES RETAIL BANK", 'code' => '4073'),
                "73" =>  array('name' => "TMBThanachart Bank", 'code' => '4011'),
            );
            $this->utils->debug_log("=======================getting wxpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- signatures --
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);

        return strtoupper($sign);
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return $signStr.'key='.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign'){
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        $sign = md5($signStr);
        if($params['sign'] == strtoupper($sign)){
            return true;
        }
        else{

            return false;
        }
    }
}


