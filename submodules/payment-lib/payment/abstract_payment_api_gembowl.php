<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * GEMBOWL 聚宝盆
 * *
 * * GEMBOWL_ALIPAY_PAYMENT_API, ID: 915
 * * GEMBOWL_ALIPAY_H5_PAYMENT_API, ID: 916
 * * GEMBOWL_UNIONPAY_PAYMENT_API, ID: 5489
 * * GEMBOWL_UNIONPAY_H5_PAYMENT_API, ID: 5490
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra Info:
 * * {
 * *    "order_prefix"
 * * }
 *
 * Field Values:
 * * URL: https://gateway.gembowlcenter.com/gateway.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 * * Extra Info:
 * * {
 * *    "order_prefix": "## Order Prefix ##"
 * * }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_gembowl extends Abstract_payment_api {


    const RESULT_STATUS_SUCCESS = true;
    const CALLBACK_SUCCESS = 'SUCCESS';
    const CALLBACK_PAID = 'PAID';
    const RETURN_SUCCESS_CODE = 'ok';
    const CHANNEL_CODE_ALIPAY = '1';
    const CHANNEL_CODE_ALIPAY_H5 = '2';
    const CHANNEL_CODE_UNIONPAY = '5';


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
        $params['merchant_code'] = $this->getSystemInfo('account');
        $params['orderid'] = strtolower($order->secure_id);
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['timestamp'] = time();
        $params['notifyurl'] = $this->getNotifyUrl($orderId);
        $params['httpurl'] = $this->getReturnUrl($orderId);
        $params['reference'] = $order->secure_id;
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log('=====================gembowl generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    # Implement processPaymentUrlForm
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['orderid']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================gembowl processPaymentUrlFormPost response', $response);

        if($response['status'] == self::RESULT_STATUS_SUCCESS) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['orderid']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['data']['transaction_id']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data']['return'],
            );
        }
        else if($response['message']) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['message']
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

    protected function processPaymentUrlFormQRCode($params) {
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

        $this->CI->utils->debug_log("=====================gembowl callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================gembowl raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================gembowl json_decode params", $params);
            }

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['order_id'], null, null, null, $response_result_id);
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
            'amount', 'merchant_code', 'order_id', 'status', 'reference', 'transaction_id', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================gembowl checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================gembowl checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if (($fields['status'] != self::CALLBACK_SUCCESS) && ($fields['status'] != self::CALLBACK_PAID)) {
            $this->writePaymentErrorLog("======================gembowl checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================gembowl checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }
        $order_id = strtolower($order->secure_id);
        if ($fields['order_id'] != $order_id) {
            $this->writePaymentErrorLog("======================gembowl checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5(urldecode($signStr));
        return $sign;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = "";
        foreach($params as $key => $value) {
            if($key == 'sign'){
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= $this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        ksort($params);
        $signStr = "";
        foreach($params as $key => $value) {
            if($key == 'sign'){
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= $this->getSystemInfo('key');
        $sign = md5(urldecode($signStr));

        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    # -- Private functions --
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }
}