<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * easypays
 *
 * * 'EASYPAYS_ALIPAY_PAYMENT_API', ID 5225
 * * 'EASYPAYS_ALIPAY_H5_PAYMENT_API', ID 5226
 * * 'BCIDTOKEN_UNIONPAY_PAYMENT_API', ID 5279
 * * 'BCIDTOKEN_ALIPAY_PAYMENT_API', ID 5280
 * * 'BCIDTOKEN_ALIPAY_H5_PAYMENT_API', ID 5281
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_easypays extends Abstract_payment_api {


    const RETURN_SUCCESS_CODE = '200';
    const RETURN_SUCCESS = 'success';

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

        $params = array();
        $params['uid'] = $this->getSystemInfo("account");
        $params['money'] = $this->convertAmountToCurrency($amount);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['post_url'] = $this->getNotifyUrl($orderId);
        $params['return_url'] = $this->getReturnUrl($orderId);
        $params['order_id'] = $order->secure_id;
        $params['key'] = $this->sign($params);

        $this->CI->utils->debug_log("=====================easypays generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    # Submit POST form
    protected function processPaymentUrlFormPost($params) {

        $url = $this->getSystemInfo('url');
        $this->CI->utils->debug_log("=====================easypays processPaymentUrlFormPost URL", $url);
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $url,
            'params' => $params,
            'post' => true,
        );
    }

    protected function processPaymentUrlFormRedirectBag($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['order_id']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================easypays processPaymentUrlFormRedirectBag response', $response);

        if(!empty($response['code']) && ($response['code'] == self::RETURN_SUCCESS_CODE)) {
            if($this->utils->is_mobile() && !$this->getSystemInfo('use_qrcode')) {
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['h5Qrcode']
                );
            }
            else {
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['pcQrcode']
                );
            }
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidte API response')
            );
        }
    }

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['order_id']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================bcidtoken processPaymentUrlFormRedirect response', $response);

        if(!empty($response['code']) && ($response['code'] == self::RETURN_SUCCESS_CODE)) {
            $redirect_url = $response['h5Qrcode'];
            $qrcode_url = $response['qrcode'];

            if(!empty($response['h5Qrcode'])) {
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $redirect_url
                );
            }
            else {
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_QRCODE,
                    'url' => $qrcode_url
                );
            }
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
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['order_id']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================bcidtoken processPaymentUrlFormQRCode response', $response);

        if(!empty($response['code']) && ($response['code'] == self::RETURN_SUCCESS_CODE)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $response['pcQrcode']
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

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================easypays callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id,
                $params['order_no'], 'Third Party Payment (No Bank Order Number)', # no info available
                null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($success) {
            $result['message'] = self::RETURN_SUCCESS;
        } else {
            $result['message'] = $processed ? self::RETURN_SUCCESS : self::RETURN_FAILED_CODE;
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

        $requiredFields = array('key','trade_no','order_id','channel','money','remark','order_uid','goods_name');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================easypays missing parameter: [$f]", $fields);
                return false;
            }
        }
        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================easypays checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass


        if ($fields['money'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================easypays checkCallbackOrder Payment amount is wrong, expected [$order->money]", $fields);
            return false;
        }

        if ($fields['order_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================easypays checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
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
        return number_format($amount,2);
    }


    # -- signatures --
     private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    private function createSignStr($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'uid') {
                $signStr .= $value.$this->getSystemInfo('key');
            }
            else {
                $signStr .= $value;
            }
        }
        return $signStr;
    }

    private function validateSign($params) {
        $signStr = '';
        $signStr = $this->getSystemInfo('key').$params['trade_no'].$params['order_id'].$params['channel'].$params['money'].$params['remark'].$params['order_uid'].$params['goods_name'];
        $sign = md5($signStr);
        if($params['key'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }
}


