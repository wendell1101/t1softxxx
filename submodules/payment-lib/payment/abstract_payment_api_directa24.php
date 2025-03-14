<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * DIRECTA24
 *
 * * DIRECTA24_CHILE_PAYMENT_API, ID: 6226
 * * DIRECTA24_WITHDRAWAL_PAYMENT_API, ID: 6227
 * * DIRECTA24_MEXICO_PAYMENT_API, ID: 6231
 * * DIRECTA24_PERU_PAYMENT_API, ID: 6236
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
abstract class Abstract_payment_api_directa24 extends Abstract_payment_api {
    const PAYMENT_COUNTRY_IN   = 'IN';
    const PAYMENT_COUNTRY_MX   = 'MX';
    const PAYMENT_COUNTRY_CL   = 'CL';
    const PAYMENT_COUNTRY_PE   = 'PE';
    const CURRENCY             = 'USD';
    const PAYWAY_NETBANK       = 'NB';
	const PAYWAY_UPI           = 'UI';
    const REPONSE_CODE_SUCCESS = 'ONE_SHOT';
    const CALLBACK_SUCCESS     = 'COMPLETED';
    const RETURN_SUCCESS_CODE  = 'SUCCESS';
    const RETURN_FAILED_CODE   = 'FAILED';

    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement these to specify pay type
    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
        $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName'] : 'none';
        $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName'] : 'none';
        $phone      = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : 'none';
        $email      = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email'] : 'none';

        $params = array();
        $params['invoice_id']       = $order->secure_id;
        $params['amount']           = $this->convertAmountToCurrency($amount);
        $params['payer']            = [ 'document' => $this->uuid(),
                                        'first_name' => $firstname,
			                            'last_name' => $lastname,
                                        'email' => $email,
                                        'phone' => $phone];
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['success_url']      = $this->getReturnUrl($orderId);
        $params['notification_url'] = $this->getNotifyUrl($orderId);

        $this->CI->utils->debug_log("=====================directa24 generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {

        $response = $this->processCurl($params, $this->getSystemInfo('url'), true, $params['invoice_id']);
        $this->CI->utils->debug_log("=====================directa24 processPaymentUrlFormPost response", $response);

        $msg = lang('Invalidate API response');
        if( isset($response['checkout_type']) && $response['checkout_type'] == self::REPONSE_CODE_SUCCESS ){

            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['invoice_id']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['deposit_id'], null, null, null, null);

            if(isset($response['redirect_url']) && !empty($response['redirect_url'])){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['redirect_url'],
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => $msg
                );
            }
        }else {
            if(isset($response['code']) && !empty($response['code'])) {
                $details = '';
                if (isset($response['details'])) {
                    $details = ' details:' . json_encode($response['details']);
                }
                $msg = $response['description'] . $details;
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $msg
            );
        }
    }

    ## This will be called when the payment is async, API server calls our callback page
    ## When that happens, we perform verifications and necessary database updates to mark the payment as successful
    ## Reference: sample code, callback.php
    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    ## This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $processed = false;
        $callback_succ = false;

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $this->CI->utils->debug_log("=====================directa24 callbackFrom $source params", $params);

        if (!empty($orderId)) {
            if(substr($orderId, 0, 1) == 'W') {
                $order     = $this->CI->wallet_model->getWalletAccountByTransactionCode($orderId);
                $secure_id = $order['transactionCode'];
                $type      = 'withdrawal';
            }
            else{
                $order     = $this->CI->sale_order->getSaleOrderById($orderId);
                $secure_id = $order->secure_id;
                $type      = 'deposit';
            }
        }

        if(isset($params['deposit_id'])){
            $external_order_id = $order->external_order_id;
            if (!empty($external_order_id)) {

                $checkDepositURL = $this->getSystemInfo('checkDepositURL') . '/' . $external_order_id;
                $response = $this->processCurl('', $checkDepositURL, false, $secure_id);
                $this->CI->utils->debug_log('=====================directa24 callbackFrom check deposit response', $response);

                #check deposit status
                if (isset($response['status'])) {
                    if ($response['status'] == self::CALLBACK_SUCCESS) {
                        #check more params when receive
                            if (!$order || !$this->checkCallbackOrder($order, $response, $processed)) {
                                return $result;
                            }
                    } else {
                        $this->utils->writePaymentErrorLog('=====================directa24 callbackFrom status error', $response);
                        $result['return_error'] = lang('directa24 callbackFrom status failed').': ['.$response['status'].'] ';
                        return $result;
                    }

                } else {
                    $this->utils->writePaymentErrorLog('=====================directa24 callbackFrom error', $response);
                    $result['return_error'] = lang('directa24 callbackFrom check callback params failed');
                    return $result;
                }

            }else{
                $this->writePaymentErrorLog('=====================directa24 callbackFrom miss deposit id or external_order_id is empty', $params);
                $result['return_error'] = lang('directa24 callbackFrom miss deposit id or external_order_id is empty');
                return $result;
            }
        }else if(isset($params['cashout_id'])){
            if (!empty($params['cashout_id'])) {
                if (!$this->checkWithdrawalCallbackOrder($order, $params)) {
                    return $result;
                }else{
                    #check callback status
                    $res = $this->checkWithdrawStatus($secure_id);
                    if ($res['success']) {
                        $callback_succ = true;
                    }else{
                        return $res;
                    }
                }
            }
        }

        if ($type == 'deposit') {
            $success=true;

            if ($order->status == Sale_order::STATUS_BROWSER_CALLBACK || $order->status == Sale_order::STATUS_SETTLED) {
                $this->CI->utils->debug_log('callbackFromServer already get callback for order:' . $order->id, $result);
            } else {
                if ($processed) {
                    if ($response['status'] == self::CALLBACK_SUCCESS) {
                        $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                    }
                }
            }

            $result['success'] = $success;
            if ($processed) {
                $result['message'] = self::RETURN_SUCCESS_CODE;
            }

            if ($source == 'browser') {
                $result['next_url'] = $this->getPlayerBackUrl();
                $result['go_success_page'] = true;
            }
        }else if ($type == 'withdrawal'){
            if ($callback_succ) {
                $msg = sprintf('directa24_withdrawal success: Cashout ID [%s]', $params['cashout_id']);
                $this->CI->wallet_model->withdrawalAPIReturnSuccess($secure_id, $msg);
                $result['message'] = self::RETURN_SUCCESS_CODE;
                $result['success'] = true;
            }else {
                $msg = sprintf('directa24_withdrawal withdrawal was not success: [%s]', $params['status_reason']);
                $this->writePaymentErrorLog($msg, $params);
                $result['message'] = self::RETURN_FAILED_CODE;
            }
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'deposit_id', 'invoice_id', 'country', 'currency', 'usd_amount', 'status', 'payment_method'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================directa24 checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['deposit_id'] != $order->external_order_id) {
            $this->writePaymentErrorLog("=====================directa24 checkCallbackOrder deposit id not match external_order_id [$order->external_order_id]", $fields);
            return false;
        }

        if (floatval( $fields['usd_amount'] != $this->convertAmountToCurrency($order->amount))) {
            $this->writePaymentErrorLog("=====================directa24 checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['invoice_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================directa24 checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        $processed = true;

        # everything checked ok
        return true;
    }

    public function checkDepositStatus($secureId) {

        if(!empty($secureId)) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($secureId);
            if (!empty($order->external_order_id)){
                $checkDepositURL = $this->getSystemInfo('checkDepositURL') . '/' . $order->external_order_id;
            }else{
                $this->CI->utils->debug_log("=====================directa24 checkDepositStatus miss external_order_id", $secureId);
                return array('success' => false, 'message' => 'Miss Deposit ID');
            }
            $this->CI->utils->debug_log("=====================directa24 checkDepositStatus request", $secureId);
        }else{
            $this->CI->utils->debug_log('======================================directa24 checkDepositStatus miss secureId');
            return array('success' => false, 'message' => 'Miss Deposit Id');
        }

        $response = $this->processCurl('', $checkDepositURL, false, $secureId);

        return $this->decodedirecta24DepositStatusResult($order, $response);
    }

    public function decodedirecta24DepositStatusResult($order, $response){
        if(empty($response)){
            $this->CI->utils->debug_log('==================================directa24 decodedirecta24DepositStatusResult unknown result: ', $response);
            return [
                'success' => FALSE,
                'message' => 'Unknown response data'
            ];
        }

        $this->CI->utils->debug_log('======================================directa24 decodedirecta24DepositStatusResult response: ', $response);

        if ($this->checkOrderStatusByManualCheck($order, $response)) {
            $responseStatus = $response['status'];
            if($this->getSystemInfo("auto_approve_after_check_deposit_status")){
                if($response['status'] === self::CALLBACK_SUCCESS){
                    $this->CI->utils->debug_log('===============directa24 decodedirecta24DepositStatusResult auto approve: ', $response);
                    $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($order->id);
                    if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
                        $this->CI->utils->debug_log('already get callback for order:' . $order->id, $response);
                        if ($order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                            $this->CI->sale_order->setStatusToSettled($order->id);
                        }
                    } else {
                        # update player balance
                        $this->CI->sale_order->updateExternalInfo($order->id, $response['invoice_id'], '', null, null, $response['response_result_id']);
                        $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                        $message = "directa24 payment status:".$responseStatus.", deposit id:".$response['deposit_id'].", invoice id:".$response['invoice_id'].", usd amount:".$response['usd_amount'].", payment_method:".$response['payment_method']. " [auto approve by check order status]";
                        return array('success' => true, 'message' => $message);
                    }
                }else{
                    $message = "directa24 payment status:".$responseStatus.", deposit id:".$response['deposit_id'].", invoice id:".$response['invoice_id'].", usd amount:".$response['usd_amount'].", payment_method:".$response['payment_method'];
                    return array('success' => true, 'message' => $message);
                }
            }else{
                $message = "directa24 payment status:".$responseStatus.", deposit id:".$response['deposit_id'].", invoice id:".$response['invoice_id'].", usd amount:".$response['usd_amount'].", payment_method:".$response['payment_method'];
                return array('success' => true, 'message' => $message);
            }
        } else {
            if(isset($response['code']) && isset($response['description']) && isset($response['type'])){
                $message = "directa24 payment failed Status code: ".$response['code'].", description:".$response['description'].", type:".$response['type'];
                return array('success' => false, 'message' => $message);
            }else{
                return array('success' => false, 'message' => lang('Invalidate API response'));
            }
        }
    }

    private function checkOrderStatusByManualCheck($order, $fields) {
        $requiredFields = array(
            'deposit_id', 'invoice_id', 'country', 'currency', 'usd_amount', 'status', 'payment_method'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================directa24 checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (floatval( $fields['usd_amount'] != $this->convertAmountToCurrency($order->amount))) {
            $this->writePaymentErrorLog("=====================directa24 Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['invoice_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================directa24 checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    public function uuid() {
        $data = random_bytes(12);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s', str_split(bin2hex($data), 4));
    }

    # -- signatures --
    # Reference: PHP Demo
    public function sign($date,$params) {
        $secret = $this->getSystemInfo('secret');
        $signStr = $this->createSignStr($date,$params);
        $sign = 'D24 '. hash_hmac('sha256', $signStr, $secret);
        return $sign;
    }

    public function createSignStr($date,$params) {
        $key = $this->getSystemInfo('key');
        $body = $params ? json_encode($params) : "";
        $signStr = $date . $key . $body;
        return $signStr;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId) {
        $use_https_with_url = $this->getSystemInfo('use_https_with_url');
        $notifyUrl = parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
        if($use_https_with_url) {
            $notifyUrl = str_replace('http://', 'https://', $notifyUrl);
        }
        return $notifyUrl;
    }

    ## After payment is complete, the gateway will send redirect back to this URL
    private function getReturnUrl($orderId) {
        $use_https_with_url = $this->getSystemInfo('use_https_with_url');
        $returnUrl = parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
        if($use_https_with_url) {
            $returnUrl = str_replace('http://', 'https://', $returnUrl);
        }
        return $returnUrl;
    }

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    protected function processCurl($params, $url, $post = true, $secureId = null) {
        $ch = curl_init();
        $date = date('Y-m-d\TH:i:s\Z');
        if($post){
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            $sign = $this->sign($date, $params);
        }else{
            $sign = $this->sign($date, '');
        }

        $headers_arr = array(
            'Authorization:'.$sign,
            'X-Login:'.$this->getSystemInfo('key'),
            'X-Date:'.$date,
            'Content-Type:application/json'
        );

        $this->CI->utils->debug_log('=====================directa24 processCurl headers', $headers_arr);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_arr);

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $secureId);
        $response = json_decode($response, true);
        $response['response_result_id'] = $response_result_id;

        $this->CI->utils->debug_log('=====================directa24 processCurl decoded response', $response);
        return $response;
    }

    public function getPaymentMethods($params){
        $methods_url = $this->getSystemInfo('methods_url');
        $data['country'] = $params['country'];
        $this->processHeaders($data);
        return $this->submitGetForm($methods_url, $data, false, null);
    }

    public function processHeaders($params = null){
        $headers = array(
            'Authorization:Bearer '.$this->getSystemInfo('readonly_key'),
        );
        $this->_custom_curl_header = $headers;
        $this->CI->utils->debug_log('=====================directa24 processHeaders headers', $headers);
        return $headers;
    }
}