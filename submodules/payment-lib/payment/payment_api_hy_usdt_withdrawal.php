<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hy_usdt.php';
/**
 * HY_USDT
 *
 * * HY_USDT_WITHDRAWAL_PAYMENT_API, ID: 5883
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * URL
 *
 * Field Values:
 * * Account: ## APP ID ##
 * * Key: ## APP KEY ##
 * * Secret: ## APP SECRET ##
 * * URL: https://www.hy_usdt.com/oss/wallet/cre_propay_order
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hy_usdt_withdrawal extends Abstract_payment_api_hy_usdt {
    public $txAuth = '';

    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getPlatformCode() {
        return HY_USDT_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'hy_usdt_withdrawal';
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        $data = $this->CI->playerbankdetails->getBankCodeByBankType($bank);
        if(strpos(strtoupper($data['bank_code']), 'USDT') === false){
            $this->utils->error_log("========================hy_usdt submitWithdrawRequest bank whose bank code is not supported by hy_usdt");
            return array('success' => false, 'message' => 'Bank not supported by hy_usdt');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();
        $token_url = $this->getSystemInfo('token_url');

        // 1: acquire access token first
        $get_token_params['grant_type'] = 'client_credentials';
        $get_token_params['client_id'] = $this->getSystemInfo("account");
        $get_token_params['client_secret'] = $this->getSystemInfo('key');
        list($response_token, $response_token_result) = $this->submitPostForm($token_url, $get_token_params, false, $transId, true);

        if(!empty($response_token)){
            $response_token = json_decode($response_token,true);
            $this->utils->debug_log('=====================HY_USDT usdt response_token', $response_token);
        }else{
            return array('success' => false, 'message' => 'token is empty');
        }

        // 2: send pay request with acquired response token
        list($content, $response_result) = $this->processCurl($params, $response_token, true);
        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================hy_usdt submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================hy_usdt submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================hy_usdt submitWithdrawRequest content', $content);
        $this->CI->utils->debug_log('======================================hy_usdt submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================hy_usdt json_decode result", $result);
        if(!empty($result) && isset($result)){
            if(!empty($result['payment_txid']) && isset($result['payment_txid'])){
                return array('success' => true, 'message' => 'hy_usdt request successful.');
            }else if(!empty($result['errors'])){
                $errorMsg = $result['errors'];
                return array('success' => false, 'message' => $errorMsg);
            }
            else{
                return array('success' => false, 'message' => 'hy usdt withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'hy usdt withdrawal exist errors');
        }
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model'));
        # look up bank code
        $wallet_account_id = $this->CI->wallet_model->getWalletaccountIdByTransactionCode($transId);
        $cryptolOrder = $this->CI->wallet_model->getCryptoWithdrawalOrderById($wallet_account_id);

        if(empty($cryptolOrder) && !is_array($cryptolOrder)){
            $this->utils->debug_log("=========================hy_usdt crypto order not exists", $transId);
            return array('success' => false, 'message' => 'crypto order not exists');
        }

        $params = array();
        $params['amount'] = $cryptolOrder['transfered_crypto'];
        $params['wallet_id'] = $this->getSystemInfo("wallet_id");
        $params['transaction_id'] = $transId;
        $params['currency_code']   = 'USDT';
        $params['dest_crypto_address'] = $accNum;
        $this->generateTxAuth($params);
        return $params;
    }

    /**
     * Calculates the hash value for 'txAuthorization'
     * @param   array   $params     param array
     * @return  string  hash
     */
    public function generateTxAuth($params) {
        $fields = ['amount', 'currency_code', 'dest_crypto_address', 'wallet_id', 'transaction_id'];
        $plain_ar = '';
        foreach ($fields as $key) {
            $plain_ar[] = "{$key}:{$params[$key]}";
        }

        $plain_ar[] = "secret:{$this->getSystemInfo('key')}";
        // concat all key-value pairs with '|' char
        $plain = implode('|', $plain_ar);

        $txAuth = md5($plain);
        $this->CI->utils->debug_log(__METHOD__, 'HY_USDT txAuth calc', [ 'plain' => $plain, 'txAuth'=> $txAuth ]);
        $this->txAuth = $txAuth;
        return $txAuth;
    }

    public function processCurl($params, $response_token, $return_all=false){

        $ch = curl_init();
        $url = $this->getSystemInfo('url');
        $token = $response_token['access_token'];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer '.$token ,
            'txAuthorization: ' . $this->txAuth
        ];
        $this->CI->utils->debug_log(__METHOD__, 'headers', $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $this->setCurlProxyOptions($ch);
        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $responseStr = substr($response, $header_size);
        curl_close($ch);
        #save response result
        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['transaction_id']);

        if($return_all){
            $response_result = [
                $params, $response, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $params['transaction_id']
            ];
            return array($response, $response_result);
        }
        return $response;
    }
}