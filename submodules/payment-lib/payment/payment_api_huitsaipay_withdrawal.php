<?php
require_once dirname(__FILE__) . '/abstract_payment_api_huitsaipay.php';

/**
 * HUITSAIPAY_WITHDRAWAL
 *
 * * huitsaipay_WITHDRAWAL_PAYMENT_API, ID: 5873
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.uiui8899.com/api/merchant/withdraw
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_huitsaipay_withdrawal extends Abstract_payment_api_huitsaipay {

    const CHANNLETYPE = '0';
    const RESPONSE_ORDER_SUCCESS = true;
    const CALLBACK_STATUS_SUCCESS = true;

    public function getPlatformCode() {
        return HUITSAIPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'huitsaipay_withdrawal';
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
            $this->utils->error_log("========================huitsaipay withdrawal bank whose bankTypeId=[$bank] is not supported by huitsaipay");
            return array('success' => false, 'message' => 'Bank not supported by huitsaipay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================huitsaipay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================huitsaipay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================huitsaipay submitWithdrawRequest decoded Result', $decodedResult);

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
        $this->utils->debug_log("===============================huitsaipay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
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
        $params['merchant']                 = $this->getSystemInfo("account");
        $params['requestReference']         = $transId;
        $params['merchantBank']             = $bankCode;
        $params['merchantBankCardRealname'] = $name;
        $params['merchantBankCardAccount']  = $accNum;
        $params['merchantBankCardProvince'] = $province;
        $params['merchantBankCardCity']     = $city;
        $params['merchantBankCardBranch']   = $bankBranch;
        $params['amount']                   = $this->convertAmountToCurrency($amount);
        $params['remark']                   = 'withdrawal';
        $params['callback']                 = $this->getNotifyUrl($transId);
        $params['sign']                     = $this->sign($params);
        $this->CI->utils->debug_log('=========================huitsaipay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================huitsaipay json_decode result", $result);

        if(isset($result['success'])) {
            if($result['success'] == self::RESPONSE_ORDER_SUCCESS) {
                $message = "huitsaipay withdrawal response successful";
                return array('success' => true, 'message' => $message);
            }
            $message = "huitsaipay withdrawal response failed. [".$result['code']."]: ".$result['message'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['message']){
            $message = 'huitsaipay withdrawal response: '.$result['message'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "huitsaipay decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================huitsaipay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================huitsaipay json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================huitsaipay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================huitsaipay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['success'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('huitsaipay withdrawal success: trade ID [%s]', $params['requestReference']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        // else if ($params['Status'] != self::ORDER_STATUS_PROCESS && $params['Status'] != self::ORDER_STATUS_CREATED) {
        //     $msg = sprintf('huitsaipay withdrawal failed: [%s]', $params['Message']);
        //     $this->writePaymentErrorLog($msg, $fields);
        //     $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
        //     $result['message'] = $msg;
        // }
        else {
            $msg = sprintf('huitsaipay withdrawal payment was not successful: [%s]', $params['success']);
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'requestReference', 'amount', 'success', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================huitsaipay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================huitsaipay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['success'] != self::CALLBACK_STATUS_SUCCESS) {
            $this->writePaymentErrorLog("======================huitsaipay withdrawal checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $order['amount']) {
            $this->writePaymentErrorLog('=========================huitsaipay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['requestReference'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================huitsaipay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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
            $this->utils->debug_log("==================getting huitsaipay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '1' => array('name' => '工商银行', 'code' => 'ICBC'),
                '2' => array('name' => '招商银行', 'code' => 'CMB'),
                '3' => array('name' => '建设银行', 'code' => 'CCB'),
                '4' => array('name' => '农业银行', 'code' => 'ABC'),
                '5' => array('name' => '交通银行', 'code' => 'BOCOM'),
                '6' => array('name' => '中国银行', 'code' => 'BOCSH'),
                '7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
                '8' => array('name' => '广发银行', 'code' => 'GDB'),
                '10' => array('name' => '中信银行', 'code' => 'CITIC'),
                '11' => array('name' => '民生银行', 'code' => 'CMBC'),
                '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                '14' => array('name' => '华夏银行', 'code' => 'HXBC'),
                '15' => array('name' => '平安银行', 'code' => 'PAB'),
                '17' => array('name' => '广州银行', 'code' => 'GZCB'),
                '18' => array('name' => '南京银行', 'code' => 'NJCB'),
                '20' => array('name' => '光大银行', 'code' => 'CEB'),
                '24' => array('name' => '浦发银行', 'code' => 'SPDB'),
                '25' => array('name' => '北京银行', 'code' => 'BOB'),
                '25' => array('name' => '上海银行', 'code' => 'BOS'),
            );
            $this->utils->debug_log("=======================getting aipay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}