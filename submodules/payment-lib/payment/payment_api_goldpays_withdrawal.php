<?php
require_once dirname(__FILE__) . '/abstract_payment_api_goldpays.php';
/**
 * GOLDPAYS
 *
 * * GOLDPAYS_WITHDRAWAL_PAYMENT_API, ID: 5988
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.goldpays.in/payout/pay/createOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_goldpays_withdrawal extends Abstract_payment_api_goldpays {
    const RESULT_CODE_SUCCESS = 200;

    public function getPlatformCode() {
        return GOLDPAYS_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'goldpays_withdrawal';
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
            $email     = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : 'sample@example.com';
        }

        $this->CI->utils->debug_log(__METHOD__, 'GOLDPAYS_withdrawal basic creds', [ 'accNum' => $accNum, 'name' => $name, 'bank' => $bank, 'bank_name' => $bank_name, 'bank_ifsc' => $bank_ifsc]);

        $params = [
            "merchant"          => $this->getSystemInfo('account'),
            "orderId"           => $transId,
            "amount"            => $this->convertAmountToCurrency($amount),
            "customName"        => $name,
            "customMobile"      => $phone,
            "customEmail"       => $email,
            "mode"              => 'IMPS',
            "bankAccount"       => $order['bankAccountNumber'],
            "ifscCode"          => $bank_ifsc,
            "notifyUrl"         => $this->getNotifyUrl($transId),

        ];
        $params['sign']         = $this->sign($params);
        $this->CI->utils->debug_log(__METHOD__, 'goldpays_withdrawal getWithdrawParams params', $params);
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

        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['orderId']);
        $this->CI->utils->debug_log(__METHOD__, 'params submit response', $response);

        $result = $this->decodeResult($response);

        return $result;

    }

    public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================goldpays json_decode result", $result);
        $resultMsg = '';

        if(isset($result['data']['msg']) && !empty($result['data']['msg'])){
            $resultMsg = $result['data']['msg'];
        }else if(isset($result['errorMessages']) && !empty($result['errorMessages'])){
            $resultMsg = $result['errorMessages'];
        }

        $this->utils->debug_log("=========================goldpays withdrawal resultMsg", $resultMsg);

        if(isset($result['code']) && $result['code'] == self::RESULT_CODE_SUCCESS) {
            $message = "goldpays request successful.";
            return array('success' => true, 'message' => $message);
        }
        else {
            if($resultMsg == '' || $resultMsg == false) {
                    $this->utils->error_log("========================goldpays return UNKNOWN ERROR!");
                    $resultMsg = "UNKNOWN ERROR";
            }

            $message = "goldpays withdrawal response, Msg: ".$resultMsg;
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
            $this->CI->utils->debug_log("=====================goldpays raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================goldpays json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================goldpays callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================goldpays callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('goldpays withdrawal success: trade ID [%s]', $params['orderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('goldpays withdrawal payment was not successful: [%s]', $params['msg']);
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'orderId', 'amount', 'status', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================goldpays withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================goldpays withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================goldpays withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['orderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================goldpays withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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

    # -- signatures --
    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    public function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($value == null || $key == 'sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        return $signStr;
    }

    public function validateSign($params) {
        $sign = $this->sign($params);
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

}