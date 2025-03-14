<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * TGO_ONEGOPAY
 *
 * * TGO_ONEGOPAY_WITHDRAWAL_PAYMENT_API, ID: 5908
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: https://six666.net
 * * Key: ## API token ## ##
 *
 * Notes:
 * 1 This API uses HTTP bearer authentication
 * @see generate_basic_auth_text()
 * @see _custom_curl_header in __construct()
 *
 * @category Payment
 * @copyright 2022 tot
 */
abstract class Abstract_payment_api_tgo_onegopay extends Abstract_payment_api {

    const RETURN_SUCCESS_CODE = 'ok';

    const ERRORCODE_SUCCESS = 1;

    public $ident = 'TGO_ONEGOPAY';

    // protected $errorCode_desc = [
    //     1   => 'Success' ,
    //     2   => 'Invalid Authorization'
    // ];

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

        $player = $this->CI->player_model->getPlayerDetailArrayById($playerId);

        $player_fullname = $this->CI->player_model->playerFullNameById($playerId);

        $params = [
            'requestid' => $order->secure_id ,
            'amount'    => $this->convertAmountToCurrency($amount) ,
            'name'      => $player_fullname ,
            'phone'     => $player['contactNumber'] ,
            'surl'      => $this->getNotifyUrl($orderId) ,
            'curl'      => $this->CI->utils->site_url_with_http('player_center2/deposit') ,
            'orderid'   => $order->secure_id
        ];

        $this->configParams($params, $order->direct_pay_extra_info);

        $this->CI->utils->debug_log(__METHOD__, 'YOURSITE-PAY generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormRedirect($params) {

        if (empty($params['name'])) {
            return [
                'success'   => false ,
                'type'      => self::REDIRECT_TYPE_ERROR ,
                'message'   => lang('Name is empty.  Please fill your real name before deposit.')
            ];
        }

        $order_id = $params['orderid'];
        unset($params['orderid']);
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, 'post_json', $order_id);
        $this->CI->utils->debug_log(__METHOD__, 'YOURSITE-PAY processPaymentUrlFormPost response', $response);
        $result = json_decode($response, 'as_array');
        $this->CI->utils->debug_log(__METHOD__, 'YOURSITE-PAY processPaymentUrlFormPost decoded result', $result);

        if (!isset($result['Result']) || !isset($result['ErrorMessage']) || !isset($result['ErrorCode'])) {
            return [
                'success'   => false ,
                'type'      => self::REDIRECT_TYPE_ERROR ,
                'message'   => lang('Invalid API response')
            ];
        }
        else if ($result['ErrorCode'] != '1') {
            return [
                'success'   => false ,
                'type'      => self::REDIRECT_TYPE_ERROR ,
                'message'   => sprintf("Error %d: %s", $result['ErrorCode'], $result['ErrorMessage'])
            ];
        }
        else {
             return [
                'success'   => true ,
                'type'      => self::REDIRECT_TYPE_URL ,
                'url'       => $result['Result']
            ];
        }

    }

    public function callbackFromServerBare($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $response_result_id;
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    protected function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log(__METHOD__, "YOURSITE-PAY callbackFrom $source params", $params);

        if ($source == 'server') {
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log(__METHOD__, "YOURSITE-PAY raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data, true);
                $this->CI->utils->debug_log(__METHOD__, "YOURSITE-PAY json_decode params", $params);
            }
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderid'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto browser callback ' . $this->getPlatformCode(), false);
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

    protected function checkCallbackOrder($order, $fields, &$processed = false) {

        /**
         * Expected members of $fields:
         *     transactionid        (tx-id, from payment service)
         *     phone                player phone
         *     amount               == $order->amount
         *     //banktransactionid    (tx-id, from bank, external)
         *     requestid            == $order->secure_id
         */

        // Check expected members
        $requiredFields = [ 'transactionid', 'phone', 'amount', 'requestid' ];

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog(__METHOD__, "YOURSITE-PAY checkCallbackOrder field missing '$f'");
                return false;
            }
        }

        // Check sign
        if (!$this->verify_callback_sign($fields)) {
            $this->writePaymentErrorLog(__METHOD__, 'YOURSITE-PAY checkCallbackOrder signature Error');
            return false;
        }

        // set $processed true after passed sign verification
        $processed = true;

        // Check for amount
        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog(__METHOD__, 'YOURSITE-PAY checkCallbackOrder amount mismatch', [ 'expected' => $order->amount, 'returned' => $fields['amount'] ], $fields);
            return false;
        }

        // Check for order ID
        if ($fields['requestid'] != $order->secure_id) {
            $this->writePaymentErrorLog(__METHOD__, 'YOURSITE-PAY checkCallbackOrder order ID mismatch', [ 'expected' => $order->secure_id, 'returned' => $fields['requestid'] ], $fields);
            return false;
        }

        // if everything is fine
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    protected function verify_callback_sign($fields) {
        $plain_ar = '';
        foreach ($fields as $key => $val) {
            if ($key == 'sign') continue;
            $plain_ar = "$key=$val";
        }
        $plain = implode('&', $plain_ar);

        $merchant_key = $this->getSystemInfo('key');

        $hash = hash_hmac('sha256', $plain, $merchant_key);

        $this->CI->utils->debug_log(__METHOD__, 'YOURSITE-PAY callback sign', [ 'hash' => $hash, 'merchant_key' => $merchant_key, 'plain' => $plain ]);

        return $hash;
    }

    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    /**
     * Note: used in both deposit and withdrawal
     * @param  [type] $amount [description]
     * @return [type]         [description]
     */
    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Set up custom header for HTTP basic authentication
     * @see     generate_basic_auth_text()
     * @return  none
     */
    protected function setup_header_auth($with_content_type = true) {
        $auth_string = $this->generate_auth_text();
        $this->_custom_curl_header = [
            "Accept: application/json" ,
            "Authorization: Bearer {$auth_string}"
        ];
        if (!empty($with_content_type)) {
            $this->_custom_curl_header[] = "Content-Type: application/x-www-form-urlencoded";
        }
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} auth text", $this->_custom_curl_header);
        // $this->_custom_curl_header = [ 'Content-Type: application/json', "Authorization: Basic {$auth_string}" ];
    }

    /**
     * Generates auth text for HTTP basic authentication
     * @return  string (base64-encoded)
     */
    protected function generate_auth_text() {
        $api_token = $this->getSystemInfo('key');

        return $api_token;
    }

    public function build_sign($args) {
        $plain = $this->sign_plain($args);
        $hash = $this->sign_hash($plain);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} hash calc", [ 'plain' => $plain, 'hash' => $hash ]);

        return $hash;
    }

    /**
     * Sign calculation: remove reserved item(s) from array, then convert to json
     * @param   array   $args   Argument array
     * @return  string  JSON string
     */
    public function sign_plain($args) {
        // Remove specific array item(s)
        $except_keys = [ '_signature' ];

        foreach ($args as $key=>$val) {
            if (in_array($key, $except_keys)) {
                unset($args[$key]);
                continue;
            }
            if (empty($val)) {
                unset($args[$key]);
                continue;
            }
        }

        // Convert to json
        $args_json = json_encode($args);

        return $args_json;
    }

    /**
     * Sign calculation: the main algorithm
     * @param   string  $plain  json-encoded argument
     * @return  string  hashed $plain
     */
    public function sign_hash($plain) {
        $api_token = $this->getSystemInfo('key');
        $hash = base64_encode(hash_hmac('sha256', $plain, $api_token, true));

        return $hash;
    }

}