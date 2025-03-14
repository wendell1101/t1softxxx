<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cgpay.php';
/**
 * cgpay
 * http://cgpaypay.com
 *
 * * 'CGPAY_WITHDRAWAL_PAYMENT_API ID 6044
 *
 * Required Fields:
 *
 * * URL
 * * Account - ## Merchant ID ##
 * * Key - ## API Key ##
 *
 * Field Values:
 *
 * * URL: http://sapi.cgpaypay.com/Payment_Dfpay_add.html
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_cgpay_withdrawal extends Abstract_payment_api_cgpay {

    public function getPlatformCode() {
        return CGPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'cgpay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}
    protected function processPaymentUrlForm($params) {}

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret');
        return $secretsInfo;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================cgpay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by cgpay");
            return array('success' => false, 'message' => 'Bank not supported by cgpay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================cgpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================cgpay submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================cgpay submitWithdrawRequest response ', $response);
        $this->CI->utils->debug_log('======================================cgpay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    # Note: to avoid breaking current APIs, these abstract methods are not marked abstract
    # APIs with withdraw function need to implement these methods
    ## This function returns the URL to submit withdraw request to
    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    ## This function returns the params to be submitted to the withdraw URL
    ## Note that $bank param is the bank_type ID in database, we compare it with the supported bank_codes by this AP
    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================cgpay withdrawal bank whose bankTypeId=[$bank] is not supported by cgpay");
            return array('success' => false, 'message' => 'Bank not supported by cgpay');
            $bank = '无';
        }
        $params = array();
        $params['MerchantId']        = $this->getSystemInfo('account');
        $params['MerchantUserId']    = $transId;
        $params['MerchantWithdrawId']= $transId;
        $params['UserWallet']        = $accNum;
        $params['Symbol']            = 'CGP';
        $params['RMBAmount']         = $amount;
        $params['CryptoAmount']      = $this->convertAmountToCurrency($amount);
        $params['Ip']                = $this->getClientIP();
        $params['CallBackUrl']       = $this->getNotifyUrl($transId);
        $params['AutoWithdraw']      = 'AUTO';
        $params['Sign']              = $this->sign($params);
        return $params;
    }

    ## This function takes in the return value of the URL and translate it to the following structure
    ## array('success' => false, 'message' => 'Error message')
    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================cgpay json_decode result", $result);

        $message = 'cgpay withdrawal exist errors';
        if(isset($result['ReturnCode']) && isset($result['ReturnMessage'])) {
            $returnCode = $result['ReturnCode'];
            $returnDesc = $result['ReturnMessage'];
            if($returnCode == self::REQUEST_SUCCESS) {
                $message = "cgpay withdrawal response successful, transId: ". $result['MerchantWithdrawId']. ", msg: ". $returnDesc;
                return array('success' => true, 'message' => 'cgpay request successful');
            }
            $message = "cgpay withdrawal response failed. [".$returnCode."]: ".$returnDesc;
            return array('success' => false, 'message' => $message);

        }
        else{
            if(isset($result['ReturnMessage'])){
                $message = $message.' API response: '.$returnDesc;
            }
            return array('success' => false, 'message' => $message);
        }

        return array('success' => false, 'message' => "cgpay decoded fail.");
    }

    /**
     * detail: Help2Pay withdraw callback implementation
     *
     * @param int $transId transaction id
     * @param int $paramsRaw
     * @return array
     */
    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');

        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================cgpay callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
        }

        $this->utils->debug_log("=========================cgpay checkCallback params", $params);
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        $msg = sprintf('cgpay withdrawal payment was successful: trade ID [%s]', $params['orderno']);
        $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
        $result['message'] = self::RETURN_SUCCESS_CODE;
        $result['success'] = true;
        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array(
            'MerchantId', 'UserWallet', 'WithdrawId', 'MerchantWithdrawId', 'RMBAmount', 'Sign');
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================cgpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=========================cgpay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($order['amount'] != $fields['RMBAmount']) {
            $this->writePaymentErrorLog("======================cgpay withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
            return false;
        }

        if ($fields['MerchantWithdrawId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================cgpay withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
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
            $this->utils->debug_log("==================getting cgpay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '218' => array('name' => 'CGPAY', 'code' => 'CGPAY')
            );
            $this->utils->debug_log("=======================getting cgpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}