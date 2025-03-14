<?php
require_once dirname(__FILE__) . '/abstract_payment_api_waas_usdt.php';
/**
 * WAAS_USDT
 *
 * * WAAS_USDT_WITHDRAWAL_PAYMENT_API, ID: 6011
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
 * * URL: https://www.waas_usdt.com/oss/wallet/cre_propay_order
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_waas_usdt_withdrawal extends Abstract_payment_api_waas_usdt {
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getPlatformCode() {
        return WAAS_USDT_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'waas_usdt_withdrawal';
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
            $this->utils->error_log("========================waas_usdt submitWithdrawRequest bank whose bank code is not supported by waas_usdt");
            return array('success' => false, 'message' => 'Bank not supported by waas_usdt');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        if(empty($params['waasCryptoUid'])){
            $this->utils->error_log("========================waas_usdt submitWithdrawRequest is not exist waas uid");
            return array('success' => false, 'message' => 'not exist Waas Uid by waas_usdt');
        }else{
            unset($params['waasCryptoUid']);
        }

        $url = $this->getWithdrawUrl();
        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================waas_usdt submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================waas_usdt submitWithdrawRequest content', $content);
        $this->CI->utils->debug_log('======================================waas_usdt submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================waas_usdt json_decode result", $result);
        $decrypt_result = json_decode($this->decrypt($result['data'], $this->getPubKey()), true);

        $this->utils->debug_log("=========================waas_usdt decrypt json_decode result", $decrypt_result);
        if($queryAPI){
            if(!empty($decrypt_result['data']['status']) && isset($decrypt_result['data']['status']) && ($decrypt_result['data']['status'] == self::CALLBACK_WITHDRAWAL_SUCCESS)){
                $message = sprintf('waas_usdt withdrawal payment was successful: trade ID [%s]', $decrypt_result['data']['request_id']);
                $this->CI->wallet_model->withdrawalAPIReturnSuccess($decrypt_result['data']['request_id'], $message);
                return array('success' => true, 'message' => $message);
            }elseif(!empty($decrypt_result['code']) && isset($decrypt_result['code'])){
                if(!empty($decrypt_result['data']['status']) && isset($decrypt_result['data']['status'])){
                    $errorMsg = 'waas_usdt request successful, status is => status['.$decrypt_result['data']['status'].']';
                }else{
                    $errorMsg = 'code['.$decrypt_result['code'].']:waas_usdt request successful.';
                }
                return array('success' => false, 'message' => $errorMsg);
            }
            else{
                return array('success' => false, 'message' => 'waas withdrawal checkstatus has error');
            }
        }else{
            if(!empty($decrypt_result) && isset($decrypt_result)){
                if(!empty($decrypt_result['code']) && isset($decrypt_result['code']) && $decrypt_result['code'] == self::RESULT_CODE_SUCCESS){
                        return array('success' => true, 'message' => 'waas_usdt request successful.');
                    }else if(!empty($decrypt_result['msg']) && isset($decrypt_result['msg'])){
                        $errorMsg = 'code:['.$decrypt_result['code'].']:'.$decrypt_result['msg'];
                        return array('success' => false, 'message' => $errorMsg);
                    }else if(!empty($decrypt_result['data']['msg']) && isset($decrypt_result['data']['msg'])){
                        $errorMsg = $decrypt_result['data']['msg'];
                        return array('success' => false, 'message' => $errorMsg);
                    }else if(!empty($decrypt_result['code']) && isset($decrypt_result['code'])){
                        $errorMsg = $decrypt_result['code'];
                        return array('success' => false, 'message' => $errorMsg);
                    }
                    else{
                        return array('success' => true, 'message' => 'waas_usdt request does not exist error message');
                    }
            }else{
                return array('success' => true, 'message' => 'waas_usdt request does not exist error message');
            }
        }
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model'));
        # look up bank code
        $wallet_account_id = $this->CI->wallet_model->getWalletaccountIdByTransactionCode($transId);
        $cryptolOrder = $this->CI->wallet_model->getCryptoWithdrawalOrderById($wallet_account_id);
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);

        if(empty($cryptolOrder) && !is_array($cryptolOrder)){
            $this->utils->debug_log("=========================waas_usdt crypto order not exists", $transId);
            return array('success' => false, 'message' => 'crypto order not exists');
        }

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $waasCryptoOrder = $this->CI->sale_order->getSaleOrderByPlayerId($playerId);
            $waasCryptoUid = '';
            foreach ($waasCryptoOrder as $key => $value) {
                if(!empty($value['bank_order_id']) && $value['system_id'] == '6010'){
                    $waasCryptoUid = $value['bank_order_id'];
                }
            }
        }

        $params = array();
        $params['app_id'] = $this->getSystemInfo('account');
        $params['waasCryptoUid']        = $waasCryptoUid;
        $params['data']['time']         = time();
        $params['data']['charset']      = 'utf-8';
        $params['data']['version']      = 'v2';
        $params['data']['request_id']   = $transId;
        $params['data']['from_uid']     = $waasCryptoUid;
        $params['data']['to_address']   = $accNum;
        $params['data']['amount']       = $cryptolOrder['transfered_crypto'];
        $params['data']['symbol']       = $this->getSystemInfo('crypto_currency');
        $this->CI->utils->debug_log('======================================waas_usdt submitWithdrawRequest params: ', $params);
        $params['data'] = $this->encrypt(json_encode($params['data']), $this->getPrivKey());
        return $params;
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($params) {
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $this->utils->debug_log('=====================WAAS_USDT_WITHDRAWAL check request params', $params);
        $decrypt_check_request_params = json_decode($this->decrypt($params['data'], $this->getPubKey()), true);
        $this->utils->debug_log('=====================WAAS_USDT_WITHDRAWAL decrypt check request params', $decrypt_check_request_params);

        if (isset($decrypt_check_request_params['request_id']) && isset($decrypt_check_request_params['request_id'])) {
            $trans_id = $decrypt_check_request_params['request_id'];
            $this->CI->load->model(array('wallet_model'));
            $walletAccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($trans_id);
            if(!empty($walletAccount)){
                $transId = $walletAccount['transactionCode'];
                return $transId;
            }else{
                $this->utils->debug_log('====================================WAAS_USDT_WITHDRAWAL callbackOrder transId is empty when getOrderIdFromParameters', $params);
            }
        }
        else {
            $this->utils->debug_log('=====================WAAS_USDT_WITHDRAWAL callbackOrder cannot get any order_id when getOrderIdFromParameters', $params);
            return;
        }
    }

    public function checkWithdrawStatus($transId) {
        $params = array();
        $params['app_id'] = $this->getSystemInfo('account');
        $params['data']['time']         = time();
        $params['data']['charset']      = 'utf-8';
        $params['data']['version']      = 'v2';
        $params['data']['ids']          = $transId;

        $params['data'] = $this->encrypt(json_encode($params['data']), $this->getPrivKey());

        $url = $this->getSystemInfo('check_withdraw_status_url', 'https://openapi.hicoin.vip/api/v2/billing/withdrawList');

        list($content, $response_result) = $this->submitGetForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content, true);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================waas checkWithdrawStatus params: ', $params);
        $this->CI->utils->debug_log('======================================waas checkWithdrawStatus url: ', $url );
        $this->CI->utils->debug_log('======================================waas_usdt submitWithdrawRequest content', $content);
        $this->CI->utils->debug_log('======================================waas_usdt submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function callbackFromServer($transId, $params) {
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }


        $decrypt_check_request_params = json_decode($this->decrypt($params['data'], $this->getPubKey()), true);
        $this->CI->utils->debug_log("=====================WAAS_USDT_WITHDRAWAL callbackFromServer params", $params);
        $this->utils->debug_log('=====================WAAS_USDT callbackFromServer decrypt params', $decrypt_check_request_params);

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->utils->debug_log('==========================WAAS_USDT_WITHDRAWAL process withdrawalResult order id', $transId);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $decrypt_check_request_params)) {
            return $result;
        }

        if(isset($decrypt_check_request_params['check_sum']) && !empty($decrypt_check_request_params['check_sum'])){
            $encrypt_check_request_data['time']      = time();
            $encrypt_check_request_data['check_sum'] = $decrypt_check_request_params['check_sum'];
            $return_encrypt_params['data']   = $this->encrypt(json_encode($encrypt_check_request_data), $this->getPrivKey());
            $return_msg = json_encode($return_encrypt_params);
            $result['message'] = $return_msg;
            $result['success'] = true;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array('check_sum', 'request_id', 'from_uid', 'amount');
        $cryptolOrder = $this->CI->wallet_model->getCryptoWithdrawalOrderById($order['walletAccountId']);

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================25ypay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['amount'] != $cryptolOrder['transfered_crypto']){
            $this->writePaymentErrorLog('======================WAASusdt withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $cryptolOrder['transfered_crypto'], $fields);
            return false;
        }

        if ($fields['request_id'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================25ypay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

}