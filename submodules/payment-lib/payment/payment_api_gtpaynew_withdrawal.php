<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gtpaynew.php';

/**
 * GTPAYNEW_WITHDRAWAL
 *
 * * GTPAYNEW_WITHDRAWAL_PAYMENT_API, ID: 5884
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://interface.payp.vip/api/guest/instead/insPay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gtpaynew_withdrawal extends Abstract_payment_api_gtpaynew {

    const RESPONSE_ORDER_SUCCESS = 'processing';
    const CALLBACK_STATUS_SUCCESS = '1';
    const CURRENCY = 'INR';
    const CHARGETYPE = '1';

    public function getPlatformCode() {
        return GTPAYNEW_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'gtpaynew_withdrawal';
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
            $this->utils->error_log("========================gtpaynew withdrawal bank whose bankTypeId=[$bank] is not supported by gtpaynew");
            return array('success' => false, 'message' => 'Bank not supported by gtpaynew');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================gtpaynew submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================gtpaynew submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================gtpaynew submitWithdrawRequest decoded Result', $decodedResult);

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
        $this->utils->debug_log("===============================gtpaynew Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
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
        $player = $this->CI->player_model->getPlayerDetailArrayById($order['playerId']);

        $params = array();

        $params['platformNo']      = strval($this->getSystemInfo('account'));
        $fields['commercialPayNo'] = strval($transId);
        $fields['totalAmount']     = strval($this->convertAmountToCurrency($amount));
        $fields['payeeBank']       = strval($bankCode);
        $fields['payeeBankCode']   = strval($bankBranch);
        $fields['payeeAcc']        = strval($accNum);
        $fields['payeeName']       = strval($order['bankAccountFullName']);
        $fields['payeePhone']      = strval($player['contactNumber']);
        $fields['currency']        = strval($this->getSystemInfo('currency', self::CURRENCY));
        $fields['chargeType']      = strval($this->getSystemInfo('chargeType', self::CHARGETYPE));
        $fields['notifyUrl']       = strval($this->getNotifyUrl($transId));
        $json_fields = json_encode($fields);
        $params['parameter']       = strval($this->encrypt($json_fields,$this->getSystemInfo('key')));
        $params['sign']            = strval($this->sign($json_fields));

        $this->CI->utils->debug_log('=========================gtpaynew getWithdrawParams params', $params,'json_fields',$json_fields);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================gtpaynew json_decode result", $result);

        if(isset($result['result'])) {
            if($result['result'] == self::RESPONSE_ORDER_SUCCESS) {
                $message = "gtpaynew withdrawal response successful, code:[".$result['result']."]: ".$result['msg'];
                return array('success' => true, 'message' => $message);
            }
            $message = "gtpaynew withdrawal response failed. [".$result['result']."]: ".$result['msg'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['msg']){
            $message = 'gtpaynew withdrawal response: '.$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "gtpaynew decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================gtpaynew raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================gtpaynew json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================gtpaynew callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================gtpaynew callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        $decrypt_callback_parameter = $this->decrypt($params['parameter'],$this->getSystemInfo('key'));
        $this->CI->utils->debug_log("=========================gtpaynew callbackFromServer decrypt_callback_parameter", $decrypt_callback_parameter);
        $decrypt_callback_parameter = json_decode($decrypt_callback_parameter,true);

        if ($decrypt_callback_parameter['result'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('gtpaynew withdrawal success: trade ID [%s]', $decrypt_callback_parameter['outTradeNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        // else if ($params['Status'] != self::ORDER_STATUS_PROCESS && $params['Status'] != self::ORDER_STATUS_CREATED) {
        //     $msg = sprintf('gtpaynew withdrawal failed: [%s]', $params['Message']);
        //     $this->writePaymentErrorLog($msg, $fields);
        //     $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
        //     $result['message'] = $msg;
        // }
        else {
            $msg = sprintf('gtpaynew withdrawal payment was not successful: [%s]', $decrypt_callback_parameter['msg']);
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'platformno', 'parameter', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================gtpaynew withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        $decrypt_callback_parameter = $this->decrypt($fields['parameter'],$this->getSystemInfo('key'));
        $this->utils->debug_log("==================getting checkCallbackOrder parameter: ", $decrypt_callback_parameter);

        if (!$this->validateSign(strval($decrypt_callback_parameter),$fields['sign'])) {
            $this->writePaymentErrorLog('=========================gtpaynew withdrawal checkCallback signature Error', $fields);
            return false;
        }

        $decrypt_callback_parameter = json_decode($decrypt_callback_parameter,true);

        if ($decrypt_callback_parameter['result'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================gtpaynew withdrawal checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($decrypt_callback_parameter['totalAmount'] != $order['amount']) {
            $this->writePaymentErrorLog('=========================gtpaynew withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($decrypt_callback_parameter['outTradeNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================gtpaynew withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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
            $this->utils->debug_log("==================getting gtpaynew bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '273' => array('name' => 'UJJIVAN BANK', 'code' => 'UJJIVN'),
                '2' => array('name' => 'DBI BANK', 'code' => 'IDBIBK'),    
                '3' => array('name' => 'HDFC BANK', 'code' => 'HDFCBK'),
                '4' => array('name' => 'ICICI BANK', 'code' => 'ICICI'),
                '5' => array('name' => 'AXIS BANK', 'code' => 'AXIS'),
            );
            $this->utils->debug_log("=======================getting aipay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    # -- signatures --
    private function sign($params) {
        // $signStr = $this->createSignStr($params);
        $sign = strtolower(md5($params));
        return $sign;
    }

    private function validateSign($params,$callback_sign) {
        $sign = $this->sign($params);
        if($callback_sign == $sign){
            return true;
        }
        else{
            return false;
        }
    }
}