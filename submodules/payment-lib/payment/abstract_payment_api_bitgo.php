<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * BITGO
 *
 * * BITGO_BTC_PAYMENT_API, ID: 5081
 * *
 * Required Fields:
 * * URL
 * * wallet_id
 * * token
 * * address
 *
 * Field Values:
 * * URL: http://localhost:3080/api/v2/

 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_bitgo extends Abstract_payment_api {
    const COIN_BTC = "btc";
    const COIN_ETH = "eth";
    const CALLBACK_TYPE_RECEIVE = "receive";
    const CALLBACK_TYPE_SEND    = "send";
    const CALLBACK_SUCCESS      = "confirmed";
    const STATUS_FAILED         = "removed";
    const RETURN_SUCCESS_CODE   = 'success';
    const SATOSHI = 100000000;
    const WEI = 1000000000000000000;

    public $coin;
    public $wallet_id;
    public $token;
    public $password;

    public function __construct($params = null) {
        parent::__construct($params);
        $this->coin         = $this->getCoin();
        $this->wallet_id    = $this->getSystemInfo('wallet_id');
        $this->password     = $this->getSystemInfo('password');
        $this->token        = $this->getSystemInfo('token');
        $this->confirmation = $this->getSystemInfo('confirmation', 6);
    }

    # Implement these to specify pay type
    protected abstract function getCoin();
    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {}
    protected function handlePaymentFormResponse($handle) {}
    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $raw_post_data = file_get_contents('php://input', 'r');
        $flds = json_decode($raw_post_data, true);
        $this->CI->utils->debug_log('=====================bitgo getOrderIdFromParameters raw_post_data', $raw_post_data);
        $this->CI->utils->debug_log('=====================bitgo getOrderIdFromParameters json_decode flds', $flds);

        if(isset($flds['transfer'])) {

            $txid = $flds['transfer'];
            $this->utils->debug_log('=====================bitgo getOrderIdFromParameters get transfer id', $txid);

            #----Get Transaction----
            #api/v2/:coin/wallet/:walletId/transfer/:id
            $get_transaction_url = $this->getSystemInfo('url').$this->coin.'/wallet/'.$this->wallet_id.'/transfer/'.$txid;
            $response = $this->processCurl($get_transaction_url, $this->token, null, null, false);
            if($response){
                if(isset($response['error'])){
                    $this->utils->debug_log('=====================bitgo getOrderIdFromParameters get_transaction error', $response);
                    return;
                }

                #deposit
                if($response['type'] == self::CALLBACK_TYPE_RECEIVE){
                    foreach($response['entries'] as $entry) {
                        if(isset($entry['wallet'])){
                            $address = $entry['address'];
                            break;
                        }
                    }
                    $this->utils->debug_log('=====================bitgo getOrderIdFromParameters deposit address', $address);
                    $order = $this->CI->sale_order->getSaleOrderByExternalOrderId($address);
                    if(is_null($order)){
                        $this->utils->debug_log('=====================bitgo getOrderIdFromParameters cannot find order by address', $address);
                        return;
                    }
                    return $order->id;
                }
                else if($response['type'] == self::CALLBACK_TYPE_SEND){
                    $order = $this->CI->wallet_model->getWalletAccountByExtraInfo($txid);
                    if(is_null($order)){
                        $this->utils->debug_log('=====================bitgo getOrderIdFromParameters cannot find order by txid', $txid);
                        return;
                    }
                    return $order['transactionCode'];
                }
            }
            else{
                $this->utils->debug_log('=====================bitgo getOrderIdFromParameters cannot get_transaction, response', $response);
                return;
            }
        }
        else {
            $this->utils->debug_log('=====================bitgo getOrderIdFromParameters cannot get any transfer from webhook params', $flds);
            return;
        }
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));

        #check deposit or withdrawal
        if (!empty($orderId)) {
            if(substr($orderId, 0, 1) == 'W') {
                $order     = $this->CI->wallet_model->getWalletAccountByTransactionCode($orderId);
                $type      = self::CALLBACK_TYPE_SEND;
                $secure_id = $order['transactionCode'];
            }
            else{
                $order     = $this->CI->sale_order->getSaleOrderById($orderId);
                $type      = self::CALLBACK_TYPE_RECEIVE;
                $secure_id = $order->secure_id;
            }
        }

        $processed = false;

        $raw_post_data = file_get_contents('php://input', 'r');
        $flds = json_decode($raw_post_data, true);
        $this->CI->utils->debug_log('=====================callbackFromServer raw_post_data', $raw_post_data);
        $this->CI->utils->debug_log('=====================callbackFromServer json_decode flds', $flds);


        if(isset($flds['transfer'])) {
            $txid      = $flds['transfer'];
            $this->utils->debug_log('=====================callbackFromServer get transfer id', $txid);

            $coin      = $this->coin;
            $wallet_id = $this->wallet_id;
            $token     = $this->token;

            #----Get Transaction----
            #api/v2/:coin/wallet/:walletId/transfer/:id
            $get_transaction_url = $this->getSystemInfo('url').$coin.'/wallet/'.$wallet_id.'/transfer/'.$txid;
            $response = $this->processCurl($get_transaction_url, $token, null, $secure_id, false);
            if(isset($response['error'])){
                $this->utils->debug_log('=====================callbackFromServer get_transaction error', $response);
                $result['message'] = lang('Bitgo Get Transaction Failed').': ['.$response['name'].']'.$response['error'];
                return $result;
            }

            $validate['confirmations'] = $response['confirmations'];
            $validate['value']         = abs($response['value']);
            $validate['usd']           = abs($response['usd']);
            $validate['state']         = $response['state'];


            #check more params when receive
            if($type == self::CALLBACK_TYPE_RECEIVE){
                #----Get Address----
                #api/v2/:coin/wallet/:walletId/transfer/:id
                $get_address_url = $this->getSystemInfo('url').$coin.'/wallet/'.$wallet_id.'/address/'.$order->external_order_id;
                $response = $this->processCurl($get_address_url, $token, null, $secure_id, false);
                if(isset($response['error'])){
                    $this->utils->debug_log('=====================callbackFromServer get_transaction error', $response);
                    $result['message'] = lang('Bitgo Get Transaction Failed').': ['.$response['name'].']'.$response['error'];
                    return $result;
                }
                $validate['label'] = $response['label'];
                if (!$order || !$this->checkCallbackOrder($order, $validate, $processed)) {
                    return $result;
                }
            }
            else if($type == self::CALLBACK_TYPE_SEND){
                if (!$order || !$this->checkCallbackTransaction($order, $validate)) {
                    return $result;
                }
            }
        }


        if($type == self::CALLBACK_TYPE_RECEIVE){
            # Update player balance based on order status
            # if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
            if ($order->status == Sale_order::STATUS_BROWSER_CALLBACK || $order->status == Sale_order::STATUS_SETTLED) {
                $this->CI->utils->debug_log('callbackFromServer already get callback for order:' . $order->id, $response);
            } else {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }

            $result['success'] = true;
            if ($processed) {
                $result['message'] = self::RETURN_SUCCESS_CODE;
            } else {
                $result['return_error'] = 'Error';
            }
        }
        else if($type == self::CALLBACK_TYPE_SEND){
            if ($response['state'] == self::CALLBACK_SUCCESS) {
                $msg = sprintf('Bitgo withdrawal success: bitgo transfer ID [%s]', $response['id']);
                $this->CI->wallet_model->withdrawalAPIReturnSuccess($orderId, $msg);

                $result['message'] = self::RETURN_SUCCESS_CODE;
                $result['success'] = true;
            }
            else if ($response['state'] == self::STATUS_FAILED) {
                $msg = sprintf('Bitgo withdrawal failed.');
                $this->writePaymentErrorLog($msg, $fields);
                $this->CI->wallet_model->withdrawalAPIReturnFailure($orderId, $msg);
                $result['message'] = $msg;
            }
            else {
                $msg = sprintf('Bitgo withdrawal payment was not successful: [%s]', $response['state']);
                $this->writePaymentErrorLog($msg, $fields);
                $result['message'] = $msg;
            }
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'state', 'confirmations', 'value', 'label'
        );

        foreach($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================bitgo checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass


        if ($fields['state'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("=====================bitgo checkCallbackOrder Payment status is not confirmed", $fields['state']);
            return false;
        }

        if ($fields['confirmations'] < $this->confirmation) {
            $this->writePaymentErrorLog("=====================bitgo checkCallbackOrder Payment confirmations less than ".$this->confirmation, $fields['confirmations']);
            return false;
        }

        if ($fields['value'] != $order->status_payment_gateway) {
            $this->writePaymentErrorLog("=====================bitgo checkCallbackOrder Payment amount is wrong, expected [$order->status_payment_gateway]", $fields['value']);
            return false;
        }

        if ($fields['label'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================bitgo checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields['label']);
            return false;
        }

        # everything checked ok
        return true;
    }

    private function checkCallbackTransaction($order, $fields) {
        $requiredFields = array(
            'state', 'confirmations', 'value', 'usd'
        );

        foreach($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================bitgo checkCallbackTransaction Missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['state'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("=====================bitgo checkCallbackTransaction Payment status is not confirmed", $fields['state']);
            return false;
        }

        if ($fields['confirmations'] < $this->confirmation) {
            $this->writePaymentErrorLog("=====================bitgo checkCallbackTransaction Payment confirmations less than ".$this->confirmation, $fields['confirmations']);
            return false;
        }

        if ($fields['usd'] > $order['amount']) {
            $this->writePaymentErrorLog("=====================bitgo checkCallbackTransaction Payment amount is wrong, expected smaller than [".$order['amount']."]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- Private functions --
    protected function currencyFormat($amount, $format) {
        $float_amount  = $amount*$format;
        $string_amount = (string)$float_amount;
        if(preg_match("/E[\+\-]\d+$/", $string_amount)){
            return $result_amount = number_format($string_amount, 0, '.', '');
        }
        else{
            return $result_amount = number_format($float_amount, 0, '.', '');
        }
    }

    protected function processCurl($url, $token, $params, $secure_id, $post = true) {
        $header_array = array('Authorization: Bearer '.$token);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if($post){
            $header_array = array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$token
            );
            curl_setopt($ch, CURLOPT_POST, $post);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header_array);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('=====================bitgo processCurl url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $secure_id);
        $response = json_decode($response, true);
        return $response;
    }
}