<?php
require_once dirname(__FILE__) . '/abstract_payment_api_nopay.php';

/**
 * NOPAY_WITHDRAWAL
 *
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
 * @copyright 2013-2022 tot
 */
class Payment_api_nopay_usdc_erc_withdrawal extends Abstract_payment_api_nopay {
    public function getPlatformCode() {
        return NOPAY_USDC_ERC_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'nopay_usdc_erc_withdrawal';
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
            $this->utils->error_log("========================nopay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by nopay");
            return array('success' => false, 'message' => 'Bank not supported by nopay');
        }


        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();
        $this->processHeaders($params);

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================nopay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================nopay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================nopay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $params = array();
        $params['merchantOrderNo'] = $transId;
        $params['merchantMemberNo'] = $transId;
        $params['coin'] = self::COIN_USDC;
        $params['language'] = $this->getSystemInfo('language', 'en');
        $params['rateType'] = $this->getSystemInfo('rateType', 1);
        $params['protocol'] = $this->getSystemInfo("protocol", "ERC20");
        $params['rate'] = $this->getSystemInfo('rate', "1");
        $params['amount'] = $amount;
        $params['currencyAmount'] = $amount;
        $params['currency'] = $this->getSystemInfo('currency')?$this->getSystemInfo('currency'):$this->getRequestCallbackCurrency();
        $params['notifyUrl'] = $this->getNotifyUrl($transId);
        $params['toAddress'] = $accNum;
        $params['timestamp'] = time();
        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log('=========================nopay getWithdrawParams params', $params);
        return $params;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================nopay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================nopay json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================nopay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================nopay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($this->getSystemInfo("allow_auto_decline") == true && $params['state'] != self::WITHDRAWAL_CALLBACK_STATE) {
            $msg = sprintf('nopay withdrawal failed: [%s]', $params['msg']);
            $this->writePaymentErrorLog($msg, $params);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['return_error_msg'] = $msg;
            $this->CI->utils->debug_log("=========================nopay withdrawal callbackFromServer status is failed. set to decline");
            return $result;
        }

        if ($params['state'] == self::WITHDRAWAL_CALLBACK_STATE) {
            $msg = sprintf('nopay withdrawal success: trade ID [%s]', $params['merchantOrderNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RESPONSE_SUCCESS_MSG;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('nopay withdrawal payment was not successful: [%s]');
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'appId', 'merchantOrderNo', 'amount', 'serviceFee',"state","sign",
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================nopay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================nopay withdrawal checkCallbackOrder signature Error', $fields);
            return false;
        }

        if ($fields['state'] != self::WITHDRAWAL_RESPONSE_STATE) {
            $this->writePaymentErrorLog("======================nopay withdrawal checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        $transId = $order['transactionCode'];
        if ($fields['merchantOrderNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("=========================nopay withdrawal checkCallbackOrder order IDs do not match, expected => [$transId]", $fields);
            return false;
        }

        $orderAmount = $order['amount'];
        if ($fields['amount'] != $this->convertAmountToCurrency($orderAmount)) {
            $this->writePaymentErrorLog("=====================nopay withdrawal checkCallbackOrder amounts do not match, expected [$orderAmount]", $fields);
            return false;
        }

        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }
}