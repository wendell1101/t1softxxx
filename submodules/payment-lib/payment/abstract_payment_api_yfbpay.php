<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * YFBPAY
 *
 * * YFBPAY_PAYMENT_API, ID: 5969
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
abstract class Abstract_payment_api_yfbpay extends Abstract_payment_api {

    const DEPOSIT_CHANNEL_BANK = 4;
    const RESULT_STATUS_SUCCESS = 0;
    const ORDER_STATUS_SUCCESS = 'PAID';
    const RETURN_SUCCESS = 'success';

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = ['Content-Type:application/json'];
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
        $params['merchantNo']   = $this->getSystemInfo('account');
        $params['orderNo']      = $order->secure_id;
        $params['userNo']       = $playerId;
        $params['userName']     = $firstname;
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['amount']       = $this->convertAmountToCurrency($amount);
        $params['discount']     = 0;
        $params['extra']        = '';
        $params['datetime']     = $orderDateTime->format('Y-m-d H:i:s');
        $params['notifyUrl']    = $this->getNotifyUrl($orderId);
        $params['time']         = time();
        $params['appSecret']    = $this->getSystemInfo('appSecret');
        $params['payeeName']    = $firstname;
        $params['sign']         = $this->sign($params);
        $this->CI->utils->debug_log('=====================YFBPAY generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['orderNo']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================YFBPAY processPaymentUrlFormPost response', $response);

        if(isset($response['code']) && $response['code'] == self::RESULT_STATUS_SUCCESS){
            if(isset($response['targetUrl']) && !empty($response['targetUrl']))
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['targetUrl'],
            );
        }
        else if(isset($response['text'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['text']
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

        $this->CI->utils->debug_log("=====================YFBPAY callbackFrom $source params", $params);

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
                $this->CI->sale_order->updateExternalInfo($order->id, $params['orderNo'], null, null, null, $response_result_id);
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
            'status', 'orderNo', 'tradeNo', 'amount', 'paid', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================YFBPAY checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================YFBPAY checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        $amount = $this->convertAmountToCurrency($order->amount);
        if ($fields['amount'] != $amount) {
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $diffAmount = abs(str_replace(',', '', $amount) - str_replace(',', '', $fields['Amount']));
                if ($diffAmount >= 1) {
                    $this->writePaymentErrorLog("=====================YFBPAY checkCallbackOrder Payment amounts ordAmt - payAmt > 1, expected [$order->amount]", $fields ,$diffAmount);
                    return false;
                }
                $this->CI->utils->debug_log("=====================YFBPAY checkCallbackOrder amount not match expected [$order->amount]",$fields);
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, str_replace(',', '', $fields['paid']), $notes);
            }else{
                $this->writePaymentErrorLog("======================YFBPAY checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['orderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================YFBPAY checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $sign = strtoupper(md5(hash('sha256', $signStr)));
        return $sign;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'userName' || $key == 'channelNo' || $key == 'payeeName' || $key == 'bankName' || $key == 'appSecret' || $key == 'payeeName' || $key == 'sign') {
                continue;
            }
            $signStr .= "{$key}={$value}&";
        }
        $signStr = rtrim($signStr, '&');
        return $signStr.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'userName' || $key == 'channelNo' || $key == 'payeeName' || $key == 'bankName' || $key == 'appSecret' || $key == 'payeeName' || $key == 'sign' || $key == 'amountBeforeFixed'){
                continue;
            }

            if($key == 'amount' || $key == 'discount' || $key == 'lucky' || $key == 'paid'){
                $value = $this->convertAmountToCurrency($value);
            }

            $signStr .= "{$key}={$value}&";
        }
        $signStr = rtrim($signStr, '&');
        $sign = strtoupper(md5(hash('sha256', $signStr.$this->getSystemInfo('key'))));

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

    public function getPlayerDetails($playerId) {
        $this->CI->load->model(array('player_model'));
        $player = $this->CI->player_model->getPlayerDetails($playerId);
        return $player;
    }
}