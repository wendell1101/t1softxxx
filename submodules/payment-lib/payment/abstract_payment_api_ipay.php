<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ipay
 *
 * * IPAY_PAYMENT_API, ID: 6082
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://hub.thepasjg.com/order/create
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_ipay extends Abstract_payment_api {

    const DEPOSIT_CHANNEL_BANK = 'bank';
    const RESULT_STATUS_SUCCESS = 0;
    const ORDER_STATUS_SUCCESS = 1;
    const RETURN_SUCCESS = 'success';

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
        $playerDetails = $this->getPlayerDetails($playerId);
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'no firstName';

        $params = array();
        $params['amount']       = $this->convertAmountToCurrency($amount);
        $params['clientip']     = $this->getClientIP();
        $params['currency']     = 'CNY';
        $params['mhtorderno']   = $order->secure_id;
        $params['mhtuserid']    = $playerId;
        $params['notifyurl']    = $this->getNotifyUrl($orderId);
        $params['opmhtid']      = $this->getSystemInfo('account');
        $params['payername']    = $firstname;
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['random']       = (string) rand(1000,9999);
        $params['returnurl']    = $this->getReturnUrl($orderId);
        $params['sign']         = $this->sign($params);
        $this->CI->utils->debug_log('=====================ipay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['mhtorderno']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================ipay processPaymentUrlFormPost response', $response);

        if(isset($response['rtCode']) && $response['rtCode'] == self::RESULT_STATUS_SUCCESS){
            if(isset($response['result']['payurl']) && !empty($response['result']['payurl']))
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['result']['payurl'],
            );
        }
        else if(isset($response['msg'])) {
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

        $this->CI->utils->debug_log("=====================ipay callbackFrom $source params", $params);

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
                $this->CI->sale_order->updateExternalInfo($order->id, $params['mhtorderno'], null, null, null, $response_result_id);
                #redirect to success/fail page according to return params
                if($params['status'] == self::ORDER_STATUS_SUCCESS){
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
            'status', 'pforderno', 'mhtorderno', 'paidamount', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================ipay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================ipay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        $check_amount = $this->convertAmountToCurrency($order->amount);

        if ($fields['paidamount'] != $check_amount) {
            $this->writePaymentErrorLog("======================fxmb Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        }

        if ($fields['mhtorderno'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================ipay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $sign = hash_hmac('sha384', $signStr, $this->getSystemInfo('key'));
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
        $signStr = rtrim($signStr, '&');
        return $signStr;
    }

    private function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign' || empty($value)){
                continue;
            }
            $signStr .= "{$key}={$value}&";
        }
        $signStr = rtrim($signStr, '&');
        $sign = hash_hmac('sha384', $signStr, $this->getSystemInfo('key'));
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
        return number_format($amount * 100, 2, '.', '');
    }

    public function getPlayerDetails($playerId) {
        $this->CI->load->model(array('player_model'));
        $player = $this->CI->player_model->getPlayerDetails($playerId);
        return $player;
    }
}