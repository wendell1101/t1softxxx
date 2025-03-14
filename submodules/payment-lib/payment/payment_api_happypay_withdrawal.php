<?php
require_once dirname(__FILE__) . '/abstract_payment_api_happypay.php';

/**
 * HAPPYPAY_WITHDRAWAL
 *
 * * HAPPYPAY_WITHDRAWAL_PAYMENT_API, ID: 5848
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://khgri4829.com:6084/api/defray/V2
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_happypay_withdrawal extends Abstract_payment_api_happypay {

    const CHANNLETYPE = '0';
    const RESPONSE_ORDER_SUCCESS = '0';
    const CALLBACK_STATUS_SUCCESS = '1';

    public function getPlatformCode() {
        return HAPPYPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'happypay_withdrawal';
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

        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================happypay withdrawal bank whose bankTypeId=[$bank] is not supported by happypay");
            return array('success' => false, 'message' => 'Bank not supported by happypay');
        }

        $this->_custom_curl_header = array('Content-Type: application/json');
        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================happypay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================happypay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================happypay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================happypay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $province = $playerBankDetails['province'];
            $city = $playerBankDetails['city'];
            $bankBranch = $playerBankDetails['branch'];
        } else {
            $province = 'none';
            $city = 'none';
            $bankBranch = 'none';
        }

        $province = empty($province) ? "none" : $province;
        $city = empty($city) ? "none" : $city;
        $bankBranch = empty($bankBranch) ? "none" : $bankBranch;

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $params = array();
        $params['version']        = 'V2';
        $params['signType']       = 'MD5';
        $params['merchantNo']     = $this->getSystemInfo("account");
        $params['date']           = date("Y-m-d h:i:s");
        $params['channleType']    = $this->getSystemInfo('channleType', self::CHANNLETYPE);
        $params['orderNo']        = $transId;
        $params['bizAmt']         = $this->convertAmountToCurrency($amount);
        $params['accName']        = $name;
        $params['bankCode']       = $bankCode;
        $params['bankBranchName'] = $bankBranch;
        $params['cardNo']         = $accNum;
        $params['noticeUrl']      = $this->getNotifyUrl($transId);
        $params['openProvince']   = $province;
        $params['openCity']       = $city;
        $params['sign']           = $this->sign($params);

        $this->CI->utils->debug_log('=========================happypay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================happypay json_decode result", $result);

        if(isset($result['code'])) {
            if($result['code'] == self::RESPONSE_ORDER_SUCCESS) {
                $message = "happypay withdrawal response successful, code:[".$result['code']."]: ".$result['msg'];
                return array('success' => true, 'message' => $message);
            }
            $message = "happypay withdrawal response failed. [".$result['code']."]: ".$result['msg'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['msg']){
            $message = 'happypay withdrawal response: '.$result['message'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "happypay decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================happypay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================happypay json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================happypay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================happypay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('happypay withdrawal success: trade ID [%s]', $params['orderNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        // else if ($params['Status'] != self::ORDER_STATUS_PROCESS && $params['Status'] != self::ORDER_STATUS_CREATED) {
        //     $msg = sprintf('happypay withdrawal failed: [%s]', $params['Message']);
        //     $this->writePaymentErrorLog($msg, $fields);
        //     $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
        //     $result['message'] = $msg;
        // }
        else {
            $msg = sprintf('happypay withdrawal payment was not successful: [%s]', $params['Message']);
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'orderNo', 'bizAmt', 'status', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================happypay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================happypay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['status'] != self::CALLBACK_STATUS_SUCCESS) {
            $this->writePaymentErrorLog("======================happypay withdrawal checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['bizAmt'] != $order['amount']) {
            $this->writePaymentErrorLog('=========================happypay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['orderNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================happypay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
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
            $this->utils->debug_log("==================getting happypay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '1' => array('name' => '工商银行', 'code' => 'ICBC'),
                '2' => array('name' => '招商银行', 'code' => 'CMBCHINA'),    
                '3' => array('name' => '建设银行', 'code' => 'CCB'),
                '4' => array('name' => '农业银行', 'code' => 'ABC'),
                '5' => array('name' => '交通银行', 'code' => 'BOCO'),
                '6' => array('name' => '中国银行', 'code' => 'BOC'),
                // '7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
                '8' => array('name' => '广发银行', 'code' => 'CGB'),
                '10' => array('name' => '中信银行', 'code' => 'ECITIC'),
                '11' => array('name' => '民生银行', 'code' => 'CMBC'),
                '12' => array('name' => '中国邮政银行', 'code' => 'POST'),
                '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                '14' => array('name' => '华夏银行', 'code' => 'HXB'),
                '15' => array('name' => '平安银行', 'code' => 'PINGANBANK'),
                //'17' => array('name' => '广州银行', 'code' => 'GZCB'),
                //'18' => array('name' => '南京银行', 'code' => 'NJCB'),
                '20' => array('name' => '光大银行', 'code' => 'CEB'),
                '24' => array('name' => '浦发银行', 'code' => 'SPDB'),
                '25' => array('name' => '北京银行', 'code' => 'BCCB'),
                '25' => array('name' => '上海银行', 'code' => 'SHB'),
                '26' => array('name' => '苏州银行', 'code' => 'BSZ'),
                '27' => array('name' => '桂林银行', 'code' => 'GUILINBANK'),
                '28' => array('name' => '广西农村信用社', 'code' => 'GX966888'),
                '29' => array('name' => '郑州银行', 'code' => 'ZZBANK'),
                '30' => array('name' => '四川天府銀行', 'code' => 'TFB'),
                '31' => array('name' => '宁波銀行', 'code' => 'NBCB'),
                '32' => array('name' => '江蘇銀行', 'code' => 'JSBCHINA'),
                '33' => array('name' => '浙江泰隆商业银行', 'code' => 'ZJTLCB'),
            );
            $this->utils->debug_log("=======================getting aipay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- signatures --
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
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
        $signStr = rtrim($signStr, '&').$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}