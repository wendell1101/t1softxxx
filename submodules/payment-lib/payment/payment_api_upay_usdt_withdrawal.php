<?php
require_once dirname(__FILE__) . '/abstract_payment_api_upay.php';

/**
 * UPAY_WITHDRAWAL
 *
 * * UPAY_USDT_WITHDRAWAL_PAYMENT_API, ID: 6183
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://16.162.87.159:1235/wallet/withdraw
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_upay_usdt_withdrawal extends Abstract_payment_api_upay {
    // const CHANNLETYPE = 1;
    const RESPONSE_ORDER_SUCCESS = 0;
    const CALLBACK_STATUS_SUCCESS = '1';
    const SYMBOL = 'USDT';

    public function getPlatformCode() {
        return UPAY_USDT_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'upay_usdt_withdrawal';
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

        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================upay withdrawal bank whose bankTypeId=[$bank] is not supported by upay");
            return array('success' => false, 'message' => 'Bank not supported by upay');
        }

        $this->_custom_curl_header = array('Content-Type: application/json');
        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================upay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================upay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================upay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank];
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================upay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        $wallet_account_id = $this->CI->wallet_model->getWalletaccountIdByTransactionCode($transId);
        $cryptolOrder = $this->CI->wallet_model->getCryptoWithdrawalOrderById($wallet_account_id);

        if(empty($cryptolOrder) && !is_array($cryptolOrder)){
            $this->utils->debug_log("=========================upay crypto order not exists", $transId);
            return array('success' => false, 'message' => 'crypto order not exists');
        }

        $playerId = $playerBankDetails['playerId'];
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $today = $this->CI->utils->getTodayForMysql();
        $userID = $this->getSystemInfo('upay_userid', strtotime($today));

        $params = array();
        $params['protocol']    = $this->getSystemInfo("protocol", self::PROTOCOL_TYPE_TRC);
        // $params['from']        = $this->getSystemInfo('from_address','TYjBaCYBgngDA3nMpBD76Qk7qBx8twvDqY');
        // $params['privateKey']  = $this->getSystemInfo('privateKey');
        $params['to']          = $accNum;
        $params['symbol']      = $this->getSystemInfo("symbol", self::SYMBOL);
        $params['userID']      = (int)$userID;
        $params['amount']      = $cryptolOrder['transfered_crypto'];
        $params['orderID']     = $transId;
        $params['callbackURL'] = $this->getNotifyUrl($transId);

        $submit['sign'] = $this->sign($params);
        $submit['data'] = $params;

        $this->CI->utils->debug_log('=========================upay getWithdrawParams params', $submit);
        return $submit;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================upay json_decode result", $result);

        if(isset($result['code'])) {
            if($result['code'] == self::RESPONSE_ORDER_SUCCESS) {
                $message = "upay withdrawal response successful, code:[".$result['code']."]: ".$result['msg'];
                return array('success' => true, 'message' => $message);
            }
            $message = "upay withdrawal response failed. [".$result['code']."]: ".$result['msg'];
            return array('success' => false, 'message' => $message);

        }
        elseif(isset($result['msg'])){
            $message = 'upay withdrawal response: '.$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "upay decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================upay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================upay json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================upay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================upay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if (empty($params['extend']['reason'])) {
            $msg = sprintf('upay withdrawal success: trade ID [%s]', $params['extend']['OrderNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS;
            $result['success'] = true;
        }
        // else if ($params['Status'] != self::ORDER_STATUS_PROCESS && $params['Status'] != self::ORDER_STATUS_CREATED) {
        //     $msg = sprintf('upay withdrawal failed: [%s]', $params['Message']);
        //     $this->writePaymentErrorLog($msg, $fields);
        //     $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
        //     $result['message'] = $msg;
        // }
        else {
            $msg = sprintf('upay withdrawal payment was not successful: [%s]', $params['extend']['reason']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array('OrderNo','amount','sign');

        $checkFields = $fields['data'];
        $checkFields['OrderNo'] = $fields['extend']['OrderNo'];
        $checkFields['sign'] = $fields['sign'];
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $checkFields)) {
                $this->writePaymentErrorLog("======================upay withdrawal checkCallbackOrder missing parameter: [$f]", $checkFields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================upay withdrawal checkCallbackOrder signature Error', $fields);
            return false;
        }

        $cryptolOrder = $this->CI->wallet_model->getCryptoWithdrawalOrderById($order['walletAccountId']);

        if($cryptolOrder['transfered_crypto'] == 0 || $fields['data']['amount'] == 0){
            $this->writePaymentErrorLog("=====================upay withdrawal checkCallbackOrder Payment crypto amounts is null");
            return false;
        }

        if ($fields['data']['amount'] != $cryptolOrder['transfered_crypto']){
            $this->writePaymentErrorLog('======================upay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $cryptolOrder['transfered_crypto'], $fields);
            return false;
        }

        // if ($fields['data']['amount'] != $this->convertAmountToCurrency($order['amount'])) {
        //     if ($this->getSystemInfo('allow_callback_amount_diff')) {
        //         $diffAmount = abs($this->convertAmountToCurrency($order['amount']) - floatval($fields['data']['amount']));
        //         if ($diffAmount >= 1) {
        //             $this->writePaymentErrorLog("=====================upay withdrawal checkCallbackOrder Payment amounts ordAmt - payAmount > 1, expected [$order->amount]", $fields, $diffAmount);
        //             return false;
        //         }
        //     }else {
        //         $this->writePaymentErrorLog("=====================upay withdrawal checkCallbackOrder amounts do not match, expected [$order->amount]", $fields);
        //         return false;
        //     }
        // }

        if ($fields['extend']['OrderNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================upay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        if (!empty($fields['data']['reason'])) {
            $this->writePaymentErrorLog("========================upay withdrawal checkCallbackOrder reason is not empty, expected", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }

    # -- bankinfo --
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                $bankInfo[$system_bank_type_id] = $bankInfoItem;
            }
            $this->utils->debug_log("==================getting withdrawal upay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '44'  => 'USDT',
            );
            $this->utils->debug_log("==================getting withdrawal upay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    protected function convertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 0, '.', '') ;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}