<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * QPGPAY
 *
 * * QPGPAY_PAYMENT_API, ID: 5977
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://web.jf3092.com/paygate/pay.aspx
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_qpgpay extends Abstract_payment_api {

    const DEPOSIT_CHANNEL_BANK = 'BANK_PAY';
    const RETURN_SUCCESS = 'OK';

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
        $params = array();
        $params['merchantId']      = $this->getSystemInfo('account');
        $params['merchantOrderId'] = $order->secure_id;
        $params['orderAmount']     = $this->convertAmountToCurrency($amount);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['notifyUrl']       = $this->getNotifyUrl($orderId);
        $params['returnUrl']       = $this->getReturnUrl($orderId);
        $params['ip']              = $this->getClientIP();
        $params['remark']          = 'Deposit';
        $params['jsonResult']      = '1';
        $params['sign']            = $this->sign($params);
        $this->CI->utils->debug_log('=====================qpgpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['merchantOrderId']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log("=======================qpgpay processPaymentUrlFormPost response", $response);

        if(isset($response['Success']) && $response['Success']) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['Qrcode'],
            );
        }
        else if(isset($response['ErrorMessage']) && !empty($response['ErrorMessage'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['ErrorMessage']
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
            );
        }
    }

    public function handlePaymentFormResponse($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['merchantOrderId']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================bifupay handlePaymentFormResponse response', $response);
        if(isset($response['Success'])){
            if($response['Success']){
                $data = array();
                $data['Name'] = $response['BankName'];
                $data['Bank'] = $response['BankType'];
                $data['Account'] = $response['BankAccount'];
                $data['Amount'] = $response['PayAmount'];
                $collection_text_transfer = '';
                $collection_text = $this->getSystemInfo("collection_text_transfer", array(''));
                if(is_array($collection_text)){
                    $collection_text_transfer = $collection_text;
                }
                $is_not_display_recharge_instructions = $this->getSystemInfo('is_not_display_recharge_instructions');

                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_STATIC,
                    'data' => $data,
                    'hide_timeout' => true,
                    'collection_text_transfer' => $collection_text_transfer,
                    'is_not_display_recharge_instructions' => $is_not_display_recharge_instructions
                );
            }
        }else {
            if(isset($response['ErrorMessage']) && !empty($response['ErrorMessage'])) {
                $msg = $response['ErrorMessage'];
            }else{
                $msg = lang('Invalidate API response');
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
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

        $this->CI->utils->debug_log("=====================qpgpay callbackFrom $source params", $params);

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
                $this->CI->sale_order->updateExternalInfo($order->id, $params['merchantOrderId'], null, null, null, $response_result_id);
                #redirect to success/fail page according to return params
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);

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
            'merchantOrderId', 'orderAmount', 'systemOrderId', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================qpgpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================qpgpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($this->convertAmountToCurrency($order->amount) != $fields['orderAmount']) {
            $this->writePaymentErrorLog("=========================islpay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['merchantOrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================qpgpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $fields = ['merchantId','merchantOrderId','orderAmount','notifyUrl','channelType','remark','ip'];
        $sign = $this->calcSignGeneral($params, $fields);
        return $sign;
    }

    private function validateSign($params) {
        $fields = ['merchantId','merchantOrderId','orderAmount','systemOrderId','channelType','remark','ip'];
        $sign = $this->calcSignGeneral($params, $fields);
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    protected function calcSignGeneral($params = [], $fields = []) {
        $signStr = '';
        foreach ($fields as $key) {
            if($key == 'orderAmount'){
                $params[$key] = $this->convertAmountToCurrency($params[$key]);
            }
            $value = $params[$key];
            $signStr .= "$key=$value&";
        }
        $signStr = rtrim($signStr,'&').$this->getSystemInfo('key');
        $sign = md5($signStr);

        return $sign;
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
}