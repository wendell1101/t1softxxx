<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * PPPAY
 *
 * * PPPAY_WEIXIN_PAYMENT_API, ID: 518
 * * PPPAY_ALIPAY_PAYMENT_API, ID: 520
 * * PPPAY_QQPAY_PAYMENT_API,  ID: 522
 * * PPPAY_JDPAY_PAYMENT_API,  ID: 523
 * * PPPAY_QUICKPAY_PAYMENT_API,  ID: 5136
 *
 * Required Fields:
 *
 * * URL:https://api.63405.com/mctrpc/order/mkReceiptOrder.htm
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_pppay extends Abstract_payment_api {

    const PAYTYPE_WEIXIN = '3_';
    const PAYTYPE_WEIXIN_H5 = '5_';
    const PAYTYPE_ALIPAY = '11_';
    const PAYTYPE_ALIPAY_H5 = '6_';
    const PAYTYPE_QQPAY = '9_';
    const PAYTYPE_JDPAY = '10_';
    const PAYTYPE_QUICKPAY = '16_';

    const QRCODE_REPONSE_CODE_SUCCESS = '000';  //成功接受訂單
    const ORDER_STATUS_PROCESS = '9';   //QRcodeResponse訂單狀態表 0,待支付;1,支付成功;2,支付失败;3,已退款;4,退款中;9,处理中;
    const ORDER_STATUS_SUCCESS = '1';   //QRcodeResponse訂單狀態
    const RETURN_SUCCESS_CODE = 'Y';
    const RETURN_FAILED_CODE = 'N';

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
        $this->CI->load->model('player');
        $player = $this->CI->player->getPlayerById($playerId);
        $username = $player['username'];

        $params['version'] = '2.0';
        $params['merchantId'] = $this->getSystemInfo("account");
        $params['merchantTime'] = date('Ymdhis');
        $params['traceNO'] = $order->secure_id;
        $params['requestAmount'] = $this->convertAmountToCurrency($amount);
        $params['returnUrl'] = $this->getReturnUrl($orderId);
        $params['notifyUrl'] = $this->getNotifyUrl($orderId);
        $params['goodsName'] = 'deposit';
        $params['source'] = '1';#1:pc 端，2:andriod 客户端， 3:ios 客户端
        $params['ip'] = $this->getClientIp();
        $params['terminalUserId'] = $username;
        $params['paymentCount'] = '1';
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log("=====================pppay generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
        $received = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['traceNO']);
        $response = explode('|',$received);
        $response_status = $response['0'];
        $decode_data = json_decode($response['1'],true);

        $orderStatus = $decode_data['orderStatus'];

        if($response_status == self::QRCODE_REPONSE_CODE_SUCCESS && $orderStatus == self::ORDER_STATUS_PROCESS) {
            if(isset($decode_data['payments']['0']['itemResponseMsg']['barcodeInfo'])){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $decode_data['payments']['0']['itemResponseMsg']['barcodeInfo']
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => $decode_data['errorMsg']
                );
            }
        }
        else if($orderStatus != self::ORDER_STATUS_PROCESS) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $decode_data['errorMsg']
            );
        } else{
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
            $response = explode('|',$params['msg']);
            $flds['0'] = $response['0'];    //应答码
            $flds['1'] = json_decode($response['1'],true);
            $flds['2'] = $response['2'];    //签名

            $new_params = array();
            $new_params = array_merge( $new_params, $flds );
            $this->CI->utils->debug_log('=======================pppay callbackFrom in Function callbackFrom new_params', $new_params);
            if (!$order || !$this->checkCallbackOrder($order, $new_params,$processed, $response['1'])) {
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderId'], null, null, null, $response_result_id);
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
            $result['return_error'] = self::RETURN_FAILED_CODE;
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

    private function checkCallbackOrder($order, $fields, &$processed = false, $json_string = '{}') {
        $requiredFields = array(
            'createTime', 'orderId', 'orderStatus', 'orderSuccAmount', 'traceNO'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields['1'])) {
                $this->writePaymentErrorLog("=====================pppay Payment missing parameter: [$f]", $fields['1']);
                return false;
            }
        }

        if ($fields['1']['orderStatus'] != self::ORDER_STATUS_SUCCESS) {
            $payStatus = $fields['1']['orderStatus'];
            $this->writePaymentErrorLog("=====================pppay Payment was not successful, orderStatus is [$payStatus]", $fields['1']);
            return false;
        }

        if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['1']['orderSuccAmount'] ) ) {
            $this->writePaymentErrorLog("=====================pppay Payment orderSuccAmount do not match, expected [$order->amount]", $fields['1']);
            return false;
        }

        if ($fields['1']['traceNO'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================pppay Payment checkCallbackOrder payment , Order IDs do not match, expected [$order->secure_id]", $fields['1']);
            return false;
        }

        # is signature authentic?
        if($this->callbackSign($json_string) != $fields['2']) {
            $this->writePaymentErrorLog('=====================pppay Payment checkCallbackOrder signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

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
        foreach($params as $key) {
            $signStr .= $key;
        }
        $signStr .= $this->getSystemInfo('key');
        $sign = hash('sha256', $signStr);
        return $sign;
    }


    ##validate whether callback signature is correspond with sign of callback biz_conent or not
    private function callbackSign($content){
        $signStr =  $content . $this->getSystemInfo('key');
        $sign = hash('sha256',$signStr);

        return $sign;
    }

}
