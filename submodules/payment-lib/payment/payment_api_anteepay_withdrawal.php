<?php
require_once dirname(__FILE__) . '/abstract_payment_api_anteepay.php';

/**
 * ANTEEPAY_WITHDRAWAL
 *
 * * ANTEEPAY_WITHDRAWAL_PAYMENT_API, ID: 6277
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_anteepay_withdrawal extends Abstract_payment_api_anteepay {

    const REPONSE_CODE_SUCCESS = 1;
    const COUNTRY_CODE = "55";
    const CALLBACK_SUCCESS = 2;

    public function getPlatformCode() {
        return ANTEEPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'anteepay_withdrawal';
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

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================anteepay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================anteepay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================anteepay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================anteepay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        $this->utils->debug_log("===============================anteepay params [$bank] + [$accNum] + [$name] + [$amount] + [$transId]");

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $mobile = $this->CI->player_model->getPlayerContactNumber($order['playerId']);

        $params = array();
        $params['appid']         = $this->getSystemInfo("appId");
        $params['order_no']      = $transId;
        $params['amount']        = $this->convertAmountToCurrency($amount);
        $params['mobile']        = $this->getSystemInfo("country_code",self::COUNTRY_CODE).$mobile;
        $params['notify_url']    = $this->getNotifyUrl($transId);
        $params['bank_code']     = $this->getSystemInfo('bank_code');
        $params['account_no']    = $accNum;
        $params['account_name']  = !empty($name) ? $name : "no account";
        $params['business_type'] = $this->getSystemInfo('business_type');
        $params['sign']          = $this->sign($params);

        $this->CI->utils->debug_log('=========================anteepay getWithdrawParams params', $params);

        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================anteepay json_decode result", $result);

        if (isset($result['status'])) {
            if($result['status'] == self::REPONSE_CODE_SUCCESS) {
                $message = "anteepay withdrawal response successful, transaction ID:".$result['transaction_id'];
                return array('success' => true, 'message' => $message);
            }
            $message = "anteepay withdrawal response failed. ErrorMessage: ".$result['msg'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['msg']){
            $message = 'anteepay withdrawal response: '.$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "anteepay decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);

        $raw_post_data = file_get_contents('php://input', 'r');
        $this->CI->utils->debug_log("========================anteepay raw_post_data", $raw_post_data);
        parse_str($raw_post_data ,$params);
        $this->CI->utils->debug_log("========================anteepay json_decode params", $params); 

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================anteepay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================anteepay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('anteepay withdrawal success: trade ID [%s]', $params['order_no']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }else {
            $msg = sprintf("anteepay withdrawal payment unsuccessful status=%s", $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'status', 'amount', 'order_no', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================anteepay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================anteepay withdrawal checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================anteepay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['order_no'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================anteepay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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