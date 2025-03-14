<?php
require_once dirname(__FILE__) . '/abstract_payment_api_worldpay.php';

/**
 * WORLDPAY_WITHDRAWAL
 *
 * * WORLDPAY_WITHDRAWAL_PAYMENT_API, ID: 6234
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://mio.oceanp168.com/api/createDepositOrder
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_worldpay_withdrawal extends Abstract_payment_api_worldpay {

    public function getPlatformCode() {
        return WORLDPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'worldpay_withdrawal';
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

        $this->CI->utils->debug_log('======================================worldpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================worldpay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================worldpay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $this->CI->load->library([ 'ifsc_razorpay_lib' ]);

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================worldpay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName'] : 'no firstName';
            $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName'] : 'no lastName';
            $pixNumber  = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
            $phone      = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : 'none';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $bank_name = $this->findBankName($bank);

        $params = array();
        $params['Amount']             = $this->convertAmountToCurrency($amount);
        $params['CurrencyId']         = $this->getSystemInfo('currency', self::CURRENCY_CNY);
        $params['IsTest']             = "false";
        $params['PayeeAccountName']   = $lastname.' '.$firstname;
        $params['PayeeAccountNumber'] = $accNum;
        $params['PayeeBankName']      = $bank_name;
        $params['PayeePhoneNumber']   = $phone;
        $params['PaymentChannelId']   = $this->getSystemInfo('Channel', self::CHANNEL_BANK);
        $params['ShopInformUrl']      = $this->getNotifyUrl($transId);
        $params['ShopOrderId']        = $transId;
        $params['ShopUserLongId']     = $this->getSystemInfo("account");
        $params['EncryptValue']       = $this->sign($params);
        
        $this->CI->utils->debug_log('=========================worldpay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================worldpay json_decode result", $result);

        if (isset($result['Success'])) {
            if($result['Success'] == self::REPONSE_CODE_SUCCESS) {
                $message = "worldpay withdrawal response successful, TrackingNumber:".$result['TrackingNumber'];
                return array('success' => true, 'message' => $message);
            }
            $message = "worldpay withdrawal response failed. ErrorMessage: ".$result['ErrorMessage'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['ErrorMessage']){
            $message = 'worldpay withdrawal response: '.$result['ErrorMessage'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "worldpay decoded fail.");
    }

    protected function findBankName($bank_id) {
        $bank_row = $this->CI->banktype->getBankTypeById($bank_id);
        $bank_name = lang($bank_row->bankName);

        return $bank_name;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================worldpay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================worldpay json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================worldpay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================worldpay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['DepositOrderStatusId'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('worldpay withdrawal success: trade ID [%s]', $params['ShopOrderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }else {
            $msg = sprintf("worldpay withdrawal payment unsuccessful or pending: status=%s", $params['DepositOrderStatusId']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'Amount', 'CurrencyId', 'DepositOrderStatusId', 'EncryptValue', 'PaymentChannelId', 'ShopCommissionAmount', 'TrackingNumber'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================worldpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================worldpay withdrawal checkCallbackOrder Signature Error', $fields['EncryptValue']);
            return false;
        }

        if ($fields['Amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================worldpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['ShopOrderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================worldpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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