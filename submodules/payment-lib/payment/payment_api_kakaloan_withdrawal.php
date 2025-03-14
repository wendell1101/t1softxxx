<?php
require_once dirname(__FILE__) . '/abstract_payment_api_kakaloan_1.php';
/**
 * kakaloan 麒麟支付
 *
 * * KAKALOAN_WITHDRAWAL_PAYMENT_API, ID: 5158
 *
 * Required Fields:
 *
 * * URL
 * * Account - ## Merchant ID ##
 * * Key - ## API Key ##
 *
 * Field Values:
 *
 * * URL: http://106.15.82.132:91/Home/OpenAcc/distill
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_kakaloan_withdrawal extends Abstract_payment_api_kakaloan_1 {
    const RETURN_STATUS_SUCCESS = 'SUCCESS';
    const RETURN_STATUS_FAILED  = 'FAIL';

    const CALLBACK_STATUS_SUCCESS  = 'SUCCESS';
    const CALLBACK_STATUS_FAILED   = 'FAIL';

    const RETURN_SUCCESS_CODE = 'SUCCESS';

    public function getPlatformCode() {
        return KAKALOAN_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'kakaloan_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function processPaymentUrlForm($params){}

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================kakaloan submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by kakaloan");
            return array('success' => false, 'message' => 'Bank not supported by kakaloan');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();
        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================kakaloan submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================kakaloan submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================kakaloan submitWithdrawRequest response ', $response);
        $this->CI->utils->debug_log('======================================kakaloan submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getBankInfo();

        $params = array();
        $params['sp_id']        = $this->getSystemInfo("sp_id");
        $params['mch_id']       = $this->getSystemInfo("account");
        $params['out_trade_no'] = $transId;
        $params['body']         = 'Deposit';
        $params['total_fee']    = $this->convertAmountToCurrency($amount); //分
        $params['card_name']    = $name;
        $params['card_no']      = $accNum;
        $params['bank_name']    = $bankInfo[$bank];
        $params['id_type']      = '01';
        $params['id_no']        = '147733198001018210';
        $params['notify_url']   = $this->getNotifyUrl($transId);
        $params['df_type']      = '0';
        $params['nonce_str']    = $this->uuid();
        $params['sign']         = $this->sign($params);

        return $params;
    }
    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }

        if(!is_null(json_decode($resultString))){
            $resultString = json_decode($resultString, true);
            $this->CI->utils->debug_log('==============kakaloan submitWithdrawRequest decodeResult json decoded', $resultString);
        }

        if(isset($resultString['status'])) {
            $returnCode = $resultString['status'];
            $returnDesc = $resultString['message'];
            if($returnCode == self::RETURN_STATUS_SUCCESS) {
                $message = "Kakaloan withdrawal response successful, transId: ". $resultString['trade_no']. ", trade status: ". $resultString['trade_state'];
                return array('success' => true, 'message' => $message);
            }
            $message = "Kakaloan withdrawal response failed. [".$returnCode."]: ".$returnDesc;
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "Kakaloan decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $result = array('success' => false, 'message' => 'Payment failed');
        $response_result_id = parent::callbackFromServer($transId, $params);
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================kakaloan callbackFromServer transId', $transId);
        $this->CI->utils->debug_log('=========================kakaloan callbackFromServer params', $params);

        if(!is_null(json_decode($params))){
            $params = json_decode($params, true);
            $this->CI->utils->debug_log('==============kakaloan callbackFromServer json decoded params', $params);
        }

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['trade_state'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('Kakaloan withdrawal Payment was successful: trade ID [%s]', $params['out_trade_no']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        } else if($params['trade_state'] == self::CALLBACK_STATUS_FAILED){
            $msg = sprintf('Kakaloan withdrawal payment was failed. [%s]: %s', $params['trade_state'], $params['trade_state_desc']);
            $this->writePaymentErrorLog($msg, $params);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $msg;
        } else {
            $msg = sprintf('Kakaloan withdrawal payment was not successful. [%s]: %s', $params['trade_state'], $params['trade_state_desc']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'out_trade_no', 'trade_no', 'trade_state', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================kakaloan Missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================kakaloan Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        $check_amount = $this->convertAmountToCurrency($order['amount']);
        if ($fields['total_fee'] != $check_amount) {
            $this->writePaymentErrorLog("======================kakaloan Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        }

        if ($fields['out_trade_no'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================kakaloan Payment order IDs do not match, expected [". $order['transactionCode']. "]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("kakaloan_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting kakaloan bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => '工商银行',
                '3' => '建设银行',
                '4' => '农业银行',
                '6' => '中国银行',
                '8' => '广发银行',
                '11' => '民生银行',
                '12' => '邮储银行',
                '14' => '华夏银行',
                '20' => '光大银行',
                '26' => '广发银行',
                '29' => '北京银行',
                '31' => '上海银行',
            );
            $this->utils->debug_log("=======================getting kakaloan bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- signing --
    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
    
        return $sign;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign' || is_null($value)) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return $signStr."key=".$this->getSystemInfo('key');
    }

    public function convertAmountToCurrency($amount) {
        return number_format($amount*100, 0, '.', '');
    }
}