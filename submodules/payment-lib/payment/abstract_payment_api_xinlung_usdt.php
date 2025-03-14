<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * applepay
 *
 * * XINLUNG_USDT_PAYMENT_API, ID: 5924
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://merchant.chainpro.me/api/Interface/Guide
 * * Account: ## Live Merchant no ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
abstract class Abstract_payment_api_xinlung_usdt extends Abstract_payment_api {
    const PAY_RESP_CODE_SUCCESS         = 1;
    const PAY_RESP_CODE_FAILURE         = 0;

    const PAY_DO_DEFAULT                = 'deposit';
    const PAY_BLOCKTYPE_TRXUSDT         = 'TrxUsdt';
    const PAY_BLOCKTYPE_ETHUSDT         = 'EthUsdt';

    const CALLBACK_STATUS_SUCCESS       = 'success';
    const CALLBACK_DO_DEFAULT           = 'deposit_notice';

    protected $RETURN_ARR_SUCCESS  = [ 'status' => 'success' ];
    protected $RETURN_ARR_FAILURE  = [ 'status' => 'error', 'info' => '回调失败' ];
    protected $ident = 'XINLUNG';

    public function __construct($params = null) {
        parent::__construct($params);
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

        $params = [
            'do'            => self::PAY_DO_DEFAULT ,
            'merchant_id'    => $this->getSystemInfo('account') ,
            'blockType'     => self::PAY_BLOCKTYPE_TRXUSDT ,
            'order_id'      => $order->secure_id ,
            'callback_url'  => $this->getNotifyUrl($orderId) ,
        ];


        $this->configParams($params, $order->direct_pay_extra_info);

        // token calculation: moved to processPaymentUrlFormRedirect()

        return $this->processPaymentUrlForm($params);
    }

    protected function pay_arg_timestamp() {
        $tstamp = date('YmdHis');
        return $tstamp;
    }

    protected function processPaymentUrlFormRedirect($params) {
        // $orderId = $params['order_id'];
        $crypto = $params['money'];

        // OGP-23701: use fixed_exchange_rate if present
        $fixed_exchange_rate = floatval($this->getSystemInfo('fixed_exchange_rate'));
        if ($fixed_exchange_rate <= 0) {
            $rate = $params['rate'];
        }
        else {
            $rate = $fixed_exchange_rate;
        }


        $this->CI->utils->debug_log("{$this->ident} processPaymentUrlFormRedirect crypto info", [ 'crypto' => $crypto, 'rate' => $rate ]);

        // token calculation: remove field 'rate' first
        unset($params['rate']);
        $params['token'] = $this->calc_sign_payment($params);
        $this->CI->utils->debug_log("{$this->ident} processPaymentUrlFormRedirect params", $params);

        $url = $this->getSystemInfo('url');
        $this->CI->utils->debug_log("{$this->ident} processPaymentUrlFormRedirect api host url", $url);
        $response = $this->submitPostForm($url, $params, true, $params['order_id']);
        $decode_data = json_decode($response, true);
        $this->CI->utils->debug_log("{$this->ident} processPaymentUrlFormRedirect response json to array", $decode_data);
        $msg = lang('Invalid API response');

        try {
            if (!isset($decode_data['code'])) {
                if (isset($decode_data['Message'])) {
                    throw new Exception("Request failed, Message={$decode_data['Message']}", 0x11);
                }
                else {
                    throw new Exception("Malformed response, field missing: code", 0x12);
                }
            }

            if ($decode_data['code'] != self::PAY_RESP_CODE_SUCCESS) {
                if (isset($decode_data['msg'])) {
                    throw new Exception("Request failed, msg={$decode_data['msg']}", 0x13);
                }
                else {
                    throw new Exception("Request failed", 0x14);
                }
            }

            // Must be successful at this point
            if (!isset($decode_data['data'])) {
                throw new Exception("Malformed response, field missing: data", 0x15);
            }

            $data = $decode_data['data'];
            if (!isset($data['pay_url'])) {
                throw new Exception("Malformed response, field missing: data.pay_url", 0x16);
            }

            /**
             * Expected response format
             * code     int     1 | 0
             * msg      string
             * data     object
             *     platform_order_id    string
             *     original_money       decimal
             *     transfer_money       decimal
             *     toAddress            string      USDT account
             *     pay_url              string      URL for 3rd payment page
             */

            // Create USDT sale order

            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['order_id']);
            $orderId = $order->id;
            $fee = floatval($data['transfer_money']) - floatval($data['original_money']);
            $this->CI->sale_order->updateExternalInfo($order->id, $data['toAddress'], $fee, $crypto);
            $this->CI->sale_order->createCryptoDepositOrder($orderId, $crypto, $rate, null, null,'USDT');

            $deposit_notes = "3rd Api Wallet address: {$data['toAddress']} | Real Rate: {$rate} | USDTcoin: {$crypto}";
            $this->CI->sale_order->appendNotes($order->id, $deposit_notes);

            // Return (redirect) url for successful response
            $ret = [
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $data['pay_url']
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
                $this->CI->utils->debug_log("{$this->ident} raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data, true);
                $this->CI->utils->debug_log("{$this->ident} json_decode params", $params);
            }
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        $this->CI->utils->debug_log("{$this->ident} callbackFrom $source params", $params);

        $success = true;

        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            // update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $params['platform_order_id'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto browser callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            // $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['message'] = json_encode($this->RETURN_ARR_SUCCESS);
        } else {
            // $result['return_error'] = 'Error';
            $result['return_error'] = json_encode($this->RETURN_ARR_FAILURE);
            $result['message'] = json_encode($this->RETURN_ARR_FAILURE);
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        /**
         * Expected callback fields
         *     do                   default: string 'deposit_notice'
         *     platform_order_id    external order id
         *     order_id             SBE order secure_id
         *     status               default: string 'success'
         *     money                Amount of deposit
         *     pay_money            Actual paid amount
         *     time                 Unit timestamp
         *     token                Token
         */
        $requiredFields = array(
            // 'do', 'platform_order_id', 'order_id', 'status', 'money', 'pay_money', 'time', 'token'
            // 'platform_order_id', 'order_id', 'status', 'money', 'pay_money', 'time', 'token'
            'platform_order_id', 'order_id', 'status', 'original_money', 'transfer_money', 'time', 'token'
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
            if ($sign_expected != $fields['token']) {
                // $this->writePaymentErrorLog("=====================applepay checkCallbackOrder signature mismatch, expected={$sign_expected}, received={$fields['sign']}");
                throw new Exception("sign mismatch, expected={$sign_expected}, received={$fields['token']}");
                return false;
            }

            $processed = true;

            // if (self::CALLBACK_DO_DEFAULT != $fields['do']) {
            //     throw new Exception("do != 'deposit_notice', received={$fields['do']}");
            // }

            // Check status
            if (self::CALLBACK_STATUS_SUCCESS != $fields['status']) {
                throw new Exception("status != success, received={$fields['status']}");
            }

            // Check orderNo
            if ($order->secure_id != $fields['order_id']) {
                throw new Exception("order_id mismatch, expected={$order->secure_id}, received={$fields['order_id']}");
            }

            // $expected_money = $this->convertAmountToCurrency($order->amount);
            // if ($expected_money != $fields['money']) {
            //     throw new Exception("money mismatch, expected={$expected_money}, received={$fields['money']}");
            // }

            // order->amount    unit: main currency
            $amount = $order->amount;
            // fields.original_money    unit: USDT
            // crypto_amount            unit: main currency
            //                          (= field.original_money * rate in crypto_order created when payment is submitted)
            $crypto_amount = $this->convertAmountToCrypto($fields['original_money'], $order);

            $this->CI->utils->debug_log("{$this->ident} checkCallbackOrder", [ 'order.amount' => $order->amount, 'amount' => $amount, 'crypto_amount' => $crypto_amount ]);

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
                            throw new Exception("original_money mismatch, received={$crypto_amount}, expected={$amount}; percentage_amt > limit_amount ({$percentage_amt} > {$limit_amount})");
                        }

                        if ($fields['original_money'] < $diffAmtPercentage) {
                            throw new Exception("original_money mismatch, received={$crypto_amount}, expected={$amount}; original_money > diffAmtPercentage ({$original_money} > {$diffAmtPercentage})");
                        }

                    }

                    $this->CI->utils->debug_log("original_money mismatch, received={$crypto_amount}, expected={$amount}", $fields);
                    $notes = "{$order->notes} | original_money from callback different, original was: {$amount}";
                    $this->CI->sale_order->fixOrderAmount($order->id, str_replace(',', '', $fields['original_money']), $notes);
                }
                else {
                   throw new Exception("original_money mismatch, received={$crypto_amount}, expected={$amount}");
                }
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

    protected function convertAmountToCrypto($amount, $order) {
        $cryptoOrder = $this->CI->sale_order->getUsdtRateBySaleOrderId($order->id);
        // $fee = $order->bank_order_id; //bank_order_id is a template to record fee for this api
        // $cryptoAmount = ($amount - $fee) * $cryptoOrder->rate;
        $cryptoAmount = $amount * $cryptoOrder->rate;
        $cryptoAmount_ret = number_format($cryptoAmount, 2, '.', '');
        $this->CI->utils->debug_log("{$this->ident} convertAmountToCrypto", [ 'amount' => $amount, 'rate' => $cryptoOrder->rate , 'cryptoAmount' => $cryptoAmount, 'cryptoAmount_ret' => $cryptoAmount_ret ]);
        // return substr(sprintf("%.3f", $cryptoAmount),0,-1);
        return $cryptoAmount_ret;
    }

    protected function calc_sign_general($params, $fields_excluded = [], $exclude_empty = false) {
        // sort params by key, ascending
        ksort($params, SORT_STRING);

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
            $plain_ar[] = $val;
        }

        // $plain = implode('&', $plain_ar);
        $plain = implode('', $plain_ar);

        // Append key to plaintext
        $plain = $this->getSystemInfo('key') . $plain;

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
        $calc_res = $this->calc_sign_general($params, [ 'token' ], 1);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for callback", [ 'plain' => $calc_res['plain'], 'sign' => $calc_res['sign'] ]);

        return $calc_res['sign'];
    }


}

