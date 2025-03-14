<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dywinpay.php';

/**
 * 王朝 DYWINPAY
 *
 * * DYWINPAY_WITHDRAWAL_PAYMENT_API, ID: 5629
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://dywinpay.com/api/generateorder
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

class Payment_api_dywinpay_withdrawal extends Abstract_payment_api_dywinpay {
    const CALLBACK_RETURN_MSG = 'SUCCESS';
    const CALLBACK_SUCCESS = 'SUCCESS';
    const CALLBACK_FAILED = 'FAILED';
    const RESULTCODE_SUCCESS = '0000';

    public function getPlatformCode() {
        return DYWINPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'dywinpay_withdrawal';
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
        # look up bank code
        $bankInfo = $this->getBankInfo();
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $bankAddress = empty($playerBankDetails['bankAddress']) ? "无" : $playerBankDetails['bankAddress'];
        } else {
            $bankAddress = '无';
        }

        $params = array();
        $params['merchant'] = $this->getSystemInfo("account");
        $params['tradeno'] = $transId;
        $params['tradedate'] = date('Y-m-d H:i:s');
        $params['tradedesc'] = 'withdrawal';
        $params['bankcode'] =  $bankInfo[$bank]['code'];
        $params['bankaccountno'] = $accNum;
        $params['bankaccountname'] = $name;
        $params['bankaddress'] = $bankAddress;
        $params['currency'] = 'CNY';
        $params['totalamount'] = $this->convertAmountToCurrency($amount);
        $params['notifyurl'] = $this->getNotifyUrl($transId);
        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log('=========================dywinpay getWithdrawParams params', $params);
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
            $this->utils->error_log("========================dywinpay withdrawal bank whose bankTypeId=[$bank] is not supported by dywinpay");
            return array('success' => false, 'message' => 'Bank not supported by dywinpay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        $url = $this->getSystemInfo('url');

        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $this->CI->utils->debug_log('=========================dywinpay submitWithdrawRequest decoded Result', $decodedResult);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================dywinpay json_decode result", $result);

        $respCode = $result['resultCode'];
        $resultMsg = $result['errMsg'];
        $this->utils->debug_log("=========================dywinpay withdrawal resultMsg", $resultMsg);

        if($respCode == self::RESULTCODE_SUCCESS) {
            $message = "dywinpay request successful.";
            return array('success' => true, 'message' => $message);
        }
        else {
            if(!empty($resultMsg)) {
                $resultMsg = $this->unicodeDecode($resultMsg);
            }
            else{
                $this->utils->error_log("========================dywinpay return UNKNOWN ERROR!");
                $resultMsg = "未知错误";
            }
            $message = "dywinpay withdrawal response, Code: [ ".$respCode." ] , Msg: ".$resultMsg;
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

        $this->utils->debug_log("==========================dywinpay checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['tradestatus'] == self::CALLBACK_SUCCESS ){

            $this->utils->debug_log('=====================dywinpay withdrawal payment was successful: trade ID [%s]', $params['tradeno']);
            $msg = sprintf('dywinpay withdrawal was successful: trade ID [%s]',$params['tradeno']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::CALLBACK_RETURN_MSG;
            $result['success'] = true;
        }else if($params['status'] == self::CALLBACK_FAILED){
            $msg = sprintf('dywinpay withdrawal was failed: trade ID [%s]',$params['tradeno']);
            $this->utils->debug_log('=========================dywinpay withdrawal payment was failed: trade ID [%s]', $params['tradeno']);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = self::CALLBACK_RETURN_MSG;
        }
        else {
            $msg = sprintf('dywinpay withdrawal payment was not successful  trade ID [%s] ',$params['tradeno']);
            $this->utils->debug_log('=========================dywinpay withdrawal payment was not successful  trade ID [%s]', $params['tradeno']);
            $result['message'] = self::CALLBACK_RETURN_MSG;
        }
        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array('tradeno','totalamount','sign');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================dywinpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['sign'] != $this->validateSign($fields)) {
            $this->writePaymentErrorLog('==========================dywinpay withdrawal checkCallback signature Error',$fields);
            return false;
        }

        if ($fields['totalamount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================dywinpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['tradeno'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================dywinpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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


    private function validateSign($params){
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);

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
            if(($key == 'sign')) {
                continue;
            }
            if(($key == 'bankaccountname')){
                $value = $this->unicodeDecode($value);
            }

            $signStr .= "$key=$value&";
        }
        $signStr .= 'paykey='.$this->getSystemInfo('key');
        return $signStr;
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
            $this->utils->debug_log("==================getting dywinpay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => array('name' => '工商银行', 'code' => 'ICBC'),
                '2' => array('name' => '招商银行', 'code' => 'CMB'),
                '3' => array('name' => '建设银行', 'code' => 'CCB'),
                '4' => array('name' => '农业银行', 'code' => 'ABC'),
                '5' => array('name' => '交通银行', 'code' => 'COMM'),
                '6' => array('name' => '中国银行', 'code' => 'BOC'),
                '9' => array('name' => '东莞农商银行', 'code' => 'DRCB'),
                '10' => array('name' => '中信银行', 'code' => 'CITIC'),
                '11' => array('name' => '民生银行', 'code' => 'CMBC'),
                '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                '14' => array('name' => '华夏银行', 'code' => 'HXB'),
                '15' => array('name' => '平安银行', 'code' => 'PABC'),
                '17' => array('name' => '广州银行', 'code' => 'GUA'),
                '18' => array('name' => '南京银行', 'code' => 'NJCB'),
                '19' => array('name' => '广州农商银行', 'code' => 'GRCB'),
                '20' => array('name' => '光大银行', 'code' => 'CEB'),
                '26' => array('name' => '广发银行', 'code' => 'GDB'),
                '28' => array('name' => '东亚银行', 'code' => 'BEA'),
                '29' => array('name' => '北京银行', 'code' => 'BOB'),
                '30' => array('name' => '天津银行', 'code' => 'TIANJIN'),
                '31' => array('name' => '上海银行', 'code' => 'BOS'),
                '33' => array('name' => '北京农商', 'code' => 'BRCB'),
                '41' => array('name' => '大连银行', 'code' => 'BOD'),
                '44' => array('name' => '东莞银行', 'code' => 'DGCB'),
                '48' => array('name' => '杭州银行', 'code' => 'HZB'),
                '49' => array('name' => '河北银行', 'code' => 'BOHB'),
                '52' => array('name' => '内蒙古银行', 'code' => 'BOIM'),
                '55' => array('name' => '吉林银行', 'code' => 'JLCB'),
                '57' => array('name' => '济宁银行', 'code' => 'BOJN'),
                '58' => array('name' => '锦州银行', 'code' => 'BOJZ'),
                '60' => array('name' => '昆仑银行', 'code' => 'BOKL'),
                '67' => array('name' => '宁波银行', 'code' => 'NBCB'),
                '69' => array('name' => '青岛银行', 'code' => 'BQD'),
                '76' => array('name' => '台州银行', 'code' => 'TZB'),
                '79' => array('name' => '西安银行', 'code' => 'XIAN'),
                '81' => array('name' => '郑州银行', 'code' => 'BOZZ'),
                '86' => array('name' => '渤海银行', 'code' => 'CBHB'),
                '89' => array('name' => '浙商银行', 'code' => 'CZBANK'),
                '100' => array('name' => '恒丰银行', 'code' => 'HFB'),
                '102' => array('name' => '富滇银行', 'code' => 'FDB'),
                '106' => array('name' => '广西北部湾银行', 'code' => 'GBGB'),
            );

            $this->utils->debug_log("=======================getting dywinpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function unicodeDecode($unicode_str){
        $json = '{"str":"'.$unicode_str.'"}';
        $arr = json_decode($json,true);
            if(empty($arr)){
                return '';
            }
            return $arr['str'];
    }
}