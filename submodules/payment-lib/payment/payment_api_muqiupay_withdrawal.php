<?php
require_once dirname(__FILE__) . '/abstract_payment_api_muqiupay.php';
/**
 * MUQIUPAY
 * http://muqiupaypay.com
 *
 * * 'MUQIUPAY_WITHDRAWAL_PAYMENT_API', ID 6099
 *
 * Required Fields:
 *
 * * URL
 * * Account - ## Merchant ID ##
 * * Key - ## API Key ##
 *
 * Field Values:
 *
 * * URL: http://sapi.muqiupaypay.com/Payment_Dfpay_add.html
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_muqiupay_withdrawal extends Abstract_payment_api_muqiupay {
    const RETURN_STATUS_SUCCESS = '1';

    public function getPlatformCode() {
        return MUQIUPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'muqiupay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}
    protected function processPaymentUrlForm($params) {}

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'cpay_priv_key');
        return $secretsInfo;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================muqiupay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by muqiupay");
            return array('success' => false, 'message' => 'Bank not supported by bvvpay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================muqiupay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================muqiupay submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================muqiupay submitWithdrawRequest response ', $response);
        $this->CI->utils->debug_log('======================================muqiupay submitWithdrawRequest decoded Result', $decodedResult);

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
            $this->utils->error_log("========================muqiupay withdrawal bank whose bankTypeId=[$bank] is not supported by muqiupay");
            return array('success' => false, 'message' => 'Bank not supported by muqiupay');
            $bank = '无';
        }
        $bankCode = $bankInfo[$bank]['code'];
        $bankName = $bankInfo[$bank]['name'];

        # look up bank detail
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $bankBranch = empty($playerBankDetails['branch']) ? "无" : $playerBankDetails['branch'];
            $province = empty($playerBankDetails['province']) ? "无" : $playerBankDetails['province'];
            $city = empty($playerBankDetails['city']) ? "无" : $playerBankDetails['city'];
        } else {
            $bankBranch = '无';
            $province = '无';
            $city = '无';
        }

        $contentData['orderno']   = $transId;
        $contentData['date']      = date('YmdHis');
        $contentData['amount']    = $this->convertAmountToCurrency($amount);
        $contentData['account']   = $accNum;
        $contentData['name']      = $name;
        $contentData['bank']      = $bankName;
        $contentData['subbranch'] = $bankBranch;

        $params = array();
        $params['userid']        = $this->getSystemInfo('account');
        $params['action']        = 'withdraw';
        $params['notifyurl']     = $this->getNotifyUrl($transId);
        $params['content']       = '['.json_encode($contentData, JSON_UNESCAPED_UNICODE).']';
        $params['sign']          = $this->sign($params);

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
        $this->utils->debug_log("=========================muqiupay json_decode result", $result);
        if(isset($result['status'])) {
            $returnCode = $result['status'];
            $returnDesc = $result['msg'];
            if($returnCode == self::RETURN_STATUS_SUCCESS) {
                $message = "muqiupay withdrawal response successful, transId: ". $result['orderno']. ", msg: ". $returnDesc;
                return array('success' => true, 'message' => $message);
            }
            $message = "muqiupay withdrawal response failed. [".$returnCode."]: ".$returnDesc;
            return array('success' => false, 'message' => $message);

        }
        else{
            $message = 'muqiupay withdrawal response failed.';
            return array('success' => false, 'message' => $message);
        }

        return array('success' => false, 'message' => "muqiupay decoded fail.");
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
            $this->CI->utils->debug_log("=====================muqiupay callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
        }

        $this->utils->debug_log("=========================muqiupay checkCallback params", $params);
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] = self::ORDER_STATUS_SUCCESS) {
            $msg = sprintf('dpay withdrawal payment was successful: trade ID [%s]', $params['orderno']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array(
            'userid', 'orderno', 'outorder', 'status', 'amount', 'fee', 'account', 'name', 'bank');
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================muqiupay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=========================muqiupay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($this->convertAmountToCurrency($order['amount']) != $this->convertAmountToCurrency(floatval($fields['amount'])) ) {
            $this->writePaymentErrorLog("======================muqiupay withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
            return false;
        }

        if ($fields['orderno'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================muqiupay withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    # -- bankinfo --
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting haofu bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '1' => array('name' => '中国工商银行', 'code' => 'ICBC'),
                '2' => array('name' => '招商银行', 'code' => 'CMB'),
                '3' => array('name' => '中国建设银行', 'code' => 'CCB'),
                '4' => array('name' => '中国农业银行', 'code' => 'ABC'),
                '5' => array('name' => '交通银行', 'code' => 'COMM'),
                '6' => array('name' => '中国银行', 'code' => 'BOC'),
                '8' => array('name' => '广发银行', 'code' => 'GDB'),
                '10' => array('name' => '中信银行', 'code' => 'CITIC'),
                '11' => array('name' => '中国民生银行', 'code' => 'CMBC'),
                '12' => array('name' => '中国邮政储蓄银行', 'code' => 'PSBC'),
                '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                '14' => array('name' => '华夏银行', 'code' => 'HXB'),
                '15' => array('name' => '平安银行', 'code' => 'SZPAB'),
                '18' => array('name' => '南京银行', 'code' => 'NJCB'),
                '20' => array('name' => '中国光大银行', 'code' => 'CEB'),
                '32' => array('name' => '上海浦东发展银行', 'code' => 'SPDB'),
            );
            $this->utils->debug_log("=======================getting haofu bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    public function sign($params) {
        $data = [
            'userid'  => $params["userid"],
            'action'  => $params["action"],
            'content' => $params["content"]
        ];
        $signStr =  $this->createSignStr($data);
        $sign = md5($signStr);
        return $sign;
    }

    public function verifySignature($params) {
        $data = [
            'userid'    => $params["userid"],
            'orderno'   => $params["orderno"],
            'outorder'  => $params["outorder"],
            'status'    => $params["status"],
            'amount'    => $params["amount"],
            'fee'       => $params["fee"],
            'account'   => $params["account"],
            'name'      => $params["name"],
            'bank'      => $params["bank"],
        ];
        $signStr =  $this->createSignStr($data);
        $sign = md5($signStr);
        return $sign == $params['sign'];
    }

    public function createSignStr($data) {
        $signStr = '';
        foreach($data as $key => $value) {
            if($value == null || $key == 'sign') {
                continue;
            }
            $signStr .= "$value";
        }
        $signStr .= $this->getSystemInfo('key');

        return $signStr;
    }

    public function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}