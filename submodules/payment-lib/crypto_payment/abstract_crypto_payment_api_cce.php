<?php
require_once APPPATH . '/libraries/crypto_payment/abstract_crypto_payment_api.php';

/**
 * CCE
 *
 * @category CryptoPayment
 * @copyright 2013-2023 tot
 *
 * @see Abstract_payment_api_bibao_otc
 */
abstract class Abstract_crypto_payment_api_cce extends Abstract_crypto_payment_api
{
    const API_SUCCESS = '0000';
    const ORDER_STATUS_SUCCESS = '1';

    public function getPlatformCode()
    {
        return CCE_CRYPTO_PAYMENT_API;
    }

    public function directPay($order = null)
    {
        return array('success' => false);
    }

    public function processHeaders()
    {
        $headers = array(
            "content-type: Content-Type: application/json"
        );

        $this->_custom_curl_header = $headers;
    }

    public function getInputGetAndPost()
    {
        $params = $this->CI->input->readJsonOnce();

        if (empty($params)) {
            return null;
        }

        $required = ['client_id', 'sign_method', 'body'];
        $params_keys = array_keys($params);
        if (count(array_intersect($required, $params_keys)) !== count($required)) {
            return null;
        }

        /** @var \payment\crypto_payment\cce\entities\CallbackBody */
        $body = $this->decryptData($params['body'], $params['sign_method']);
        if (empty($body)) {
            return null;
        }

        $required_body = ['serie_no', 'opt_type', 'mny_smb', 'mny_count', 'state', 'cust_no'];
        $body_keys = array_keys(get_object_vars($body));
        if (count(array_intersect($required_body, $body_keys)) !== count($required_body)) {
            return null;
        }

        $params['body'] = $body;

        return $params;
    }

    public function getOrderIdFromParameters($params)
    {
        if (empty($params)) {
            return null;
        }

        /** @var \payment\crypto_payment\cce\entities\CallbackBody */
        $body = $params['body'];
        $this->CI->load->library(array('playerapi_lib'));
        $currency_db_key = $this->getTargetCurrencyForCCE($params);
        if(!empty($currency_db_key)){
            $result = $this->CI->playerapi_lib->switchCurrencyForAction($currency_db_key, function() use ($body) {
                switch ($body->opt_type) {
                    case '充值':
                        return $this->_createSaleOrder($body);
                        break;
                    case '提币':
                        return $body->serie_no;
                        break;
                    default:
                        return null;
                        break;
                }
            });
            return $result;
        }else{
            return null;
        }
    }

    public function getTargetCurrencyFromFixProcess($params){
        return $this->getTargetCurrencyForCCE($params);
    }

    private function getTargetCurrencyForCCE($params){
        $target_db_key = $this->getSystemInfo('targetFaitDB');
        $body = $params['body'];
        if(!empty($target_db_key) && isset($target_db_key[$body->mny_smb])){
            return $target_db_key[$body->mny_smb];
        }else{
            return null;
        }
    }

    /**
     * @param \payment\crypto_payment\cce\entities\CallbackBody $body
     * @return null|int
     */
    protected function _createSaleOrder($body)
    {
        $playerId = $this->parse_uid($body->cust_no);
        $player = $this->CI->player_model->getPlayerById($playerId);
        $targetDB = $this->CI->utils->getActiveTargetDB();
        
        $this->CI->utils->debug_log(__METHOD__, '_____createSaleOrder target db',$body->mny_smb,  $targetDB );

        if (empty($player)) {
            return null;
        }

        $order = $this->CI->sale_order->getSaleOrderByExternalOrderId($body->tx_hash);
        if(!empty($order)) {
            return $order->id;
        }

        return $this->createSaleOrder($playerId, $body->mny_count);
    }

    public function isOrderExpired($oderId)
    {
        return false;
    }

    public function callbackException($params)
    {
        return [
            'success' => false,
            'return_error_json' => [
                'code' => '9999'
            ]
        ];
    }

    public function callbackFromServer($orderId, $callbackExtraInfo)
    {
        $response_result_id = parent::callbackFromServer($orderId, $callbackExtraInfo);

        $result = [
            'success' => false,
            'json_result' => [
                'code' => '9999'
            ]
        ];

        /** @var \payment\crypto_payment\cce\entities\CallbackBody */
        $body = $callbackExtraInfo['body'];

        switch ($body->opt_type) {
            case '充值':
                $this->_callbackDepositOrder($result, $orderId, $body, $response_result_id);
                break;
            case '提币':
                $this->_callbackWithdrawalOrder($result, $orderId, $body, $response_result_id);
                break;
        }

        return $result;
    }

    /**
     * Undocumented function
     *
     * @param array $result
     * @param string $orderId
     * @param \payment\crypto_payment\cce\entities\CallbackBody $body
     * @param int $response_result_id
     * @return void
     */
    protected function _callbackDepositOrder(&$result, $orderId, $body, $response_result_id)
    {
        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);

        if ($orderStatus == Sale_order::STATUS_SETTLED) {
            $result['success'] = true;
            $result['json_result']['code'] = '0000';
            return;
        }

        $this->CI->sale_order->updateExternalInfo($orderId, $body->tx_hash, null, null, null, $response_result_id);
        if ($body->state != '1') {
            $result['success'] = false;
            $result['json_result']['code'] = '9999';
        } else {
            $this->approveSaleOrder($orderId, 'auto server callback ' . $this->getPlatformCode(), false);

            $result['success'] = true;
            $result['json_result']['code'] = '0000';
        }
    }

    /**
     * Undocumented function
     *
     * @param array $result
     * @param string $transId
     * @param \payment\crypto_payment\cce\entities\CallbackBody $body
     * @param int $response_result_id
     * @return void
     */
    protected function _callbackWithdrawalOrder(&$result, $transId, $body, $response_result_id)
    {
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $orderStatus = $this->CI->wallet_model->getWalletAccountStatus($order['walletAccountId']);

        if ($orderStatus == Wallet_model::PAID_STATUS) {
            $result['success'] = true;
            $result['json_result']['code'] = '0000';
            return;
        }

        if (!$this->checkCallbackOrder($order, $body)) {
            $result['success'] = false;
            $result['json_result']['code'] = '9999';
            return;
        }

        if ($body->state == self::ORDER_STATUS_SUCCESS) {
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, 'auto server success callback ' . $this->getPlatformCode());
            $result['success'] = true;
            $result['json_result']['code'] = '0000';
        } else {
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, 'auto server failure callback ' . $this->getPlatformCode());
            $result['success'] = false;
            $result['json_result']['code'] = '9999';
        }
    }

    public function checkCallbackOrder($order, $params)
    {
        if ($params->mny_count != $order['amount']){
            $this->writePaymentErrorLog('======================cce withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $params);
            return false;
        }

        if ($params->serie_no != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================cce withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $params);
            return false;
        }
        # everything checked ok
        return true;
    }

    public function encryptData($data, $sign_method = 'des')
    {
        switch (strtolower($sign_method)) {
            case 'unsigned':
                return $data;
                break;
            case 'des':
            default:
                $passphrase = $this->getSystemInfo('secret');
                // $iv = $this->getSystemInfo('secret');
                $result = openssl_encrypt(json_encode($data), 'DES-ECB', $passphrase, OPENSSL_RAW_DATA);
                return base64_encode($result);
                break;
        }
    }

    public function decryptData($data, $sign_method = 'des')
    {
        switch (strtolower($sign_method)) {
            case 'unsigned':
                return $data;
                break;
            case 'des':
            default:
                $passphrase = $this->getSystemInfo('secret');
                $iv = $this->getSystemInfo('secret');
                $result = openssl_decrypt(base64_decode($data), 'DES-ECB', $passphrase, OPENSSL_RAW_DATA);
                return json_decode($result);
                break;
        }
    }

    public function makePostData($request_type, $payload, $sign_method = 'des')
    {
        $data = [
            'client_id' => $this->getSystemInfo('account'),
            'request_type' => $request_type,
            'sign_method' => $sign_method,
            'signature' => null,
            'charset' => 'utf-8',
            'message_no' => 'M' . floor(microtime(true) * 1000),
            'request_time' => time(),
            'body' => null
        ];

        $data['body'] = $this->encryptData($payload, $data['sign_method']);

        return $data;
    }

    private function __process_api_response($body)
    {
        $json = json_decode($body, true);

        if (!is_array($json)) {
            return [false, null];
        }

        if (isset($json['body'])) {
            $json['body'] = $this->decryptData($json['body']);
            if (!is_array($json['body'])) {
                $json['body'] = json_decode($json['body']);
            }
        }

        if (!is_object($json['body'])) {
            return [false, $json];
        }

        if (!property_exists($json['body'], 'code')) {
            return [false, $json];
        }

        if ($json['body']->code != self::API_SUCCESS || !empty((int)$json['body']->code)) {
            return [false, $json];
        }

        return [true, $json];
    }

    public function formate_uid($player_id)
    {
        return $this->getSystemInfo('account') . sprintf("%032d", $player_id);
    }

    public function parse_uid($uid)
    {
        return (int)substr($uid, strlen($this->getSystemInfo('account')));
    }

    /**
     * API CreateAccount function
     *
     * @param string $player_id
     * @param string $symbol
     * @return null|\payment\crypto_payment\cce\entities\CreateAccount
     */
    public function api_createAccount($player_id, $symbol = 'ETH.USDT')
    {
        $url = $this->getSystemInfo('url');
        // $url = 'http://10.117.11.200:8000/dex/rou/pbjso.do';

        $payload = [
            'serie_no' => 'S' . floor(microtime(true) * 1000),
            'mny_smb' => $symbol,
            'cust_no' => $this->formate_uid($player_id),
        ];
        $encryptData = $this->makePostData('createAccount', $payload);
        $this->processHeaders();
        $body = $this->submitPostForm($url, $encryptData, true);

        list($return_state, $json) = $this->__process_api_response($body);

        if (!$return_state) {
            $this->utils->debug_log(__METHOD__ . '(): call failure', $json);
            return null;
        }

        /** @var \payment\crypto_payment\cce\entities\CreateAccount $data */
        $data = $json['body'];

        return $data;
    }

    /**
     * API GetBalance function
     *
     * @param string $address
     * @param string $symbol
     * @return null|\payment\crypto_payment\cce\entities\GetBalance
     */
    public function api_getBalance($address, $symbol = 'ETH.USDT')
    {
        $url = $this->getSystemInfo('url');
        // $url = 'http://10.117.11.200:8000/dex/rou/pbjso.do';

        $payload = [
            'serie_no' => 'S' . floor(microtime(true) * 1000),
            'mny_smb' => $symbol,
            'cust_address' => $address
        ];
        $encryptData = $this->makePostData('getBalance', $payload);
        $this->processHeaders();
        $body = $this->submitPostForm($url, $encryptData, true);

        list($return_state, $json) = $this->__process_api_response($body);

        if (!$return_state) {
            $this->utils->debug_log(__METHOD__ . '(): call failure', $json);
            return null;
        }

        /** @var \payment\crypto_payment\cce\entities\GetBalance $data */
        $data = $json['body'];

        return $data;
    }

    /**
     * API transferOutCoinlogs function
     *
     * @param string $player_id
     * @param string $symbol
     * @return null|\payment\crypto_payment\cce\entities\TransferOutCoinlogs
     */
    public function api_transferOutCoinlogs($orderId, $player_id, $amount, $cust_address, $symbol = 'ETH.USDT')
    {
        $url = $this->getSystemInfo('url');
        // $url = 'http://10.117.11.200:8000/dex/rou/pbjso.do';

        $symbol = strtoupper($symbol);
        $gas = $this->getSystemInfo('gas', [
            'ETH.USDT' => '0',
            'TRX.USDT' => '0',
        ]);

        $gas = (is_array($gas)) ? (isset($gas[$symbol]) ? $gas[$symbol] : -1) : $gas;
        if($gas === -1) {
            return null;
        }

        $payload = [
            'serie_no' => $orderId,
            'cust_no' => $this->formate_uid($player_id),
            'mny_smb' => $symbol,
            'mny_count' => (string)$amount,
            'gas' => (string)$gas,
            'cust_address' => $cust_address
        ];
        $encryptData = $this->makePostData('transferOutCoinlogs', $payload);
        $this->utils->debug_log(__METHOD__ . 'CCE api_transferOutCoinlogs', $payload);
        $this->processHeaders();
        $body = $this->submitPostForm($url, $encryptData, true, $orderId);

        list($return_state, $json) = $this->__process_api_response($body);

        if (!$return_state) {
            $this->utils->debug_log(__METHOD__ . '(): call failure', $json);
            return null;
        }

        /** @var \payment\crypto_payment\cce\entities\TransferOutCoinlogs $data */
        $data = $json['body'];

        return $data;
    }

    /**
     * API searchHistory function
     *
     * @param string $address
     * @param string $start_time Y-m-d H:i:s
     * @param string $end_times Y-m-d H:i:s
     * @param string $status 0: all, 1: success, 2: fail, 3: pending
     * @param int $page
     * @param limit $limit default: 100
     * @param string $trans_type 0: all, 1: recharge, 2: withdraw, 3: manual
     * @return \payment\crypto_payment\cce\entities\HistoryEntity[]
     */
    public function api_searchHistory($address, $start_time = null, $end_times = null, $status = '0', $page = 1, $limit = 100, $trans_type = '0')
    {
        $url = $this->getSystemInfo('url');
        // $url = 'http://10.117.11.200:8000/dex/rou/pbjso.do';

        $payload = [
            'serie_no' => 'S' . floor(microtime(true) * 1000),
            'cust_address' => $address,
            'start_time' => (empty($start_time)) ? date('Y-m-d 00:00:00') : $start_time,
            'end_time' => (empty($end_times)) ? date('Y-m-d 23:59:59') : $end_times,
            'status' => (string)$status,
            'page' => (string)$page,
            'limit' => (string)$limit,
            'trans_type' => (string)$trans_type
        ];
        $encryptData = $this->makePostData('searchHistory', $payload);
        $this->processHeaders();
        $body = $this->submitPostForm($url, $encryptData, true);

        list($return_state, $json) = $this->__process_api_response($body);

        if (!$return_state) {
            $this->utils->debug_log(__METHOD__ . '(): call failure', $json);
            return [];
        }

        /** @var \payment\crypto_payment\cce\entities\SearchHistory $data */
        $data = $json['body'];

        return $data->records;
    }

    /**
     * API GetCoinInfo function
     *
     * @param string $symbol
     * @return null|\payment\crypto_payment\cce\entities\CoinInfo
     */
    public function api_getCoinInfo($symbol = 'ETH.USDT')
    {
        $url = $this->getSystemInfo('url');
        // $url = 'http://10.117.11.200:8000/dex/rou/pbjso.do';

        $payload = [
            'serie_no' => 'S' . floor(microtime(true) * 1000),
            'mny_smb' => $symbol,
        ];
        $encryptData = $this->makePostData('getCoinInfo', $payload);
        $this->processHeaders();
        $body = $this->submitPostForm($url, $encryptData, true);

        list($return_state, $json) = $this->__process_api_response($body);

        if (!$return_state) {
            $this->utils->debug_log(__METHOD__ . '(): call failure', $json);
            return null;
        }

        /** @var \payment\crypto_payment\cce\entities\CoinInfo $data */
        $data = $json['body'];

        return $data;
    }

    public function getAddress($player_id, $chain_id, $token)
    {
        $result = null;

        $chain_id = strtoupper($chain_id);
        $token = strtoupper($token);

        $symbol = '';
        switch ($chain_id) {
            case CRYPTO_CURRENCY_CHAIN_BTC:
                $symbol = $chain_id;
                break;
            default:
                $symbol = ($chain_id === $token) ? $chain_id : $chain_id . '.' . $token;
                break;
        }

        $result = $this->api_createAccount($player_id, $symbol);

        if (empty($result)) {
            return null;
        }

        return $result->cust_address;
    }

    public function submitWithdrawRequest($bank, $address, $name, $amount, $transId)
    {
        $result = array('success' => false, 'message' => 'payment failed');

        $this->utils->debug_log(__METHOD__ . 'CCE submitWithdrawRequest', $bank, $address, $name, $amount, $transId);

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $cryptolOrder = $this->CI->wallet_model->getCryptoWithdrawalOrderById($order['walletAccountId']);
        if(empty($order) && empty($cryptolOrder)){
            $result['success'] = false;
            $result['message'] = 'withdraw order or crypto oreder are not exisst.';
            return $result;
        }

        if(empty($order['bankBranch'])){
            $result['success'] = false;
            $result['message'] = 'the address does not indicate chain name.';
            return $result;
        }else{
            $symbol = "{$order['bankBranch']}.{$cryptolOrder['crypto_currency']}";
        }

        $response = $this->api_transferOutCoinlogs($transId, $order['playerId'], $amount, $address, $symbol);

        if(!is_null($response)){
            $decodedResult = $this->decodeResult($response);
            if($decodedResult['success']) {
                $message = "cce withdrawal response successful, transaction ID:".$transId;
                $result['success'] = true;
                $result['message'] = $message;
            }
        }
        return $result;
    }

    public function decodeResult($resultString, $queryAPI = false)
    {
        $this->utils->debug_log(__METHOD__ . 'decodeResult', $resultString);
        if(property_exists($resultString, 'code') && $resultString->code == self::API_SUCCESS){
            $result['success'] = true;
        }else{
            $result['success'] = false;
        }
        return $result;
    }
}
