<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * 66支付
 * http://www.fkpay.vip/client/pay/getPayUrl
 *
 * * FKPAY_ALIPAY_PAYMENT_API, ID: 5572
 * * FKPAY_ALIPAY_H5_PAYMENT_API, ID: 5573
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_fkpay extends Abstract_payment_api {

    const PAYTYPE_ALIPAY = "alipayH5";
    const PAYTYPE_WEIXIN = "wxpayH5";

    const RESULT_CODE_SUCCESS = "1";
    const CALLBACK_STATUS_SUCCESS = "1";

    const RETURN_FAILED_CODE = 'FAILED';
    const RETURN_SUCCESS_CODE = 'SUCCESS';

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
        $params['appId'] = $this->getSystemInfo('account');
        $params['version'] = "1.0";
        $params['amount'] = $this->convertAmountToCurrency($amount); //cent
        $params['customOrderId'] = $order->secure_id;
        $params['notifyUrl'] = $this->getNotifyUrl($orderId);
        $params['callbackUrl'] = $this->getReturnUrl($orderId);
        $this->configParams($params, $order->direct_pay_extra_info); //$params['payType']
        $params['remark'] = $playerId;
        $params['responseType'] = "Json";
        $params['ip'] = $this->getClientIP();
        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log('=====================fkpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['customOrderId']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================fkpay processPaymentUrlFormRedirect response', $response);

        if($response['code'] == self::RESULT_CODE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data']['payUrl'],
            );
        }
        else if(isset($response['msg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['code'].': '.$response['msg']
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

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $this->CI->utils->debug_log("=====================fkpay callbackFrom $source params", $params);

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
            # update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $params['customOrderId'], null, null, null, $response_result_id);
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
            $result['return_error'] = self::RETURN_FAIL_CODE;
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
            'status', 'customOrderId', 'amount', 'realAmount', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================fkpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================fkpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_STATUS_SUCCESS) {
            $this->writePaymentErrorLog('=====================fkpay checkCallbackOrder Payment status is not success', $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $diffAmount = abs($this->convertAmountToCurrency($order->amount) - floatval($fields['realAmount']));
                if ($diffAmount > 500) {
                    $this->writePaymentErrorLog("=====================fkpay checkCallbackOrder Payment amounts ordAmt - payAmt > 500cent, expected [$order->amount]", $fields ,$diffAmount);
                    return false;
                }
                $this->CI->utils->debug_log('=====================fkpay checkCallbackOrder amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, ($fields['realAmount']/100), $notes);
            }
            else{
                $this->writePaymentErrorLog("=====================fkpay checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['customOrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================fkpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $sign = md5($signStr);
        return $sign;
    }

    private function createSignStr($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            $signStr .= $value;
        }
        return $signStr.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        $data = array(
            'appid'         => $this->getSystemInfo('account'),
            'status'        => $params['status'],
            'customOrderId' => $params['customOrderId'],
            'amount'        => $params['amount'],
            'realAmount'    => $params['realAmount'],
            'payTime'       => $params['payTime'],
            'remark'        => $params['remark']
        );
        $signStr = $this->createSignStr($data);
        $sign = md5($signStr);
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
        return number_format($amount*100, 0, '.', '');
    }
}