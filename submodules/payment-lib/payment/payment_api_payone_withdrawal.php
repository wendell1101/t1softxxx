<?php
require_once dirname(__FILE__) . '/abstract_payment_api_payone.php';

/**
 * PAYONE_WITHDRAWAL_PAYMENT_API
 *
 * * PAYONE_WITHDRAWAL_PAYMENT_API, ID: 6312
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.payone1.com/br/payout.json
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_payone_withdrawal extends Abstract_payment_api_payone {
    const TYPE_PIX         = 'PIX';
    const CALLBACK_SUCCESS = '07';

    public function getPlatformCode() {
        return PAYONE_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'payone_withdrawal';
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

         # look up bank code
        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================payone withdrawal bank whose bankTypeId=[$bank] is not supported by payone");
            return array('success' => false, 'message' => 'Bank not supported by payone');
        }

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $playerId = $playerBankDetails['playerId'];
        $validationResults = $this->checkWalletaccountPlayerId($playerId, $transId);

        if (!$validationResults['success']) {
            $this->utils->debug_log("===========payone", ["result" => $validationResults]);
            return $validationResults;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->processShell($params, $url, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================payone submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================payone submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================payone submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function processShell($params, $url, $transId, $return_all=false){

        $proxy = $this->getSystemInfo('call_socks5_proxy', 'socks5://10.158.0.5:1000');
        $header = 'Content-Type: application/json';
        $json_encode = json_encode($params);

        $command = "curl -v --http1.1 -x '{$proxy}' --location '{$url}' -H '{$header}' -d '{$json_encode}'";
        $response = shell_exec($command);

        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => NULL, 'error' => NULL, 'statusCode' => NULL), $transId);

        if($return_all){
            $response_result = [
                $params, $response, $url, $response, ['errCode' => NULL, 'error' => NULL, 'statusCode' => NULL], $transId 
            ];
            return array($response, $response_result);
        }
        return $response;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================payone Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        
        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName'] : 'no firstName';
            $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName'] : 'no lastName';
            $pixNumber  = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))    ? $playerDetails[0]['pix_number'] : 'none';
            $phone      = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : 'none';
            $email      = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : 'sample@example.com';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];
        $walletInfo = array('CPF' => $pixNumber, 'EMAIL' => $email, 'PHONE' => $phone);
    
        $params = array();
        $params['amount']          = $this->convertAmountToCurrency($amount);
        $params['appId']           = $this->getSystemInfo('appId');
        $params['backUrl']         = $this->getNotifyUrl($transId);
        $params['cardType']        = $bankCode;
        $params['countryCode']     = $this->getSystemInfo('country', self::COUNTRY_CODE);
        $params['currencyCode']    = $this->getSystemInfo('currency', self::CURRENCY_CODE);
        $params['custId']          = $this->getSystemInfo('account');
        $params['email']           = $email;
        $params['merchantOrderId'] = $transId;
        $params['cpf']             = $pixNumber;
        $params['phone']           = $phone;
        $params['remark']          = "withdrawal";
        $params['type']            = self::TYPE_PIX;
        $params['userName']        = $lastname.' '.$firstname;
        $params['walletId']        = $walletInfo[$bankCode];
        $params['sign']            = $this->sign($params);
        
        $this->CI->utils->debug_log('=========================payone getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================payone json_decode result", $result);

        if (isset($result['code'])) {
            if($result['code'] == self::REPONSE_CODE_SUCCESS) {
                $message = "payone withdrawal response successful, merchantOrderId:".$result['merchantOrderId'];
                return array('success' => true, 'message' => $message);
            }
            $message = "payone withdrawal response failed. ErrorMessage: ".$result['msg'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['msg']){
            $message = 'payone withdrawal response: '.$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "payone decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        
        $raw_post_data = file_get_contents('php://input', 'r');
        $this->CI->utils->debug_log("=====================payone raw_post_data", $raw_post_data);
        parse_str($raw_post_data ,$params);
        $this->CI->utils->debug_log("========================payone parse_str params", $params); 

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================payone callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================payone callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['orderStatus'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('payone withdrawal success: trade ID [%s]', $params['merchantOrderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }else {
            $msg = sprintf("payone withdrawal payment unsuccessful or pending: status=%s", $params['orderStatus']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'appId', 'custId', 'merchantOrderId', 'orderStatus', 'amount', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================payone withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================payone withdrawal checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================payone withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['merchantOrderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================payone withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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
    private function getBankInfo(){
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
            $this->utils->debug_log("==================getting payone bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '47' => array('name' => 'CPF', 'code' => 'CPF'),
                '48' => array('name' => 'EMAIL', 'code' => 'EMAIL'),
                '49' => array('name' => 'PHONE', 'code' => 'PHONE'),
            );
            $this->utils->debug_log("=======================getting payone bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
}