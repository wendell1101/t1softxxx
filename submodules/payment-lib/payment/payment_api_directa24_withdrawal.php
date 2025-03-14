<?php
require_once dirname(__FILE__) . '/abstract_payment_api_directa24.php';

/**
 * DIRECTA24_WITHDRAWAL
 *
 * * DIRECTA24_WITHDRAWAL_PAYMENT_API, ID: 6227
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://docs.directa24.com/api-documentation/cashouts-api/endpoints/cashout-creation-endpoint
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_directa24_withdrawal extends Abstract_payment_api_directa24 {

    const RETURN_SUCCESS = 'success';
    const CASHOUT_PENDING_CODE = "0";
    const CASHOUT_COMPLETED_CODE = "1";
    const CASHOUT_DELIVERED_CODE = "4";

    public function getPlatformCode() {
        return DIRECTA24_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'directa24_withdrawal';
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

        // if (empty($params['bank_branch'])) {
        //     return [
        //         'success' => false ,
        //         'message' => 'IFSC not set, please set IFSC code of your withdrawal account'
        //     ];
        // }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        list($content, $response_result) = $this->processCurl($params, $url, false, true);

        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================directa24_withdrawal submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================directa24_withdrawal submitWithdrawRequest response', $content);
        $this->CI->utils->debug_log('======================================directa24_withdrawal submitWithdrawRequest decoded Result', $decodedResult);

        if($decodedResult['success']){
            $set_data = json_decode($content, true);

            if (!empty($order['extra_info'])) {
                $extraInfo = $order['extra_info'];
                $extraInfo = json_decode($extraInfo, true);
                $extraInfo = array_merge($extraInfo, array('cashout_id' => $set_data['cashout_id']));
            }

            $this->CI->utils->debug_log('======================================directa24_withdrawal submitWithdrawRequest extraInfo', $extraInfo);
            $this->CI->wallet_model->setExtraInfoByTransactionCode($transId, json_encode($extraInfo));
        }
        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $this->CI->load->library(['ifsc_razorpay_lib']);

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================directa24_withdrawal Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName'] : 'no firstName';
            $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName'] : 'no lastName';
        }
        
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $bank_name = $this->findBankName($bank);
        $bank_ifsc = $order['bankBranch'];
        $extraInfo = json_decode($order['extra_info'],true);

        $this->CI->utils->debug_log(__METHOD__, 'directa24_withdrawal basic creds', [ 'accNum' => $accNum, 'name' => $name, 'bank' => $bank, 'bank_name' => $bank_name, 'bank_ifsc' => $bank_ifsc, 'extraInfo' => $extraInfo]);

        $params = array();
        $params['login']             = $this->getSystemInfo('key');
        $params['pass']              = $this->getSystemInfo('secret');
        $params['external_id']       = $transId;
        $params['document_id']       = isset($extraInfo['document_id']) ? $extraInfo['document_id'] : '';
        $params['country']           = isset($extraInfo['country']) ? $extraInfo['country'] : $this->getSystemInfo("country", self::PAYMENT_COUNTRY_MX);
        $params['currency']          = isset($extraInfo['currency']) ? $extraInfo['currency'] : $this->getSystemInfo("currency",self::CURRENCY);

        $params['amount']            = $this->convertAmountToCurrency($amount);
        if (!empty($extraInfo['currency'])) {
            if ($extraInfo['currency'] != $this->CI->utils->getCurrentCurrency()['currency_code']) {
                $params['amount'] = isset($extraInfo['converted_amount']) ? $extraInfo['converted_amount'] : 0;
            }
        }

        $params['bank_account']      = isset($extraInfo['bank_account']) ? $extraInfo['bank_account'] : '';;
        $params['bank_code']         = isset($extraInfo['bank_code']) ? $extraInfo['bank_code'] : '';
        if (!empty($extraInfo['document_type'])) {
            $params['document_type'] = $extraInfo['document_type'];
        }
        if (!empty($extraInfo['account_type'])) {
            $params['account_type'] = $extraInfo['account_type'];
        }
        // $params['bank_branch']       = $bank_ifsc;
        $params['beneficiary_name']  = $lastname.' '.$firstname;
        $params['beneficiary_lastname']  = $lastname;
        $params['notification_url']  = $this->getNotifyUrl($transId);
        
        $this->CI->utils->debug_log('=========================directa24_withdrawal getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================directa24_withdrawal json_decode result", $result);

        if(isset($result['cashout_id']) && !empty($result['cashout_id'])) {
            $message = "directa24_withdrawal response successful, cashout id :[".$result['cashout_id']."]";
            return array('success' => true, 'message' => $message);
        }

        elseif($result['code']){
            $message = 'directa24_withdrawal response: '.$result['message'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "directa24_withdrawal decoded fail.");
    }

    protected function findBankName($bank_id) {
        $bank_row = $this->CI->banktype->getBankTypeById($bank_id);
        $bank_name = lang($bank_row->bankName);
        return $bank_name;
    }

    public function checkWithdrawStatus($transId) {

        if(!empty($transId)) {
            $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
            $extra_info = json_decode($order['extra_info'],true);
            $cashout_id = $extra_info['cashout_id'];
            if(!empty($cashout_id)) {
                $params = array();
		        $params['login']       = $this->getSystemInfo('key');
		        $params['pass']        = $this->getSystemInfo('secret');
		        $params['cashout_id']  = $cashout_id;
                $params['transId']     = $transId;
                $this->CI->utils->debug_log('======================================directa24_withdrawal checkWithdrawStatus params',$params);
                $checkWithdrawURL = $this->getSystemInfo('checkWithdrawURL');
            } else {
                $this->CI->utils->debug_log('======================================directa24_withdrawal checkWithdrawStatus miss cashoutId');
                return array('success' => false, 'message' => 'Miss Trans Id');
            }
        }else{
            $this->CI->utils->debug_log('======================================directa24_withdrawal checkWithdrawStatus miss transId');
            return array('success' => false, 'message' => 'Miss Trans Id');
        }
		
        $response = $this->processCurl($params, $checkWithdrawURL, true, false);
		$decodedResult = $this->decodeDirecta24WithdrawStatusResult($response);
        $this->utils->debug_log("=========================directa24_withdrawal decodedirecta24WithdrawStatusResult decodedResult", $decodedResult);

		return $decodedResult;
	}

    public function decodeDirecta24WithdrawStatusResult($response){

        if(empty($response)){
            $this->CI->utils->debug_log('==================================directa24_withdrawal decodedirecta24WithdrawStatusResult unknown result: ', $response);
            return [
                'success' => FALSE,
                'message' => 'Unknown response data'
            ];
        }

        $result = json_decode($response, true);
        $this->utils->debug_log("=========================directa24_withdrawal decodedirecta24WithdrawStatusResult response", $result);

        if (isset($result['rejection_code']) && isset($result['rejection_reason'])) {
            $message = "directa24_withdrawal failed Status code: ".$result['rejection_code'].", reason:".$response['rejection_reason'];
            return array('success' => false, 'message' => $message);
        }

        if (isset($result['cashout_status'])) {
            switch ($result['cashout_status']) {
                 case self::CASHOUT_PENDING_CODE:
                      $message = 'code=> ' . $result['cashout_status'] . ', directa24 withdrawal Pending.';
                      $return = array('success' => true, 'message' => $message);
                      break;
                 case self::CASHOUT_COMPLETED_CODE:
                      $message = 'code=> ' . $result['cashout_status'] . ', directa24 withdrawal completed.';
                      $return = array('success' => true, 'message' => $message);
                      break;
                 case self::CASHOUT_DELIVERED_CODE:
                      $message = 'code=> ' . $result['cashout_status'] . ', directa24 withdrawal delivered.';
                      $return = array('success' => true, 'message' => $message);
                      break;
                 default:
                      $message = 'code=> ' . $result['cashout_status'] . ', directa24 withdrawal Fail.';
                      $return = array('success' => false, 'message' => $message);
                      break;
            }
        } else {
            $return = array('success' => false, 'message' => "directa24 withdrawal Invalidate API response");
        }
       return $return;
    }

    public function checkWithdrawalCallbackOrder($order, $fields) {
        $requiredFields = array(
            'external_id', 'cashout_id', 'control'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================directa24_withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->w_verifySignature($fields)) {
            $this->writePaymentErrorLog('=========================directa24_withdrawal checkCallbackOrder signature Error', $fields);
            return false;
        }

        $extraInfo = $order['extra_info'];
        $extraInfo = json_decode($extraInfo, true);
        $cashout_id= $extraInfo['cashout_id'];
        if ($fields['cashout_id'] != $cashout_id) {
            $this->writePaymentErrorLog("=====================directa24_withdrawal checkCallbackOrder cashout_id id not match external_order_id [$cashout_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }

    # -- signatures --
    # Reference: PHP Demo
    public function w_sign($params) {
        $secret = $this->getSystemInfo('account');
        $sign = strtolower(hash_hmac('sha256', pack('A*', json_encode($params)), pack('A*', $secret)));
        return $sign;
    }

    public function w_verifySignature($params) {

        $secret = $this->getSystemInfo('account');
        $controlStr = $params['control'];
        unset($params['control']);

        $externalId = $params['external_id'];
        $message = 'Be4' . $externalId . 'Bo7';
        $sign = strtoupper(hash_hmac('sha256', pack('A*', $message), pack('A*', $secret)));

        if ($controlStr == $sign) {
            return true;
        } else {
            return false;
        }     
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    public function getNotifyUrl($orderId) {
        $use_https_with_url = $this->getSystemInfo('use_https_with_url');
        $notifyUrl = parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
        if($use_https_with_url) {
            $notifyUrl = str_replace('http://', 'https://', $notifyUrl);
        }
        return $notifyUrl;
    }

    protected function processCurl($params, $url, $isCheckState = false, $return_all=false) {
        
        if ($isCheckState) {
            $transId = $params['transId'];
            unset($params['transId']);
        } else {
            $transId = $params['external_id'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        $sign = $this->w_sign($params);
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Payload-Signature:'.$sign,
            'Content-Type:application/json'
            )
        );

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

        #withdrawal lock processing
        if(substr($transId, 0, 1) == 'W' && $errCode == '28') {   //curl_errno means timeout
            $response = array('lock' => true, 'msg' => 'Ready to lock processing withdrawal order. curl error message: errCode = '.$errCode.' - '.$error);
        }

        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $transId);

        if($return_all){
            $response_result = [
                $params, $response, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $transId
            ];
            return array($response, $response_result);
        }
        return $response;
    }
}