<?php
require_once dirname(__FILE__) . '/abstract_payment_api_eferpay.php';
/**
 * EFERPAY
 *
 * * EFERPAY_WITHDRAWAL_PAYMENT_API, ID: 5192
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
 * * URL: https://www.eferpay.com/oss/wallet/cre_propay_order
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_eferpay_withdrawal extends Abstract_payment_api_eferpay {

    const CALLBACK_STATUS_PROCESSING = 0; #待处理
    const CALLBACK_STATUS_SUCCESS    = 1; #处理成功
    const CALLBACK_STATUS_ERROR      = 2; #订单异常被冻结
    const CALLBACK_STATUS_FAILED     = 3; #表示付款被拒

    const RETURN_SUCCESS_CODE     = 'success';
    const RETURN_FAILED_CODE      = 'fail';

    public function getPlatformCode() {
        return EFERPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'eferpay_withdrawal';
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================eferpay withdrawal bank whose bankTypeId=[$bank] is not supported by eferpay");
            return array('success' => false, 'message' => 'Bank not supported by eferpay');
            $bank = '无';
        }

        #----Login----
            $response = $this->processCurl($this->login_url, null, $transId);
            if(isset($response['code']) && $response['code'] == self::RESULT_CODE_SUCCESS){
                $token   = $response['data']['user_token'];
                $expired = $response['data']['expired'];
            }
            else if(isset($response['code']) && $response['code'] > self::RESULT_CODE_SUCCESS){
                $message = lang('Eferpay login failed').': ['.$response['code'].']'.$response['msg'];
                return array('success' => false, 'message' => $message);
            }
            else{
                $message = lang("Eferpay login failed for unknown reason");
                return array('success' => false, 'message' => $message);
            }

        #----Create Order----
            $withdraw_url = $this->getSystemInfo('url');
            $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
            $response = $this->processCurl($withdraw_url, $params, $transId, $token);
            $decodedResult = $this->decodeResult($response);

        $this->CI->utils->debug_log('======================================eferpay submitWithdrawRequest decoded Result', $decodedResult);
        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getBankInfo();

        $params = array();
        $params['bankcode']  = $bankInfo[$bank]['code'];
        $params['ordersn']   = $transId;
        $params['promoney']  = $this->convertAmountToCurrency($amount);
        $params['realname']  = $name;
        $params['bankno']    = $accNum;
        $params['notifyurl'] = $this->getNotifyUrl($transId);
        return $params;
    }

    public function decodeResult($response, $queryAPI = false) {
        if(isset($response['code']) && $response['code'] == 0){
            $message = "Eferpay withdrawal response successful!";
            return array('success' => true, 'message' => $message);
        }
        else if(isset($response['code']) && $response['code'] > 0){
            $message = "Eferpay withdrawal response failed. [".$response['code']."]: ".$response['msg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "Eferpay decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        $statusCode = $params['order_state'];
        if($statusCode == self::CALLBACK_STATUS_SUCCESS) {
            $msg = "Eferpay withdrawal success!";
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else if($statusCode == self::CALLBACK_STATUS_FAILED){
            $msg = "Eferpay withdrawal failed.";
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
        }
        else {
            $msg = "Eferpay withdrawal response order_state: [".$params['order_state']."]";
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'order_state', 'order_trade_sn', 'order_money'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================eferpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================eferpay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['order_money'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================eferpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['order_trade_sn'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================eferpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
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
            $this->utils->debug_log("=========================eferpay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => array('name' => '工商银行', 'code' => '1002'),
                '2' => array('name' => '招商银行', 'code' => '1001'),
                '3' => array('name' => '建设银行', 'code' => '1003'),
                '4' => array('name' => '农业银行', 'code' => '1005'),
                '5' => array('name' => '交通银行', 'code' => '1020'),
                '6' => array('name' => '中国银行', 'code' => '1026'),
                '8' => array('name' => '广发银行', 'code' => '1027'),
                '10' => array('name' => '中信银行', 'code' => '1021'),
                '11' => array('name' => '民生银行', 'code' => '1006'),
                '12' => array('name' => '邮储银行', 'code' => '1066'),
                '13' => array('name' => '兴业银行', 'code' => '1009'),
                '14' => array('name' => '华夏银行', 'code' => '1025'),
                '15' => array('name' => '平安银行', 'code' => '1010'),
                '20' => array('name' => '光大银行', 'code' => '1022'),
                '32' => array('name' => '浦发银行', 'code' => '1004')
            );
            $this->utils->debug_log("=========================eferpay bank info from code: ", $bankInfo);

        }
        return $bankInfo;
    }
}