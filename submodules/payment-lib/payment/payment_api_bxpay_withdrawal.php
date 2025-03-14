<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bxpay.php';

/**
 * BXPAY_WITHDRAWAL
 *
 * * BXPAY_WITHDRAWAL_PAYMENT_API, ID: 6244
 *
 * Field Values:
 * * URL: https://pay.baxizhifu.com/api/pay/repay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_bxpay_withdrawal extends Abstract_payment_api_bxpay {

    const PAYTYPE_CPF = 'CPF';
    const PAY_CODE = '0000';

    public function getPlatformCode() {
        return BXPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'bxpay_withdrawal';
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
            $this->utils->error_log("========================bxpay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by bxpay");
            return array('success' => false, 'message' => 'Bank not supported by bxpay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================bxpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================bxpay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================bxpay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];
        $playerInfo = $this->getPlayerInfoByTransactionCode($transId, $bankInfo[$bank]['name']);
        $playerName = trim($playerInfo['firstName']).' '.trim($playerInfo['lastName']);
    
        $params = array();
        $params['merId']      = $this->getSystemInfo("account");
        $params['orderId']    = $transId;
        $params['money']      = $this->convertAmountToCurrency($amount);
        $params['acc_name']   = trim($playerName);
        $params['acc_no']     = $playerInfo['pixAccount'];
        $params['acc_code']   = self::PAY_CODE;
        $params['province']   = self::PAYTYPE_CPF;
        $params['city']       = 'none';
        $params['zhihang']    = self::CHANNEL_PIX;
        $params['notifyUrl']  = $this->getNotifyUrl($transId);
        $params['nonceStr']   = $this->uuid();
        $params['otherpara1'] = $bankCode;
        $params['otherpara2'] = self::CHANNEL_PIX;
        $params['sign']       = $this->sign($params);
        
        $this->CI->utils->debug_log('=========================bxpay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================bxpay json_decode result", $result);

        if (isset($result['code'])) {
            if($result['code'] == self::REPONSE_CODE_SUCCESS) {
                $message = "bxpay withdrawal response successful, orderId:".$result['data']['orderId'];
                return array('success' => true, 'message' => $message);
            }
            $message = "bxpay withdrawal response failed. ErrorMessage: ".$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        elseif($result['msg']){
            $message = 'bxpay withdrawal response: '.$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "bxpay decoded fail.");
    }

    public function getBankInfo(){
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
            $this->utils->debug_log("==================bxpay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '47' => array('name' => 'CPF', 'code' => 'CPF'),
                '48' => array('name' => 'EMAIL', 'code' => 'EMAIL'),
                '49' => array('name' => 'PHONE', 'code' => 'PHONE'),
            );
            $this->utils->debug_log("=======================bxpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);

        $raw_post_data = file_get_contents('php://input', 'r');
        $this->CI->utils->debug_log("=====================bxpay raw_post_data", $raw_post_data);
        parse_str($raw_post_data ,$params);
        $this->CI->utils->debug_log("=====================bxpay parse_str params", $params);

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================bxpay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================bxpay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('bxpay withdrawal success: order ID [%s]', $params['orderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }else {
            $msg = sprintf("bxpay withdrawal payment unsuccessful or pending: status=%s", $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'orderId', 'money', 'status', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================bxpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================bxpay withdrawal checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }

        if ($fields['money'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================bxpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['orderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================bxpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}