<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * * PAYBUS_DEPAY_BANK_WITHDRAWAL_PAYMENT_API
 *
 * Field Values:
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_paybus_depay_bank_withdrawal extends Abstract_payment_api_paybus {

    public function getPlatformCode() {
        return PAYBUS_DEPAY_BANK_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_depay_bank_withdrawal';
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
            $this->utils->error_log("========================paybus submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by paybus");
            return array('success' => false, 'message' => 'Bank not supported by paybus');
        }

        $token = $this->getPaymentToken();
        $token = json_decode($token,true);
        $this->CI->utils->debug_log('========================================paybus processPaymentUrlFormPost token', $token);

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        $requestPath = 'POST /payment/withdraw';
        $requestBody = json_encode($params);
        $sign = $this->sign($requestPath, $requestBody);

        $this->_custom_curl_header = array(
            'x-token:'.$token['token'],
            'x-sign:'. $sign,
            'Content-Type:application/json'
        );

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);

        //update wallet account
        if($decodedResult['success']){
            $order_id='';
            $platform_id='';
            $result = json_decode($response, true);
            if(isset($result['order_id'])){
                $order_id=$result['order_id'];
            }
            if(isset($result['platform_id'])){
                $platform_id=$result['platform_id'];
            }
            $this->updateWalletaccountExtraInfo($transId, $order_id, $platform_id);
        }

        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================paybus submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================paybus submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================paybus submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $this->utils->debug_log("===============================paybus Get playerBankDetails using [$bank] + [$accNum]");

        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];

        $params = array();
        $params['client_id']     = $transId;
        $params['amount']        = (double)$this->convertAmountToCurrency($amount);
        $params['channel_input'] = json_decode(json_encode([
            self::CHANNEL_DEPAY_BANK_WITHDRAWAL => [
                'bank' => $bankCode,
                'bank_card_name' => trim($name), 
                'bank_card_account' => $accNum,
                'bank_card_remark' => $bankCode]]));

        $params['callback_url']  = $this->getNotifyUrl($transId);
        
        $this->CI->utils->debug_log('=========================paybus getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================paybus json_decode result", $result);

        if (isset($result['order_status'])) {
            if($result['order_status'] == self::REPONSE_CODE_SUCCESS) {
                $message = "paybus withdrawal response successful, orderId:".$result['order_id'];
                return array('success' => true, 'message' => $message);
            }
            $message = "paybus withdrawal response failed. ErrorMessage: ".$result['extra_message'];
            return array('success' => false, 'message' => $message);
        }
        elseif(!$result['is_success']){
            $message = 'paybus withdrawal response: '.$result['error']['message']. '; detail: '.$result['error']['detail'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "paybus decoded fail.");
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
            $this->utils->debug_log("==================getting paybus bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo =  array(
                "32" => [
                    "name" => "Gcash",
                    "code" => "gcash"
                ]
            );
            $this->utils->debug_log("=======================getting paybus bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("========================paybus callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("========================paybus callbackFromServer json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================paybus callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================paybus callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        $callbackSuccess = !empty($this->getSystemInfo('callback_success')) ? $this->getSystemInfo('callback_success') : self::CALLBACK_SUCCESS;

        if (in_array($params['order_status'], $callbackSuccess)) {
            $msg = sprintf('paybus withdrawal success: order ID [%s]', $params['client_id']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }else {
            $msg = sprintf("paybus withdrawal payment unsuccessful or pending: status=%s", $params['order_status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
            $result['return_error_json'] = array('success' => false, 'message' => $result['message']);
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'client_id', 'amount', 'order_status', 'order_id', 'platform_callback_amount'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================paybus withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        $headers = $this->CI->input->request_headers();
        $this->CI->utils->debug_log("=====================paybus withdrawal checkCallbackOrder headers", $headers);

        $requestPath = 'POST '.'/callback/process/' . $this->getPlatformCode() . '/' . $order['transactionCode'];
        $requestBody = json_encode($fields, JSON_UNESCAPED_UNICODE);
        $callbackSign = $headers['X-Sign'];

        # is signature authentic?
        if (!$this->validateSign($requestPath, $requestBody, $callbackSign)) {
            $this->writePaymentErrorLog('=====================paybus withdrawal checkCallbackOrder Signature Error', $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================paybus withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['client_id'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================paybus withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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

    public function getPaymentToken(){
        $tokenUrl = $this->getSystemInfo('token_url');
        $merchantCode = $this->getSystemInfo("account");
        $timestamp= time();

        $params['merchant_code'] = $merchantCode;
        $params['timestamp']     = $timestamp;

        $requestPath = 'GET /token?merchant_code=' .$merchantCode . '&timestamp=' . $timestamp;
        $requestBody = '';
        $sign = $this->sign($requestPath, $requestBody);

        $this->CI->utils->debug_log('========================================paybus getPaymentToken sign', $sign);

        $this->_custom_curl_header = array(
            'x-sign:'. $sign,
            'Content-Type:application/json'
        );

        return $this->submitGetForm($tokenUrl, $params);
    }
}