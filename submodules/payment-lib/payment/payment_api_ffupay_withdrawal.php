<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ffupay.php';
/**
 * FFUPAY
 *
 * * FFUPAY_WITHDRAWAL_PAYMENT_API, ID: 5917
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * URL
 *
 * Field Values:
 * * Account: ## APP ID ##
 * * Key: ## APP KEY ##
 * * Secret: ## APP SECRET ##
 * * URL: https://www.ffupay.com/oss/wallet/cre_propay_order
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ffupay_withdrawal extends Abstract_payment_api_ffupay {


    const CALLBACK_STATUS_SUCCESS    = 'CODE_SUCCESS'; #处理成功
    const CALLBACK_STATUS_FAILED     = 'CODE_FAILURE'; #表示付款被拒
    const REQUEST_STATUS_SUCCESS     = 10000; #送出成功
    const RETURN_SUCCESS_CODE     = 'success';
    const RETURN_FAILED_CODE      = 'fail';

    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getPlatformCode() {
        return FFUPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'ffupay_withdrawal';
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================ffupay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by ffupay");
            return array('success' => false, 'message' => 'Bank not supported by ffupay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================ffupay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================ffupay submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================ffupay submitWithdrawRequest response ', $response);
        $this->CI->utils->debug_log('======================================ffupay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getBankInfo();

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        if(!empty($playerBankDetails)){
            $bankBranch = $playerBankDetails['branch'];
        } else {
            $bankBranch = '无';
        }

        $params = array();
        $params['appid'] = $this->getSystemInfo("account");
        $params['out_trade_no'] = $transId;
        $params['money'] = $this->convertAmountToCurrency($amount);
        $params['cardnumber'] = $accNum;
        $params['accountname'] = $name;
        $params['bankname'] =  $bankInfo[$bank]['name'];
        $params['subbranch'] = $bankBranch;
        $params['notifyurl'] = $this->getNotifyUrl($transId);
        $params['sign'] = $this->sign($params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================ffupay json_decode result", $result);

        $respCode = $result['code'];
        $resultMsg = $result['msg'];
        $this->utils->debug_log("=========================ffupay withdrawal resultMsg", $resultMsg);

        if(isset($respCode) && $respCode == self::REQUEST_STATUS_SUCCESS) {
            $message = "ffupay request successful.";
            return array('success' => true, 'message' => $message);
        }
        else {
            if(!isset($respCode) && !isset($resultMsg) && empty($resultMsg)) {
                 $this->utils->error_log("========================ffupay return UNKNOWN ERROR!");
                $resultMsg = "未知错误";
            }
            $message = "ffupay withdrawal response, Code: [ ".$respCode." ] , Msg: ".$resultMsg;
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

        $this->utils->debug_log("==========================ffupay checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        $statusCode = $params['callbacks'];
        if($statusCode == self::CALLBACK_STATUS_SUCCESS) {
            $msg = "ffupay withdrawal success!";
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else if($statusCode == self::CALLBACK_STATUS_FAILED){
            $msg = "ffupay withdrawal failed.";
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
        }
        else {
            $msg = "ffupay withdrawal response order_state: [".$params['order_state']."]";
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'callbacks' ,'appid' ,'out_trade_no', 'money'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================ffupay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================ffupay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['money'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================ffupay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['out_trade_no'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================ffupay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
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
            $this->utils->debug_log("=========================ffupay bank info from extra_info: ", $bankInfo);
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
            $this->utils->debug_log("=========================ffupay bank info from code: ", $bankInfo);

        }
        return $bankInfo;
    }
}