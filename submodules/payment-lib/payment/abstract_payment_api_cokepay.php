<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * COKEPAY 卡卡支付
 *
 *
 * * COKEPAY_PAYMENT_API, ID: 5919
 * * COKEPAY_PAYMENT_CLOUDTOCARD_API, ID: 5920
 *
 * Required Fields:
 * * URL
 * * Key    (appid)
 * * Secret (secret key)
 *
 * Field Values:
 * * URL: https://api.cokepaypal.com/index/unifiedorder
 * * Key: ## app ID ##
 * * Secret: ## Secret Key ##
 *
 * @see         payment_api_cokepay_cloudtocard.php
 * @see         payment_api_cokepay.php
 * @category    Payment
 * @copyright   2022 tot
 */
abstract class Abstract_payment_api_cokepay extends Abstract_payment_api {

    // const PAY_REQUEST_CODE_SUCCESS          = 1;
    // const PAY_CALLBACK_TRADE_STATUS_DEFAULT = 'TRADE_SUCCESS';

    public $deposit_callback_errors = [
        10000   => '非法参数或者传参错误' ,
        // 10002 => '请传入通道类型' ,
        10001   => '传入的return_type 参数错误，不是app或pc' ,
        10003   => '未传入appid 参数' ,
        10004   => '传入的pay_type 参数错误，不是wechat或alipay' ,
        10005   => '未传入callback_url 参数' ,
        10006   => '未传入out_trade_no 参数' ,
        10007   => '未传入amount 参数' ,
        10008   => '未传入sign 参数' ,
        10009   => '未传入ip 参数' ,
        20000   => '网站用户不存在' ,
        20001   => '网站用户不存在' ,
        20002   => '网站用户状态已禁止' ,
        20003   => '网站用户状态未审核' ,
        20004   => '网站用户费率不存在' ,
        20005   => '网站用户费率不正确' ,
        20006   => '码商用户费率不存在' ,
        20007   => '码商用户费率不正确' ,
        20008   => 'ip 请求未完成过多无法下单' ,
        30000   => '签名验证失败' ,
        40000   => '轮训通道错误' ,
        40001   => '没有可用的通道' ,
        40002   => '请求的支付方式的通道不存在' ,
        40003   => '请求的支付方式的通道已关闭' ,
        50000   => '生成订单错误' ,
        50001   => '有未完成订单，需完成旧327订单'
    ];

    public $deposit_status_codes = [
        10000   => '参数非法' ,
        10003   => '未传入appid参数' ,
        10004   => '未传入out_trade_no参数' ,
        10005   => '未传入sign 参数' ,
        20001   => '网站用户不存在' ,
        20003   => '网站用户状态未审核' ,
        30000   => '签名验证失败' ,
        40000   => '该用户暂无订单'
    ];

    const RETURN_SUCCESS_CODE         = 'success';

    const PAY_TYPE_WECHAT             = 'wechat';
    const PAY_TYPE_ALIPAY             = 'alipay';
    const PAY_TYPE_BANK               = 'bank';
    const PAY_TYPE_ALIPAYTOALI        = 'alipaytoali';
    // const PAY_TYPE_PAYCARDTOCARD      = 'paycardtocard';
    const PAY_TYPE_CARDTOCARD      = 'cardtocard';
    const PAY_TYPE_WECHATTOCARD       = 'wechattocard';
    const PAY_TYPE_CLOUDTOCARD        = 'cloudtocard';
    const PAY_TYPE_USDT               = 'usdt';

    const MONEY_TYPE_CNY              = 'cny';
    const MONEY_TYPE_USDT             = 'usdt';

    const VERSION_DEFAULT             = 'v1.1';

    const PAY_REQUEST_CODE_SUCCESS    = 200;

    const PAY_CALLBACK_COKE_CALLBACKS_SUCCESSFUL    = 'COKESSS';
    const PAY_CALLBACK_COKE_CALLBACKS_FAILURE       = 'COKEFFF';

    const PAY_STATUS_CHECK_STATUS_UNPAID            = 2;
    const PAY_STATUS_CHECK_STATUS_TIMEOUT           = 3;
    const PAY_STATUS_CHECK_STATUS_COMPLETE          = 4;

    public $ident = 'COKEPAY';

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

        // $params = [
        //     'pid'           => $this->getSystemInfo('key') ,
        //     'money'         => $this->convertAmountToCurrency($amount) ,
        //     'sn'            => $order->secure_id ,
        //     // 'sn'            => $orderId ,
        //     'notify_url'    => $this->getNotifyUrl($orderId) ,
        //     'remark'        => 'remark'
        // ];

        $params = [
            'appid'         => $this->getSystemInfo('key') ,
            'money_type'    => self::MONEY_TYPE_CNY ,
            'amount'        => $this->convertAmountToCurrency($amount) ,
            'rate'          => 1.0 ,
            // 'callback_url'  => $this->getReturnUrl($orderId) ,
            'callback_url'  => $this->getNotifyUrl($orderId) ,
            'success_url'   => $this->CI->utils->site_url_with_http('player_center2/deposit') ,
            'error_url'     => $this->CI->utils->site_url_with_http('player_center2/deposit') ,
            'out_uid'       => $playerId ,
            'out_trade_no'  => $order->secure_id ,
            'version'       => self::VERSION_DEFAULT ,
            'ip'            => $this->getClientIp()
        ];

        // add pay_type
        $this->configParams($params, $order->direct_pay_extra_info);

        $params['sign'] = $this->calc_sign_payment($params);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} generatePaymentUrlForm params", $params);

        return $this->processPaymentUrlFormPost($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $access_token = $this->getSystemInfo('key');
        // $this->_custom_curl_header = [
        //     "Content-Type: application/x-www-form-urlencoded",
        //     "Authorization: Bearer {$access_token}"
        // ];

        $url_pay_request = "{$this->getSystemInfo('url')}?format=json";
        $resp_raw = $this->submitPostForm($url_pay_request, $params, false, $params['out_trade_no']);
        $resp = json_decode($resp_raw, true);
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident}  processPaymentUrlFormPost response", $resp);


        // if (isset($resp['code']) && $resp['code'] = self::PAY_REQUEST_CODE_SUCCESS) {
        //     // if (isset($resp['data']) && isset($resp['data']['code_url'])) {
        //     if (isset($resp['url'])) {
        //         return [
        //             'success'   => true ,
        //             'type'      => self::REDIRECT_TYPE_URL ,
        //             'url'       => $resp['url'] ,
        //         ];
        //     }
        //     else {
        //         return [
        //             'success'   => false ,
        //             'type'      => self::REDIRECT_TYPE_ERROR ,
        //             'url'       => lang('Invalid API response') . " (-1)" ,
        //         ];
        //     }
        // }

        if (!isset($resp['code'])) {
            $this->CI->utils->debug_log(__METHOD__, "malformed response: code missing");
            return [
                'success'   => false ,
                'type'      => self::REDIRECT_TYPE_ERROR ,
                'url'       => lang('Invalid API response') . " (-1)" ,
            ];
        }
        else if ($resp['code'] == self::PAY_REQUEST_CODE_SUCCESS) {
            if (isset($resp['url'])) {
                return [
                    'success'   => true ,
                    'type'      => self::REDIRECT_TYPE_URL ,
                    'url'       => $resp['url'] ,
                ];
            }
            else {
                $this->CI->utils->debug_log(__METHOD__, "malformed response: url missing");
                return [
                    'success'   => false ,
                    'type'      => self::REDIRECT_TYPE_ERROR ,
                    'url'       => lang('Invalid API response') . " (-2)" ,
                ];
            }
        }
        else if (isset($resp['msg'])) {
            return [
                'success'   => false ,
                'type'      => self::REDIRECT_TYPE_ERROR ,
                'url'       => sprintf('%s (%s)', $resp['msg'], $resp['code'])
            ];
        }
        else {
            $this->CI->utils->debug_log(__METHOD__, "malformed response: msg missing");
            return [
                'success'   => false ,
                'type'      => self::REDIRECT_TYPE_ERROR ,
                'url'       => lang('Invalid API response') . " (-3)"
            ];
        }
    } // end function processPaymentUrlFormPost()

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


        // if(empty($params)){
        //     $raw_post_data = file_get_contents('php://input', 'r');
        //     $params = json_decode($raw_post_data, true);
        // }

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} callbackFrom params from Callback::process()", $params);

        // OGP-22635 workaround: always use raw input
        $raw_post_data = file_get_contents('php://input', 'r');
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} callbackFrom raw post data", $raw_post_data);
        // $params = null;
        // parse_str($raw_post_data, $params);
        $params = $_REQUEST;

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} callbackFrom $source params", $params);

        $check_result = null;
        if ($source == 'server'){
            $check_res = $this->checkCallbackOrder($order, $params, $processed, $check_result);
            if (!$order || $check_res['error'] != 0) {
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
            // COKE_PAYMENT supplies no their order ID in response, only our secure id (==coke_out_trade_no)
            $this->CI->sale_order->updateExternalInfo($order->id, $params['coke_out_trade_no'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto browser callback ' . $this->getPlatformCode(), false);
            }
            elseif ($source == 'server') {
                // if ($params['trade_status'] == self::PAY_CALLBACK_TRADE_STATUS_DEFAULT) {
                //     $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                // }
                if ($check_result == true) {
                    $this->approveSaleOrder($order->id, "auto server callback {$this->getPlatformCode()}", false);
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
            $req_fields_1 = [ 'coke_appid', 'coke_out_trade_no', 'coke_data' ];

            foreach ($req_fields_1 as $rf) {
                if (!isset($fields[$rf])) {
                    throw new Exception("Callback field missing: {$rf}", 0x12);
                }
            }

            if ($fields['coke_appid'] != $this->getSystemInfo('key')) {
                throw new Exception("Appid mismatch, coke_appid={$fields['coke_appid']}, appid={$this->getSystemInfo('key')}", 0x12);
            }

            if ($fields['coke_out_trade_no'] != $order->secure_id) {
                throw new Exception("secure_id mismatch, coke_out_trade_no={$fields['coke_out_trade_no']}, secure_id={$order->secure_id}", 0x13);
            }

            // decrypt coke_data, AES-256-ECB
            $coke_data_raw = urldecode($fields['coke_data']);

            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} callbackFrom coke_data urldecoded", $coke_data_raw);

            $coke_data_plain = $this->coke_data_decrypt($coke_data_raw);

            $coke_data = json_decode($coke_data_plain, 1);

            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} callbackFrom coke_data json_decoded", $coke_data);

            // Check coke_data fields
            $req_fields_coke = [ 'coke_callbacks', 'coke_amount', 'coke_out_trade_no' ];

            foreach ($req_fields_coke as $rf) {
                if (!isset($coke_data[$rf])) {
                    throw new Exception("coke_data field missing: {$rf}", 0x14);
                }
            }

            // Check coke_data.sign
            $sign_expected = $this->calc_sign_callback($coke_data);
            if ($coke_data['sign'] != $sign_expected) {
                throw new Exception("coke_data.sign mismatch, sign={$coke_data['sign']}, expected={$sign_expected}", 0x15);
            }

            // Check coke_data.coke_amount
            $amount_expected = $this->convertAmountToCurrency($order->amount);
            if ($amount_expected != $coke_data['coke_amount']) {
                throw new Exception("coke_data.coke_amount mismatch, coke_amount={$coke_data['coke_amount']}, expected={$amount_expected}", 0x16);
            }

            // Check coke_out_trade_no == secure_id
            if ($coke_data['coke_out_trade_no'] != $order->secure_id) {
                throw new Exception("coke_data.coke_out_trade_no mismatch, coke_out_trade_no={$coke_data['coke_out_trade_no']}, expected={$order->secure_id}", 0x17);
            }

            // Value of coke_data.coke_callbacks, should be 'COKESSS'
            if ($coke_data['coke_callbacks'] != self::PAY_CALLBACK_COKE_CALLBACKS_SUCCESSFUL) {
                throw new Exception("Callback reports failure, coke_data.coke_callbacks={$coke_data['coke_callbacks']}", 0x18);
            }

            $payment_stat_check_res = $this->checkDepositStatus($order->secure_id);

            // Check payment_stat_check_res
            if ($payment_stat_check_res['success'] != true) {
                throw new Exception("Deposit status check failed", 0x19);
            }

            $check_result = true;

            $processed = true;

            // Point of success
            $error = 0x0;
            $err_mesg = '';

        }
        catch (Exception $ex) {
            $ex_code = $ex->getCode();
            $error = $ex_code;
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

    public function checkDepositStatus($secureId) {
        $params = [
            // 'pid'           => $this->getSystemInfo('key') ,
            // 'money'         => $this->convertAmountToCurrency($amount) ,
            // 'sn'            => $secureId ,
            // 'notify_url'    => $this->getNotifyUrl($secureId) ,
            'appid'            => $this->getSystemInfo('key') ,
            'out_trade_no'     => $secureId ,
        ];

        // $this->configParams($params, $order->direct_pay_extra_info);

        $params['sign'] = $this->calc_sign_pay_stat_check($params);

        // $this->CI->utils->debug_log(__METHOD__, "{$this->ident} checkDepositStatus params", $params);

        $check_deposit_url = $this->getCheckDepositUrl();

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} checkDepositStatus params", $params, 'check_deposit_url', $check_deposit_url);

        $resp_raw = $this->submitPostForm($check_deposit_url, $params, false, $params['out_trade_no']);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident}  checkDepositStatus resp_raw", $resp_raw);

        $order = $this->CI->sale_order->getSaleOrderBySecureId($secureId);

        $check_decode_res = $this->decodeDepositStatusResult($resp_raw, $order);

        // expected fields: success (bool), message (string)
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident}  checkDepositStatus check_decode_res", $check_decode_res);

        return $check_decode_res;
    }

    public function decodeDepositStatusResult($resp_raw, $order) {
        if(empty($resp_raw)){
            $this->CI->utils->debug_log("{$this->ident} decodeDepositStatusResult unknown result: ", $resp_raw);
            return [
                'success' => FALSE,
                'message' => 'Unknown response data'
            ];
        }

        $resp = json_decode($resp_raw, true);
        $this->CI->utils->debug_log("{$this->ident} decodeDepositStatusResult json_decode response: ", $resp);

        try {
            $req_fields_cd_1 = [ 'code', 'msg', 'data' ];

            foreach ($req_fields_cd_1 as $rf) {
                if (!isset($resp[$rf])) {
                    throw new Exception("Deposit status check resp field missing: {$rf}", 0x21);
                }
            }

            if ($resp['code'] != '200') {
                throw new Exception("Deposit status check failure: code={$resp['code']}, msg={$resp['mesg']}", 0x22);
            }

            $data = $resp['data'][0];

            $req_fields_cd_data = [ 'out_trade_no', 'status', 'amount' ];

            foreach ($req_fields_cd_data as $rf) {
                if (!isset($data[$rf])) {
                    throw new Exception("Deposit status check data field missing: {$rf}", 0x23);
                }
            }

            if ($data['out_trade_no'] != $order->secure_id) {
                throw new Exception("Deposit status check out_trade_no mismatch, out_trade_no={$data['out_trade_no']}, expected={$order->secure_id}", 0x24);
            }

            if ($data['status'] != self::PAY_STATUS_CHECK_STATUS_COMPLETE) {
                throw new Exception("Deposit status check failed, status={$data['status']}", 0x25);
            }

            $ret = [
                'success' => true, 'message' => "{$this->ident} deposit stat check successful, secure_id: {$order->secure_id}"
            ];

        }
        catch (Exception $ex) {
            $ex_code = $ex->getCode();
            $err_mesg = $ex->getMessage();

            if ($ex_code > 0) {
                $this->CI->utils->debug_log(__METHOD__, "{$this->ident} decodeDepositStatusResult", "exception", $err_mesg, $ex_code);
                $this->writePaymentErrorLog("{$this->ident} decodeDepositStatusResult {$err_mesg}", $data);
                $ret = [ 'success' => false, 'message' => $err_mesg ];
            }
            else {
                // successful
                $ret = [ 'success' => false, 'message' => sprintf("{$this->ident} payment successful, orderId=%s", $resp['out_trade_no']) ];
            }
        }
        finally {
            return $ret;
        }

    }

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

    protected function calc_sign_general($params, $fields_rejected) {
        $plain_ar = [];

        ksort($params);

        foreach ($params as $key => $val) {
            if (empty($val) || in_array($key, $fields_rejected)) {
                continue;
            }
            $plain_ar[] = "{$key}={$val}";
        }

        // append key
        $plain_ar[] = "key={$this->getSystemInfo('secret')}";

        $plain = implode('&', $plain_ar);

        $sign = strtoupper(md5($plain));

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc general", [ 'plain' => $plain, 'sign' => $sign ]);

        return $sign;
    }

    protected function calc_sign_payment($params) {
        // Exclude field 'ip', 'rate'
        $sign = $this->calc_sign_general($params, [ 'ip', 'rate' ]);
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for payment", [ 'params' => $params, 'sign' => $sign ]);
        return $sign;
    }

    protected function calc_sign_callback($params) {
        // Exclude field 'sign' from callback
        $sign = $this->calc_sign_general($params, [ 'sign' ]);
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for callback", [ 'params' => $params, 'sign' => $sign ]);
        return $sign;
    }

    protected function calc_sign_pay_stat_check($params) {
        $sign = $this->calc_sign_general($params, []);
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for pay status check", [ 'params' => $params, 'sign' => $sign ]);
        return $sign;
    }

    // protected function calc_sign_callback($params) {
    //     $fields = [ 'sn', 'out_sn', 'money', 'pay_type_group', 'trade_status' ];

    //     $plain_ar = [];
    //     foreach ($fields as $f) {
    //         $plain_ar[] = "{$f}={$params[$f]}";
    //     }
    //     $plain_ar[] = "key={$this->getSystemInfo('secret')}";
    //     $plain = implode('&', $plain_ar);

    //     $sign = strtoupper(md5($plain));

    //     $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calculation", [ 'plain' => $plain, 'sign' => $sign ]);

    //     return $sign;
    // }

    protected function coke_data_decrypt($crypt_text) {
        $crypt_text_decoded = base64_decode($crypt_text);
        $plain = openssl_decrypt(
            $crypt_text_decoded,
            "aes-256-ecb",
            $this->getSystemInfo('secret')
        );
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} decryption", [ 'crypt_text' => $crypt_text, 'crypt_text_decoded' => $crypt_text_decoded, 'plain' => $plain ]);

        return $plain;
    }

}
