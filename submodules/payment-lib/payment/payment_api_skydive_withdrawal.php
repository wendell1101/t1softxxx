<?php
require_once dirname(__FILE__) . '/abstract_payment_api_skydive.php';

/**
 * skydive
 * http://merchant.topasianpg.co
 *
 * * SKYDIVE_WITHDRAWAL_PAYMENT_API, ID: 5742
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://services.missilegroup.com/autotransfer-test/transfer
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_skydive_withdrawal extends Abstract_payment_api_skydive {

    const RETURN_SUCCESS_CODE = 'success';
    const RESULT_STATUS_SUCCESS = '202.00';
    const CALLBACK_SUCCESS_CODE = '200';
    const CURRENCY = 'INR';

    public function getPlatformCode() {
        return SKYDIVE_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'skydive_withdrawal';
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

        $this->CI->utils->debug_log('======================================skydive submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================skydive submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================skydive submitWithdrawRequest decoded Result', $decodedResult);

        if($decodedResult['success']){
            $this->CI->wallet_model->setExtraInfoByTransactionCode($transId, $response['data']['transaction_id']);
        }

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
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================skydive Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $params = array();
        $params['session']            = $transId;
        $params['source_system_name'] = $this->getSystemInfo('source_system_name', 'ssn_test');
        $params['payer_bank']         = $this->getSystemInfo('payer_bank', 'SCB');
        $params['payer_account']      = $this->getSystemInfo('payer_account', '0000000101');
        $params['payer_msisdn']       = $this->getSystemInfo('payer_msisdn', '+66810000001');
        $params['payee_bank']         = $bankCode;
        $params['payee_account']      = $accNum;
        $params['amount']             = $this->convertAmountToCurrency($amount);
        $params['callback_url']       = $this->getNotifyUrl($transId);

        $this->CI->utils->debug_log('=========================skydive getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================skydive withdrawal decodeResult", $result);

        if (isset($result['status'])) {
            $respCode = $result['status']['code'];
            $resptype = $result['status']['type'];
            $msg = $result['status']['message'];
            $resultTransactionId = $result['data']['transaction_id'];
        } else {
            $respCode = $result['code'];
            $resptype = $result['type'];
            $msg = $result['message'];
        }

        if($respCode == self::RESULT_STATUS_SUCCESS) {
            $message = "skydive request successful. [Code: ". $respCode . ', Type: '. $resptype . ', transaction_id :' .$resultTransactionId." ]";
            return array('success' => true, 'message' => $message);
        }
        else {
            if($msg == '' || $msg == false) {
                $this->utils->error_log("========================skydive return UNKNOWN ERROR!");
                $msg = "Unknow Error";
            }

            $message = "skydive withdrawal response, Code: [ ".$respCode." ] , Msg: ".$msg;
            return array('success' => false, 'message' => $message);
        }
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================skydive withdrawal raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================skydive withdrawal json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================skydive callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================skydive callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $fields['status'] = $params['status']['code'];
        $fields['transaction_id'] = $params['data']['transaction_id'];
        $fields['request_amount'] = $params['data']['request_amount'];
        $fields['msg'] = $params['status']['message'];

        if (!$this->checkCallbackOrder($order, $fields)) {
            return $result;
        }

        if ($fields['status'] == self::CALLBACK_SUCCESS_CODE) {
            $msg = sprintf('skydive withdrawal success: trade ID [%s]', $transId);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        // else if ($params['Status'] != self::ORDER_STATUS_PROCESS && $params['Status'] != self::ORDER_STATUS_CREATED) {
        //     $msg = sprintf('skydive withdrawal failed: [%s]', $params['Message']);
        //     $this->writePaymentErrorLog($msg, $fields);
        //     $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
        //     $result['message'] = $msg;
        // }
        else {
            $msg = sprintf('skydive withdrawal payment was not successful: [%s]', $fields['msg']);
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'transaction_id', 'request_amount', 'status'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================skydive withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['state'] != self::CALLBACK_SUCCESS_CODE) {
            $this->writePaymentErrorLog("=====================skydive withdrawal checkCallbackOrder status is not confirmed", $fields);
            return false;
        }

        if ($fields['request_amount'] != $order['amount']) {
            $this->writePaymentErrorLog('=========================skydive withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['transaction_id'] != $order['extra_info']) {
            $this->writePaymentErrorLog('=========================skydive withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['extra_info'], $fields);
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
            $this->utils->debug_log("==================getting skydive withdrawal bank info from extra_info: ", $bankInfo);
        } else  {
            $currency  = $this->getSystemInfo('currency',self::CURRENCY);
            switch ($currency) {
                case 'THB':
                    $bankInfo = array(
                        "56" =>  array('name' => "กรุงเทพ", 'code' => 'BBLA'),
                        "55" =>  array('name' => "กสิกรไทย", 'code' => 'KBNK'),
                        "57" =>  array('name' => "กรุงไทย", 'code' => 'KTBA'),
                        "58" =>  array('name' => "ทหารไทย", 'code' => 'TMBA'),
                        "59" =>  array('name' => "ไทยพาณิชย์", 'code' => 'SCB'),
                        "60" =>  array('name' => "ซีไอเอ็มบีไทย", 'code' => 'CIMBT'),
                        "61" =>  array('name' => "ยูโอบี", 'code' => 'UOBT'),
                        "62" =>  array('name' => "กรุงศรีอยุธยา", 'code' => 'BAYA'),
                        "63" =>  array('name' => "ออมสิน", 'code' => 'GSBA'),
                        "64" =>  array('name' => "อาคารสงเคราะห์", 'code' => 'GHBA'),
                        "65" =>  array('name' => "ธกส", 'code' => 'BAAC'),
                        "66" =>  array('name' => "สกต", 'code' => 'EXIM'),
                        "67" =>  array('name' => "ธนชาต", 'code' => 'TBNK'),
                        "68" =>  array('name' => "อิสลามแห่งประเทศไทย", 'code' => 'ISBTA'),
                        "69" =>  array('name' => "ทิสโก้", 'code' => 'TISCO'),
                        "70" =>  array('name' => "เกียรตินาคิน", 'code' => 'KKPA'),
                        "71" =>  array('name' => "ไอซีบีซี", 'code' => 'ICBCTA'),
                        "72" =>  array('name' => "ไทยเครดิตเพื่อรายย่อย", 'code' => 'TCDA'),
                        "73" =>  array('name' => "แลนด์ แอนด์ เฮาส์", 'code' => 'LHFGA'),
                        "74" =>  array('name' => "ธพว", 'code' => 'SMEA'),
                    );
                    break;
                case 'IDR':
                    $bankInfo = array(
                        "28" =>  array('name' => "BANK MANDIRI (PERSERO)", 'code' => 'Mandiri'),
                        "38" =>  array('name' => "BANK CAPITAL", 'code' => 'BCA'),
                        "62" =>  array('name' => "BANK NEGARA INDONESIA", 'code' => 'BNI'),
                    );
                    break;
                default:
                    return array();
                    break;
            }
            $this->utils->debug_log("=======================getting skydive withdrawal bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}