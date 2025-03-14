<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cashpay_v2.php';

/**
 * CASHPAY_V2
 *
 * * CASHPAY_V2_WITHDRAWAL_PAYMENT_API, ID: 6593
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: https://onepay.news/api/v1/order/out
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_cashpay_withdrawal_v2 extends Abstract_payment_api_cashpay_v2 {
    public function getPlatformCode() {
        return CASHPAY_V2_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'cashpay_v2_withdrawal';
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
        $playerInfo = $this->getPlayerInfoByTransactionCode($transId);

        $params = array();
        $params['amount']          = $this->convertAmountToCurrency($amount);
        $params['merchantOrderId'] = $transId;
        $params['notifyUrl']       = $this->getNotifyUrl($transId);
        $params['customerName']    = $playerInfo['lastName'].' '.$playerInfo['firstName'];
        $params['customerCert']    = $playerInfo['cpfNumber'];
        $params['accountType']     = 'CPF';
        $params['accountNum']      = $playerInfo['cpfNumber'];
        $params['merchantUserId']  = $playerInfo['playerId'];

        $this->CI->utils->debug_log('=========================cashpay_v2 getWithdrawParams params', $params);
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
        list($content, $response_result) = $this->processCurl($params, true);
        $this->CI->utils->debug_log('=====================cashpay_v2 submitWithdrawRequest received response', $content);
        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;

    }

    public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================cashpay_v2 json_decode result", $result);
        if(!empty($result) && isset($result)){

            $errorMsg = 'cashpay_v2 withdrawal exist errors';

            if(!empty($result['msg'])){
                $errorMsg = $result['msg'];
            }

            if(empty($result['code'])){
                return array('success' => false, 'message' => $errorMsg);
            }

            if($result['code'] == self::RESPONSE_PROCESSING){
                return array('success' => true, 'message' => 'cashpay_v2 withdrawal request successful.');
            }

            if($result['code'] == self::RESPONSE_SUCCESS){
                return array('success' => true, 'message' => 'cashpay_v2 withdrawal request successful.');
            }

            return array('success' => false, 'message' => $errorMsg);

        }else{
            return array('success' => false, 'message' => 'cashpay_v2 withdrawal exist errors');
        }
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================cashpay_v2 json_decode params", $params);
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================cashpay_v2 process withdrawalResult order id', $transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('cashpay_v2 withdrawal was successful: trade ID [%s]', $params['merchantOrderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('cashpay_v2 withdrawal was not success: [%s]', $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = self::RETURN_FAIL_CODE;
        }

        return $result;
    }


    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'status', 'merchantOrderId', 'sign', 'traceId'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================cashpay_v2 withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================cashpay_v2 checkCallbackOrder Signature Error', $fields);
            return false;
        }

        if ($fields['merchantOrderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================cashpay_v2 withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================wxpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    protected function processCurl($params, $return_all=false){
        $ch = curl_init();
        $token = base64_encode($this->getSystemInfo('account').':'.$this->getSystemInfo('key'));
        $url = $this->getSystemInfo('url');

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'x-app-id: '.$this->getSystemInfo('account'),
            'Content-Type: application/json',
            'Authorization: Basic '.$token
            )
        );

        $this->setCurlProxyOptions($ch);
        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $responseStr = substr($response, $header_size);
        curl_close($ch);
        #save response result
        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['merchantOrderId']);

        if($return_all){
            $response_result = [
                $params, $response, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $params['merchantOrderId']
            ];
            return array($response, $response_result);
        }
        return $response;
    }
}