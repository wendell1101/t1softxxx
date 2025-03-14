<?php
require_once dirname(__FILE__) . '/abstract_payment_api_newepay.php';

/**
 * NEWEPAY_WITHDRAWAL
 *
 * * NEWEPAY_WITHDRAWAL_PAYMENT_API, ID: 6239
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://asdqw3ds8e3wj80opd-order.xnslxxl.com/proxypay/order
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_newepay_withdrawal extends Abstract_payment_api_newepay {
    const BANK_CODE_PIX_WITHDRAWAL = 'PIX';
    const REPONSE_CODE_SUCCESS = '200';
    const CALLBACK_SUCCESS     = 2;

    public function getPlatformCode() {
        return NEWEPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'newepay_withdrawal';
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

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================newepay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================newepay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================newepay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================newepay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName'] : 'no firstName';
            $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName'] : 'no lastName';
            $pixNumber = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $params = array();
        $params['order_no']      = $transId;
        $params['mch_account']   = $this->getSystemInfo("account");
        $params['amount']        = (int)$this->convertAmountToCurrency($amount);
        $params['account_type']  = 0;
        $params['account_no']    = $pixNumber;
        $params['account_name']  = $lastname.' '.$firstname;
        $params['bank_code']     = self::BANK_CODE_PIX_WITHDRAWAL;
        $params['bank_province'] = $pixNumber;
        $params['bank_city']     = self::IDENTIFY_TYPE;
        $params['bank_name']     = self::IDENTIFY_TYPE;
        $params['call_back_url'] = $this->getNotifyUrl($transId);
        $params['pay_type']      = '1';
        $params['sign']          = $this->sign($params);

        $this->CI->utils->debug_log('=========================newepay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================newepay json_decode result", $result);

        if(isset($result['ret'])) {
            if($result['ret'] == self::REPONSE_CODE_SUCCESS) {
                $message = "newepay withdrawal response successful, code:[".$result['ret']."],status:".$result['status'];
                return array('success' => true, 'message' => $message);
            }
            $message = "newepay withdrawal response failed. [code]: ".$result['ret'].$result['msg'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['msg']){
            $message = 'newepay withdrawal response: '.$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "newepay decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================newepay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================newepay json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================newepay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================newepay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('newepay withdrawal success: trade ID [%s]', $params['order_no']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }else {
            $msg = sprintf('newepay withdrawal payment was not successful or pending status: [%s]', $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['return_error_msg'] = self::RETURN_SUCCESS_CODE;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'order_no', 'mch_order_no', 'amount', 'status', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================newepay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================newepay withdrawal checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================newepay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['order_no'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================newepay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }
        // if ($fields['status'] != self::CALLBACK_SUCCESS) {
        //     $this->writePaymentErrorLog("======================newepay withdrawal checkCallbackOrder Payment status is not success", $fields);
        //     return false;
        // }

        # everything checked ok
        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }

       # -- signatures --
    # Reference: PHP Demo
    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    public function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign' || is_null($value) || $value === '') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return $signStr.'key='.$this->getSystemInfo('key');
    }

    public function validateSign($params) {
        $signature = $params['sign'];
        unset($params['sign']);
        $sign = $this->sign($params);
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