<?php
use PHPHtmlParser\Content;
require_once dirname(__FILE__) . '/abstract_payment_api_fortunepay.php';
/**
 * FORTUNEPAY
 *
 * * FORTUNEPAY_WITHDRAWAL_PAYMENT_API, ID: 6538
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.fortunepay.in/payout/pay/createOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_fortunepay_withdrawal extends Abstract_payment_api_fortunepay {
    const RESULT_CODE_SUCCESS = 200;


    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = [
            'Content-Type: application/json',
            'client-id: '.$this->getSystemInfo('account'),
            'client-secret: '.$this->getSystemInfo('key'),
        ];
    }

    public function getPlatformCode() {
        return FORTUNEPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'fortunepay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) 
    {
        $params = [
            "customer_number"   => $accNum,
            "external_ref_code" => $transId,
            "external_site"     => $this->getNotifyUrl($transId),
            "amount"            => $this->convertAmountToCurrency($amount),
            "external_username" => $name,
        ];
        $this->CI->utils->debug_log(__METHOD__, 'fortunepay_withdrawal getWithdrawParams params', $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log(__METHOD__, $result);
            return $result;
        }

        if(!array_key_exists($bank, $this->getBankInfo())) {
			$this->utils->error_log("========================fortunepay withdrawal submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by fortunepay_withdrawal");
			return array('success' => false, 'message' => 'Bank not supported by fortunepay');
		}

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        list($response, $response_result) = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $transId, true);
        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('======================================fortunepay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================fortunepay submitWithdrawRequest response', $decodedResult);

        if($decodedResult['success']){
            $externalRefCode['external_ref_code'] = $params['external_ref_code'];
            unset($this->_custom_curl_header['Content-Type']);
            $response = $this->submitGetForm($this->getSystemInfo('check_status_url'), $externalRefCode, false, $transId);
            $response = json_decode($response, true);
            $checkOrderStatus = false;
            $this->CI->utils->debug_log('======================================fortunepay checkstatus response', $response);

            if (!empty($response['status']) && $response['status'] == self::REQUEST_SUCCESS && !empty($response['data']['details'])) {
                $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
                $params = $response['data']['details'];
                if ($this->checkCallbackOrder($order, $params)){
                    $checkOrderStatus = true;
                    $msg = sprintf('fortunepay withdrawal success: trade ID [%s]', $params['external_ref_code']);
                    $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
                }
            }

            if(!$checkOrderStatus){
                $decodedResult['message'] = 'request is successful but check order status failed, need to manual check with payment api provider';
                $this->utils->error_log("========================fortunepay withdrawal check order failed, need to manual check with client", $response);
            }
        }

		return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) 
    {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }

        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================fortunepay json_decode result", $result);

        if( !empty($result['code']) && $result['code'] == self::RESULT_CODE_SUCCESS && 
            !empty($result['status']) && $result['status'] == parent::REQUEST_SUCCESS
        ){
            $message = "fortunepay request successful.";
            return array('success' => true, 'message' => $message);
        }

        $resultMsg = '';
        if(!empty($result['message'])) {
            $resultMsg = $result['message'];
        }else{
            $this->utils->error_log("========================fortunepay return UNKNOWN ERROR!");
            $resultMsg = "Unknown error";
        }

        $message = "fortunepay withdrawal response, Msg: ".$resultMsg;
        return array('success' => false, 'message' => $message);
    }

    public function callbackFromServer($transId, $params) {
        $result = array('success' => false, 'message' => "Payment failed, this api doesn't supported notification");
        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'txn_id', 'external_ref_code', 'external_username', 'customer_number', 'external_site', 'amount', 'signature'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================fortunepay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================fortunepay withdrawal checkCallbackOrder signature Error', $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================fortunepay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['external_ref_code'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================fortunepay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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

    protected function validateSign($params) {
        $keys = ['txn_id', 'external_ref_code', 'customer_number', 'amount'];
        $signStr = "";
        foreach($keys as $key) {
            if(isset($params[$key])){
                $signStr .= $params[$key].':';
            }
        }

        $signStr .= $this->getSystemInfo('secret');
        $sign = hash('sha1', $signStr);
        if($params['signature'] == $sign){
            return true;
        }
        
        return false;
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
            $this->utils->debug_log("==================fortunepay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                "45" =>  array('name' => "Fortune Pay", 'code' => 'fortunePay'),            
            );
            $this->utils->debug_log("=======================getting fortunepay bank info from code: ", $bankInfo);
        }

        return $bankInfo;
    }
}