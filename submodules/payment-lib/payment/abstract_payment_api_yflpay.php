<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * YFLPAY 易付联
 *
 * * YFLPAY_PAYMENT_API,        ID: 684
 *
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
abstract class Abstract_payment_api_yflpay extends Abstract_payment_api {

    const PAYTYPE_BANK = 'wgpay';        //网关

	const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE = 'failed';
    const PAY_RESULT = '3';   //1:支付中,2:已取消,3:已支付,4:删除,5:已退款,6:已冻结



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

        $params['version'] = '1.0.1';
        $params['merchantId'] = $this->getSystemInfo("account");
        $params['pageUrl'] = $this->getReturnUrl($orderId);
        $params['notifyUrl'] = $this->getNotifyUrl($orderId);
        $params['orderNo'] = $order->secure_id.'xxx';
        $params['orderAmount'] = $this->convertAmountToCurrency($amount)*100;
        $params['orderDatetime'] = $orderDateTime->format('Ymdhis');
        $params['signType'] = 'MD5';
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log("=====================yflpay generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    # Submit POST form
    protected function processPaymentUrlFormPost($params) {
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $this->getSystemInfo('url'),
            'params' => $params,
            'post' => true,
        );
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

        $this->CI->utils->debug_log("=====================yflpay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderNo'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($success) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['message'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
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
            'merchantId', 'orderNo', 'orderAmount', 'paymentDatetime', 'dealId', 'status', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================yflpay missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['status'] != self::PAY_RESULT) {
            $payStatus = $fields['status'];
            $this->writePaymentErrorLog("=====================yflpay Payment was not successful, payStatus is [$payStatus]", $fields);
            return false;
        }

        if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['orderAmount'] )/100) {
            $this->writePaymentErrorLog("=====================yflpay Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if (substr($fields['orderNo'],0,-3) != $order->secure_id) {
            $this->writePaymentErrorLog("========================yflpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================yflpay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    public function getBankListInfoFallback() {
        return array(
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '工商银行', 'value' => 'ICBC'),
            array('label' => '建设银行', 'value' => 'CCB'),
            array('label' => '广发银行', 'value' => 'CGB'),
            array('label' => '邮储银行', 'value' => 'PSBC'),
            array('label' => '民生银行', 'value' => 'CMBC'),
            array('label' => '光大银行', 'value' => 'CEB'),
            array('label' => '北京银行', 'value' => 'BOB'),
            array('label' => '交通银行', 'value' => 'BOCM'),
            array('label' => '上海银行', 'value' => 'BOS'),
            array('label' => '华夏银行', 'value' => 'HXB'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '平安银行', 'value' => 'PABC'),
            array('label' => '中信银行', 'value' => 'CITIC'),
            array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '招商银行', 'value' => 'CMB')
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
        return number_format($amount, 2, '.', '');
    }

    # -- private helper functions --

    /**
     * detail: getting the signature
     *
     * @param array $data
     * @return	string
     */
    public function sign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            $signStr .= $key.'='.$value.'&';
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        $sign = md5($signStr);
     
        return $sign;

    }

    public function verifySignature($data) {
        $callback_sign = $data['sign'];
        unset($data['sign']);
        ksort($data);
        $signStr = '';
        foreach($data as $key => $value) {
            $signStr .= $key.'='.$value.'&';
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        $sign = md5($signStr);
     
        if (strcasecmp($sign, $callback_sign) !== 0) {
            $this->CI->utils->debug_log("=====================yflpay callbackSignature [$callback_sign] and verifySignature sign [$sign]");
            return false;
        }else{
            return true;
        }

    }
}
