<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ftpay.php';

/**
 * FTPAY_WITHDRAWAL
 *
 * * FTPAY_WITHDRAWAL_PAYMENT_API, ID: 6262
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
class Payment_api_ftpay_withdrawal extends Abstract_payment_api_ftpay {

    public function getPlatformCode() {
        return FTPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'ftpay_withdrawal';
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

        $this->CI->utils->debug_log('======================================ftpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================ftpay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================ftpay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================ftpay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);


        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $bankBranch  = empty($playerBankDetails['branch']) ? '无' : $playerBankDetails['branch'];            

            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : '无名';
            $pixNumber  = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $bank_name = $this->findBankName($bank);

        $params = array();
        $params['userid']      = $this->getSystemInfo('account');
        $params['action']      = "withdraw";
        $params['notifyurl']   = $this->getNotifyUrl($transId);
        $params['notifystyle'] = '2';
        $params['content']     = '[{"orderno":"'.$transId.'","date":"'.date("YmdHis").'","amount":"'.$this->convertAmountToCurrency($amount).'","account":"'.$accNum.'","name":"'.$firstname.'","bank":"'.$bank_name.'","subbranch":"'.$bankBranch.'"}]';
        $params['sign']        = $this->sign($params);
        
        $this->CI->utils->debug_log('=========================ftpay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================ftpay json_decode result", $result);

        if (isset($result['status'])) {
            if($result['status'] == self::REPONSE_CODE_SUCCESS) {
                $message = "ftpay withdrawal response successful, orderno:".$result['orderno'];
                return array('success' => true, 'message' => $message);
            }
            $message = "ftpay withdrawal response failed. ErrorMessage: ".$result['msg'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['msg']){
            $message = 'ftpay withdrawal response: '.$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "ftpay decoded fail.");
    }

    protected function findBankName($bank_id) {
        $bank_row = $this->CI->banktype->getBankTypeById($bank_id);
        $bank_name = lang($bank_row->bankName);

        return $bank_name;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        
        $raw_post_data = file_get_contents('php://input', 'r');
        $this->CI->utils->debug_log("=====================ftpay raw_post_data", $raw_post_data);
        $params = json_decode($raw_post_data, true);
        $this->CI->utils->debug_log("=====================ftpay json_decode params", $params);
        

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================ftpay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================ftpay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('ftpay withdrawal success: trade ID [%s]', $params['orderno']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }else {
            $msg = sprintf("ftpay withdrawal payment unsuccessful: status=%s", $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'userid', 'orderno', 'amount', 'status', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================ftpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================ftpay withdrawal checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================ftpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['orderno'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================ftpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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