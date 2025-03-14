<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * applepay
 *
 * * APPLEPAY_PAYMENT_API, ID: 5922
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://mgp-pay.com:8084/
 * * Account: ## Live Merchant no ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 * @copyright 2022 tot (update 2021/06)
 */
abstract class Abstract_payment_api_applepay_v2 extends Abstract_payment_api {


    const URL_PATH_PAY                  = 'api/pay/V2';

    const PAY_ARG_VERSION_DEFAULT       = 'V2';
    const PAY_ARG_SIGNTYPE_DEFAULT      = 'MD5';
    const PAY_ARG_CHANNELTYPE_DEFAULT   = 0;

    const PAY_RESP_CODE_SUCCESS         = 0;
    const PAY_RESP_CODE_FAILURE         = -1;

    const CALLBACK_STATUS_SUCCESS       = 1;
    const CALLBACK_STATUS_FAILURE       = 2;
    const CALLBACK_STATUS_PENDING       = 0;

    const RETURN_SUCCESS_CODE           = 'SUCCESS';


    protected $ident = 'APPLEPAY_V2';

    public function __construct($params = null) {
        parent::__construct($params);
        // $this->_custom_curl_header = array('application/x-www-form-urlencoded');
        $this->_custom_curl_header = [ 'Content-Type: application/json' ];
    }
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);
    // public abstract function getBankCode($direct_pay_extra_info);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $player = $this->CI->player_model->getAllPlayerDetailsById($playerId);

        $params = [
            'version'       => self::PAY_ARG_VERSION_DEFAULT ,
            'signType'      => self::PAY_ARG_SIGNTYPE_DEFAULT ,
            'merchantNo'    => $this->getSystemInfo('account') ,
            'date'          => $this->pay_arg_timestamp() ,
            'noticeUrl'     => $this->getNotifyUrl($orderId) ,
            'orderNo'       => $order->secure_id ,
            'bizAmt'        => $this->convertAmountToCurrency($amount) ,
            'customNo'      => $playerId ,
            'customName'    => $player['firstName']
        ];

        // Add channleType (SIC; channel type)
        $this->configParams($params, $order->direct_pay_extra_info);

        $params['sign'] = $this->calc_sign_payment($params);

        $this->CI->utils->debug_log('=====================APPLEPAY generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function pay_arg_timestamp() {
        $tstamp = date('YmdHis');
        return $tstamp;
    }

    protected function processPaymentUrlFormRedirect($params) {

        $url = $this->getSystemInfo('url') . self::URL_PATH_PAY;
        $this->CI->utils->debug_log('========================================APPLEPAY processPaymentUrlFormRedirect api host url', $url);
        $response = $this->submitPostForm($url, $params, true, $params['orderNo']);
        $decode_data = json_decode($response, true);
        $this->CI->utils->debug_log('========================================APPLEPAY processPaymentUrlFormRedirect response json to array', $decode_data);
        $msg = lang('Invalidate API response');

        try {
            if (!isset($decode_data['code'])) {
                throw new Exception("Malformed response, field missing: code", 0x11);
            }

            if ($decode_data['code'] != self::PAY_RESP_CODE_SUCCESS) {
                if (isset($decode_data['msg'])) {
                    throw new Exception("Request failed, msg={$decode_data['msg']}", 0x12);
                }
                else {
                    throw new Exception("Request failed", 0x13);
                }
            }

            // Must be successful at this point
            if (!isset($decode_data['detail'])) {
                throw new Exception("Malformed response, field missing: detail", 0x14);
            }

            if (!isset($decode_data['detail']['PayURL'])) {
                throw new Exception("Malformed response, field missing: detail.PayURL", 0x15);
            }

            // Return (redirect) url for successful response
            $ret = [
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decode_data['detail']['PayURL']
            ];

        }
        catch (Exception $ex) {
            $ex_code = $ex->getCode();
            $error = $ex_code;
            $msg = $ex->getMessage();

            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} processPaymentUrlFormRedirect", sprintf("exception: (0x%x) %s ", $ex_code, $msg));

            // Return message for errors
            $ret = [
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $msg
            ];

        }
        finally {
            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} processPaymentUrlFormRedirect", "return", $ret);

            return $ret;
        }

    } // End function processPaymentUrlFormRedirect()


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
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server' ){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("========================applepay raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data, true);
                $this->CI->utils->debug_log("========================applepay json_decode params", $params);
            }
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        $this->CI->utils->debug_log("=====================applepay callbackFrom $source params", $params);

        $success = true;

        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            # update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderNo'], null, null, null, $response_result_id);
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

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            // 'result','receive_state', 'receive_tradeno','receive_money','receive_inmoney','check'
            'orderNo', 'bizAmt', 'status', 'sign'
        );

        try {

            foreach ($requiredFields as $f) {
                if (!array_key_exists($f, $fields)) {
                    // $this->writePaymentErrorLog("=====================APPLEPAY field missing: $f", $fields);
                    throw new Exception("Field missing: $f");
                    return false;
                }
            }

            $sign_expected = $this->calc_sign_callback($fields);
            if ($sign_expected != $fields['sign']) {
                // $this->writePaymentErrorLog("=====================applepay checkCallbackOrder signature mismatch, expected={$sign_expected}, received={$fields['sign']}");
                throw new Exception("sign mismatch, expected={$sign_expected}, received={$fields['sign']}");
                return false;
            }

            $processed = true;

            // Check status
            if (self::CALLBACK_STATUS_SUCCESS != $fields['status']) {
                throw new Exception("status != successful (1), received={$fields['status']}");
            }

            // Check orderNo
            if ($order->secure_id != $fields['orderNo']) {
                throw new Exception("orderNo mismatch, expected={$order->secure_id}, received={$fields['orderNo']}");
            }

            // Check status
            $expected_bizAmt = $this->convertAmountToCurrency($order->amount);
            if ($expected_bizAmt != $fields['bizAmt']) {
                throw new Exception("bizAmt mismatch, expected={$expected_bizAmt}, received={$fields['bizAmt']}");
            }

            // If everything is OK
            return true;
        }
        catch (Exception $ex) {
            // $ex_code = $ex->getCode();
            // $error = $ex_code;
            $err_mesg = $ex->getMessage();

            $this->writePaymentErrorLog("{$this->ident} checkCallbackOrder: {$err_mesg}", $fields);
            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} checkCallbackOrder", "exception", $err_mesg);

            return false;
        }


    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## After payment is complete, the gateway will send redirect back to this URL
    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

     public function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '') ;
        // return number_format($amount, 0, '.', '') ;
    }

    protected function calc_sign_general($params, $fields_excluded = [], $exclude_empty = false) {
        // sort params by key, ascending
        ksort($params);

        // construct plaintext
        $plain_ar = [];
        foreach ($params as $key=>$val) {
            if (in_array($key, $fields_excluded)) {
                continue;
            }
            // Workaround: excluding empty item (not mentioned in pay service documents)
            if ($exclude_empty && empty($val)) {
                continue;
            }
            $plain_ar[] = "{$key}={$val}";
        }

        $plain = implode('&', $plain_ar);

        // Append key to plaintext
        $plain .= $this->getSystemInfo('key');

        // md5
        $sign = md5($plain);

        return [ 'plain' => $plain, 'sign' => $sign ];
    }

    protected function calc_sign_payment($params) {
        $calc_res = $this->calc_sign_general($params, []);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for payment request", [ 'plain' => $calc_res['plain'], 'sign' => $calc_res['sign'] ]);

        return $calc_res['sign'];
    }

    protected function calc_sign_callback($params) {
        $calc_res = $this->calc_sign_general($params, [ 'sign' ], 1);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for callback", [ 'plain' => $calc_res['plain'], 'sign' => $calc_res['sign'] ]);

        return $calc_res['sign'];
    }


}

