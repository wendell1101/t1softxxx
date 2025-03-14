<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ypay.php';

/**
 * ypay
 *
 *
 * * YPAY_WITHDRAWAL_PAYMENT_API, ID: 6106
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://service.ypaytest.biz/MerchantPayout/
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ypay_withdrawal extends Abstract_payment_api_ypay {
    const CALLBACK_STATUS_SUCCESS = 4;
    const CALLBACK_STATUS_FAIL    = 5;
    const CURRENCY                = 'IDR';

    public function getPlatformCode() {
        return YPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'ypay_withdrawal';
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

        list($response, $response_result) = $this->submitGetForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================ypay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================ypay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================ypay submitWithdrawRequest decoded Result', $decodedResult);

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
        $params = array();
        $params['appid']     = $this->getSystemInfo("account");
        $params['orderId']   = $transId;
        $params['name']      = $name;
        $params['money']     = $this->convertAmountToCurrency($amount);
        $params['bankMark']  = $bankCode;
        $params['recAcc']    = $accNum;
        $params['notifyUrl'] = $this->getNotifyUrl($transId);
        $params['sn']        = $this->sign($params);

        $this->CI->utils->debug_log('=========================ypay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }

        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================ypay json_decode result", $result);

        if(isset($result['code']) && !empty($result['code'])){
            $respCode = $result['code'];
        }else{
            $respCode ='';
        }

        if(isset($result['desc']) && !empty($result['desc'])){
            $resultMsg = $result['desc'];
        }else{
            $resultMsg ='';
        }

        $this->utils->debug_log("=========================ypay withdrawal resultMsg", $resultMsg);
        if($respCode == self::REQUEST_SUCCESS) {
            $message = "ypay request successful.";
            return array('success' => true, 'message' => $message);
        }
        else {
            if($resultMsg == '' || $resultMsg == false) {
                $this->utils->error_log("========================ypay return UNKNOWN ERROR!");
                $resultMsg = "Unknow Error";
            }

            $message = "ypay withdrawal response, Code: [ ".$respCode." ] , Msg: ".$resultMsg;
            return array('success' => false, 'message' => $message);
        }
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================ypay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================ypay json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================ypay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================ypay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('ypay withdrawal success: trade ID [%s]', $params['order']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('ypay withdrawal payment was not successful: [%s]', $params['Message']);
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'order','amount','status', 'sn'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================ypay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=========================ypay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['amount'] != $order['amount']) {
            $this->writePaymentErrorLog('=========================ypay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['order'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================ypay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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
            $this->utils->debug_log("==================getting ypay bank info from extra_info: ", $bankInfo);
        } else  {
            $currency  = $this->getSystemInfo('currency',self::CURRENCY);
            switch ($currency) {
                case 'THB':
                    $bankInfo = array(
                        "28" =>  array('name' => "Bangkok Bank", 'code' => 'BBL'),
                        "29" =>  array('name' => "Krung Thai Bank", 'code' => 'KTB'),
                        "30" =>  array('name' => "Siam Commercial Bank", 'code' => 'SCB'),
                        "31" =>  array('name' => "Karsikorn Bank (K-Bank)", 'code' => 'KBANK'),
                        "32" =>  array('name' => "TMB Bank Public Company Limited", 'code' => 'TMB'),
                        "33" =>  array('name' => "Bank of Ayudhya (Krungsri)", 'code' => 'BAY'),
                        "34" =>  array('name' => "CIMB Thai", 'code' => 'CIMBT'),
                        "37" =>  array('name' => "Kiatnakin Bank", 'code' => 'KKB'),
                        "43" =>  array('name' => "Government Savings Bank", 'code' => 'GSB'),
                    );
                    break;
                case 'IDR':
                    $bankInfo = array(
                        "28" =>  array('name' => "BANK MANDIRI (PERSERO)", 'code' => 'MANDIRI'),
                        "29" =>  array('name' => "BANK RAKYAT INDONESIA AGRONIAGA", 'code' => 'BRI'),
                        "38" =>  array('name' => "BANK CAPITAL", 'code' => 'BCA'),
                        "62" =>  array('name' => "BANK NEGARA INDONESIA", 'code' => 'BNI'),
                    );
                    break;
                default:
                    return array();
                    break;
            }
            $this->utils->debug_log("=======================getting ypay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}