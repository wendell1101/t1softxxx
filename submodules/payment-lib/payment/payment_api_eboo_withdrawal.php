<?php
require_once dirname(__FILE__) . '/abstract_payment_api_eboo.php';
/**
 * EBOO E宝
 * http://eboopay.com
 *
 * * EBOO_WITHDRAWAL_PAYMENT_API, ID: 5076
 * * DINGSHENG_WITHDRAWAL_PAYMENT_API, ID: 5524
 *
 * Required Fields:
 *
 * * URL
 * * Account - ## Merchant ID ##
 * * Key - ## API Key ##
 *
 * Field Values:
 *
 * * URL: http://sapi.eboopay.com/Payment_Dfpay_add.html
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_eboo_withdrawal extends Abstract_payment_api_eboo {
    const RETURN_STATUS_SUCCESS = 'success';
    const RETURN_STATUS_FAILED  = 'error';

    const CALLBACK_STATUS_SUCCESS  = 1;
    const CALLBACK_STATUS_FAILED   = 2;
    const CALLBACK_STATUS_REJECT   = 5;
    const CALLBACK_STATUS_NOTEXIST = 7;
    const CALLBACK_STATUS_WAITPROCESS = 4;

    public function getPlatformCode() {
        return EBOO_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'eboo_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function getBankCode() {}


    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================eboo submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================eboo submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================eboo submitWithdrawRequest response ', $response);
        $this->CI->utils->debug_log('======================================eboo submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    # Note: to avoid breaking current APIs, these abstract methods are not marked abstract
    # APIs with withdraw function need to implement these methods
    ## This function returns the URL to submit withdraw request to
    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    ## This function returns the params to be submitted to the withdraw URL
    ## Note that $bank param is the bank_type ID in database, we compare it with the supported bank_codes by this AP
    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================eboo withdrawal bank whose bankTypeId=[$bank] is not supported by eboo");
            return array('success' => false, 'message' => 'Bank not supported by eboo');
            $bank = '无';
        }
        $bankName = $bankInfo[$bank];   //銀行名稱

        # look up bank detail
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $bankBranch = empty($playerBankDetails['branch']) ? "无" : $playerBankDetails['branch'];
            $province = empty($playerBankDetails['province']) ? "无" : $playerBankDetails['province'];
            $city = empty($playerBankDetails['city']) ? "无" : $playerBankDetails['city'];
        } else {
            $bankBranch = '无';
            $province = '无';
            $city = '无';
        }

        $params = array();
        $params['mchid']        = $this->getSystemInfo('account');
        $params['out_trade_no'] = $transId;
        $params['money']        = $this->convertAmountToCurrency($amount);
        $params['bankname']     = $bankName;
        $params['subbranch']    = $bankBranch;
        $params['accountname']  = $name;
        $params['cardnumber']   = $accNum;
        $params['province']     = $province;
        $params['city']         = $city;
        $params['pay_md5sign']  = $this->sign($params);

        return $params;
    }

    ## This function takes in the return value of the URL and translate it to the following structure
    ## array('success' => false, 'message' => 'Error message')
    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }

        if(!is_null(json_decode($resultString))){
            $resultString = json_decode($resultString, true);
            $this->CI->utils->debug_log('==============eboo submitWithdrawRequest decodeResult json decoded', $resultString);
        }

        if($queryAPI){
            if($resultString['status'] == self::RETURN_STATUS_SUCCESS){
                $returnCode = $resultString['refCode'];
                $returnDesc = $resultString['refMsg'];

                if($returnCode == self::CALLBACK_STATUS_SUCCESS) {
                    $message = "Eboo withdrawal success! transaction_id: ". $resultString['transaction_id'];
                    return array('success' => true, 'message' => $message);
                }
                else if($returnCode == self::CALLBACK_STATUS_FAILED || $returnCode == self::CALLBACK_STATUS_REJECT || $returnCode == self::CALLBACK_STATUS_NOTEXIST){
                    $message = "Eboo withdrawal failed. [".$returnCode."]: ".$returnDesc;
                    return array('success' => false, 'message' => $message, 'payment_fail' => true);
                }
                else{
                    if(isset($resultString['msg'])){
                        $returnDesc = $resultString['msg'];
                        $this->CI->utils->debug_log('==============eboo query msg', $returnDesc);

                        if($returnCode == self::CALLBACK_STATUS_WAITPROCESS){
                            $returnDesc = '待处理';
                        }
                    }
                    $message = "Eboo withdrawal response status. [".$returnCode."]: ".$returnDesc;
                    return array('success' => false, 'message' => $message);
                }
            }
            else if($resultString['status'] == self::RETURN_STATUS_FAILED){
                return array('success' => false, 'message' => "Get withdrawal status failed");
            }
            return array('success' => false, 'message' => "Decode failed");
        }
        else{
            if(isset($resultString['status'])) {
                $returnCode = $resultString['status'];
                $returnDesc = $resultString['msg'];
                if($returnCode == self::RETURN_STATUS_SUCCESS) {
                    $message = "Eboo withdrawal response successful, transId: ". $resultString['transaction_id']. ", msg: ". $returnDesc;
                    return array('success' => true, 'message' => $message);
                }
                $message = "Eboo withdrawal response failed. [".$returnCode."]: ".$returnDesc;
                return array('success' => false, 'message' => $message);

            }
            else{
                $message = $message.' API response: '.$resultString;
                return array('success' => false, 'message' => $message);
            }
        }
        return array('success' => false, 'message' => "Eboo decoded fail.");
    }

    public function checkWithdrawStatus($transId) {

        $params = array();
        $params['mchid']        = $this->getSystemInfo('account');
        $params['out_trade_no'] = $transId;
        $params['pay_md5sign']  = $this->sign($params);

        $url = $this->getSystemInfo('check_status_url','http://gtpay-2.nds966.com/Payment_Dfpay_query.html');
        $response = $this->submitPostForm($url, $params, false, $transId);
        $decodedResult = $this->decodeResult($response, true);

        $this->CI->utils->debug_log('======================================eboo checkWithdrawStatus params: ', $params);
        $this->CI->utils->debug_log('======================================eboo checkWithdrawStatus url: ', $url);
        $this->CI->utils->debug_log('======================================eboo checkWithdrawStatus result: ', $response);
        $this->CI->utils->debug_log('======================================eboo checkWithdrawStatus decoded Result', $decodedResult);
        return $decodedResult;
    }

    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("eboo_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting eboo bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => '中国工商银行',
                '2' => '招商银行',
                '3' => '中国建设银行',
                '4' => '中国农业银行',
                '5' => '交通银行',
                '6' => '中国银行',
                //'7' => '深圳发展银行',
                '8' => '广发银行',
                '9' => '东莞农村商业银行股份有限公司',
                '10' => '中信银行',
                '11' => '中国民生银行',
                '12' => '中国邮政储蓄银行',
                '13' => '兴业银行',
                '14' => '华夏银行',
                '15' => '平安银行',
                //'16' => '广西农村信用社',
                '17' => '广州银行',
                '18' => '南京银行',
                '19' => '广州农村商业银行股份有限公司',
                '20' => '中国光大银行',
                '24' => '上海浦东发展银行',
                '26' => '广发银行',
                '27' => '上海浦东发展银行',
                //'28' => '东亚银行',
                '29' => '北京银行',
                '30' => '天津银行',
                '31' => '上海银行',
                '32' => '上海农商行',
                '33' => '北京农村商业银行股份有限公司'
            );
            $this->utils->debug_log("=======================getting eboo bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- signing --
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        
        return $sign;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign' || $key == 'pay_md5sign'|| $key == 'attach' || empty($value)) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return $signStr."key=".$this->getSystemInfo('key');
    }

    public function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }
}