<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * NIBPAY
 *
 * * NIBPAY_ADV_PAYMENT_API, ID: 5999
 * * NIBPAY_MOONPAY_PAYMENT_API, ID: 6000
 * * NIBPAY_MERCURYO_PAYMENT_API, ID: 6001
 * * NIBPAY_NIBTRAN_PAYMENT_API, ID: 6002
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.nibpay.com
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_nibpay extends Abstract_payment_api {

    const DEPOSIT_CHANNEL_ADV = 'adv';
    const DEPOSIT_CHANNEL_MOONPAY = 'moonpay';
    const DEPOSIT_CHANNEL_MERCURYO = 'mercuryo';
    const DEPOSIT_CHANNEL_NIBTRAN = 'nib-tran';

    const ORDER_STATUS_SUCCESS = 1;
    const REQUEST_SUCCESS = 200;
    const RETURN_SUCCESS = 'success';

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('application/x-www-form-urlencoded');
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
        $params = array();
        $params['service']         = 'App.Pay.GatewayPay';
        $params['merchant_id']     = $this->getSystemInfo('account');
        $params['amount']          = $this->convertAmountToCurrency($amount);
        $params['out_order_id']    = $order->secure_id;
        $params['nonce']           = $this->getNonce();
        $params['timestamp']       = time();
        $params['notify_url']      = $this->getNotifyUrl($orderId);
        $params['pay_currency']    = 'BRL';
        $params['buy_currency']    = 'USDT';
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign']            = $this->sign($params);
        $this->CI->utils->debug_log('=====================nibpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['out_order_id']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================nibpay processPaymentUrlFormPost response', $response);

        if(isset($response['ret']) && $response['ret'] == self::REQUEST_SUCCESS && isset($response['data']['gateway_url']) && !empty($response['data']['gateway_url'])){
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data']['gateway_url'],
            );
        }
        else if(isset($response['msg']) && !empty($response['msg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['msg']
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidte API response')
            );
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

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if (empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $this->CI->utils->debug_log("=====================nibpay callbackFrom $source params", $params);

        if($source == 'server' ){
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
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                # update player balance
                $this->CI->sale_order->updateExternalInfo($order->id, $params['order_number'], null, null, null, $response_result_id);
                #redirect to success/fail page according to return params
                if($params['order_status'] == self::ORDER_STATUS_SUCCESS){
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
            }
        }

        $result['success'] = $success;
            if ($processed) {
                $result['message'] = self::RETURN_SUCCESS;
            } else {
               $result['return_error'] = 'Error';
            }

            if ($source == 'browser') {
               $result['next_url'] = $this->getPlayerBackUrl();
               $result['go_success_page'] = true;
            }

          return $result;
    }

    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'out_order_id', 'amount', 'order_status', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================nibpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================nibpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($this->convertAmountToCurrency($order->amount) != $fields['amount']) {
            $this->writePaymentErrorLog("=========================islpay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['out_order_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================nibpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    protected function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign') {
                continue;
            }
            $signStr .= "{$key}={$value}&";
        }
        return $signStr.'secret='.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign'){
                continue;
            }
            $signStr .= "{$key}={$value}&";
        }
        $sign = strtoupper(md5($signStr.'secret='.$this->getSystemInfo('key')));

        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
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

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    private function getNonce($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }

        return $str;
    }
}