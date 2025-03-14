<?php
require_once dirname(__FILE__) . '/abstract_payment_api_neweasypay.php';
/**
 * neweasypay
 *
 * * NEWEASYPAY_WITHDRAWAL_PAYMENT_API, ID: 5996
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://payment.easypay999.com/pay/v1/df/createOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_neweasypay_withdrawal extends Abstract_payment_api_neweasypay {
    const RESULT_CODE_SUCCESS = 0;
    const STATUS_SUCCESS = 1;

    public function getPlatformCode() {
        return NEWEASYPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'neweasypay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {

        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $this->CI->load->library([ 'ifsc_razorpay_lib' ]);
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $bank_name = $this->findBankName($bank);
        $bank_ifsc = $order['bankBranch'];
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);

        $this->utils->debug_log("==================luxpag withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $phone     = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '8615551234567';

            $bankBranch    = $playerBankDetails['branch'];
            $province      = $playerBankDetails['province'];
            $city          = $playerBankDetails['city'];
        }
        $province      = empty($province) ? "none" : $province;
        $city          = empty($city) ? "none" : $city;
        $bankBranch    = empty($bankBranch) ? "none" : $bankBranch;


        $this->CI->utils->debug_log(__METHOD__, 'neweasypay_withdrawal basic creds', [ 'accNum' => $accNum, 'name' => $name, 'bank' => $bank, 'bank_name' => $bank_name, 'bank_ifsc' => $bank_ifsc]);

        $params = [
            "merchantId"       => $this->getSystemInfo('account'),
            "outTradeNo"       => $transId,
            "coin"             => $this->convertAmountToCurrency($amount),
            // "notifyUrl"        => $this->getNotifyUrl($transId),
            "transferCategory" => 'IMPS',
            "bankCardNum"      => $order['bankAccountNumber'],
            "ifscCode"         => $bank_ifsc,
            "bankAccountName"  => $name,
            "bankName"         => $bank_name,
            "bankBranchName"   => $bankBranch,
            "city"             => $city,
            "province"         => $province,
        ];
        $params['sign']         = $this->sign($params);
        $this->CI->utils->debug_log(__METHOD__, 'neweasypay_withdrawal getWithdrawParams params', $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log(__METHOD__, $result);
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        if (empty($params['ifscCode'])) {
            return [
                'success' => false ,
                'message' => 'IFSC not set, please set IFSC code of your withdrawal account'
            ];
        }

        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['outTradeNo']);
        $this->CI->utils->debug_log(__METHOD__, 'params submit response', $response);

        $result = $this->decodeResult($response);

        return $result;

    }

    public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================neweasypay json_decode result", $result);
        $resultMsg = '';

        if(isset($result['message']) && !empty($result['message'])){
            $resultMsg = $result['message'];
        }

        $this->utils->debug_log("=========================neweasypay withdrawal resultMsg", $resultMsg);

        if(isset($result['code']) && $result['code'] == self::RESULT_CODE_SUCCESS) {
            $message = "neweasypay request successful.";
            return array('success' => true, 'message' => $message);
        }
        else {
            if($resultMsg == '' || $resultMsg == false) {
                    $this->utils->error_log("========================neweasypay return UNKNOWN ERROR!");
                    $resultMsg = "UNKNOWN ERROR";
            }

            $message = "neweasypay withdrawal response, Msg: ".$resultMsg;
            return array('success' => false, 'message' => $message);
        }
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
            $this->CI->utils->debug_log("=====================neweasypay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================neweasypay json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================neweasypay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================neweasypay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['code'] == self::STATUS_SUCCESS) {
            $msg = sprintf('neweasypay withdrawal success: trade ID [%s]', $params['outTradeNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('neweasypay withdrawal payment was not successful: [%s]', $params['message']);
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'code', 'outTradeNo', 'merchantId', 'coin'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================neweasypay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================neweasypay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['coin'] != $order['amount']) {
            $this->writePaymentErrorLog('=========================neweasypay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['outTradeNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================neweasypay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }

    # -- Private functions --
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

}