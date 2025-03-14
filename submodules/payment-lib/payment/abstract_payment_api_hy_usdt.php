<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * HY_USDT
 *
 * * HY_USDT_PAYMENT_API, ID: 5882
 * *
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
abstract class Abstract_payment_api_hy_usdt extends Abstract_payment_api {

    const RESULT_CODE_SUCCESS = '000';
    const RESULT_MSG_SUCCESS = '处理成功';
    const CALLBACK_SUCCESS = 'Completed';

    const RETURN_SUCCESS_CODE = 'success';

    public $txAuth = '';


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
        $username  = (isset($playerDetails[0]) && !empty($playerDetails[0]['username']))      ? $playerDetails[0]['username']      : 'no username';
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'no firstName';
        $lastname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName']      : 'no lastName';
        $email     = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : 'sample@example.com';

        $params = array();
        $params['name']             = $firstname.$lastname;
        $params['email_address']    = $email;
        $params['cryptocurrency']   = 'USDT';
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['transaction_id']   = $order->secure_id;
        $params['wallet_id']        = $this->getSystemInfo("wallet_id");
        $params['orderId']          = $orderId;
        $params['amount']           = $amount;

        $this->generateTxAuth($params);

        $this->CI->utils->debug_log('=====================HY_USDT generatePaymentUrlForm params', $params);
        return $this->processPaymentUrlForm($params);
    }

    /**
     * Calculates the hash value for 'txAuthorization'
     * @param   array   $params     param array
     * @return  string  hash
     */
    public function generateTxAuth($params) {
        $fields = [ 'name', 'email_address', 'cryptocurrency', 'amount', 'transaction_id', 'wallet_id' ];
        $plain_ar = '';
        foreach ($fields as $key) {
            $plain_ar[] = "{$key}:{$params[$key]}";
        }

        $plain_ar[] = "secret:{$this->getSystemInfo('key')}";
        // concat all key-value pairs with '|' char
        $plain = implode('|', $plain_ar);

        $txAuth = md5($plain);
        $this->txAuth = $txAuth;
        return $txAuth;
    }

    # Implement processPaymentUrlForm
    protected function processPaymentUrlFormQRCode($params) {
        $orderId = $params['orderId'];
        $cryptoQty = $params['crypto_amount'];
        $amount = $params['amount'];

        $validateRateResult = $this->validateDepositCryptoRate('USDT', $amount, $cryptoQty);

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

        unset ($params['orderId'],$params['rate'],$params['crypto_amount']);
        // 1: acquire access token first
        $get_token_params['grant_type'] = 'client_credentials';
        $get_token_params['client_id'] = $this->getSystemInfo("account");
        $get_token_params['client_secret'] = $this->getSystemInfo('key');
        // $get_token_params = json_encode($get_token_params);
        $response_token = $this->submitPostForm($this->getSystemInfo('token_url'), $get_token_params, false, $params['transaction_id']);

        $response_token = json_decode($response_token,true);

        $this->utils->debug_log('=====================HY_USDT usdt deposit_notes', $response_token);
        // $this->CI->sale_order->updateExternalInfo($order->id, $response_token['access_token']);

        // 2: send pay request with acquired response token
        $params['amount'] = $cryptoQty;
        $response = $this->processCurl($params, $response_token);

        if(isset($response['qr_code'])) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['transaction_id']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['wallet_address'], $response['fee'], $crypto);
            $this->CI->sale_order->createCryptoDepositOrder($orderId, $crypto, $rate, null, null,'USDT');
            $deposit_notes = '3rd Api Wallet address: '.$response['wallet_address'].' | '.' Real Rate: '.$rate . '|' . 'USDTcoin: ' . $cryptoQty;
            $this->CI->sale_order->appendNotes($order->id, $deposit_notes);

            $cust_payment_data['pay.sale_order_id'] = $response['transaction_id'];
            $cust_payment_data['3rdParty USDT Deposit'] = $response['amount'];
            $cust_payment_data['financial_account.bankaccount.walletaddress'] = $response['wallet_address'];
            $cust_payment_data['crypto network'] = $this->getSystemInfo('crypto_network');
            $cust_payment_data['collection.label.6'] = $order->created_at;
            $cust_payment_data['collection.label.7'] = $order->timeout_at;

            $cust_hide_copy_button_of_payment_data_index = array(4,5);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'cust_payment_data' => $cust_payment_data,
                'cust_hide_copy_button_of_payment_data_index' => $cust_hide_copy_button_of_payment_data_index,
                'url' => $response['qr_code'],
            );
        }
        else if($response) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $response
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidte API response')
            );
        }
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($params) {
        if(empty($params) || is_null($params) || is_array($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = $raw_post_data;
        }

        $params = json_decode($params,true);
        $this->utils->debug_log('=====================HY_USDT callback params', $params);

        if (isset($params['transaction_id'])) {
            $this->CI->load->model(array('sale_order','wallet_model'));
            if(substr($params['transaction_id'],0,1) == 'D'){
                $order = $this->CI->sale_order->getSaleOrderBySecureId($params['transaction_id']);
                return $order->id;
            }else{
                return $params['transaction_id'];
            }
        }
        else {
            $this->utils->debug_log('=====================HY_USDT callbackOrder cannot get any order_id when getOrderIdFromParameters', $params);
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

        $this->CI->utils->debug_log("=====================HY_USDT callbackFrom $source params", $params);

        if($source == 'server' ){
            if(empty($params) || is_null($params) || is_array($params)){
                $raw_post_data = file_get_contents('php://input', 'r');
                $params = $raw_post_data;
            }
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================HY_USDT json_decode params", $params);

            if(substr($params['transaction_id'] , 0, 1) == 'W'){
                $result = $this->isWithdrawal($params, $params['transaction_id']);
                $this->CI->utils->debug_log('=======================HY_USDT callbackFrom transaction_id', $params['transaction_id']);
                return $result;
            }elseif(!$order || !$this->checkCallbackOrder($order, $params, $processed)){
                return $result;
            }
        }

        # Update order payment status and balance
        $success = true;

        # Update player balance based on order status
        # if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
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
            'status', 'transaction_id', 'amount', 'wallet_address'
        );

        foreach($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================hyusdt checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->verify($fields)) {
            $this->writePaymentErrorLog('=====================hyusdt checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("=====================hyusdt checkCallbackOrder Payment status is not credited", $fields);
            return false;
        }

        if ($fields['wallet_address'] != $order->external_order_id) {
            $this->writePaymentErrorLog("=====================hyusdt checkCallbackOrder Payment address is wrong, expected [$order->external_order_id]", $fields);
            return false;
        }

        $amount = $this->convertAmountToCurrency($order->amount);
        $crypto_amount = $this->convertAmountToCrypto($fields['amount'],$order);

        if ($crypto_amount != $amount) {
            if($this->getSystemInfo('allow_callback_amount_diff')){

                $percentage = $this->getSystemInfo('diff_amount_percentage');
                $limit_amount = $this->getSystemInfo('diff_limit_amount');

                if (!empty($percentage) && !empty($limit_amount)) {
                    $percentage_amt = str_replace(',', '', $amount) * ($percentage / 100);
                    $diffAmtPercentage = abs(str_replace(',', '', $amount) - $percentage_amt);

                    $this->CI->utils->debug_log("=====================hyusdt checkCallbackOrder amount details",$percentag,$limit_amount,$percentage_amt,$diffAmtPercentage);

                    if ($percentage_amt > $limit_amount) {
                        $this->writePaymentErrorLog("=====================hyusdt checkCallbackOrder Payment amounts ordAmt - payAmt > $limit_amount limit amount, expected [$order->amount]", $fields ,$diffAmount);
                        return false;
                    }

                    if ($fields['cashierAmount'] < $diffAmtPercentage) {
                        $this->writePaymentErrorLog("=====================hyusdt checkCallbackOrder Payment amounts ordAmt - payAmt > $percentage Percentage, expected [$order->amount]", $fields ,$diffAmtPercentage);
                        return false;
                    }
                }

                $this->CI->utils->debug_log("=====================hyusdt checkCallbackOrder amount not match expected [$order->amount]",$fields);
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, str_replace(',', '', $fields['cashierAmount']), $notes);
            }
            else{
                $this->writePaymentErrorLog("=====================hyusdt Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['transaction_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================hyusdt checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        if($statusCode == self::CALLBACK_SUCCESS) {
            $msg = "hy_usdt withdrawal success!";
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($orderId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('hy_usdt withdrawal payment was not successful  trade ID [%s] ',$params['transaction_id']);
            $this->utils->debug_log('=========================hy_usdt withdrawal payment was not successful  trade ID [%s]', $params['transaction_id']);
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackWithdrawalOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array(
            'status', 'transaction_id', 'amount', 'wallet_address'
        );

        $cryptolOrder = $this->CI->wallet_model->getCryptoWithdrawalOrderById($order['walletAccountId']);

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================hyusdt withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->verify($fields)) {
            $this->writePaymentErrorLog('=====================hyusdt checkCallbackOrder Signature Error', $fields);
            return false;
        }

        if ($fields['amount'] != $cryptolOrder['transfered_crypto']){
            $this->writePaymentErrorLog('======================hyusdt withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['transaction_id'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('======================hyusdt withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }


    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    protected function convertAmountToCurrency($amount) {
        $this->CI->utils->debug_log("=======================hyusdt convertAmountToCurrency",$amount);
        return number_format($amount, 2, '.', '');
        // return substr(sprintf("%.3f", $amount),0,-1);
    }

    protected function convertAmountToCrypto($amount, $order) {
        $cryptoOrder = $this->CI->sale_order->getUsdtRateBySaleOrderId($order->id);
        $fee = $order->bank_order_id; //bank_order_id is a template to record fee for this api
        $cryptoAmount = ($amount - $fee) * $cryptoOrder->rate;
        $this->CI->utils->debug_log("=======================hyusdt convertAmountToCrypto",$cryptoAmount);
        // return substr(sprintf("%.3f", $cryptoAmount),0,-1);
        return number_format($cryptoAmount, 2, '.', '');
    }

    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function verify($params) {
        $headers = $this->CI->input->request_headers();
        foreach ($headers as $key => $value) {
            if($key == 'Txauthorization'){
                $hmac = $value;
            }
        }
        $signStr = '';
        foreach ($params as $key => $value) {
           $signStr .= "$key:$value|";
        }
        $sign = md5($signStr.'secret:'.$this->getSystemInfo('key'));

        if ( $hmac == $sign ) {
            return true;
        } else {
            return false;
        }
    }

    public function processCurl($params, $response_token) {
        $ch = curl_init();
        $url = $this->getSystemInfo('url');
        $token = $response_token['access_token'];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //     'Content-Type: application/json',
        //     'Authorization: Bearer '.$token ,
        //     'txAuthorization: ' . $this->txAuth
        // )
        // );
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

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['transaction_id']);


        $this->CI->utils->debug_log('=====================HY_USDT processCurl response', $response);
        $response = json_decode($response, true);

        $this->CI->utils->debug_log('=====================HY_USDT processCurl decoded response', $response);
        return $response;
    }
}