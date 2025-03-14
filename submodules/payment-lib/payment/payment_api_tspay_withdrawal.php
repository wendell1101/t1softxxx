<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tspay.php';

/**
 * tspay_WITHDRAWAL
 *
 * * tspay_WITHDRAWAL_PAYMENT_API, ID: 6200
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.zm-pay.com/api/agentpay/apply
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tspay_withdrawal extends Abstract_payment_api_tspay {
    const RESPONSE_ORDER_SUCCESS = 'SUCCESS';
    const CHANNEL_TYPE_PIX_WITHDRAWAL = '4';
    const CALLBACK_STATUS_FAILD   = 3;
    const CALLBACK_STATUS_SUCCESS = 2;
    const RETURN_SUCCESS = 'success';

    public function getPlatformCode() {
        return TSPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tspay_withdrawal';
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

        $this->CI->utils->debug_log('======================================tspay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================tspay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================tspay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================tspay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName'] : 'no firstName';
            $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName'] : 'no lastName';
            $pixNumber = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
            $email     = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : 'sample@example.com';
            $phone      = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : 'none';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $params = array();
        $params['appid']        = $this->getSystemInfo("account");
        $params['out_trade_no'] = $transId;
        $params['type']         = self::CHANNEL_TYPE_PIX_WITHDRAWAL;
        $params['name']         = $lastname.' '.$firstname;
        $params['email']        = $email;
        $params['mobile']       = $phone;
        $params['amount']       = $this->convertAmountToCurrency($amount);
        $params['currency']     = $this->getSystemInfo("currency", 'BRL');
        $params['version']      = 'v1.0';
        $params['notify_url']   = $this->getNotifyUrl($transId);
        $params['document_id']  = $pixNumber;
        $params['pix_type']     = 'CPF';
        $params['pix_key']      = $pixNumber;
        $params['sign']         = $this->sign($params);

        $this->CI->utils->debug_log('=========================tspay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================tspay json_decode result", $result);

        if(isset($result['code'])) {
            if($result['code'] == self::REPONSE_CODE_SUCCESS) {
                $message = "tspay withdrawal response successful, code:[".$result['code']."]: ".$result['msg'];
                return array('success' => true, 'message' => $message);
            }
            $message = "tspay withdrawal response failed. [code]: ".$result['code'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['msg']){
            $message = 'tspay withdrawal response: '.$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "tspay decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================tspay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================tspay json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================tspay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================tspay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['code'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('tspay withdrawal success: trade ID [%s]', $params['data']['out_trade_no']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS;
            $result['success'] = true;
        }else {
            $msg = sprintf('tspay withdrawal payment was not successful: [%s]', $params['msg']);
            $this->writePaymentErrorLog($msg, $params);
            $result['return_error_msg'] = self::RETURN_SUCCESS;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'out_trade_no', 'appid', 'amount','sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields['data'])) {
                $this->writePaymentErrorLog("======================tspay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if ($fields['data']['sign'] != $this->validateSign($fields['data'])) {
            $this->writePaymentErrorLog('=====================tspay withdrawal checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }

        // if ($fields['code'] != self::CALLBACK_SUCCESS) {
        //     $this->writePaymentErrorLog("======================tspay withdrawal checkCallbackOrder Payment status is not success", $fields);
        //     return false;
        // }

        if ($fields['data']['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================today withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['data']['out_trade_no'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================tspay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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