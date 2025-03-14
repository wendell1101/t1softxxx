<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * ASIADRAGON
 *
 * * ASIADRAGON_PAYMENT_API, ID: 5927
 *
 * Required Fields:
 * * URL
 * * Account    (merchant id)
 * * extra_info.merchant_priv_key
 * * extra_info.platform_public_key
 *
 * Field Values:
 * * URL        http://api.asia-pay8.com/api/unifiedorder
 * * Account    ## merchant id #
 * * extra_info.merchant_priv_key       ## merchant private key ##
 * * extra_info.platform_public_key     ## platform public key ##
 *
 * @see         payment_api_asiadragon.php
 * @category    Payment
 * @copyright   2022 tot
 */
abstract class Abstract_payment_api_asiadragon extends Abstract_payment_api {

    const RETURN_SUCCESS_CODE           = 'OK';

    const URL_PATH_DEPOSIT              = '/api/unifiedorder';

    const PAY_TRADETYPE_DEFAULT         = 'ZFBZK';

    const PAY_RESP_CODE_SUCCESS         = 200;
    const PAY_RESP_CODE_FAILURE         = 500;

    const PAY_CALLBACK_SUCCESS_DEFAULT  = 'true';

    const PAY_NONCE_LENGTH_DEFAULT      = 8;    // each byte converts to 2 chars

    public $ident = 'ASIADRAGON';

	public function __construct($params = null) {
		parent::__construct($params);
	}

    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        // $player = $this->CI->player_model->getPlayerDetailArrayById($playerId);

        $params = [
            'mchId'         => $this->getSystemInfo('account') ,
            'outTradeNo'    => $order->secure_id ,
            'tradeType'     => self::PAY_TRADETYPE_DEFAULT ,
            'nonceStr'      => $this->generate_nonce() ,
            'notifyUrl'     => $this->getNotifyUrl($orderId) ,
            'payAmount'     => $this->convertAmountToCurrency($amount) ,
            // 'payName'       =>
        ];

        // add pay_type
        $this->configParams($params, $order->direct_pay_extra_info);

        $params['sign'] = $this->calc_sign_pay_req($params);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} generatePaymentUrlForm params", $params);

        return $this->processPaymentUrlFormPost($params);
    }

    protected function pay_timestamp() {
        return date('YmdHis');
    }

    protected function processPaymentUrlFormPost($params) {
        $url_pay_request = $this->getSystemInfo('url') . self::URL_PATH_DEPOSIT;
        // $url_pay_request = $this->getSystemInfo('url');

        $resp_raw = $this->submitPostForm($url_pay_request, $params, false, $params['outTradeNo']);
        $resp = json_decode($resp_raw, true);
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} processPaymentUrlFormPost response", $resp);

        try {

            // Check common fields
            $req_fields = [ 'code', 'message', 'redirect' ];
            foreach ($req_fields as $rf) {
                if (!array_key_exists($rf, $resp)) {
                    throw new Exception("Malformed response, field missing: {$rf}", 0x11);
                }
            }

            // Check code
            if (self::PAY_RESP_CODE_SUCCESS != $resp['code']) {
                throw new Exception("code != success, error={$resp['message']}", 0x12);
            }

            // Check redirect
            if (empty($resp['redirect'])) {
                throw new Exception("redirect empty", 0x13);
            }

            // Point of success
            $ret = [
                'success'   => true ,
                'type'      => self::REDIRECT_TYPE_URL ,
                'url'       => $resp['redirect']
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

    } // end function processPaymentUrlFormPost()

    public function callbackFromServerBare($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
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
        $result = [
            'success' => false,
            'next_url' => null,
            'message' => lang('error.payment.failed')
        ];

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        // Use $_REQUEST: ci Input::post() is somewhat buggy while reading long base64 string generated by encryption algorithms
        $params = $_REQUEST;

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} callbackFrom $source params", $params);

        // $check_result = null;
        if ($source == 'server'){
            $check_res = $this->checkCallbackOrder($order, $params, $processed);
            if (!$order || $check_res['error'] != 0) {
                $result['message'] .= "; {$check_res['mesg']}";
                return $result;
            }
        }

        // Update order payment status and balance
        $success = true;

        // Update player balance based on order status
        // if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log(__METHOD__, "callbackFrom {$source} already received callback for order {$order->id}", $params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            // update player balance
            // updateExternalInfo($id, $externalOrderId, $bankOrderId = null, $statusPaymentGateway = null, $statusBank = null, $response_result_id = null) {
            $this->CI->sale_order->updateExternalInfo($order->id, $params['transactionId'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto browser callback ' . $this->getPlatformCode(), false);
            }
            elseif ($source == 'server') {
                if ($params['success'] == self::PAY_CALLBACK_SUCCESS_DEFAULT) {
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
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

    private function checkCallbackOrder($order, $fields, &$processed = false, &$check_result = false) {
        $info_env = $this->getInfoByEnv();
        $error = 0x11;
        $err_mesg = "checkCallbackOrder exec incomplete";

        try {
            // $req_fields = [ 'orderno', 'usercode', 'customno', 'type', 'bankcode', 'tjmoney', 'money', 'status', 'currency', 'sign', 'resultmsg' ];
            $req_fields = [ 'mchId' , 'outTradeNo' , 'payAmount' , 'transactionId' , 'nonceStr' , 'success' , 'sign' ];

            foreach ($req_fields as $rf) {
                if (!isset($fields[$rf])) {
                    throw new Exception("Callback field missing: {$rf}", 0x21);
                }
            }

            // Check sign
            // $sign_expected = $this->calc_sign_callback($fields);
            if (!$this->verify_sign_pay_callback($fields)) {
                throw new Exception("sign verification failure", 0x22);
            }

            // Check mchId (account)
            if ($this->getSystemInfo('account') != $fields['mchId']) {
                throw new Exception("mchId mismatch, received={$fields['mchId']}, expected={$this->getSystemInfo('account')}", 0x23);
            }

            // Check payAmount (amount)
            $amount_expected = $this->convertAmountToCurrency($order->amount);
            $payAmount_formatted = $this->convertAmountToCurrency($fields['payAmount']);
            if ($amount_expected != $payAmount_formatted) {
                throw new Exception("payAmount mismatch, received={$payAmount_formatted} ({$fields['payAmount']}), expected={$amount_expected}", 0x24);
            }

            // Check outTradeNo == secure_id
            if ($order->secure_id != $fields['outTradeNo']) {
                throw new Exception("outTradeNo mismatch, received={$fields['outTradeNo']}, expected={$order->secure_id}", 0x25);
            }

            // Check success
            if (self::PAY_CALLBACK_SUCCESS_DEFAULT != $fields['success']) {
                throw new Exception("success != true, received={$fields['success']}, expected=" . self::PAY_CALLBACK_SUCCESS_DEFAULT, 0x26);
            }

            $processed = true;

            // Point of success
            $error = 0x0;
            $err_mesg = '';

        }
        catch (Exception $ex) {
            $error = $ex->getCode();
            $err_mesg = $ex->getMessage();

            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} checkCallbackOrder", "exception", $err_mesg);
            $this->writePaymentErrorLog("{$this->ident} checkCallbackOrder: {$err_mesg}", $fields);
        }
        finally {
            $ret = [ 'error' => $error, 'mesg' => $err_mesg ];
            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} checkCallbackOrder", "return", $err_mesg);

            return $ret;
        }
    } // End function checkCallbackOrder()


    public function getCheckDepositUrl() {
        $url = $this->getSystemInfo('check_deposit_url');
        return $url;
    }

    // public function checkDepositStatus($secureId) {
    //     $params = [
    //         // 'pid'           => $this->getSystemInfo('key') ,
    //         // 'money'         => $this->convertAmountToCurrency($amount) ,
    //         // 'sn'            => $secureId ,
    //         // 'notify_url'    => $this->getNotifyUrl($secureId) ,
    //         'appid'            => $this->getSystemInfo('key') ,
    //         'out_trade_no'     => $secureId ,
    //     ];

    //     // $this->configParams($params, $order->direct_pay_extra_info);

    //     $params['sign'] = $this->calc_sign_pay_stat_check($params);

    //     // $this->CI->utils->debug_log(__METHOD__, "{$this->ident} checkDepositStatus params", $params);

    //     $check_deposit_url = $this->getCheckDepositUrl();

    //     $this->CI->utils->debug_log(__METHOD__, "{$this->ident} checkDepositStatus params", $params, 'check_deposit_url', $check_deposit_url);

    //     $resp_raw = $this->submitPostForm($check_deposit_url, $params, false, $params['out_trade_no']);

    //     $this->CI->utils->debug_log(__METHOD__, "{$this->ident}  checkDepositStatus resp_raw", $resp_raw);

    //     $order = $this->CI->sale_order->getSaleOrderBySecureId($secureId);

    //     $check_decode_res = $this->decodeDepositStatusResult($resp_raw, $order);

    //     // expected fields: success (bool), message (string)
    //     $this->CI->utils->debug_log(__METHOD__, "{$this->ident}  checkDepositStatus check_decode_res", $check_decode_res);

    //     return $check_decode_res;
    // }

    // public function decodeDepositStatusResult($resp_raw, $order) {
    //     if(empty($resp_raw)){
    //         $this->CI->utils->debug_log("{$this->ident} decodeDepositStatusResult unknown result: ", $resp_raw);
    //         return [
    //             'success' => FALSE,
    //             'message' => 'Unknown response data'
    //         ];
    //     }

    //     $resp = json_decode($resp_raw, true);
    //     $this->CI->utils->debug_log("{$this->ident} decodeDepositStatusResult json_decode response: ", $resp);

    //     try {
    //         $req_fields_cd_1 = [ 'code', 'msg', 'data' ];

    //         foreach ($req_fields_cd_1 as $rf) {
    //             if (!isset($resp[$rf])) {
    //                 throw new Exception("Deposit status check resp field missing: {$rf}", 0x21);
    //             }
    //         }

    //         if ($resp['code'] != '200') {
    //             throw new Exception("Deposit status check failure: code={$resp['code']}, msg={$resp['mesg']}", 0x22);
    //         }

    //         $data = $resp['data'][0];

    //         $req_fields_cd_data = [ 'out_trade_no', 'status', 'amount' ];

    //         foreach ($req_fields_cd_data as $rf) {
    //             if (!isset($data[$rf])) {
    //                 throw new Exception("Deposit status check data field missing: {$rf}", 0x23);
    //             }
    //         }

    //         if ($data['out_trade_no'] != $order->secure_id) {
    //             throw new Exception("Deposit status check out_trade_no mismatch, out_trade_no={$data['out_trade_no']}, expected={$order->secure_id}", 0x24);
    //         }

    //         if ($data['status'] != self::PAY_STATUS_CHECK_STATUS_COMPLETE) {
    //             throw new Exception("Deposit status check failed, status={$data['status']}", 0x25);
    //         }

    //         $ret = [
    //             'success' => true, 'message' => "{$this->ident} deposit stat check successful, secure_id: {$order->secure_id}"
    //         ];

    //     }
    //     catch (Exception $ex) {
    //         $ex_code = $ex->getCode();
    //         $err_mesg = $ex->getMessage();

    //         if ($ex_code > 0) {
    //             $this->CI->utils->debug_log(__METHOD__, "{$this->ident} decodeDepositStatusResult", "exception", $err_mesg, $ex_code);
    //             $this->writePaymentErrorLog("{$this->ident} decodeDepositStatusResult {$err_mesg}", $data);
    //             $ret = [ 'success' => false, 'message' => $err_mesg ];
    //         }
    //         else {
    //             // successful
    //             $ret = [ 'success' => false, 'message' => sprintf("{$this->ident} payment successful, orderId=%s", $resp['out_trade_no']) ];
    //         }
    //     }
    //     finally {
    //         return $ret;
    //     }

    // }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    // use 2-digit precision only because we only work with CNY.
    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    protected function calc_sign_general($params, $fields) {
        // 0: prepare plaintext
        $plain_ar = [];
        foreach ($fields as $f) {
            $plain_ar[] = "{$f}={$params[$f]}";
        }

        $plain = implode('&', $plain_ar);

        // 1: prepare public key
        $key = $this->get_merchant_priv_key();

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc general", [ 'key' => $key ]);

        // 2: openssl sign
        openssl_sign($plain, $sign_raw, $key);
        // 3: base64_encode sign string
        $sign = base64_encode($sign_raw);

        $ret = [ 'plain' => $plain, 'sign' => $sign ];

        return $ret;
    }


    protected function format_key_in_openssl_format($key_raw) {
        $key = chunk_split($key_raw, 64, "\n");

        return $key;
    }

    /**
     * Format merchant private key in openssl format
     */
    protected function get_merchant_priv_key() {
        $key_raw = $this->getSystemInfo('merchant_priv_key');
        $key = $this->format_key_in_openssl_format($key_raw);
        $key_formatted = "-----BEGIN RSA PRIVATE KEY-----\n{$key}-----END RSA PRIVATE KEY-----\n";

        return $key_formatted;
    }

    /**
     * Format platform private key in openssl format
     */
    protected function get_platform_public_key() {
        $key_raw = $this->getSystemInfo('platform_public_key');
        $key = $this->format_key_in_openssl_format($key_raw);
        $key_formatted = "-----BEGIN PUBLIC KEY-----\n{$key}-----END PUBLIC KEY-----\n";

        return $key_formatted;
    }

    protected function calc_sign_pay_req($params) {
        $fields = [ 'mchId', 'outTradeNo', 'payAmount', 'nonceStr', 'tradeType', 'notifyUrl' ];

        $sign_ar = $this->calc_sign_general($params, $fields);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for payment", [ 'params' => $params, 'sign_ar' => $sign_ar ]);

        return $sign_ar['sign'];
    }

    protected function calc_sign_wx_req($params) {
        $fields = [ 'mchId' , 'orderid' , 'tkmoney' , 'banknumber' ];

        $sign_ar = $this->calc_sign_general($params, $fields);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for wx-request", [ 'params' => $params, 'sign_ar' => $sign_ar ]);

        return $sign_ar['sign'];
    }

    protected function verify_sign_general($params, $fields, $use_urldecode = false) {
        // 0: prepare plaintext
        $plain_ar = [];
        foreach ($fields as $f) {
            $plain_ar[] = "{$f}={$params[$f]}";
        }

        $plain = implode('&', $plain_ar);

        // 1: prepare public key
        $key = $this->get_platform_public_key();

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} verify sign general", [ 'key' => $key ]);
        // 2: base64_decode sign string
        if ($use_urldecode) {
            $sign_de64 = base64_decode(urldecode($params['sign']));
        }
        else {
            $sign_de64 = base64_decode($params['sign']);
        }
        // 3: openssl verify
        $verify_res = openssl_verify($plain, $sign_de64, $key);

        $ret = [ 'plain' => $plain, 'verify_res' => $verify_res ];

        return $ret;

    }

    protected function verify_sign_pay_callback($params) {
        $fields = [ 'mchId' , 'outTradeNo' , 'payAmount' , 'transactionId' , 'nonceStr' , 'success' ];

        $res = $this->verify_sign_general($params, $fields);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} verify sign for pay callback", $res);

        return $res['verify_res'];
    }

    protected function verify_sign_wx_callback($params) {
        $fields = [  'orderid', 'mchId', 'status' ];

        $res = $this->verify_sign_general($params, $fields);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} verify sign for wx callback", $res);

        return $res['verify_res'];
    }

    protected function generate_nonce() {
        $rb = random_bytes(self::PAY_NONCE_LENGTH_DEFAULT);
        $nonce = bin2hex($rb);

        return $nonce;
    }


}
