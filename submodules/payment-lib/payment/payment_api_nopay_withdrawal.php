<?php
require_once dirname(__FILE__) . '/abstract_payment_api_nopay.php';

/**
 * NOPAY_WITHDRAWAL
 *
 * * NOPAY_WITHDRAWAL_PAYMENT_API, ID: 6200
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_nopay_withdrawal extends Abstract_payment_api_nopay {
    const RESPONSE_ORDER_SUCCESS = 'SUCCESS';
    const CALLBACK_STATUS_FAILD   = 3;
    const CALLBACK_STATUS_SUCCESS = 2;
    const RETURN_SUCCESS = 'success';

    public function getPlatformCode() {
        return NOPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'nopay_withdrawal';
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

        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================nopay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by nopay");
            return array('success' => false, 'message' => 'Bank not supported by nopay');
        }


        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================nopay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================nopay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================nopay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================nopay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];
        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName'] : 'no firstName';
            $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName'] : 'no lastName';
            $pixNumber = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $params = array();
        $params['mchId']       = $this->getSystemInfo("account");
        $params['mchOrderNo']  = $transId;
        $params['amount']      = (int)$this->convertAmountToCurrency($amount);
        $params['bankName']    = $name;
        $params['bankCode']    = $bankCode;
        $params['accountName'] = $lastname.$firstname;;
        $params['accountNo']   = $accNum;
        $params['notifyUrl']   = $this->getNotifyUrl($transId);
        $params['remark']      = $this->getSystemInfo("remark", 'withdrawal');
        $params['reqTime']     = date('YmdHis');
        $params['sign']        = $this->sign($params);

        $this->CI->utils->debug_log('=========================nopay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================nopay json_decode result", $result);

        if(isset($result['status'])) {
            if($result['status'] != self::CALLBACK_STATUS_FAILD) {
                $message = "nopay withdrawal response successful, code:[".$result['retCode']."]: ".$result['retMsg'];
                return array('success' => true, 'message' => $message);
            }
            $message = "nopay withdrawal response failed. [status]: ".$result['status'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['retMsg']){
            $message = 'nopay withdrawal response: '.$result['retMsg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "nopay decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================nopay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================nopay json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================nopay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================nopay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($this->getSystemInfo("allow_auto_decline") == true && $params['status'] == self::CALLBACK_STATUS_FAILD) {
            $msg = sprintf('nopay withdrawal failed: [%s]', $params['transMsg']);
            $this->writePaymentErrorLog($msg, $params);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['return_error_msg'] = self::RETURN_SUCCESS;
            $this->CI->utils->debug_log("=========================nopay withdrawal callbackFromServer status is failed. set to decline");
            return $result;
        }

        if ($params['status'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('nopay withdrawal success: trade ID [%s]', $params['mchOrderNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS;
            $result['success'] = true;
        }
        // else if ($params['Status'] != self::ORDER_STATUS_PROCESS && $params['Status'] != self::ORDER_STATUS_CREATED) {
        //     $msg = sprintf('nopay withdrawal failed: [%s]', $params['Message']);
        //     $this->writePaymentErrorLog($msg, $fields);
        //     $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
        //     $result['message'] = $msg;
        // }
        else {
            $msg = sprintf('nopay withdrawal payment was not successful: [%s]', $params['transMsg']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'agentpayOrderId', 'mchOrderNo', 'status', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================nopay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================nopay withdrawal checkCallbackOrder signature Error', $fields);
            return false;
        }

        // if ($fields['status'] != self::CALLBACK_STATUS_SUCCESS) {
        //     $this->writePaymentErrorLog("======================nopay withdrawal checkCallbackOrder Payment status is not success", $fields);
        //     return false;
        // }

        if ($fields['mchOrderNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================nopay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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
            $this->utils->debug_log("==================getting kolapay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '28' => array('name' => 'CPF', 'code' => 'CPF'),
                '29' => array('name' => 'EMAIL', 'code' => 'EMAIL'),
                '30' => array('name' => 'PHONE', 'code' => 'PHONE'),
            );
            $this->utils->debug_log("=======================getting aipay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
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