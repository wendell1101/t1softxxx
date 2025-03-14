<?php
require_once dirname(__FILE__) . '/abstract_payment_api_funpay_2.php';

/**
 * FUNPAY_2
 *
 * * FUNPAY_2_WITHDRAWAL_PAYMENT_API, ID: 5220
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://paypaul.385mall.top/onlinepay/agentTransfer
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_funpay_2_withdrawal extends Abstract_payment_api_funpay_2 {

    const RESULT_STATUS_SUCCESS = "520000";
    const PAYMENT_STATUS_SUCCESS    = 1;
    const PAYMENT_STATUS_PROCESSING = 2;
    const PAYMENT_STATUS_FAILED     = 3;
    const PAYMENT_STATUS_UNKNOWN    = 6;
    const PAYMENT_STATUS_REFUNDED   = 9;

    public function getPlatformCode() {
        return FUNPAY_2_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'funpay_2_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================funpay_2 submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by funpay_2");
            return array('success' => false, 'message' => 'Bank not supported by Funpay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();
        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================funpay_2 submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================funpay_2 submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================funpay_2 submitWithdrawRequest response ', $response);
        $this->CI->utils->debug_log('======================================funpay_2 submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('player_model', 'playerbankdetails'));

        # look up bank code
        $bankInfo = $this->getBankInfo();
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

        $params = array();
        $params['merchantId']   = $this->getSystemInfo('account');
        $params['transCode']    = '006';
        $params['orderNo']      = $transId;
        $params['mode']         = $this->getSystemInfo('mode', 'T0');
        $params['amount']       = $this->convertAmountToCurrency($amount);
        $params['accountType']  = '0'; #对私
        $params['accountName']  = $name;
        $params['idCardNo']     = '147733198001018210'; #身份证号
        $params['bankCard']     = $accNum;
        $params['mobile']       = '00000000000'; #收款人手机号码
        $params['bankName']     = $bankInfo[$bank]['name'];
        $params['bankCode']     = $bankInfo[$bank]['code'];
        $params['openBankName'] = $bankBranch;
        $params['bankLinked']   = '000000000000'; #支行联行号
        $params['province']     = $province;
        $params['city']         = $city;
        $params['memo']         = $transId;
        $params['signType']     = "MD5";
        $params['sign']         = $this->sign($params);

        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }

        $result = array('success' => false, 'message' => 'Funpay decoded fail.');
        $response = json_decode($resultString, true);
        $this->CI->utils->debug_log('==============funpay_2 submitWithdrawRequest decodeResult json decoded', $response);
        $returnCode = $response['code'];
        $returnDesc = $response['message'];

        if($returnCode == self::RESULT_STATUS_SUCCESS) {
            $returnStatus = $response['paymentStatus'];
            if($queryAPI){
                $result = array('success' => false, 'message' => 'Funpay check status decoded fail.', 'payment_fail' => false);
                if($returnStatus == self::PAYMENT_STATUS_SUCCESS){
                    $result['success'] = true;
                    $result['message'] = "Funpay withdrawal success! [".$returnStatus."]".$returnDesc;
                }
                elseif($returnStatus == self::PAYMENT_STATUS_PROCESSING){
                    $result['message'] = "Funpay withdrawal still processing. [".$returnStatus."]".$returnDesc;
                }
                elseif($returnStatus == self::PAYMENT_STATUS_FAILED || $returnStatus == self::PAYMENT_STATUS_REFUNDED){
                    $result['payment_fail'] = true;
                    $result['message'] = "Funpay withdrawal failed. [".$returnStatus."]".$returnDesc;
                }
                else{
                    $result['message'] = "Funpay withdrawal response [".$returnStatus."]".$returnDesc;
                }
            }
            else{
                if($returnStatus == self::PAYMENT_STATUS_PROCESSING){
                    $result['success'] = true;
                    $result['message'] = "Funpay withdrawal response success! [".$returnStatus."]".$returnDesc;
                }
                else{
                    $result['message'] = "Funpay withdrawal response [".$returnStatus."]".$returnDesc;
                }
            }
        }
        else{
            $result['message'] = "Funpay withdrawal response failed. [".$returnCode."]: ".$returnDesc;
        }
        return $result;
    }

    public function checkWithdrawStatus($transId) {

        $params = array();
        $params['transCode']  = '007';
        $params['merchantId'] = $this->getSystemInfo('account');
        $params['orderNo']    = $transId;
        $params['sign']       = strtoupper($this->sign($params));
        $this->CI->utils->debug_log('======================================funpay_2 checkWithdrawStatus params: ', $params);

        $url = $this->getSystemInfo('check_status_url', 'http://paypaul.385mall.top/onlinepay/replaceQuery');
        $response = $this->submitPostForm($url, $params, true, $transId);
        $decodedResult = $this->decodeResult($response, true);

        $this->CI->utils->debug_log('======================================funpay_2 checkWithdrawStatus result: ', $response);
        return $decodedResult;
    }

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
            $this->utils->debug_log("=========================funpay_2 bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => array('name' => '工商银行', 'code' => 'ICBC'),
                '2' => array('name' => '招商银行', 'code' => 'CMB'),
                '3' => array('name' => '建设银行', 'code' => 'CCB'),
                '4' => array('name' => '农业银行', 'code' => 'ABC'),
                '5' => array('name' => '交通银行', 'code' => 'BCOM'),
                '6' => array('name' => '中国银行', 'code' => 'BOC'),
                '7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
                '8' => array('name' => '广东发展银行', 'code' => 'GDB'),
                '10' => array('name' => '中信银行', 'code' => 'CITIC'),
                '11' => array('name' => '民生银行', 'code' => 'CMBC'),
                '12' => array('name' => '邮储银行', 'code' => 'PSBC'),
                '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                '14' => array('name' => '华夏银行', 'code' => 'HXB'),
                '15' => array('name' => '平安银行', 'code' => 'PABC'),
                '18' => array('name' => '南京银行', 'code' => 'BON'),
                '20' => array('name' => '光大银行', 'code' => 'CEB'),
                '24' => array('name' => '上海浦东发展银行', 'code' => 'SPDB'),
                '26' => array('name' => '广东发展银行', 'code' => 'GDB'),
                '27' => array('name' => '上海浦东发展银行', 'code' => 'SPDB'),
                '29' => array('name' => '北京银行', 'code' => 'BOB'),
                '31' => array('name' => '上海银行', 'code' => 'SHB'),
                '33' => array('name' => '北京农商', 'code' => 'BJRCB'),
            );
            $this->utils->debug_log("=========================funpay_2 bank info from code: ", $bankInfo);

        }
        return $bankInfo;
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
            if(is_null($value) || $key == 'sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return $signStr.'key='.$this->getSystemInfo('key');
    }
}