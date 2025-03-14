<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cfupay.php';

/**
 * CFUPAY 创富-出款
 *
 * * CFUPAY_WITHDRAWAL_PAYMENT_API, ID: 665
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://client.cfupay.com/
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_cfupay_withdrawal extends Abstract_payment_api_cfupay {
    const CALLBACK_STATUS_SUCCESS = 1;

    public function getPlatformCode() {
        return CFUPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'cfupay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}

    /**
     * detail: override common API functionsh
     *
     * @return void
     */
    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        return $this->returnUnimplemented();
    }

    public function processPaymentUrlForm($params) {
        return $this->returnUnimplemented();
    }

    # APIs with withdraw function need to implement these methods
    ## This function returns the URL to submit withdraw request to
    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    ## This function returns the params to be submitted to the withdraw URL
    ## Note that $bank param is the bank_type ID in database, we compare it with the supported bank_codes by this API
    private $errMsg = 'Payment failed'; # This variable is used to store error message that's available upon submit
    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $params = array();
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $params = [
            "merchantId" => $this->getSystemInfo("account"),
            "batchAmount" => $amount,
            "batchVersion" => "00",
            "batchBiztype" => "00000",
            "batchDate" => date("Ymd"),
            "batchNo" => 'batch' . $transId,
            "charset" => "UTF-8"
        ];

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $province = $playerBankDetails['province'];
            $city = $playerBankDetails['city'];
            $bankBranch = $playerBankDetails['branch'];
            $bankSubBranch = $playerBankDetails['branch'];
        } else {
            $province = '无';
            $city = '无';
            $bankBranch = '无';
            $bankSubBranch = '无';
        }

        $province = empty($province) ? "无" : $province;
        $city = empty($city) ? "无" : $city;
        $bankBranch = empty($bankBranch) ? "无" : $bankBranch;
        $bankSubBranch = empty($bankSubBranch) ? "无" : $bankSubBranch;
        $accountType = '0';
        $currency = 'CNY';
        $mobile = $this->getBankMobileNo();
        $idType = '身份证';
        //$idNo = $this->getIdNo();
        $idNo = "123456789123456789";
        $licenseNo = '123456';
        $merchantOrderNo = $transId;
        $remark = "Withdrawal";

        # look up bank code
        $bankInfo = $this->getHuidpayBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================cfupay withdrawal bank whose bankTypeId=[$bank] is not supported by cfupay");
            return array('success' => false, 'message' => 'Bank not supported by cfupay');
            $bank = '无';
        }

        $bank = $bankInfo[$bank]['name'];	//开户行名称

        # but if we cannot look up those info, will leave the fields blank
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("=========================cfupay withdrawal get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $bankBranch = $playerBankDetails['branch'];
            $bankSubBranch = $playerBankDetails['branch'];
            $province = $playerBankDetails['province'];	//开户省  卡号的开户省
            $city = $playerBankDetails['city'];	//开户市 卡号的开户市

        }

        $batchContent = [
            $accNum,
            $name,
            $bank,
            $bankBranch,
            $bankSubBranch ,
            $accountType,
            $amount,
            $currency,
            $province,
            $city,
            $mobile,
            $idType,
            $idNo,
            $licenseNo,
            $merchantOrderNo,
            $remark
        ];

        $string = "1";
        foreach ($batchContent as $value) {
            $string .= ',' . $value;
        }

        $batchContent = $string;
        $params['batchContent'] = $batchContent;

        

        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');
        $success = false;
        $message = 'payment failed';

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        if(isset($params['success'])) {
            if($params['success'] == false) {
                $result['message'] = $params['message'];
                $this->utils->debug_log($result);
                return $result;
            }
        }

        $params['sign'] = $this->sign($params);
        $params['signType'] = "SHA";
        $this->CI->utils->debug_log('======================================cfupay submitWithdrawRequest params: ', $params);

        $url = $this->getSystemInfo('url').$params['merchantId'].'-'.$transId;
        $this->CI->utils->debug_log('======================================cfupay submitWithdrawRequest url: ', $url );

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('======================================cfupay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        if($queryAPI) {
            $this->utils->debug_log("=========================cfupay checkWithdrawStatus decoded result string", $result);
        }
        else {
            $this->utils->debug_log("=========================cfupay withdrawal decoded result string", $result);
        }

        if($result['respCode'] == 'S0001') {
            $message = 'cfupay payment response successful, respCode: '.$result['respCode'];

            if($queryAPI) {	//checkWithdrawStatus result
                $success = false;

                $batchContent = $result['batchContent'];
                $batchContentArr = explode(",", $batchContent);

                $this->utils->debug_log("=========================cfupay checkWithdrawStatus batchContentArr", $batchContentArr);

                $tradeNum =          array_key_exists(0  , $batchContentArr) ? $batchContentArr[0]  : "default_empty_string";
                $tradeCustorder =    array_key_exists(1  , $batchContentArr) ? $batchContentArr[1]  : "default_empty_string";
                $tradeCardnum =      array_key_exists(2  , $batchContentArr) ? $batchContentArr[2]  : "default_empty_string";
                $tradeCardname =     array_key_exists(3  , $batchContentArr) ? $batchContentArr[3]  : "default_empty_string";
                $tradeBranchbank =   array_key_exists(4  , $batchContentArr) ? $batchContentArr[4]  : "default_empty_string";
                $tradeSubbranchban = array_key_exists(5  , $batchContentArr) ? $batchContentArr[5]  : "default_empty_string";
                $tradeAccountname =  array_key_exists(6  , $batchContentArr) ? $batchContentArr[6]  : "default_empty_string";
                $tradeAccounttype =  array_key_exists(7  , $batchContentArr) ? $batchContentArr[7]  : "default_empty_string";
                $tradeAmount =       array_key_exists(8  , $batchContentArr) ? $batchContentArr[8]  : "default_empty_string";
                $tradeAmounttype =   array_key_exists(9  , $batchContentArr) ? $batchContentArr[9]  : "default_empty_string";
                $tradeRemark =       array_key_exists(10 , $batchContentArr) ? $batchContentArr[10] : "default_empty_string";
                $contractUsercode =  array_key_exists(11 , $batchContentArr) ? $batchContentArr[11] : "default_empty_string";
                $tradeFeedbackcode = array_key_exists(12 , $batchContentArr) ? $batchContentArr[12] : "default_empty_string";
                $tradeReason =       array_key_exists(13 , $batchContentArr) ? $batchContentArr[13] : "default_empty_string";

                switch ($tradeFeedbackcode) {
                    case 'null':
                        $message = 'CFUPAY_WITHDRAWAL(创富代付) 处理中, 商户订单号: ['. $tradeCustorder .']';
                        break;

                    case '成功':
                        $success = true;
                        $message = 'CFUPAY_WITHDRAWAL(创富代付) 成功, 商户订单号: ['. $tradeCustorder .']';
                        break;

                    case '失败':
                        $message = 'CFUPAY_WITHDRAWAL(创富代付) 失敗, 原因: ['. $tradeReason .'], 商户订单号: ['. $tradeCustorder .']';
                        break;

                    default:
                        $message = 'cfupay payment check withdraw status and got an unknown tradeFeedbackcode, tradeFeedbackcode: '.$tradeFeedbackcode;
                        break;
                }

                return array('success' => $success, 'message' => $message);
            }

            return array('success' => true, 'message' => $message);
        }
        else if($result['respMessage']) {
            $this->errMsg = '['.$result['respCode'].']: '.$result['respMessage'];
        }
        else {
            $this->errMsg = 'cfupay payment failed for unknown reason';
        }

        return array('success' => false, 'message' => $this->errMsg);
    }

    public function getHuidpayBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("cfupay_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1], 'code' => $bankInfoItem[2]);
            }
            $this->utils->debug_log("==================getting cfupay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => array('name' => '中国工商银行', 'code' => 'ICBC'),
                '2' => array('name' => '招商银行', 'code' => 'CMB'),
                '3' => array('name' => '中国建设银行', 'code' => 'CCB'),
                '4' => array('name' => '中国农业银行', 'code' => 'ABC'),
                '5' => array('name' => '交通银行', 'code' => 'BOCM'),
                '6' => array('name' => '中国银行', 'code' => 'BOC'),
                '8' => array('name' => '广发银行', 'code' => 'CGB'),
                '10' => array('name' => '中信银行', 'code' => 'CITIC'),
                '11' => array('name' => '民生银行', 'code' => 'CMBC'),
                '12' => array('name' => '中国邮政储蓄', 'code' => 'PSBC'),
                '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                '14' => array('name' => '华夏银行', 'code' => 'HXB'),
                '15' => array('name' => '平安银行', 'code' => 'PAYH'),
                '20' => array('name' => '光大银行', 'code' => 'CEB'),
                '24' => array('name' => '浦发银行', 'code' => 'SPDB')
            );
            $this->utils->debug_log("=======================getting cfupay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    public function checkWithdrawStatus($transId) {
        $this->CI->load->model(array('wallet_model'));
        $walletaccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $dateTimeString = $walletaccount['dwDateTime'];
        $datetime = new DateTime($dateTimeString);

        # ---- First add bank card entry ----
        $param = array();
        $param['batchDate'] = $datetime->format('Ymd');
        $param['batchNo'] = 'batch' . $transId;
        $param['batchVersion'] = "00";
        $param['charset'] = "UTF-8";
        $param['merchantId'] = $this->getSystemInfo("account");

        $param['sign'] = $this->sign($param);
        $param['signType'] = "SHA";
        $this->CI->utils->debug_log('======================================cfupay checkWithdrawStatus param: ', $param);

        $url = $this->getSystemInfo('url').$param['merchantId'].'-'.$transId;
        $this->CI->utils->debug_log('======================================cfupay checkWithdrawStatus url: ', $url );

        list($response, $response_result) = $this->submitGetForm($url, $param, false, $transId, true);

        $decodedResult = $this->decodeResult($response, true);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================cfupay checkWithdrawStatus decodedResult: ', $decodedResult );

        return $decodedResult;
    }

    public function getBankMobileNo() {
        $headNum = array("135", "139");
        $k = array_rand($headNum);
        $num = $headNum[$k].mt_rand(10000000, 99999999);

        return $num;
    }

    public function getIdNo() {
        $randAddrCode = mt_rand(100000, 999999);
        $int = mt_rand(0000000000, 1262053300);
        $randDateCode = date("Ymd", $int);
        $randOrderCode = mt_rand(100, 999);
        $checksum = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "X");
        $k = array_rand($checksum);
        $randChecksum = $checksum[$k];

        $idNo = $randAddrCode.$randDateCode.$randOrderCode.$randChecksum;

        return $idNo;
    }
}
