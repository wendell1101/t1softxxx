<?php
require_once dirname(__FILE__) . '/abstract_payment_api_goopago.php';

/**
 * GOOPAGO_WITHDRAWAL
 *
 * * GOOPAGO_WITHDRAWAL_PAYMENT_API, ID: 6281
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
 * @copyright 2013-2023 tot
 */
class Payment_api_goopago_withdrawal_iugu extends Abstract_payment_api_goopago {

    const REPONSE_CODE_SUCCESS = 1;
    const COUNTRY_CODE = "+55";
    const CALLBACK_SUCCESS = 2;

    public function getPlatformCode() {
        return GOOPAGO_WITHDRAWAL_IUGU_PAYMENT_API;
    }

    public function getPrefix() {
        return 'goopago_withdrawal_iugu';
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

        $this->CI->utils->debug_log('======================================goopago iugu submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================goopago iugu submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================goopago iugu submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];
        $playerInfo = $this->getPlayerInfoByTransactionCode($transId, $bankInfo[$bank]['name']);
        $params = array();
        $channel = $this->getSystemInfo('channel');

        $this->_custom_curl_header = array('tmId:'. $channel,
                                           'Content-Type:application/json');
        $params['mchId']       = $this->getSystemInfo("account");
        $params['mchOrderNo']  = $transId;       
        $params['appId']       = $this->getSystemInfo('appId')[$channel];
        $params['amount']      = $this->convertAmountToCurrency($amount);
        $params['notifyUrl']   = $this->getNotifyUrl($transId);
        $params['nonceStr']    = $this->nonceStr(rand(10, 31));
        $params['accountNo']   = $playerInfo['pixAccount'];
        $params['accountType'] = $bankCode;
        $params['idNumber']    = $playerInfo['cpfNumber'];
        $params['sign']        = $this->sign($params);

        $this->CI->utils->debug_log('=========================goopago iugu getWithdrawParams params', $params);

        return $params;
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
            $this->utils->debug_log("==================getting goopago bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '47' => array('name' => 'CPF',   'code' => 1),
                '48' => array('name' => 'EMAIL', 'code' => 3),
                '49' => array('name' => 'PHONE', 'code' => 4),
            );
            $this->utils->debug_log("=======================getting goopago bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================goopago iugu json_decode result", $result);

        if (isset($result['status'])) {
            if($result['status'] == self::REPONSE_CODE_SUCCESS) {
                $message = "goopago iugu withdrawal response successful, transaction ID:".$result['transaction_id'];
                return array('success' => true, 'message' => $message);
            }
            $message = "goopago iugu withdrawal response failed. ErrorMessage: ".$result['msg'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['msg']){
            $message = 'goopago iugu withdrawal response: '.$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "goopago iugu decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("========================goopago iugu raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("========================goopago iugu json_decode params", $params); 
        }
        
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================goopago iugu callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================goopago iugu callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('goopago iugu withdrawal success: trade ID [%s]', $params['mchOrderNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }else {
            $msg = sprintf("goopago iugu withdrawal payment unsuccessful status=%s", $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'status', 'amount', 'mchOrderNo', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================goopago iugu withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================goopago iugu withdrawal checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================goopago iugu withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['mchOrderNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================goopago iugu withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
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