<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ftpay.php';

/**
 * FTPAY_USDT_WITHDRAWAL
 *
 * * FTPAY_USDT_WITHDRAWAL_PAYMENT_API, ID: 6264
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://yyds68.cc/Apipay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_ftpay_usdt_withdrawal extends Abstract_payment_api_ftpay {

    const PAY_TYPE = 'USDT';

    public function getPlatformCode() {
        return FTPAY_USDT_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'ftpay_usdt_withdrawal';
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

        $this->CI->utils->debug_log('======================================ftpay_usdt submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================ftpay_usdt submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================ftpay_usdt submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================ftpay_usdt Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        # look up bank code
        $wallet_account_id = $this->CI->wallet_model->getWalletaccountIdByTransactionCode($transId);
        $cryptolOrder = $this->CI->wallet_model->getCryptoWithdrawalOrderById($wallet_account_id);

        if(empty($cryptolOrder) && !is_array($cryptolOrder)){
            $this->utils->debug_log("=========================cpaycrypto_usdc crypto order not exists", $transId);
            return array('success' => false, 'message' => 'crypto order not exists');
        }

        $params = array();
        $params['userid']      = $this->getSystemInfo('account');
        $params['action']      = "withdraw";
        $params['notifyurl']   = $this->getNotifyUrl($transId);
        $params['notifystyle'] = '2';
        $params['content']     = '[{"orderno":"'.$transId.'","date":"'.date("YmdHis").'","amount":"'.$cryptolOrder['transfered_crypto'].'","account":"'.$accNum.'","name":"'.self::PAY_TYPE.'","bank":"'.self::BANK_NAME_TRC20.'","subbranch":"'.self::PAY_TYPE.'"}]';
        $params['sign']        = $this->sign($params);
        
        $this->CI->utils->debug_log('=========================ftpay_usdt getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================ftpay_usdt json_decode result", $result);

        if (isset($result['status'])) {
            if($result['status'] == self::REPONSE_CODE_SUCCESS) {
                $message = "ftpay_usdt withdrawal response successful, orderno:".$result['orderno'];
                return array('success' => true, 'message' => $message);
            }
            $message = "ftpay_usdt withdrawal response failed. ErrorMessage: ".$result['msg'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['msg']){
            $message = 'ftpay_usdt withdrawal response: '.$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "ftpay_usdt decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        
        $raw_post_data = file_get_contents('php://input', 'r');
        $this->CI->utils->debug_log("=====================ftpay_usdt raw_post_data", $raw_post_data);
        $params = json_decode($raw_post_data, true);
        $this->CI->utils->debug_log("=====================ftpay_usdt json_decode params", $params);
        
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================ftpay_usdt callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================ftpay_usdt callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('ftpay_usdt withdrawal success: trade ID [%s]', $params['orderno']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }else {
            $msg = sprintf("ftpay_usdt withdrawal payment unsuccessful: status=%s", $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'userid', 'orderno', 'amount', 'status', 'sign'
        );

        $cryptolOrder = $this->CI->wallet_model->getCryptoWithdrawalOrderById($order['walletAccountId']);

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================ftpay_usdt withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================ftpay_usdt withdrawal checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }

        if ($fields['amount'] != $cryptolOrder['transfered_crypto']) {
            $this->writePaymentErrorLog('=========================ftpay_usdt withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $cryptolOrder['transfered_crypto'], $fields);
            return false;
        }

        if ($fields['orderno'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================ftpay_usdt withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }

    # -- signatures --
    # Reference: PHP Demo
    public function sign($params, $isValid = false) {

        if ($isValid) {
            $signStr = $this->createValidateStr($params);
        } else {
            $signStr = $this->createSignStr($params);
        }

        $sign = strtolower($signStr);
        return $sign;
    }

    public function createSignStr($params) {
        $signStr = $params['userid'].$params['action'].$params['content'].$this->getSystemInfo('key');
        return md5($signStr);
    }

    public function createValidateStr($params) {
        $signStr = $params['userid'].$params['orderno'].$params['outorder'].$params['status'].$params['amount'].$params['fee'].$params['account'].$params['name'].$params['bank'].$this->getSystemInfo('key');
        return md5($signStr);
    }

    public function validateSign($params) {
        $signature = $params['sign'];
        $sign = $this->sign($params, true);
        if ( $signature == $sign ) {
            return true;
        } else {
            return false;
        }    
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}