<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * WAAS_USDT
 *
 * * WAAS_USDT_PAYMENT_API, ID: 6010
 * * WAAS_USDT_BSC_PAYMENT_API, ID: 6037
 * * WAAS_USDT_ERC_PAYMENT_API, ID: 6038
 * * WAAS_USDT_WITHDRAWAL_PAYMENT_API, ID: 6011
 * * WAAS_USDT_ERC_WITHDRAWAL_PAYMENT_API, ID: 6039
 * * WAAS_USDT_BSC_WITHDRAWAL_PAYMENT_API, ID: 6040
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Key: ## Terminal ID##
 * * URL: https://api.coinopayment.com/api/v1/pay
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_waas_usdt extends Abstract_payment_api {

    const RESULT_CODE_SUCCESS = '0';
    const CALLBACK_SUCCESS = '1';
    const CALLBACK_WITHDRAWAL_CREATE      = '0';
    const CALLBACK_WITHDRAWAL_CHECK_SUCC  = '1';
    const CALLBACK_WITHDRAWAL_REJECT      = '2';
    const CALLBACK_WITHDRAWAL_CHECK_PAYED = '3';
    const CALLBACK_WITHDRAWAL_FAILED      = '4';
    const CALLBACK_WITHDRAWAL_SUCCESS     = '5';
    const CALLBACK_WITHDRAWAL_CANCEL      = '6';
    const RETURN_SUCCESS_CODE             = 'SUCCESS';

    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
        $waasCryptoOrder = $this->CI->sale_order->getSaleOrderByPlayerId($playerId);
        $waasCryptoUid = '';
        $email = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : $order->secure_id.'@waas.com';
        foreach ($waasCryptoOrder as $key => $value) {
            if(!empty($value['bank_order_id']) && ($value['system_id'] == '6010' || $value['system_id'] == '6037' || $value['system_id'] == '6038')){
                $waasCryptoUid = $value['bank_order_id'];
            }
        }

        $params = array();
        $params['data']['time']     = time();
        $params['data']['charset']  = 'utf-8';
        $params['data']['vesion']   = 'v2';
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['data']['uid']      = $waasCryptoUid;
        $params['client_order_id']  = $order->secure_id;
        $params['email']            = $email;
        $params['amount']           = $amount;

        $this->CI->utils->debug_log('=====================WAAS_USDT generatePaymentUrlForm params', $params);
        return $this->processPaymentUrlForm($params);
    }

    public function handlePaymentFormResponse($params) {
        // step1 : if Uid is null , then get new uid
        $orderId = $params['client_order_id'];
        $crypto  = $params['crypto_amount'];
        $amount  = $params['amount'];
        $isPCFApi = !empty($params['is_pcf_api']) ? $params['is_pcf_api'] : false;
        unset($params['is_pcf_api']);

        $validateRateResult = $this->validateDepositCryptoRate('USDT', $amount, $crypto, $isPCFApi);
        $this->CI->utils->debug_log('=====================WAAS_USDT validateRateResult', $validateRateResult);

        if(!$validateRateResult['status']){
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $validateRateResult['msg']
            );
        }elseif($validateRateResult['rate'] != 0){
            $rate = $validateRateResult['rate'];
        }else{
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'crypto rate has errors'
            );
        }
        if(!empty($crypto) && !empty($rate) && $crypto != 0 && $rate != 0){
            if(empty($params['data']['uid'])){
                $register_params['app_id'] = $this->getSystemInfo('account');
                $encrypt_register_data['time']      = time();
                $encrypt_register_data['charset']   = 'utf-8';
                $encrypt_register_data['version']   = 'v2';
                $encrypt_register_data['email']     = $params['email'];
                $register_params['data']   = $this->encrypt(json_encode($encrypt_register_data), $this->getPrivKey());
                $this->CI->utils->debug_log('=====================waas email request', $register_params['data']);
                $response_register_email = json_decode($this->submitPostForm($this->getSystemInfo('register_email_url'), $register_params, false, $orderId), true);
                $decrypt_response_register_email = json_decode($this->decrypt($response_register_email['data'], $this->getPubKey()), true);
                $this->CI->utils->debug_log('=====================waas register_email_url response', $decrypt_response_register_email);

                if(isset($decrypt_response_register_email) && !empty($decrypt_response_register_email)){
                    if(isset($decrypt_response_register_email['data']['uid']) &&  !empty($decrypt_response_register_email['data']['uid'])){
                        $params['data']['uid'] = $decrypt_response_register_email['data']['uid'];
                    }else{
                        if(isset($decrypt_response_register_email['msg']) && !empty($decrypt_response_register_email['msg'])) {
                            $msg = $decrypt_response_register_email['msg'];
                        }else{
                            $msg = 'register uid failed';
                        }
                        return array(
                            'success' => false,
                            'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                            'message' => $msg
                        );
                    }
                }
            }
            // step2 : get address
            unset($params['client_order_id']);
            unset($params['amount']);
            unset($params['crypto_amount']);
            unset($params['email']);

            $encrypt_address_data['app_id'] = $this->getSystemInfo('account');
            $encrypt_address_data['data'] = $this->encrypt(json_encode($params['data']), $this->getPrivKey());
            $response_get_address = json_decode($this->submitPostForm($this->getSystemInfo('url'), $encrypt_address_data, false, $orderId), true);
            $decrypt_address_data = json_decode($this->decrypt($response_get_address['data'], $this->getPubKey()), true);

            $this->CI->utils->debug_log('=====================waas handlePaymentFormResponse response', $decrypt_address_data);
            if(isset($decrypt_address_data['code']) && $decrypt_address_data['code'] == self::RESULT_CODE_SUCCESS){
                $order = $this->CI->sale_order->getSaleOrderBySecureId($orderId);
                $this->CI->sale_order->updateExternalInfo($order->id, $decrypt_address_data['data']['address'], $decrypt_address_data['data']['uid'], $crypto);
                $this->CI->sale_order->createCryptoDepositOrder($order->id, $crypto, $rate, null, null,'USDT');
                $deposit_notes = '3rd Api Wallet address: '.$decrypt_address_data['data']['address'].' | '.' Real Rate: '.$rate . '|' . 'USDTcoin: ' . $crypto;
                $this->CI->sale_order->appendNotes($order->id, $deposit_notes);
                $cust_payment_data = array();
                $cust_payment_data['pay.sale_order_id'] = $orderId;
                $cust_payment_data['3rdParty USDT Deposit'] = $crypto;
                $cust_payment_data['financial_account.bankaccount.walletaddress'] = $decrypt_address_data['data']['address'];
                // $cust_payment_data['crypto network'] = $this->getSystemInfo('crypto_network');
                $cust_payment_data['collection.label.6'] = $order->created_at;
                $cust_payment_data['collection.label.7'] = $order->timeout_at;

                $collection_text_transfer = '';
                $collection_text = $this->getSystemInfo("collection_text_transfer", array(''));
                if(is_array($collection_text)){
                    $collection_text_transfer = $collection_text;
                }
                $is_not_display_recharge_instructions = $this->getSystemInfo('is_not_display_recharge_instructions');
                return array(
                        'success' => true,
                        'type' => self::REDIRECT_TYPE_STATIC,
                        'data' => $cust_payment_data,
                        'hide_timeout' => true,
                        'collection_text_transfer' => $collection_text_transfer,
                        'is_not_display_recharge_instructions' => $is_not_display_recharge_instructions
                    );
            }else {
                if(!empty($decrypt_address_data['msg'])) {
                    $msg = $decrypt_address_data['msg'];
                }else{
                    $msg = 'unknow errors';
                }

                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => $msg
                );
            }
        }else{
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'crypto rate has errors'
            );
        }
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($params) {
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $this->utils->debug_log('=====================WAAS_USDT callback params', $params);
        $decrypt_callback_params = json_decode($this->decrypt($params['data'], $this->getPubKey()), true);
        $this->utils->debug_log('=====================WAAS_USDT decrypt callback params', $decrypt_callback_params);

        if (isset($decrypt_callback_params['uid']) && isset($decrypt_callback_params['side'])) {
            $this->CI->load->model(array('sale_order','wallet_model'));
            if($decrypt_callback_params['side'] == 'deposit'){
                $order = $this->CI->sale_order->getLastSaleOrderByBankOrderId($decrypt_callback_params['uid']);
                if(!empty($order)){
                    return $order->id;
                }else{
                    $this->utils->debug_log('=====================WAAS_USDT callbackOrder cannot get any order_id when getOrderIdFromParameters', $decrypt_callback_params);
                    return;
                }
            }else if($decrypt_callback_params['side'] == 'withdraw'){
                if (isset($decrypt_callback_params['request_id']) && isset($decrypt_callback_params['request_id'])) {
                    $trans_id = $decrypt_callback_params['request_id'];
                    $this->CI->load->model(array('wallet_model'));
                    $walletAccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($trans_id);
                    if(!empty($walletAccount)){
                        $transId = $walletAccount['transactionCode'];
                        return $transId;
                    }else{
                        $this->utils->debug_log('====================================WAAS_USDT_WITHDRAWAL callbackOrder transId is empty when getOrderIdFromParameters', $params);
                        return;
                    }
                }
        else {
            $this->utils->debug_log('=====================WAAS_USDT callbackOrder cannot get any order_id when getOrderIdFromParameters', $params);
            return;
        }
            }else{
                $this->utils->debug_log('=====================WAAS_USDT callbackOrder cannot get any order_id when getOrderIdFromParameters', $decrypt_callback_params);
                return;
            }
        }
        else {
            $this->utils->debug_log('=====================WAAS_USDT callbackOrder cannot get any order_id when getOrderIdFromParameters', $params);
            return;
        }
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;
        if($source == 'server' ){
            if(empty($params) || is_null($params)){
                $raw_post_data = file_get_contents('php://input', 'r');
                $params = json_decode($raw_post_data, true);
            }

            $decrypt_callback_params = json_decode($this->decrypt($params['data'], $this->getPubKey()), true);

            $this->utils->debug_log('=====================WAAS_USDT callback decrypt params', $decrypt_callback_params);

            if (isset($decrypt_callback_params['uid']) && isset($decrypt_callback_params['side'])) {
                $this->CI->load->model(array('sale_order','wallet_model'));
                if($decrypt_callback_params['side'] == 'deposit'){
                    if(!$order || !$this->checkCallbackOrder($order, $decrypt_callback_params, $processed)){
                        return $result;
                    }
                }else if($decrypt_callback_params['side'] == 'withdraw'){
                    $result = $this->isWithdrawal($decrypt_callback_params, $decrypt_callback_params['request_id']);
                    $this->CI->utils->debug_log('=======================WAAS_USDT callbackFrom transaction_id', $params['transaction_id']);
                    return $result;
                }
            }
            else {
                $this->utils->debug_log('=====================WAAS_USDT callbackOrder cannot get any params when callbackFrom', $params);
                return;
            }
        }

        # Update order payment status and balance
        $success = true;

        # Update player balance based on order status
        # if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $decrypt_callback_params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            # update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, null, null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['return_error'] = 'Error';
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'status', 'address_to', 'amount', 'id', 'symbol', 'uid'
        );

        foreach($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================WAASusdt checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("=====================WAASusdt checkCallbackOrder Payment status is not credited", $fields);
            return false;
        }

        if ($fields['address_to'] != $order->external_order_id) {
            $this->writePaymentErrorLog("=====================WAASusdt checkCallbackOrder Payment address is wrong, expected [$order->external_order_id]", $fields);
            return false;
        }

        $symbols = array('6010'=>'TUSDT', '6037'=>'USDTBEP20', '6038'=>'USDTERC20');

        if(strpos($fields['symbol'], $symbols[$order->system_id]) === false){
            $this->writePaymentErrorLog("=====================WAASusdt checkCallbackOrder Payment symbol is wrong, expected symbol", $fields);
            return false;
        }

        $crypto_amount = $this->convertAmountToCrypto($order->id);
        if($crypto_amount == 0 || $fields['amount'] == 0){
            $this->writePaymentErrorLog("=====================WAASusdt Payment crypto amounts is null");
            return false;
        }

        if ($crypto_amount != $fields['amount']) {
            $this->writePaymentErrorLog("=====================WAASusdt Payment amounts do not match, expected ", $crypto_amount);
            return false;
        }

        # everything checked ok
        return true;
    }

    #For Withdrawal
    public function isWithdrawal($params, $orderId){
        $result = array('success' => false, 'message' => 'Payment failed');
        $processed = false;
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($orderId);
        if (!$this->checkCallbackWithdrawalOrder($order, $params, $processed)) {
            return $result;
        }

        $statusCode = $params['status'];
        if($statusCode == self::CALLBACK_WITHDRAWAL_SUCCESS) {
            $msg = "WAAS_USDT withdrawal success!";
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($orderId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('WAAS_USDT withdrawal payment was not successful  trade ID [%s] ',$params['transaction_id']);
            $this->utils->debug_log('=========================WAAS_usdt withdrawal payment was not successful  trade ID [%s]', $params['transaction_id']);
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackWithdrawalOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array(
            'status', 'request_id', 'amount', 'address_to'
        );

        $cryptolOrder = $this->CI->wallet_model->getCryptoWithdrawalOrderById($order['walletAccountId']);

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================WAASusdt withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        $symbols = array('6011'=>'TUSDT', '6040'=>'USDTBEP20', '6039'=>'USDTERC20');

        if(strpos($fields['symbol'], $symbols[$order['paymentAPI']]) === false){
            $this->writePaymentErrorLog("=====================WAASusdt withdrawal checkCallbackOrder Payment symbol is wrong, expected USDT", $fields);
            return false;
        }

        if($cryptolOrder['transfered_crypto'] == 0 || $fields['amount'] == 0){
            $this->writePaymentErrorLog("=====================WAASusdt withdrawal checkCallbackOrder Payment crypto amounts is null");
            return false;
        }

        if ($fields['amount'] != $cryptolOrder['transfered_crypto']){
            $this->writePaymentErrorLog('======================WAASusdt withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $cryptolOrder['transfered_crypto'], $fields);
            return false;
        }

        if ($fields['request_id'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('======================WAASusdt withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }


    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    protected function convertAmountToCrypto($orderId) {
        $cryptoAmount = 0;
        $cryptoOrder = $this->CI->sale_order->getCryptoDepositOrderBySaleOrderId($orderId);
        if(empty($cryptoOrder)){
            return $cryptoAmount;
        }else{
            $cryptoAmount = $cryptoOrder->received_crypto;
            $this->CI->utils->debug_log("=======================WAASusdt convertAmountToCrypto,orderId",$cryptoAmount,$orderId);
            return $cryptoAmount;
        }
    }

    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    # Returns public key given by gateway
    public function getPubKey() {
        $waas_pub_key = $this->getSystemInfo('waas_pub_key');

        $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($waas_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($pub_key);
    }

    # Returns the private key generated by merchant
    public function getPrivKey() {
        $waas_priv_key = $this->getSystemInfo('waas_priv_key');

        $priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($waas_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($priv_key);
    }

    public function decode($string) {
        $data = str_replace(array('-','_'),array('+','/'),$string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }

    public function encode($string) {
        $data = base64_encode($string);
        $data = str_replace(array('+','/','='),array('-','_',''),$data);
        return $data;
    }

    public function encrypt($str, $privateKey) {
        $crypted = array();
        $data = $str;
        $dataArray = str_split($data, 234);
        foreach($dataArray as $subData){
            $subCrypted = null;
            openssl_private_encrypt($subData, $subCrypted, $privateKey);
            $crypted[] = $subCrypted;
        }
        $crypted = implode('',$crypted);
        return $this->encode($crypted);
    }

    public function decrypt($encryptstr, $publickKey) {
        $encryptstr = $this->decode($encryptstr);
        $decrypted = array();
        $dataArray = str_split($encryptstr, 256);

        foreach($dataArray as $subData){
            $subDecrypted = null;
            openssl_public_decrypt($subData, $subDecrypted, $publickKey);
            $decrypted[] = $subDecrypted;
        }
        $decrypted = implode('',$decrypted);
        return $decrypted;
    }

    public function getSymbol(){
        $params['time'] = time();
        $params['charset'] = 'utf-8';
        $params['version'] = 'v2';
        $getSymbol_params['app_id'] = $this->getSystemInfo('account');

        $getSymbol_params['data']   = $this->encrypt(json_encode($params), $this->getPrivKey());
        $this->CI->utils->debug_log('=====================waas getSymbol', $getSymbol_params['data']);
        $response_getSymbol = json_decode($this->submitPostForm('https://openapi.hicoin.vip/api/v2/user/getCoinList', $getSymbol_params, false, null), true);

        $decrypt_response_register_email = json_decode($this->decrypt($response_getSymbol['data'], $this->getPubKey()), true);

        var_dump($decrypt_response_register_email);
        die();
    }

    public function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if(($key == 'sign')){
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr = rtrim($extInfoSignStr, '&');
        $signStr .= $this->getSystemInfo('key');
        $sign = md5($signStr);
        if($params['sign'] == $sign){
            return true;
        }
        else{

            return false;
        }
    }
}