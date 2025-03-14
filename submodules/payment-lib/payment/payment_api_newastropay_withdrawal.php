<?php
require_once dirname(__FILE__) . '/abstract_payment_api_newastropay.php';

/**
 * NEWASTROPAY
 *
 * * NEWASTROPAY_WITHDRAWAL_PAYMENT_API, ID: 6215
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_newastropay_withdrawal extends Abstract_payment_api_newastropay {
    public function getPlatformCode() {
        return NEWASTROPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'newastropay_withdrawal';
    }

    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $phone      = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : 'none';
        }

        $user_data = array();
        $user_data['merchant_user_id'] = $playerId;
        $user_data['phone'] = $this->getSystemInfo("country_code",self::COUNTRY_CODE).$phone;

        $params = array();
        $params['amount']              = $this->convertAmountToCurrency($amount);
        $params['currency']            = $this->getSystemInfo("currency",self::CURRENCY);
        $params['country']             = $this->getSystemInfo("country",self::COUNTRY);
        $params['merchant_cashout_id'] = $transId;
        $params['callback_url']        = $this->getNotifyUrl($transId);
        $params['user']                = $user_data;

        $this->CI->utils->debug_log('=========================NEWASTROPAY getWithdrawParams params', $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');
        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getSystemInfo('url');
        list($content, $response_result) = $this->processCurl($params, $url, $transId, true);
        $this->CI->utils->debug_log('=====================NEWASTROPAY submitWithdrawRequest received response', $content);
        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;

    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================NEWASTROPAY json_decode result", $result);

        if(!empty($result) && isset($result)){
            if(!empty($result['status']) && isset($result['status']) && $result['status'] == self::PAY_RESULT_SUCCESS ){
                return array('success' => true, 'message' => 'NEWASTROPAY withdrawal request successful.');
            }else if(isset($result['description']) && !empty($result['description'])){
                $errorMsg = $result['description'];
                return array('success' => false, 'message' => $errorMsg);
            }
            else{
                return array('success' => false, 'message' => 'NEWASTROPAY withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'NEWASTROPAY withdrawal exist errors');
        }
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================NEWASTROPAY json_decode params", $params);
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================NEWASTROPAY process withdrawalResult order id', $transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['status'] == self::CALLBACK_RESULT_SUCCESS) {
            $msg = sprintf('NEWASTROPAY withdrawal was successful: trade ID [%s]', $params['merchant_cashout_id']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('NEWASTROPAY withdrawal was not success: [%s]', $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = self::RETURN_SUCCESS_CODE;
        }

        return $result;
    }


    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array('status', 'cashout_id', 'merchant_cashout_id');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================NEWASTROPAY withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================NEWASTROPAY checkCallbackOrder Signature Error', $fields);
            return false;
        }

        if ($fields['merchant_cashout_id'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================NEWASTROPAY withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }
}