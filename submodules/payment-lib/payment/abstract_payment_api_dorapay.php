<?php
header("Content-Type:text/html; charset=utf-8");
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * DORAPAY
 * *
 * * DORAPAY_WEIXIN_PAYMENT_API', ID: 721
 * * DORAPAY_ALIPAY_PAYMENT_API', ID: 722
 * * DORAPAY_BANKCARD_PAYMENT_API', ID: 723
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_dorapay extends Abstract_payment_api {


    const PAYTYPE_ALIPAY= '1';
    const PAYTYPE_WEIXIN= '2';
    const PAYTYPE_BANKCARD= '3';
    const PAYTYPE_EB_BANKCARD= '6' ;

    const TERMINAL_MOBILE='1';
    const TERMINAL_PC='2';

    const RETURN_SUCCESS_CODE = 'SUCCESS';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '0';
    const PAY_RESULT_SUCCESS = '0';

    # Implement these for specific pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params['app_id']      = null;
        $params['format']      = null;
        $params['timestamp']   = date('Ymdhis');
        $params['charset']     = 'UTF-8';
        $params['api_version'] = '1.5';
        $params['client_ip']   = $this->getClientIp();
        $params["biz_content"] = array(
            'company_id'       => $this->getSystemInfo("account"),
            'company_order_no' => $order->secure_id,
            'player_id'        => $playerId,
            'terminal'         => $this->utils->is_mobile()?'1':'2',
            'notify_url'       => $this->getNotifyUrl($orderId),
            'amount_money'     => $this->convertAmountToCurrency($amount),
            'extra_param'      => null,
            'name'             => 'Deposit',
            'card_no'          => '12345678901',
        );

        $this->configParams($params, $order->direct_pay_extra_info);

        $sign_arr = $this->create_sign_arr($params);
        $params['sign'] = $this->sign($sign_arr);

        $params["biz_content"] = json_encode($params["biz_content"]);

        $this->CI->utils->debug_log("=====================dorapay generatePaymentUrlForm", $params);
        $params['order_secure_id'] = $order->secure_id; //for record to response result
        return $this->processPaymentUrlForm($params);
    }


    protected function create_sign_arr($params){
        $result = array(
            'company_id'       => $params['biz_content']['company_id'],
            'company_order_no' => $params['biz_content']['company_order_no'],
            'player_id'        => $params['biz_content']['player_id'],
            'amount_money'     => $params['biz_content']['amount_money'],
            'api_version'      => $params['api_version'],
            'channel_code'     => $params['biz_content']['channel_code']
        );
        return $result;
    }

    # Submit POST form
    protected function processPaymentUrlFormPost($params) {
        $params["biz_content"] = urlencode($params["biz_content"]);

        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $this->getSystemInfo('url'),
            'params' => $params,
            'post' => false,
        );
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
        $order_secure_id = $params["order_secure_id"];
        unset($params["order_secure_id"]);
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $order_secure_id);

        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================dorapay processPaymentUrlFormQRcode response json to array', $decode_data);

        $msg = lang('Invalidate API response');
        if(!empty($decode_data['company_order_no']) && ($decode_data['status'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $decode_data['company_order_no'],
            );
        }else {
            if(!empty($decode_data['error_msg'])) {
                $msg = $decode_data['error_msg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }


    /**
     * detail: This will be called when the payment is async, API server calls our callback page,
     * When that happens, we perform verifications and necessary database updates to mark the payment as successful
     *
     * @param int $orderId order id
     * @param array $params
     * @return array
     */
    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    /**
     * detail: This will be called when user redirects back to our page from payment API
     *
     * @param int $orderId order id
     * @param array $params
     * @return array
     */
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================dorapay callbackFrom $source params", $params);

        if($source == 'server'){
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        # Update order payment status and balance
        $success=true;

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['company_order_no'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($success) {
            $resultContent=[
                    'company_order_no'=>$params['company_order_no'],
                    'trade_no'=>$params['trade_no'],
                    'status'=>0,
                ];

            $result['message'] = json_encode($resultContent);
        } else {
            $resultContent=[
                    'error_msg'=>'handle_callback_error',
                    'company_order_no'=>$params['company_order_no'],
                    'trade_no'=>$params['trade_no'],
                    'status'=>1,
                ];

            $result['message'] = json_encode($resultContent);
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    /**
     * detail: Validates whether the callback from API contains valid info and matches with the order
     *
     * @return boolean
     */

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'company_id', 'company_order_no','trade_no','original_amount','actual_amount','status','apply_time','operating_time','api_version','type','sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================dorapay missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        $chk_sign = true;
        if (!$this->recheckSignStr($fields, $fields['sign'])) {
            if(!$this->recheckSignStr($fields, $fields['sign'], true)){
                $this->writePaymentErrorLog('====================dorapay checkCallbackOrder Signature Recheck Error', $fields);
                $chk_sign = false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::PAY_RESULT_SUCCESS) {
            $payStatus = $fields['status'];
            $this->writePaymentErrorLog("=====================dorapay Payment was not successful, payStatus is [$payStatus]", $fields);
            return false;
        }

        if ( $order->amount != floatval( $fields['original_amount'] )
        ) {
            $this->writePaymentErrorLog("=====================dorapay Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['company_order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================dorapay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    public function getBankListInfoFallback() {
        return array(
            array('label' => '工商银行', 'value' => '102'),
            array('label' => '农业银行', 'value' => '103'),
            array('label' => '中国银行', 'value' => '104'),
            array('label' => '建设银行', 'value' => '105'),
            array('label' => '农业发展银行', 'value' => '203'),
            array('label' => '交通银行', 'value' => '301'),
            array('label' => '中信银行', 'value' => '302'),
            array('label' => '光大银行', 'value' => '303'),
            array('label' => '华夏银行', 'value' => '304'),
            array('label' => '民生银行', 'value' => '305'),
            array('label' => '广发银行', 'value' => '306'),
            array('label' => '平安银行', 'value' => '307'),
            array('label' => '招商银行', 'value' => '308'),
            array('label' => '兴业银行', 'value' => '309'),
            array('label' => '浦发银行', 'value' => '310'),
            array('label' => '北京银行', 'value' => '313'),
            array('label' => '恒丰银行', 'value' => '315'),
            array('label' => '浙商银行', 'value' => '316'),
            array('label' => '渤海银行', 'value' => '318'),
            array('label' => '上海银行', 'value' => '325'),
            array('label' => '邮储银行', 'value' => '403'),
            array('label' => '徽商银行', 'value' => '440'),
            array('label' => '广州市商业银行', 'value' => '441')
        );
    }

    # -- Private functions --
    /**
     * detail: After payment is complete, the gateway will invoke this URL asynchronously
     *
     * @param int $orderId
     * @return void
     */
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    /**
     * detail: After payment is complete, the gateway will send redirect back to this URL
     *
     * @param int $orderId
     * @return void
     */
    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    /**
     * detail: Format the amount value for the API
     *
     * @param float $amount
     * @return float
     */
    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', ',');
    }

    # -- private helper functions -
    public function sign($params) {
        $signStr = '';
        $params_len = count($params);
        $counter = 0;

        foreach($params as $key => $value) {
            ++$counter;
            if($counter == $params_len){
                $signStr .= $key."=".$value;
                continue;
            }
            $signStr .= $key.'='.$value.'&';

        }
        $signStr .= $this->getSystemInfo('key');
        $sign = md5($signStr);
        return $sign;

    }

    public function verifySignature($params, $amount) {
        $data_keys = array(
            'company_id','company_order_no','trade_no','actual_amount','api_version'
        );

        $signStr = '';
        $data_len = count($data_keys);
        $counter = 0;
        foreach($data_keys as $key => $value) {
            ++$counter;

            if($counter == $data_len){
                $signStr .= $value."=".$params[$value];
                continue;
            }
            if ($value == 'actual_amount'){
                $signStr .= $value .'='. $amount.'&';
            }else{
                $signStr .= $value.'='.$params[$value].'&';
            }
        }
        $signStr .= $this->getSystemInfo('key');
        $sign = md5($signStr);
        return $sign;
    }

    public function recheckSignStr($params,$signature, $recheck = false) {
        $amount = $params['actual_amount'];
        $signStr = $this->verifySignature($params, $amount);

        $this->utils->debug_log("============================dorapay recheckSignStr signature_1 [$signature]. Source Param: ", $signStr);

        if ( $signature == $signStr ) {
            return true;
        } else {
            if($recheck){ #the broken sign would contain a space
                $this->CI->utils->debug_log("=======================dorapay recheckSignStr signature rechecking,signature is [$signature].");
                $amount_money = $params['actual_amount'];
                if(is_int(strpos($amount_money,','))){

                    $amount = str_replace( ',', '', $amount_money );
                    $signStr = $this->verifySignature($params,$amount);
                    $this->utils->debug_log("============================dorapay recheckSignStr signature_2 [$signature]. Source Param: ", $signStr);
                    if ($signature == $signStr){
                        return true;
                    }

                }else{
                    $amount = $this->convertAmountToCurrency($amount_money);
                    $signStr = $this->verifySignature($params,$amount);
                    $this->utils->debug_log("============================dorapay recheckSignStr signature_3 [$signature]. Source Param: ", $signStr);

                    if ($signature == $signStr){
                        return true;
                    }
                }
            }
            return false;
        }
    }
}
