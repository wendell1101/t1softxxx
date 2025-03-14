<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * SPEEDPAY 快付
 *
 * * SPEEDPAY_PAYMENT_API, ID: 5915
 * * SPEEDPAY_ALIPAY_PAYMENT_API, ID: 5916
 *
 * Required Fields:
 * * URL
 * * Key
 * * Secret
 *
 * Field Values:
 * * URL: https://api.speedpay123.com/pay
 * * Key: ## Live ID ##
 * * Secret: ## Secret Key ##
 *
 * @see         payment_api_speedpay_alipay.php
 * @see         payment_api_speedpay.php
 * @category    Payment
 * @copyright   2022 tot
 */
abstract class Abstract_payment_api_speedpay extends Abstract_payment_api {
    const PAY_TYPE_GROUP_ALIPAY             = 'alipaytocard';
    const PAY_TYPE_GROUP_CARD               = 'banktocard';

    const PAY_REQUEST_CODE_SUCCESS          = 1;
    const PAY_CALLBACK_TRADE_STATUS_DEFAULT = 'TRADE_SUCCESS';
    const PAY_STATUS_CHECK_STATUS_SUCCESS   = 'success';
    const RETURN_SUCCESS_CODE               = 'success';


    public $ident = 'SPEEDPAY';

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

        $params = [
            'pid'           => $this->getSystemInfo('key') ,
            'money'         => $this->convertAmountToCurrency($amount) ,
            'sn'            => $order->secure_id ,
            // 'sn'            => $orderId ,
            'notify_url'    => $this->getNotifyUrl($orderId) ,
            'remark'        => 'remark'
        ];

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

        $resp_raw = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['sn']);
        $resp = json_decode($resp_raw, true);
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident}  processPaymentUrlFormPost response", $resp);


        if (isset($resp['code']) && $resp['code'] = self::PAY_REQUEST_CODE_SUCCESS) {
            if (isset($resp['data']) && isset($resp['data']['code_url'])) {
                return [
                    'success'   => true ,
                    'type'      => self::REDIRECT_TYPE_URL ,
                    'url'       => $resp['data']['code_url'] ,
                ];
            }
            else {
                return [
                    'success'   => false ,
                    'type'      => self::REDIRECT_TYPE_ERROR ,
                    'url'       => lang('Invalid API response') . " (-1)" ,
                ];
            }
        }
        else if (isset($resp['msg'])) {
            return [
                'success'   => false ,
                'type'      => self::REDIRECT_TYPE_ERROR ,
                'url'       => $msg ,
            ];
        }
        else {
            return [
                'success'   => false ,
                'type'      => self::REDIRECT_TYPE_ERROR ,
                'url'       => lang('Invalid API response') . " (-2)" ,
            ];
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
        $result = [
            'success' => false,
            'next_url' => null,
            'message' => lang('error.payment.failed')
        ];

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} callbackFrom $source params", $params);

        if ($source == 'server'){
            $check_res = $this->checkCallbackOrder($order, $params, $processed);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['sn'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto browser callback ' . $this->getPlatformCode(), false);
            }
            elseif ($source == 'server') {
                if ($params['trade_status'] == self::PAY_CALLBACK_TRADE_STATUS_DEFAULT) {
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

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $info_env = $this->getInfoByEnv();
        $error = 0x11;
        $err_mesg = "checkCallbackOrder exec incomplete";

        try {
            // Check for required fields
            $required_fields = [
                'sn', 'out_sn', 'money', 'pay_type_group', 'trade_status', 'encryption'
            ];

            foreach ($required_fields as $rf) {
                if (!isset($fields[$rf])) {
                    throw new Exception("Callback field missing: {$f}", 0x12);
                }
            }

            // Check for signature
            $signature_expected = $this->calc_sign_callback($fields);
            if ($signature_expected != $fields['encryption']) {
                $this->writePaymentErrorLog('Wrong signature for server callback', $fields);
                $this->CI->utils->debug_log('Signature mismatch', 'given', $fields['encryption'], 'expected', $secure_expected);
                throw new Exception('Signature mismatch', 0x13);
            }

            // Check amount
            if ($this->convertAmountToCurrency($order->amount) != $fields['money']) {
                throw new Exception('Amount mismatch', 0x14);
            }

            // Check trade_no == secure_id
            if ($fields['out_sn'] != $order->secure_id) {
                throw new Exception('Secure_id mismatch', 0x15);
            }

            if ($fields['trade_status'] != self::PAY_CALLBACK_TRADE_STATUS_DEFAULT) {
                throw new Exception('Unexpected value for trade_status', 0x16);
            }

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

    public function checkDepositStatus($secureId) {
        $params = [
            'pid'           => $this->getSystemInfo('key') ,
            'money'         => $this->convertAmountToCurrency($amount) ,
            // 'sn'            => $order->secure_id ,
            'sn'            => $secureId ,
            'notify_url'    => $this->getNotifyUrl($secureId) ,
        ];

        $this->configParams($params, $order->direct_pay_extra_info);

        $params['sign'] = $this->calc_sign_payment($params);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} checkDepositStatus params", $params);

        $check_deposit_url = $this->getCheckDepositUrl();
        $resp_raw = $this->submitPostForm($check_deposit_url, $params, false, $params['sn']);
        // $resp = json_decode($resp_raw, true);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident}  checkDepositStatus resp_raw", $resp_raw);

        return $this->decodeDepositStatusResult($resp_raw);
    }

    public function getCheckDepositUrl() {
        $url = $this->getSystemInfo('check_deposit_url');
    }

    public function decodeDepositStatusResult($resp_raw) {
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
            if (!isset($resp['code'])) {
                throw new Exception('Deposit check return malformed (1)', 0x31);
            }

            if ($resp['code'] == self::PAY_REQUEST_CODE_SUCCESS) {
                if (!isset($resp['data']) || !isset($resp['data']['status'])) {
                    throw new Exception('Deposit check return malformed (2)', 0x32);
                }

                if ($resp['data']['status'] != self::PAY_STATUS_CHECK_STATUS_SUCCESS) {
                    throw new Exception(sprintf('Deposit check code=%s, status=%s', $resp['code'], $resp['data']['status']), 0x33);
                }

                if ($resp['data']['status'] == self::PAY_STATUS_CHECK_STATUS_SUCCESS) {
                    throw new Exception('Deposit check successful', -1);
                }
            }

            if (isset($resp['msg'])) {
                throw new Exception(sprintf('Deposit check code=%s, msg=%s', $resp['code'], $resp['msg']), 0x34);
            }
        }
        catch (Exception $ex) {
            $ex_code = $ex->getCode();
            $err_mesg = $ex->getMessage();

            if ($ex_code > 0) {
                $this->CI->utils->debug_log(__METHOD__, "{$this->ident} decodeDepositStatusResult", "exception", $err_mesg, $ex_code);
                $this->writePaymentErrorLog("{$this->ident} decodeDepositStatusResult {$err_mesg}");
                $ret = [ 'success' => false, 'message' => $err_mesg ];
            }
            else {
                // successful
                $ret = [ 'success' => false, 'message' => sprintf("{$this->ident} payment successful, orderId=%s", $resp['sn']) ];
            }
        }
        finally {
            return $ret;
        }

    }

    // public function signature_for_server_callback($params, $info_env) {
    //     $access_token = $info_env['key'];

    //     unset($params['signature']);
    //     ksort($params);

    //     $plain = json_encode($params);
    //     $signature = md5($plain . $access_token);
    //     if ($this->DEBUG) {
    //         $this->utils->debug_log(__METHOD__, [ 'plain_text' => $plain, 'signature' => $signature ]);
    //     }

    //     return $signature;
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

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    protected function calc_sign_payment($params) {
        $fields = [ 'pid', 'money', 'sn', 'pay_type_group', 'notify_url' ];

        $plain_ar = [];
        foreach ($fields as $f) {
            $plain_ar[] = "{$f}={$params[$f]}";
        }
        $plain_ar[] = "key={$this->getSystemInfo('secret')}";
        $plain = implode('&', $plain_ar);

        $sign = strtoupper(md5($plain));

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calculation", [ 'plain' => $plain, 'sign' => $sign ]);

        return $sign;
    }

    protected function calc_sign_callback($params) {
        $fields = [ 'sn', 'out_sn', 'money', 'pay_type_group', 'trade_status' ];

        $plain_ar = [];
        foreach ($fields as $f) {
            $plain_ar[] = "{$f}={$params[$f]}";
        }
        $plain_ar[] = "key={$this->getSystemInfo('secret')}";
        $plain = implode('&', $plain_ar);

        $sign = strtoupper(md5($plain));

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calculation", [ 'plain' => $plain, 'sign' => $sign ]);

        return $sign;
    }

}
