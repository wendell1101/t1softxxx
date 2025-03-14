<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gccpay.php';

/**
 * GCCPAY_WITHDRAWAL_PAYMENT_API
 *
 * * GCCPAY_WITHDRAWAL_PAYMENT_API, ID: 6304
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://gccbrazil.com/api/withdraw
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gccpay_withdrawal extends Abstract_payment_api_gccpay {
    const CALLBACK_SUCCESS = 2;

    public function getPlatformCode() {
        return GCCPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'gccpay_withdrawal';
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

        $this->CI->utils->debug_log('======================================gccpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================gccpay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================gccpay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================gccpay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : 'no firstName';
            $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))  ? $playerDetails[0]['lastName'] : 'no lastName';
        }

        $params = array();
        $params['merchantCode'] = $this->getSystemInfo('account');
        $params['orderId']      = $transId;
        $params['bankCardNum']  = $accNum;
        $params['bankCardName'] = $lastname.' '.$firstname;
        $params['currency']     = $this->getSystemInfo('currency', self::CURRENCY_BRL);
        $params['amount']       = $this->convertAmountToCurrency($amount);
        $params['notifyUrl']    = $this->getNotifyUrl($transId);
        $params['orderDate']    = round(microtime(true) * 1000);
        $params['extra']        = "{'accountType':'$bankCode'}";
        $params['remark']       = 'withdrawal';
        $params['sign']         = $this->sign($params);
        
        $this->CI->utils->debug_log('=========================gccpay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================gccpay json_decode result", $result);

        if (isset($result['success'])) {
            if($result['success'] == self::REPONSE_CODE_SUCCESS) {
                $message = "gccpay withdrawal response successful, trades ID:".$result['orderId'];
                return array('success' => true, 'message' => $message);
            }
            $message = "gccpay withdrawal response failed. resultMsg: ".$result['resultMsg'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['resultMsg']){
            $message = 'gccpay withdrawal response: '.$result['resultMsg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "gccpay decoded fail.");
    }
    
    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        
        $raw_post_data = file_get_contents('php://input', 'r');
        $this->CI->utils->debug_log("=====================gccpay raw_post_data", $raw_post_data);
        parse_str($raw_post_data ,$params);
        $this->CI->utils->debug_log("========================gccpay parse_str params", $params);
        
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================gccpay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================gccpay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('gccpay withdrawal success: trade ID [%s]', $params['orderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }else {
            $msg = sprintf("gccpay withdrawal payment unsuccessful or pending: status=%s", $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'merchantCode', 'orderId', 'currency', 'amount', 'fee', 'status', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================gccpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================gccpay withdrawal checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================gccpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['orderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================gccpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
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
            $this->utils->debug_log("==================getting gccpay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '45' => array('name' => 'CPF', 'code' => 'CPF'),
                '46' => array('name' => 'EMAIL', 'code' => 'EMAIL'),
                '47' => array('name' => 'PHONE', 'code' => 'PHONE'),
            );
            $this->utils->debug_log("=======================getting gccpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
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