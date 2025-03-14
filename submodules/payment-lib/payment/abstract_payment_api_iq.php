<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * PaymentIQ
 * https://backoffice.paymentiq.io
 * https://test-backoffice.paymentiq.io
 *
 * * IQ_NETELLER_PAYMENT_API,    ID: 5559
 * * IQ_SKRILL_PAYMENT_API,      ID: 5560
 * * IQ_ECOPAYZ_PAYMENT_API,     ID: 5561
 * * IQ_PAYSAFECARD_PAYMENT_API, ID: 5562
 * * IQ_PAYVISION_PAYMENT_API,   ID: 5563
 * * IQ_HELP2PAY_PAYMENT_API,    ID: 5564
 * * IQ_WITHDRAWAL_HELP2PAY_PAYMENT_API, ID: 5581
 *
 * Required Fields:
 * * URL
 * * Account
 *
 * Field Values:
 * * URL: https://api.paymentiq.io/paymentiq
 * * Account: ## Merchant ID ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_iq extends Abstract_payment_api {

    const RETURN_TXSTATE_SUCCESS = 'SUCCESSFUL';
    const RETURN_TXSTATE_WAITING = 'WAITING_INPUT';
    const RETURN_TXSTATE_WAITING_APPROVAL  = 'WAITING_APPROVAL';
    const RETURN_TXSTATE_FAILED  = 'FAILED';

    const ACTION_VERIFYUSER       = 'verifyuser';
    const ACTION_AUTHORIZE        = 'authorize';
    const ACTION_CALLBACK_SUCCESS = 'transfer';
    const ACTION_CALLBACK_FAILED  = 'cancel';

    # Implement these for specific pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params, $orderId);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();
        $params['orderId']    = $orderId;
        $params['sessionId']  = base64_encode($order->secure_id);
        $params['userId']     = $playerId;
        $params['amount']     = $this->convertAmountToCurrency($amount);
        $params['merchantId'] = $this->getSystemInfo("account");
        $params['attributes']['secure_id']  = $order->secure_id;
        $params['attributes']['successUrl'] = $this->getReturnUrl($orderId);
        $params['attributes']['failureUrl'] = $this->getReturnFailUrl($orderId);
        $params['attributes']['cancelUrl']  = $this->getReturnFailUrl($orderId);

        $this->configParams($params, $order->direct_pay_extra_info);
        unset($params['orderId']);
        $this->CI->utils->debug_log("=====================iq generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params, $order->secure_id);
    }

    protected function processPaymentByprovider($params, $orderId, $return_all = false) {
        $ch = curl_init();
        $url = $this->getSystemInfo('url');

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $this->setCurlProxyOptions($ch);

        $response   = curl_exec($ch);
        $errCode    = curl_errno($ch);
        $error      = curl_error($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $orderId);
        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

        if($return_all){
            $response_result = [
                $params, $response, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $orderId
            ];
            $this->CI->utils->debug_log('=========================iq processPaymentByprovider return_all response_result', $response_result);
            return array($response, $response_result);
        }

        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================iq processPaymentByprovider decoded response', $response);

        return $response;
    }

    protected function processPaymentUrlFormForRedirect($params, $orderId) {
        $response = $this->processPaymentByprovider($params, $orderId);

        if (empty($response)) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => lang('Invalidate API response')
            );
        }

        if (isset($response['success']) && $response['success']) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($orderId);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['txRefId']);

            if ($response['txState'] == self::RETURN_TXSTATE_SUCCESS) {
                $data = array();
                foreach ($response['messages'] as $message) {
                    if (empty($message['value'])) {
                        continue;
                    }
                    $data[$message['label']] = $message['value'];
                }

                $collection_text_transfer = '';
                $collection_text = $this->getSystemInfo("collection_text_transfer", array(''));
                if (is_array($collection_text)) {
                    $collection_text_transfer = $collection_text;
                }
                $is_not_display_recharge_instructions = $this->getSystemInfo('is_not_display_recharge_instructions');

                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_STATIC,
                    'data' => $data,
                    'collection_text_transfer' => $collection_text_transfer,
                    'is_not_display_recharge_instructions' => $is_not_display_recharge_instructions
                );

            } elseif ($response['txState'] == self::RETURN_TXSTATE_WAITING && !empty($response['redirectOutput'])) {
                $url = $response['redirectOutput']['url'];
                $parameters = $response['redirectOutput']['parameters'];
                $is_post = ($response['redirectOutput']['method'] == 'POST') ? TRUE : FALSE;
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_FORM,
                    'url' => $url,
                    'params' => $parameters,
                    'post' => $is_post
                );
            } else {
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => 'Unknown txState'
                );
            }
        } elseif (isset($response['errors'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $response['txState'] .': '. $response['errors'][0]['msg']
            );
        }
    }

    public function getRoutingType($action) {
        switch ($action) {
            case self::ACTION_VERIFYUSER:
            case self::ACTION_AUTHORIZE:
                return "validation";
                break;

            case self::ACTION_CALLBACK_SUCCESS:
            case self::ACTION_CALLBACK_FAILED:
                return "process";
                break;
        }
    }

    # Corresponding validation URL: /callback/general_routing/5559/<action>
    # while action = verifyuser/authorize
    public function getOrderValidation($fields) {
        $this->CI->utils->debug_log("=====================iq getOrderValidation fields", $fields);
        $valid = array();
        $valid['success'] = false;

        if (empty($fields)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================iq getOrderValidation raw_post_data", $raw_post_data);
            $fields = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================iq getOrderValidation json_decode fields", $fields);
        }

        $this->basic_auth();
        $action = $this->getActionFromURI();
        switch ($action) {
            case self::ACTION_VERIFYUSER:
                if (!isset($fields['sessionId'])) {
                    $valid['errCode'] = "001";
                    $valid['errMsg']  = "SessionId is required.";
                    return $valid;
                }

                $sessionId = base64_decode($fields['sessionId']);
                if(substr($sessionId , 0, 1) == 'W'){
                    $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($sessionId);
                    $playerId = $order['playerId'] ;
                }else{
                    $order = $this->CI->sale_order->getSaleOrderBySecureId($sessionId);
                    $playerId = $order->player_id ;
                }
                $this->CI->utils->debug_log("=====================iq verifyuser order", $order);
                $this->CI->utils->debug_log("=====================iq verifyuser playerId", $playerId);

                if (empty($order)) {
                    $valid['errCode'] = "002";
                    $valid['errMsg']  = "Invalid SessionId.";
                    return $valid;
                }

                if ($playerId != $fields['userId']) {
                    $valid['errCode'] = "003";
                    $valid['errMsg']  = "Invalid UserId";
                    return $valid;
                }

                $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
                $valid['success']   = true;
                $valid['userId']    = $playerId;
                $valid['firstName'] = $playerDetails[0]['firstName'];
                $valid['lastName']  = $playerDetails[0]['lastName'];
                $valid['balance']   = $this->CI->player_model->getPlayersTotalBallanceIncludeSubwallet($playerId);
                $valid['balanceCy'] = $this->getSystemInfo("currency", $this->utils->getCurrentCurrency()['currency_code']);
                break;

            case self::ACTION_AUTHORIZE:
                if (!isset($fields['attributes']['secure_id'])) {
                    $valid['errCode'] = "001";
                    $valid['errMsg']  = "SecureId is required";
                    return $valid;
                }

                $secureId = $fields['attributes']['secure_id'];
                if(substr($secureId , 0, 1) == 'W'){
                    $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($secureId);
                    $playerId = $order['playerId'];
                    $amount = $order['amount'];
                    $merchantTxId = $order['transactionCode'];
                    $txAmount = abs($fields['txAmount']);
                }else{
                    $order = $this->CI->sale_order->getSaleOrderBySecureId($secureId);
                    $playerId = $order->player_id ;
                    $amount = $order->amount;
                    $merchantTxId = $order->secure_id;
                    $txAmount = $fields['txAmount'];
                }
                $this->CI->utils->debug_log("=====================iq authorize order", $order);
                $this->CI->utils->debug_log("=====================iq authorize playerId", $playerId);

                if (empty($order)) {
                    $valid['errCode'] = "002";
                    $valid['errMsg']  = "Invalid SecureId.";
                    return $valid;
                }

                if ($playerId != $fields['userId']) {
                    $valid['errCode'] = "003";
                    $valid['errMsg']  = "Invalid UserId";
                    return $valid;
                }

                if ($this->convertAmountToCurrency($amount) != $txAmount) {
                    $valid['errCode'] = "004";
                    $valid['errMsg']  = "Invalid Amount.";
                    return $valid;
                }

                $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
                $valid['success']      = true;
                $valid['userId']       = $playerId;
                $valid['merchantTxId'] = $merchantTxId;
                $valid['authCode']     = $this->uuid();
                break;
        }

        $this->utils->debug_log('=====================iq getOrderIdFromParameters return valid', $valid);
        return $valid;
    }

    # Corresponding validation URL: /callback/general_routing/5559/<action>
    # while action = transfer/cancel
    public function getOrderIdFromParameters($fields) {
        $this->CI->utils->debug_log("=====================iq getOrderIdFromParameters fields", $fields);
        $valid = array();
        $valid['success'] = false;

        if (empty($fields)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================iq getOrderIdFromParameters raw_post_data", $raw_post_data);
            $fields = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================iq getOrderIdFromParameters json_decode fields", $fields);
        }

        $this->basic_auth();
        $action = $this->getActionFromURI();
        switch ($action) {
            case self::ACTION_CALLBACK_SUCCESS:
            case self::ACTION_CALLBACK_FAILED:
                if (!isset($fields['attributes']['secure_id'])) {
                    $this->utils->debug_log('=====================iq getOrderIdFromParameters cannot find secure_id', $fields);
                    return;
                }

                $atrSecureId = $fields['attributes']['secure_id'];
                if(substr($atrSecureId , 0, 1) == 'W'){
                    $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($atrSecureId);
                    $orderId = $order['transactionCode'];
                }else{
                    $order = $this->CI->sale_order->getSaleOrderBySecureId($atrSecureId);
                    $orderId = $order->id;
                }

                if(is_null($order)){
                    $this->utils->debug_log('=====================iq getOrderIdFromParameters cannot find order by secure_id', $fields);
                    return;
                }

                return $orderId;
                break;
        }

        $this->utils->debug_log('=====================iq getOrderIdFromParameters return valid', $valid);
        return $valid;
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    protected function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null);
        $processed = false;

        $this->CI->utils->debug_log("=====================iq callbackFrom $source params", $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================iq callbackFrom $source raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================iq callbackFrom $source json_decode params", $params);
        }

        if ($source == 'server' ) {
            $this->basic_auth();
            $action = $this->getActionFromURI();

            if(substr($orderId , 0, 1) == 'W'){
                return $this->withdrawalCallback($action, $orderId, $params);
            }else{
                $order = $this->CI->sale_order->getSaleOrderById($orderId);
                if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                    $valid = array();
                    $valid['userId']  = $order->player_id;
                    $valid['success'] = false;
                    $valid['errCode'] = '009';
                    $valid['errMsg']  = 'CheckCallbackOrder failed.';

                    $result['return_error_json'] = $valid;
                    return $result;
                }
            }
        } elseif ($source == 'browser' ) {
            $order = $this->CI->sale_order->getSaleOrderById($orderId);
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
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                if ($action == self::ACTION_CALLBACK_SUCCESS) {
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                } elseif ($action == self::ACTION_CALLBACK_FAILED) {
                    $this->CI->sale_order->declineSaleOrder($order->id, 'auto server callback declined ' . $this->getPlatformCode() .', origin reason: ['.$params['pspStatusCode'].'] ' . $params['pspStatusMessage'], false);
                }
            }
        }

        $result['success'] = $success;
        if ($source == 'server') {
            if ($success) {
                $valid = array();
                $valid['userId']       = $order->player_id;
                $valid['success']      = true;
                $valid['txId']         = $params['txId'];
                $valid['merchantTxId'] = $order->secure_id;

                $result['json_result'] = $valid;
            } else {
                $valid = array();
                $valid['userId']  = $order->player_id;
                $valid['success'] = false;
                $valid['errCode'] = $params['txId'];
                $valid['errMsg']  = $order->secure_id;

                $result['return_error_json'] = $valid;
            }
        } elseif ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    protected function withdrawalCallback($action, $transId, $fields) {
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $valid = array();
        $valid['userId']  = $order['playerId'];
        $valid['success'] = false;

        if (abs($fields['txAmount']) != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================iq withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            $valid['errCode'] = '009';
            $valid['errMsg']  = 'CheckCallbackOrder failed.';
            $result['message'] = 'PaymentIQ withdrawal failed. Amount is wrong';
            $result['return_error_json'] = $valid;
            return $result;
        }

        if ($action == self::ACTION_CALLBACK_SUCCESS) {
            $msg = "PaymentIQ withdrawal success!";
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $valid['success'] = true;
            $valid['txId'] = $fields['txId'];
            $valid['merchantTxId'] = $transId;

            $result['success'] = true;
            $result['json_result'] = $valid;
        } elseif ($action == self::ACTION_CALLBACK_FAILED) {
            $statusCode = $fields['statusCode'];
            $pspStatusCode = isset($fields['pspStatusCode']) ? $fields['pspStatusCode'] : '';
            $pspStatusMessage = isset($fields['pspStatusMessage']) ? $fields['pspStatusMessage'] : '';
            $pspDesc = "[".$pspStatusCode."] " . $pspStatusMessage;

            $msg = "PaymentIQ withdrawal failed. ".$statusCode.": ". $pspDesc;
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);


            $valid['success'] = true;
            $result['return_error_json'] = $valid;
        }

        return $result;
    }

    protected function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array('userId', 'txAmount', 'txAmountCy');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================iq missing parameter: [$f]", $fields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($this->convertAmountToCurrency($order->amount) != $fields['txAmount']) {
            $this->writePaymentErrorLog("=====================iq Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- Private functions --
    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnFailUrl($orderId) {
        return parent::getCallbackUrl('/callback/show_error/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 2, '.', '');
    }

    protected function basic_auth() {
        $AUTH_USER = $this->getSystemInfo("auth_user");
        $AUTH_PASS = $this->getSystemInfo("auth_pass");

        $has_supplied_credentials = !(empty($_SERVER['PHP_AUTH_USER']) && empty($_SERVER['PHP_AUTH_PW']));
        $is_not_authenticated = (
            !$has_supplied_credentials ||
            $_SERVER['PHP_AUTH_USER'] != $AUTH_USER ||
            $_SERVER['PHP_AUTH_PW']   != $AUTH_PASS
        );

        if ($is_not_authenticated) {
            header('HTTP/1.1 401 Authorization Required');
            header('WWW-Authenticate: Basic realm="Access denied"');
            $this->CI->output->set_output('401 Access Denied');
            exit;
        }

        return;
    }

    protected function getActionFromURI() {
        $uri = $_SERVER['REQUEST_URI'];
        $uri_array = explode("/", $uri);
        $this->CI->utils->debug_log("=====================iq getActionFromURI uri_array", $uri_array);

        return end($uri_array);
    }

    protected function uuid() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s%s', str_split(bin2hex($data), 4));
    }
}
