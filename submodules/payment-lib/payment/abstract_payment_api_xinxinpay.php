<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * XINXINPAY
 * *
 * * XINXINPAY_PAYMENT_API, ID: 6071
 * * XINXINPAY_WITHDRAWAL_PAYMENT_API, ID: 6073
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * json: https://or.7xinpy.com/pay/orderPay
 * * form: https://or.7xinpy.com/forward/orderPay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_xinxinpay extends Abstract_payment_api {
    const BUSICODE_BANK       = 'wyyhk';  #网关支付
    const RESULT_CODE_SUCCESS = 0;
    const CALLBACK_SUCCESS    = 1;
    const RETURN_SUCCESS_CODE = 'ok';

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
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
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'no firstName';

        $params = array();
        $params['merchNo']      = $this->getSystemInfo('account');
        $params['orderNo']      = $order->secure_id;
        $params['amount']       = $this->convertAmountToCurrency($amount);
        $params['currency']     = 'CNY';
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['title']        = 'Deposit';
        $params['product']      = 'Deposit';
        $params['returnUrl']    = $this->getReturnUrl($orderId);
        $params['notifyUrl']    = $this->getNotifyUrl($orderId);
        $params['reqTime']      = date('YmdHis');
        $params['userId']       = $playerId;
        $params['realname']     = $firstname;

        $submitParams['sign']        = $this->sign($params);
        $submitParams['context']     = base64_encode(json_encode($params));
        $submitParams['encryptType'] = "MD5";
        $submitParams['orderNo']     = $order->secure_id;

        $this->CI->utils->debug_log('=====================XINXINpay generatePaymentUrlForm params', $submitParams);

        return $this->processPaymentUrlForm($submitParams);
    }

    protected function processPaymentUrlFormPost($params) {
        $orderId = $params['orderNo'];
        unset($params['orderNo']);
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $orderId );
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================XINXINpay processPaymentUrlFormPost response', $response);
        if($response['code'] == self::RESULT_CODE_SUCCESS){
            if(!empty($response['context']) && isset($response['context'])){
                $result = json_decode(base64_decode($response['context']), true);
                $this->CI->utils->debug_log('=====================XINXINpay processPaymentUrlFormPost success result', $result);
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $result['code_url']
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => lang('Invalidte API response')
                );
            }
        }else if(isset($response['msg']) && !empty($response['msg'])){
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

    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $this->CI->utils->debug_log("=====================XINXINpay callbackFrom $source params", $params);

        if($source == 'server'){
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
            # update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderNo'], '', null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
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
        $requiredFields = array(
            'code', 'context','sign'
        );

        $requiredContext = array(
            'amount', 'orderState'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================XINXINpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $contextData = json_decode(base64_decode($fields['context']), true);

        foreach ($requiredContext as $f) {
            if (!array_key_exists($f, $contextData)) {
                $this->writePaymentErrorLog("=====================XINXINpay checkCallbackOrder Missing contextData parameter: [$f]", $contextData);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields, $contextData)) {
            $this->writePaymentErrorLog('=====================XINXINpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($contextData['orderState'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================XINXINpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($contextData['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================XINXINpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($contextData['orderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================XINXINpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }


    # -- signatures --
    public function sign($params) {
        $json_params = json_encode($params);
        $sign =md5($json_params.$this->getSystemInfo('key'));
        return $sign;
    }

    public function validateSign($params, $contextData) {
        $json_params = json_encode($contextData);
        $sign = md5($json_params.$this->getSystemInfo('key'));
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    # -- Private functions --
    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }
}