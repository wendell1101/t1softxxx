<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * SANLIAN
 *
 * * SANLIAN_PAYMENT_API, ID: 5934
 * * SANLIAN_USDT_PAYMENT_API, ID: 5935
 *
 * Required Fields:
 * * URL
 * * Account    (merchant id)
 * * extra_info.request_key
 * * extra_info.callback_key
 *
 * Field Values:
 * * URL        http://api.asia-pay8.com/api/unifiedorder
 * * Account    ## merchant id #
 * * extra_info.request_key      ## request key ##
 * * extra_info.callback_key     ## callback key ##
 *
 * @see         payment_api_sanlian.php
 * @see         payment_api_sanlian_usdt.php
 * @category    Payment
 * @copyright   2022 tot
 */
abstract class Abstract_payment_api_sanlian extends Abstract_payment_api {

    const RETURN_SUCCESS_CODE           = 'success';

    const PAY_REQ_RETURN_TYPE_PC        = 'pc';
    const PAY_REQ_RETURN_TYPE_MOBILE    = 'mobile';
    const PAY_REQ_RETURN_TYPE_APP       = 'app';    // not used

    const PAY_REQ_VERSION_DEFAULT       = '2.1';

    const PAY_REQ_ISEXDATA_YES          = 1;
    const PAY_REQ_ISEXDATA_NO           = 2;
    const PAY_REQ_ISEXDATA_DEFAULT      = 1;

    const PAY_REQ_ISOPEN_DEFAULT        = 1;

    const PAY_REQ_FORM_TYPE_DEFAULT     = 1;

    const PAY_REQ_SIGN_TYPE_DEFAULT     = 'md5';

    const PAY_REQ_SHOW_TYPE_CHAT        = 'chat';
    const PAY_REQ_SHOW_TYPE_STATIC      = 'static';
    const PAY_REQ_SHOW_TYPE_DEFAULT     = 'chat';

    const PAY_REQ_PAY_ID_CARD2CARD      = 8201; // 银行卡转卡
    const PAY_REQ_PAY_ID_USDT           = 8401; // 币支付 (usdt)
    const PAY_REQ_PAY_ID_ALIPAY2CARD    = 8203; // 支付宝转卡      (not used)
    const PAY_REQ_PAY_ID_ALIPAY2ALIPAY  = 8101; // 支付宝转支付宝  (not used)
    const PAY_REQ_PAY_ID_CORPALIPAY     = 8111; // 企业支付宝      (not used)

    const PAY_CALLBACK_PAY_CODE_SUCCESS     = 'CODE_SUCCESS';
    const PAY_CALLBACK_PAY_CODE_FAILURE     = 'CODE_FAILURE';


    // const URL_PATH_DEPOSIT              = '/api/unifiedorder';

    // const PAY_TRADETYPE_DEFAULT         = 'ZFBZK';

    // const PAY_RESP_CODE_SUCCESS         = 200;
    // const PAY_RESP_CODE_FAILURE         = 500;

    // const PAY_CALLBACK_SUCCESS_DEFAULT  = 'true';

    // const PAY_NONCE_LENGTH_DEFAULT      = 8;    // each byte converts to 2 chars

    public $ident = 'SANLIAN';

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
        $player_details = $this->CI->player_model->getAllPlayerDetailsById($playerId);

        // $params = [
        //     'mchId'         => $this->getSystemInfo('account') ,
        //     'outTradeNo'    => $order->secure_id ,
        //     'tradeType'     => self::PAY_TRADETYPE_DEFAULT ,
        //     'nonceStr'      => $this->generate_nonce() ,
        //     'notifyUrl'     => $this->getNotifyUrl($orderId) ,
        //     'payAmount'     => $this->convertAmountToCurrency($amount) ,
        //     // 'payName'       =>
        // ];

        $params = [
            'return_type'   => $this->CI->utils->is_mobile() ? self::PAY_REQ_RETURN_TYPE_MOBILE : self::PAY_REQ_RETURN_TYPE_PC ,
            'appid'         => $this->getSystemInfo('account') ,
            // 'pay_id'        => '' ,
            'amount'        => $this->convertAmountToCurrency($amount) ,
            'success_url'   => $this->getNotifyUrl($orderId) ,
            'error_url'     => $this->CI->utils->site_url_with_http('player_center2/deposit') ,
            'out_uid'       => $playerId ,
            'out_trade_no'  => $order->secure_id ,
            'version'       => self::PAY_REQ_VERSION_DEFAULT ,
            // 'isexdata'      => self::PAY_REQ_ISEXDATA_DEFAULT ,
            // 'isopen'        => self::PAY_REQ_ISOPEN_DEFAULT ,
            // 'drawee'        => $player_details['firstName'] ,
            'client_ip'     => $this->getClientIp() ,
            // 'client_ip'     => '220.135.118.23' ,
            // 'form_type'     => self::PAY_REQ_FORM_TYPE_DEFAULT ,
            'sign_type'     => self::PAY_REQ_SIGN_TYPE_DEFAULT ,
            // 'show_type'     => self::PAY_REQ_SHOW_TYPE_DEFAULT  ,
            // 'sign'          => '' ,
        ];

        // add pay_type
        $this->configParams($params, $order->direct_pay_extra_info);

        return $this->processPaymentUrlFormPost($params);
    }

    protected function pay_timestamp() {
        return date('YmdHis');
    }

    protected function processPaymentUrlFormPost($params) {
        $platform_code = $this->getPlatformCode();
        // $this->CI->utils->debug_log(__METHOD__, "{$this->ident} platform", [ 'platform_code' => $platform_code, 'sanlian' => SANLIAN_PAYMENT_API, 'sanlian_usdt' => SANLIAN_USDT_PAYMENT_API ]);
        if ($platform_code != SANLIAN_USDT_PAYMENT_API) {
            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} platform", 'platform_code', $platform_code, '(SANLIAN)' );
        }
        else {
            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} platform", 'platform_code', $platform_code, '(SANLIAN_USDT)' );
            $crypto = $params['crypto_amount'];
            $amount = $params['amount'];
            $params['amount'] = $crypto;
            $validateRateResult = $this->validateDepositCryptoRate('USDT', $amount, $crypto);
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
            $this->CI->utils->debug_log("{$this->ident} processPaymentUrlFormRedirect crypto info", [ 'crypto' => $crypto, 'rate' => $rate ]);
            unset($params['crypto_amount']);
        }

        // calculate sign here
        $params['sign'] = $this->calc_sign_pay_req($params);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} processPaymentUrlFormPost params", $params);

        $url_pay_request = $this->getSystemInfo('url');

        $resp_raw = $this->submitPostForm($url_pay_request, $params, false, $params['out_trade_no']);
        $resp = json_decode($resp_raw, true);
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} processPaymentUrlFormPost response", $resp);

        try {
            if (!isset($resp['code'])) {
                throw new Exception("Malformed response, field missing: code", 0x10);
            }

            if ($resp['code'] > 0) {
                if (isset($resp['msg'])) {
                    throw new Exception("Pay request failed, message={$resp['msg']} ({$resp['code']})", 0x11);
                }
                else {
                    throw new Exception("Pay request failed, message=(none)", 0x11);
                }
            }

            if (!isset($resp['data'])) {
                throw new Exception("Malformed response, field missing: code", 0x12);
            }

            $data = $resp['data'];

            // Check common fields
            $req_fields = [ 'qrcode', 'url', 'out_trade_no' ];
            foreach ($req_fields as $rf) {
                if (!array_key_exists($rf, $data)) {
                    throw new Exception("Malformed response, field missing: {$rf}", 0x13);
                }
            }

            // Check out_trade_no
            if ($params['out_trade_no'] != $data['out_trade_no']) {
                throw new Exception("out_trade_no mismatch, received={$data['out_trade_no']}, expected={$params['out_trade_no']}", 0x12);
            }

            if (empty($data['url'])) {
                throw new Exception("url empty", 0x13);
            }

            // Create USDT sale order
            if ($platform_code == SANLIAN_USDT_PAYMENT_API) {
                $order = $this->CI->sale_order->getSaleOrderBySecureId($params['out_trade_no']);
                $orderId = $order->id;
                // $fee = floatval($data['transfer_money']) - floatval($data['original_money']);
                $fee = 0;
                $this->CI->sale_order->updateExternalInfo($order->id, null, $fee, $crypto);
                $this->CI->sale_order->createCryptoDepositOrder($orderId, $crypto, $rate, null, null,'USDT');

                $deposit_notes = "3rd Api Wallet address: (unknown) | Real Rate: {$rate} | USDTcoin: {$crypto}";
                $this->CI->sale_order->appendNotes($order->id, $deposit_notes);
            }

            // Point of success
            $ret = [
                'success'   => true ,
                'type'      => self::REDIRECT_TYPE_URL ,
                'url'       => $data['url']
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


    //
    /**
     * Extracts orderId from fixed callback requests
     * Fixed callback URI: /callback/fixed_process/<payment_id>
     */
    public function getOrderIdFromParameters($params) {
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} getOrderIdFromParameters", "raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} getOrderIdFromParameters", "json_decode params", $params);
        }

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} getOrderIdFromParameters", "params", $params);

        if (!isset($params['out_trade_no'])) {
            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} getOrderIdFromParameters", "cannot find order id in callback request");
            return;
        }

        $this->CI->load->model([ 'sale_order','wallet_model' ]);
        $out_trade_no = $params['out_trade_no'];
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} getOrderIdFromParameters", "out_trade_no", $out_trade_no);
        if (substr($out_trade_no, 0, 1) == 'D') {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($out_trade_no);
            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} getOrderIdFromParameters", "order_id", ($order ? $order->id : '(order=null)') );
            return $order->id;
        }
        else {
            return $out_trade_no;
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

    protected function callbackFromServerBare($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
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
        // $params = $_REQUEST;

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
            // SANLIAN does not supply external tx id; it just sends back the secure_id
            $this->CI->sale_order->updateExternalInfo($order->id, $params['out_trade_no'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto browser callback ' . $this->getPlatformCode(), false);
            }
            elseif ($source == 'server') {
                if ($params['callbacks'] == self::PAY_CALLBACK_PAY_CODE_SUCCESS) {
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
            $req_fields = [ 'amount', 'amount_true', 'appid', 'callbacks', 'error_url', 'fee', 'out_trade_no', 'out_uid', 'pay_id', 'success_url', 'sign' ];

            foreach ($req_fields as $rf) {
                if (!isset($fields[$rf])) {
                    throw new Exception("Callback field missing: {$rf}", 0x21);
                }
            }

            // Check sign
            $sign_expected = $this->calc_sign_pay_callback($fields);
            if ($sign_expected != $fields['sign']) {
                throw new Exception("sign verification failure, received={$fields['sign']}, expected={$sign_expected}", 0x22);
            }

            // check appid
            if ($this->getSystemInfo('account') != $fields['appid']) {
                throw new Exception("mchId mismatch, received={$fields['appid']}, expected={$this->getSystemInfo('account')}", 0x23);
            }

            // check amount (usdt/non-usdt)
            if ($fields['pay_id'] == self::PAY_REQ_PAY_ID_USDT) {
                // order->amount    unit: main currency
                $amount = $this->convertAmountToCurrency($order->amount);
                // fields.amount    unit: USDT
                // crypto_amount    unit: main currency
                //                  (= field.amount * rate in crypto_order created when payment is submitted)
                $crypto_amount = $this->convertCryptoAmountToMainCurrency($fields['amount'], $order);

                $this->CI->utils->debug_log("{$this->ident} checkCallbackOrder", [ 'order.amount' => $order->amount, 'amount' => $amount, 'callback.amount' => $fields['amount'], 'crypto_amount' => $crypto_amount ]);

                if ($crypto_amount != $amount) {
                    if ($this->getSystemInfo('allow_callback_amount_diff')) {
                        $percentage = $this->getSystemInfo('diff_amount_percentage');
                        $limit_amount = $this->getSystemInfo('diff_limit_amount');

                        if (!empty($percentage) && !empty($limit_amount)) {
                            $percentage_amt = str_replace(',', '', $amount) * ($percentage / 100);
                            $diffAmtPercentage = abs(str_replace(',', '', $amount) - $percentage_amt);

                            $this->CI->utils->debug_log("{$this->ident} checkCallbackOrder amount details",$percentage, $limit_amount, $percentage_amt, $diffAmtPercentage);

                            if ($percentage_amt > $limit_amount) {
                                // $this->writePaymentErrorLog("{$this_ident} checkCallbackOrder Payment amounts ordAmt - payAmt > $limit_amount limit amount, expected [$order->amount]", $fields ,$diffAmount);
                                throw new Exception("callback.amount mismatch, received={$crypto_amount}, expected={$amount}; percentage_amt > limit_amount ({$percentage_amt} > {$limit_amount})");
                            }

                            if ($fields['original_money'] < $diffAmtPercentage) {
                                throw new Exception("callback.amount mismatch, received={$crypto_amount}, expected={$amount}; callback.amount > diffAmtPercentage ({$original_money} > {$diffAmtPercentage})");
                            }

                        }

                        $this->CI->utils->debug_log("callback.amount mismatch, received={$crypto_amount}, expected={$amount}", $fields);
                        $notes = "{$order->notes} | amount from callback different, original was: {$amount}";
                        $this->CI->sale_order->fixOrderAmount($order->id, str_replace(',', '', $fields['original_money']), $notes);
                    }
                    else {
                       throw new Exception("callback.amount mismatch, received={$crypto_amount}, expected={$amount}");
                    }
                }
            }
            else {
                $amount_expected = $this->convertAmountToCurrency($order->amount);
                if ($amount_expected != $fields['amount']) {
                    throw new Exception("amount mismatch, received={$fields['amount']}, expected={$amount_expected}", 0x24);
                }
            }

            // check out_trade_no
            if ($order->secure_id != $fields['out_trade_no']) {
                throw new Exception("out_trade_no mismatch, received={$fields['outTradeNo']}, expected={$order->secure_id}", 0x25);
            }

            // check out_uid
            if ($order->player_id != $fields['out_uid']) {
                throw new Exception("out_uid mismatch, received={$fields['out_uid']}, expected={$order->player_id}", 0x26);
            }

            if (self::PAY_CALLBACK_PAY_CODE_SUCCESS != $fields['callbacks']) {
                throw new Exception("callbacks mismatch, received={$fields['callbacks']}, expected=" . self::PAY_CALLBACK_PAY_CODE_SUCCESS, 0x27);
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

    protected function convertCryptoAmountToMainCurrency($amount, $order) {
        $cryptoOrder = $this->CI->sale_order->getUsdtRateBySaleOrderId($order->id);
        // $fee = $order->bank_order_id; //bank_order_id is a template to record fee for this api
        // $cryptoAmount = ($amount - $fee) * $cryptoOrder->rate;
        $cryptoAmount = $amount * $cryptoOrder->rate;
        $cryptoAmount_ret = number_format($cryptoAmount, 2, '.', '');
        $this->CI->utils->debug_log("{$this->ident} convertAmountToCrypto", [ 'amount' => $amount, 'rate' => $cryptoOrder->rate , 'cryptoAmount' => $cryptoAmount, 'cryptoAmount_ret' => $cryptoAmount_ret ]);
        // return substr(sprintf("%.3f", $cryptoAmount),0,-1);
        return $cryptoAmount_ret;
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

    protected function calc_sign_general($params, $sign_key, $exclude = []) {
        // sort key by ascii, asc
        ksort($params);

        // generate plaintext
        // $plain = http_build_query($params);
        $plain_ar = [];
        foreach ($params as $key => $val) {
            if (empty($val)) {
                continue;
            }
            if (in_array($key, $exclude)) {
                continue;
            }
            $plain_ar[] = "{$key}={$val}";
        }

        $plain_ar[] = "key={$sign_key}";

        $plain = implode('&', $plain_ar);

        $hash = md5($plain);
        $sign = strtoupper($hash);

        $ret = [ 'plain' => $plain, 'hash' => $hash, 'sign' => $sign ];

        return $ret;
    }

    protected function calc_sign_pay_req($params) {
        $key = $this->getSystemInfo('request_key');
        $calc_res = $this->calc_sign_general($params, $key);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for pay-request", [ 'key' => $key ], $calc_res);

        return $calc_res['sign'];
    }

    protected function calc_sign_pay_callback($params) {
        $key = $this->getSystemInfo('callback_key');
        $calc_res = $this->calc_sign_general($params, $key, [ 'sign' ]);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for pay-callback", [ 'key' => $key ], $calc_res);

        return $calc_res['sign'];
    }


    // protected function generate_nonce() {
    //     $rb = random_bytes(self::PAY_NONCE_LENGTH_DEFAULT);
    //     $nonce = bin2hex($rb);

    //     return $nonce;
    // }


}
