<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * APPAY
 *
 *
 * * APPAY_PAYMENT_API, ID: 5925
 * * APPAY_WITHDRAWAL_PAYMENT_API, ID: 5926
 *
 * Required Fields:
 * * URL
 * * Account    (merchant id)
 * * Key        (md5key)
 *
 * Field Values:
 * * URL        https://apiclient.ap2pay.com
 * * Account    ## merchant id ##
 * * Key        ## md5key ##
 *
 * @see         payment_api_appay.php
 * @see         payment_api_appay_withdrawal.php
 * @category    Payment
 * @copyright   2022 tot
 */
abstract class Abstract_payment_api_appay extends Abstract_payment_api {

    const RETURN_SUCCESS_CODE           = 'ok';

    const URL_PATH_DEPOSIT              = '/api/scanpay';
    const URL_PATH_WITHDRAW             = '/api/withdraw';
    const URL_PATH_DEPOSIT_QUERY        = '/api/query';
    const URL_PATH_WITHDRAWAL_QUERY     = '/api/query';

    const PAY_USERNAME_DEFAULT          = 'T1T';
    const PAY_PRODUCTNAME_DEFAULT       = 'T1T_DEPOSIT';

    const PAY_DEVICETYPE_MOBILE         = 1;
    const PAY_DEVICETYPE_DESKTOP        = 3;

    // const PAY_SCANTYPE_UPI_PAYTM        = 'UPI_PAY_TM';
    // const PAY_SCANTYPE_UPI_BHIM         = 'UPI_BHIM';
    // const PAY_SCANTYPE_UPI_PHONEPE      = 'UPI_PHONE_PE';
    // const PAY_SCANTYPE_GOOGLEPAY        = 'G_PAY';
    // const PAY_SCANTYPE_NETBANKING       = 'NETBANKING';
    // const PAY_SCANTYPE_EXPRESS_BANK_TX  = 'EXPRESS_BANK_TRANSFER';
    const PAY_SCANTYPE_PG365            = 'BITOLO';
    const PAY_SCANTYPE_DEFAULT          = 'BITOLO';

    const PAY_LANGUAGE_ENGLISH          = 'eng';
    const PAY_LANGUAGE_CHINESE          = 'zho';
    const PAY_LANGUAGE_DEFAULT          = 'eng';

    const PAY_CALLBACK_STATUS_SUCCESS       = 1;
    const PAY_CALLBACK_STATUS_FAILURE       = 2;

    // const PAY_REQUEST_CODE_SUCCESS    = 200;

    public $ident = 'APPAY';

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
        $playerDetails = $this->getPlayerDetails($playerId);
        $username  = (isset($playerDetails[0]) && !empty($playerDetails[0]['username']))      ? $playerDetails[0]['username']      : 'no username';
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'no firstName';
        $lastname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName']      : 'no lastName';
        $address   = (isset($playerDetails[0]) && !empty($playerDetails[0]['address']))       ? $playerDetails[0]['address']       : 'no address';
        $city      = (isset($playerDetails[0]) && !empty($playerDetails[0]['city']))          ? $playerDetails[0]['city']          : 'no city';
        $zipcode   = (isset($playerDetails[0]) && !empty($playerDetails[0]['zipcode']))       ? $playerDetails[0]['zipcode']       : '1000';
        $country   = (isset($playerDetails[0]) && !empty($playerDetails[0]['country']))       ? $playerDetails[0]['country']       : 'CN';
        $phone     = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '8615551234567';
        $email     = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : 'sample@example.com';

        $params = [
            'usercode'      => $this->getSystemInfo('account') ,
            'username'      => self::PAY_USERNAME_DEFAULT ,
            'customno'      => $order->secure_id ,
            'productname'   => self::PAY_PRODUCTNAME_DEFAULT ,
            'money'         => $this->convertAmountToCurrency($amount) ,
            // 'scantype'      => ,
            'devicetype'    => $this->CI->utils->is_mobile() ? self::PAY_DEVICETYPE_MOBILE : self::PAY_DEVICETYPE_DESKTOP ,
            'sendtime'      => $this->pay_timestamp() ,
            'notifyurl'     => $this->getNotifyUrl($orderId) ,
            'returnurl'     => $this->getReturnUrl($orderId) ,
            'buyerip'       => $this->getClientIp() ,
            // 'buyerip'       => '220.135.118.23' ,
            'language'      => self::PAY_LANGUAGE_DEFAULT,
            'mode'          => 'S2S',
            'type'          => $this->getSystemInfo('type'),
            'firstname'     => $firstname,
            'lastname'      => $lastname,
            'mobile'        => $phone,
            'email'         => $email,
            'city'          => $city,
            'state'         => $country,
            'address'       => $address,
            'zip'           => $zipcode
        ];

        // add pay_type
        $this->configParams($params, $order->direct_pay_extra_info);

        $params['sign'] = $this->calc_sign_payment($params);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} generatePaymentUrlForm params", $params);

        return $this->processPaymentUrlFormPost($params);
    }

    protected function processPaymentUrlFormPost($params) {
        // $access_token = $this->getSystemInfo('key');

        $url_pay_request = $this->getSystemInfo('url');

        $resp_raw = $this->submitPostForm($url_pay_request, $params, false, $params['customno']);
        $resp = json_decode($resp_raw, true);
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} processPaymentUrlFormPost response", $resp);

        try {
            // Check common fields
            $req_fields = [ 'success', 'resultCode', 'resultMsg' ];
            foreach ($req_fields as $rf) {
                if (!isset($resp[$rf])) {
                    throw new Exception("Malformed response, field missing: {$rf}", 0x11);
                }
            }

            // When success == false
            if ($resp['success'] == false) {
                throw new Exception("Pay request failed, error={$resp['resultMsg']} ({$resp['resultCode']})", 0x12);
            }

            // Then success must be true
            if (!isset($resp['data'])) {
                throw new Exception("Malformed response, field missing: data", 0x13);
            }

            $data = $resp['data'];
            $sign_expected = $this->calc_sign_payment_resp($data);
            if ($sign_expected != $data['sign']) {
                throw new Exception("sign mismatch, received={$data['sign']}, expected=$sign_expected", 0x14);
            }

            // Point of success
            $ret = [
                'success'   => true ,
                'type'      => self::REDIRECT_TYPE_URL ,
                'url'       => $data['scanurl']
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

        // // OGP-22635 workaround: always use raw input
        // $raw_post_data = file_get_contents('php://input', 'r');
        // $this->CI->utils->debug_log(__METHOD__, "{$this->ident} callbackFrom raw post data", $raw_post_data);
        // // $params = null;
        // // parse_str($raw_post_data, $params);
        // $params = $_REQUEST;

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} callbackFrom $source params", $params);

        if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
            return $result;
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderno'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto browser callback ' . $this->getPlatformCode(), false);
            }
            elseif ($source == 'server') {
                if ($params['status'] == self::PAY_CALLBACK_STATUS_SUCCESS) {
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
                // if ($check_result == true) {
                //     $this->approveSaleOrder($order->id, "auto server callback {$this->getPlatformCode()}", false);
                // }
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

    public function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array('orderno', 'usercode', 'customno', 'type', 'bankcode', 'tjmoney', 'money', 'status', 'currency', 'sign');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================appay missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        $sign_expected = $this->calc_sign_callback($fields);
        if ($fields['sign'] != $sign_expected) {
            $this->writePaymentErrorLog('=======================appay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::PAY_CALLBACK_STATUS_SUCCESS) {
            $payStatus = $fields['status'];
            $this->writePaymentErrorLog("=====================appay Payment was not successful, payStatus is [$payStatus]", $fields);
            return false;
        }

        $amount_expected = $this->convertAmountToCurrency($order->amount);
        if ($amount_expected != $fields['tjmoney']) {
            $this->writePaymentErrorLog("=====================appay Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($order->secure_id != $fields['customno']) {
            $this->writePaymentErrorLog("========================appay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function getCheckDepositUrl() {
        $url = $this->getSystemInfo('check_deposit_url');
        return $url;
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

    protected function calc_sign_general($params, $fields, $separator = '|') {
        $plain_ar = [];

        foreach ($fields as $f) {
            $val = isset($params[$f]) ? $params[$f] : null;
            $plain_ar[] = $val;
        }

        // Append md5key
        $plain_ar[] = $this->getSystemInfo('key');

        $plain = implode($separator, $plain_ar);

        $sign = md5($plain);

        $ret = [ 'plain' => $plain, 'sign' => $sign ];

        // $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc general", );

        return $ret;

    }

    protected function calc_sign_payment($params) {
        $fields = [ 'usercode' , 'customno' , 'scantype' , 'notifyurl' , 'money' , 'sendtime' , 'buyerip' ];
        $sign_ar = $this->calc_sign_general($params, $fields);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for payment", [ 'params' => $params, 'sign_ar' => $sign_ar ]);
        return $sign_ar['sign'];
    }

    protected function calc_sign_payment_resp($params) {
        $fields = [ 'usercode', 'orderno', 'scanurl', 'tjmoney', 'status' ];
        $sign_ar = $this->calc_sign_general($params, $fields, '');

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for payment resp", [ 'params' => $params, 'sign_ar' => $sign_ar ]);
        return $sign_ar['sign'];
    }

    protected function calc_sign_callback($params) {
        $fields = [ 'usercode', 'orderno', 'customno', 'type', 'bankcode', 'tjmoney', 'money', 'status', 'currency' ];
        $sign_ar = $this->calc_sign_general($params, $fields);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for callback", [ 'params' => $params, 'sign_ar' => $sign_ar ]);
        return $sign_ar['sign'];
    }


    protected function pay_timestamp() {
        return date('YmdHis');
    }

    public function getPlayerDetails($playerId) {
        $this->CI->load->model(array('player_model'));
        $player = $this->CI->player_model->getPlayerDetails($playerId);
        return $player;
    }

    // protected function coke_data_decrypt($crypt_text) {
    //     $crypt_text_decoded = base64_decode($crypt_text);
    //     $plain = openssl_decrypt(
    //         $crypt_text_decoded,
    //         "aes-256-ecb",
    //         $this->getSystemInfo('secret')
    //     );
    //     $this->CI->utils->debug_log(__METHOD__, "{$this->ident} decryption", [ 'crypt_text' => $crypt_text, 'crypt_text_decoded' => $crypt_text_decoded, 'plain' => $plain ]);

    //     return $plain;
    // }

}
