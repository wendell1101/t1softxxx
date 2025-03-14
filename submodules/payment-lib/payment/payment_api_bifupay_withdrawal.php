<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bifupay.php';

/**
 * BIFUPAY_WITHDRAWAL
 *
 * * BIFUPAY_WITHDRAWAL_PAYMENT_API, ID: 5897
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.xingshengtrade.com/PaymentGetway/SinglePay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bifupay_withdrawal extends Abstract_payment_api_bifupay {

    const RESPONSE_ORDER_SUCCESS = 'processing';
    const CALLBACK_STATUS_SUCCESS = '1';
    const CURRENCY = 'INR';
    const CHARGETYPE = '1';
    const CALLBACK_SUCCESS = '3';

    public function getPlatformCode() {
        return BIFUPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'bifupay_withdrawal';
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
            $this->utils->error_log("========================bifupay withdrawal bank whose bankTypeId=[$bank] is not supported by bifupay");
            return array('success' => false, 'message' => 'Bank not supported by bifupay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================bifupay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================bifupay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================bifupay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];
        $bankName = $bankInfo[$bank]['name'];
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================bifupay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $province = $playerBankDetails['province'];
            $city = $playerBankDetails['city'];
            $bankBranch = $playerBankDetails['branch'];
        } else {
            $province = 'none';
            $city = 'none';
            $bankBranch = 'none';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $params = array();
        $params['merchantId']        = $this->getSystemInfo('account');
        $params['merchantOrderId']   = $transId;
        $params['orderAmount']       = $this->convertAmountToCurrency($amount);
        $params['payType']           = '1';
        $params['accountHolderName'] = $order['bankAccountFullName'];
        $params['accountNumber']     = $accNum;
        $params['bankType']          = $bankCode;
        $params['notifyUrl']         = $this->getNotifyUrl($transId);
        $params['reverseUrl']        = $this->getNotifyUrl($transId);
        $params['submitIp']          = $this->getClientIp();
        $params['subBranch']         = $bankBranch;
        $params['sign']              = $this->sign($params);

        $this->CI->utils->debug_log('=========================bifupay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================bifupay json_decode result", $result);

        if(!empty($result)) {
            if($result['ErrorCode'] == null && $result['ErrorMessage'] == null) {
                $message = "bifupay withdrawal response successful, code:[".$result['ErrorCode']."]: ".$result['ErrorMessage'];
                return array('success' => true, 'message' => $message);
            }
            $message = "bifupay withdrawal response failed. [".$result['ErrorCode']."]: ".$result['ErrorMessage'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['ErrorMessage']){
            $message = 'bifupay withdrawal response: '.$result['ErrorMessage'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "bifupay decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================bifupay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================bifupay json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================bifupay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================bifupay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('bifupay withdrawal success: trade ID [%s]', $params['merchantOrderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        // else if ($params['Status'] != self::ORDER_STATUS_PROCESS && $params['Status'] != self::ORDER_STATUS_CREATED) {
        //     $msg = sprintf('bifupay withdrawal failed: [%s]', $params['Message']);
        //     $this->writePaymentErrorLog($msg, $fields);
        //     $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
        //     $result['message'] = $msg;
        // }
        else {
            $msg = sprintf('bifupay withdrawal payment was not successful: [%s]', $params['remark']);
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'merchantId', 'systemOrderId', 'merchantOrderId', 'status', 'orderType', 'orderAmount', 'remark', 'submitIp', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================bifupay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('======================bifupay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================bifupay withdrawal checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['orderAmount'] != $order['amount']) {
            $this->writePaymentErrorLog('======================bifupay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['merchantOrderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('======================bifupay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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
                if(isset($bankInfoItem['name'])){
                    $bankInfo[$system_bank_type_id]['name'] = $bankInfoItem['name'];
                }
                if(isset($bankInfoItem['code'])){
                    $bankInfo[$system_bank_type_id]['code'] = $bankInfoItem['code'];
                }
            }
            $this->utils->debug_log("==================getting bifupay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '2' => array('name' => '招商银行', 'code' => '17'),
                '27' => array('name' => 'DBI BANK', 'code' => 'IDBIBK'),
                '3' => array('name' => 'HDFC BANK', 'code' => 'HDFCBK'),
                '4' => array('name' => 'ICICI BANK', 'code' => 'ICICI'),
                '5' => array('name' => 'AXIS BANK', 'code' => 'AXIS'),
            );
            $this->utils->debug_log("=======================getting bifupay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    public function createSignStr($params) {
        $signStr = '';
        $signStr = 'merchantId='.$params['merchantId'].'&merchantOrderId='.$params['merchantOrderId'].'&orderAmount='.$params['orderAmount'].'&payType='.$params['payType'].'&accountHolderName='.$params['accountHolderName'].'&accountNumber='.$params['accountNumber'].'&bankType='.$params['bankType'].'&notifyUrl='.$params['notifyUrl'].'&reverseUrl='.$params['reverseUrl'].'&submitIp='.$params['submitIp'].$this->getSystemInfo('key');
        return $signStr;
    }

    public function callbackCreateSignStr($params) {
        $signStr = '';
        $signStr = 'merchantId='.$params['merchantId'].'&merchantOrderId='.$params['merchantOrderId'].'&status='.$params['status'].'&orderType='.$params['orderType'].'&orderAmount='.$params['orderAmount'].'&systemOrderId='.$params['systemOrderId'].'&remark='.$params['remark'].'&submitIp='.$params['submitIp'].$this->getSystemInfo('key');
        return $signStr;
    }

    public function validateSign($params) {
        $signStr = $this->callbackCreateSignStr($params);
        $sign = md5($signStr);
        if($params['sign'] == $sign)
            return true;
        else
            return false;
    }
}