<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * COINSPAID CRYPTO
 * * COINSPAID_BTC_PAYMENT_API, ID: 6174
 * * COINSPAID_ETH_PAYMENT_API, ID: 6175
 * * COINSPAID_USDTE_PAYMENT_API, ID: 6176
 * * COINSPAID_USDTT_PAYMENT_API, ID: 6177
 * * COINSPAID_BTC_WITHDRAWAL_PAYMENT_API, ID: 6178
 * * COINSPAID_ETH_WITHDRAWAL_PAYMENT_API, ID: 6179
 * * COINSPAID_USDTE_WITHDRAWAL_PAYMENT_API, ID: 6180
 * * COINSPAID_USDTT_WITHDRAWAL_PAYMENT_API, ID: 6181
 * *
 * Required Fields:
 * * URL
 * * wallet_id
 * * token
 * * address
 *
 * Field Values:
 * * URL: https://app.cryptoprocessing.com/api/v2
 *        https://app.sandbox.cryptoprocessing.com/api/v2/currencies/list
    # url 
    # address 
    # https://app.cryptoprocessing.com/api/v2/addresses/take
    # https://app.sandbox.cryptoprocessing.com/api/v2/addresses/take

    # Get a particular pair and its price.
    # https://app.cryptoprocessing.com/api/v2/currencies/rates
    # https://app.sandbox.cryptoprocessing.com/api/v2/currencies/rates

    # Get list of currency exchange pairs.
    # /v2/currencies/pairs
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_coinspaid extends Abstract_payment_api {
    const COIN_BTC   = "BTC";
    const COIN_ETH   = "ETH";
    const COIN_USDTE = "USDTE";
    const COIN_USDTT = "USDTT";
    const COIN_BCH   = "BCH";
    const COIN_LTC   = "LTC";

    const CALLBACK_TYPE_RECEIVE   = "deposit_exchange";
    const CALLBACK_TYPE_SEND      = "withdrawal_exchange";

    const CALLBACK_SUCCESS        = "confirmed";
    const CALLBACK_CANCELLED      = "cancelled";
    const CALLBACK_NOT_CONFIRMED  = "not_confirmed";

    const RETURN_SUCCESS_CODE     = 'OK';
    const WITHDRAWAL_RESULT_CODE  = 'processing';

    public $coin;
    public $api_key;

    public function __construct($params = null) {
        parent::__construct($params);

        $this->coin           = $this->getCoin();
        $this->api_key        = $this->getSystemInfo('key');
        $this->currency       = $this->getSystemInfo('currency');
        $this->targetCurrency = $this->getSystemInfo('targetCurrency');
    }

    # Implement these to specify pay type
    protected abstract function getCoin();
    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {}
    // protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected function handlePaymentFormResponse($handle) {}
    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    public function processHeaders($params){
        $headers = array(
            "X-Processing-Key: ".$this->getSystemInfo('account'),
            "X-Processing-Signature: ". $this->sign($params),
            "content-type: Content-Type: application/json"
        );

        $this->_custom_curl_header = $headers;
        $this->CI->utils->debug_log('=====================coinspaid usdt processHeaders headers', $headers);
        return $headers;
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        if(empty($flds) || is_null($flds)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $flds = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log('=====================coinspaid getOrderIdFromParameters raw_post_data', $raw_post_data);
        }
        $this->CI->utils->debug_log('=====================coinspaid getOrderIdFromParameters json_decode flds', $flds);

        if(isset($flds['crypto_address']['foreign_id'])) {
            $txid = $flds['crypto_address']['foreign_id'];
            $this->utils->debug_log('=====================coinspaid getOrderIdFromParameters get deposit transfer id', $txid);

            #deposit
            if(substr($txid, 0, 1) == 'D'){
                $this->utils->debug_log('=====================coinspaid getOrderIdFromParameters deposit foreign_id', $flds);
                $order = $this->CI->sale_order->getSaleOrderBySecureId($txid);
                if(is_null($order)){
                    $this->utils->debug_log('=====================coinspaid getOrderIdFromParameters cannot find deposit order by txid', $txid);
                    return;
                }
                return $order->id;
            }
        }elseif (isset($flds['foreign_id'])) {
            $txid = $flds['foreign_id'];
            $this->utils->debug_log('=====================coinspaid getOrderIdFromParameters get withdrawal transfer id', $txid);
            if(substr($txid, 0, 1) == 'W'){
                $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($txid);
                if(is_null($order)){
                    $this->utils->debug_log('=====================coinspaid getOrderIdFromParameters cannot find withdrawal order by txid', $txid);
                    return;
                }
                return $order['transactionCode'];
            }
        }else {
            $this->utils->debug_log('=====================coinspaid getOrderIdFromParameters cannot get any transfer from webhook params', $flds);
            return;
        }
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));

        $this->CI->utils->debug_log('=====================callbackFromServer orderId', $orderId);
        #check deposit or withdrawal
        if (!empty($orderId)) {
            if(substr($orderId, 0, 1) == 'W') {
                $order     = $this->CI->wallet_model->getWalletAccountByTransactionCode($orderId);
                $secure_id = $order['transactionCode'];
            }
            else{
                $order     = $this->CI->sale_order->getSaleOrderById($orderId);
                $secure_id = $order->secure_id;
            }
        }

        $processed = false;

        $raw_post_data = file_get_contents('php://input', 'r');
        $flds = json_decode($raw_post_data, true);
        $this->CI->utils->debug_log('=====================callbackFromServer raw_post_data', $raw_post_data);
        $this->CI->utils->debug_log('=====================callbackFromServer json_decode flds', $flds);

        if(!empty($flds)){
            $type = $flds['type'];

            if($type == $this->getSystemInfo('callback_type_deposit', self::CALLBACK_TYPE_RECEIVE)){
                $txid = $flds['crypto_address']['foreign_id'];
                if (!$order || !$this->checkCallbackOrder($order, $flds, $processed)) {
                    return $result;
                }
            }else if($type == $this->getSystemInfo('callback_type_withdrawal', self::CALLBACK_TYPE_SEND)){
                $txid = $flds['foreign_id'];
                if (!$order || !$this->checkCallbackWithdrawal($order, $flds)) {
                    return $result;
                }
            }
            else{
                $this->writePaymentErrorLog('=====================callbackFromServer wrong type flds',$type, $flds);
                return $result;
            }
        }

        if($type == $this->getSystemInfo('callback_type_deposit', self::CALLBACK_TYPE_RECEIVE)){
            # Update player balance based on order status
            # if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
            if ($order->status == Sale_order::STATUS_BROWSER_CALLBACK || $order->status == Sale_order::STATUS_SETTLED) {
                $this->CI->utils->debug_log('callbackFromServer already get callback for order:' . $order->id, $raw_post_data);
            } else {
                if ($flds['status'] == $this->getSystemInfo('callback_status', self::CALLBACK_SUCCESS)) {
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
            }

            $result['success'] = true;
            if ($processed) {
                $result['message'] = self::RETURN_SUCCESS_CODE;
            } else {
                $result['return_error'] = 'Error';
            }
        }
        else if($type == $this->getSystemInfo('callback_type_withdrawal', self::CALLBACK_TYPE_SEND)){
            if ($flds['status'] == self::CALLBACK_SUCCESS) {
                $msg = sprintf('coinspaid withdrawal success: id [%s]', $flds['foreign_id']);
                $this->CI->wallet_model->withdrawalAPIReturnSuccess($orderId, $msg);

                $result['message'] = self::RETURN_SUCCESS_CODE;
                $result['success'] = true;
            }
            // else if ($flds['status'] == self::CALLBACK_CANCELLED) {
            //     $msg = sprintf('coinspaid withdrawal failed.');
            //     $this->writePaymentErrorLog($msg, $response);
            //     $this->CI->wallet_model->withdrawalAPIReturnFailure($orderId, $msg);
            //     $result['message'] = $msg;
            // }
            else {
                $msg = sprintf('coinspaid withdrawal payment was not successful: [%s]', $flds['status']);
                $this->writePaymentErrorLog($msg, $flds);
                $result['message'] = $msg;
            }
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'status', 'foreign_id', 'amount', 'address'
        );

        $checkFields = array_merge($fields['crypto_address'], $fields['currency_received']);
        $checkFields['status'] = $fields['status'];
        foreach($requiredFields as $f) {
            if (!array_key_exists($f, $checkFields)) {
                $this->writePaymentErrorLog("=====================coinspaid checkCallbackOrder Missing parameter: [$f]", $checkFields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================coinspaid checkCallbackOrder Signature Error', $fields);
            return false;
        }

        if ($fields['status'] != $this->getSystemInfo('callback_status', self::CALLBACK_SUCCESS)) {
            $this->writePaymentErrorLog("=====================coinspaid checkCallbackOrder Payment status is not confirmed", $fields);
            return false;
        }

        $cryptoDetails = $this->CI->sale_order->getCryptoDepositOrderBySaleOrderId($order->id);
        $crypto = $cryptoDetails->received_crypto;
        $currency = $cryptoDetails->crypto_currency;
        if ($fields['crypto_address']['currency'] != $currency) {
            $this->writePaymentErrorLog("=====================coinspaid checkCallbackOrder Payment currency is wrong, expected [$currency]", $fields);
            return false;
        }

        $this->CI->load->model('external_system');
        $system_id = $order->system_id;
        $this->CI->utils->debug_log("====================callback PLATFORM_CODE:", $system_id);
        $systemInfo = $this->CI->external_system->getSystemById($system_id);
        $extraInfoJson = (!isset($systemInfo->live_mode) || $systemInfo->live_mode) ? $systemInfo->extra_info : $systemInfo->sandbox_extra_info;
        $extraInfo = json_decode($extraInfoJson, true) ?: array();

        $this->CI->utils->debug_log("====================callback extraInfo:", $extraInfo);

        $currency_sent = $fields['currency_sent']['currency'];
        if (!empty($extraInfo['validate_callback_crypto'])) {
            if ($currency_sent == self::COIN_BTC || $currency_sent == self::COIN_ETH) {
                if ($fields['currency_sent']['amount'] != $crypto) {
                    $this->writePaymentErrorLog("=====================coinspaid checkCallbackOrder Payment received crypto is wrong, expected [$crypto]", $fields);
                    return false;
                }
            }
        }

        if ($fields['crypto_address']['address'] != $order->external_order_id) {
            $this->writePaymentErrorLog("=====================coinspaid checkCallbackOrder Payment address is wrong, expected [$order->external_order_id]", $fields);
            return false;
        }

        $amount = $this->convertAmountToCurrency($order->amount);
        $callbackAmount = $this->convertAmountToCurrency($fields['currency_received']['amount']);

        $this->CI->utils->debug_log("=====================coinspaid checkCallbackOrder amount", $amount, 'callbackAmount',$callbackAmount);
        if ($callbackAmount != $amount) {
            if($extraInfo['allow_callback_amount_diff']){
                $percentage = isset($extraInfo['diff_amount_percentage']) ? $extraInfo['diff_amount_percentage'] : '';
                $limit_amount = isset($extraInfo['diff_limit_amount']) ? $extraInfo['diff_limit_amount'] : '';

                if (!empty($percentage)) {
                    $percentage_amt = str_replace(',', '', $amount) * ($percentage / 100);
                    $diffAmtPercentage = abs(str_replace(',', '', $amount) - $percentage_amt);

                    $this->CI->utils->debug_log("=====================coinspaid checkCallbackOrder amount details",$percentage, $limit_amount,$percentage_amt,$diffAmtPercentage);

                    if ($callbackAmount < $diffAmtPercentage) {
                        $this->writePaymentErrorLog("=====================coinspaid checkCallbackOrder Payment amounts ordAmt - payAmt > $percentage Percentage, expected [$amount]", $fields ,$diffAmtPercentage);
                        return false;
                    }
                }

                if (!empty($limit_amount)) {
                    $diffAmount = abs($amount - floatval($callbackAmount));
                    if ($diffAmount >= $limit_amount) {
                        $this->writePaymentErrorLog("=====================coinspaid checkCallbackOrder Payment amounts ordAmt - payAmt > 1, expected [$amount]", $diffAmount ,$fields);
                        return false;
                    }
                }

                $this->CI->utils->debug_log("=====================coinspaid checkCallbackOrder amount not match expected [$amount]",$fields);

                $orderStatus = $order->status;
                if ($orderStatus == Sale_order::STATUS_DECLINED || $orderStatus == Sale_order::STATUS_SETTLED) {
                    $this->writePaymentErrorLog("=====================coinspaid checkCallbackOrder Payment order status has been approved or declined : [$orderStatus]", $fields);
                    return false;
                }else{
                    $notes = $order->notes . " | callback diff amount, origin was: " . $amount;
                    $this->CI->sale_order->fixOrderAmount($order->id, str_replace(',', '', $callbackAmount), $notes);
                }
            }
            else{
                $this->writePaymentErrorLog("======================coinspaid checkCallbackOrder Payment amount is wrong, expected <= ". $callbackAmount, $fields);
                return false;
            }
        }

        if ($fields['crypto_address']['foreign_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================coinspaid checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    private function checkCallbackWithdrawal($order, $fields) {
        $requiredFields = array(
            'status', 'foreign_id', 'amount'
        );

        $fields['amount'] = $fields['currency_sent']['amount'];
        foreach($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================coinspaid checkCallbackWithdrawal payout Missing parameter: [$f]", $fields);
                return false;
            }
        }

        unset($fields['amount']);

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================coinspaid checkCallbackWithdrawal payout Signature Error', $fields);
            return false;
        }

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("=====================coinspaid checkCallbackWithdrawal payout status is not confirmed", $fields);
            return false;
        }

        if ($this->convertAmountToCurrency($fields['currency_sent']['amount']) != $this->convertAmountToCurrency($order['amount'])) {
            $amount = $order['amount'];
            $this->writePaymentErrorLog("=====================coinspaid checkCallbackWithdrawal payout amount is wrong, expected [$amount]", $fields);
            return false;
        }

        if ($fields['foreign_id'] != $order['transactionCode']) {
            $transId = $order['transactionCode'];
            $this->writePaymentErrorLog("========================coinspaid checkCallbackWithdrawal payout transId do not match, expected [$transId]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    public function getCryptoRate($params){
        #https://app.sandbox.cryptoprocessing.com/api/v2/currencies/rates
        $crypto_url = $this->getSystemInfo('url').'/currencies/rates';
        $data['currency_from'] = $params['currency'];
        $data['currency_to'] = $params['convert_to'];
        $this->processHeaders($data);
        return $this->submitPostForm($crypto_url, $data, true, null);
    }

    public function getCryptoCalculate($params){
        #https://app.sandbox.cryptoprocessing.com/api/v2/exchange/calculate
        $crypto_url = $this->getSystemInfo('url').'/exchange/calculate';
        $data['sender_amount'] = $params['amount'];
        $data['sender_currency'] = $params['convert_to'];
        $data['receiver_currency'] = $params['currency'];
        $this->processHeaders($data);
        return $this->submitPostForm($crypto_url, $data, true, null);
    }

    public function sign($params) {
        $requestBody = json_encode($params);
        $signature   = hash_hmac('sha512', $requestBody, $this->api_key);
        return $signature;
    }

    public function validateSign($params){
        if (!isset($_SERVER['HTTP_X_PROCESSING_SIGNATURE'])) {
            $this->CI->utils->debug_log("=====================coinspaid validateSign server signature not defind");
            return false;
        }

        $paramsSign = $_SERVER['HTTP_X_PROCESSING_SIGNATURE'];
        $requestBody = json_encode($params);
        $signature   = hash_hmac('sha512', $requestBody, $this->api_key);

        if($paramsSign == $signature){
            return true;
        }
        else{
            return false;
        }
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }
}