<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * VMPAY
 *
 * * VMPAY_PAYMENT_API, ID: 5747
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.tigerpayhub.com/apisubmit
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_vmpay extends Abstract_payment_api {
    const PAYTYPE_ONLINE_BANK = 'bank';
    const PAYTYPE_ALIPAY_H5 = 'zfb_wap';
    const PAYTYPE_ALIPAY_QRCODE = 'zfb_qrcode';
    const RESULT_STATUS_SUCCESS = '1';
    const RETURN_SUCCESS_CODE = 'success';


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
        $params['version'] = '1.0';
        $params['customerid'] = $this->getSystemInfo('account');
        $params['sdorderno'] = $order->secure_id;
        $params['total_fee']  = $this->convertAmountToCurrency($amount);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['notifyurl'] = $this->getNotifyUrl($orderId);
        $params['returnurl'] = $this->getReturnUrl($orderId);
        $params['remark'] = 'deposit';
        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log('=====================vmpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['sdorderno']);
        $this->CI->utils->debug_log('=====================vmpay processPaymentUrlFormPost response', $response);
        $result = json_decode($response,true);
        $this->CI->utils->debug_log('=====================vmpay processPaymentUrlFormPost decoded result', $result);

        if(isset($result['status'])) {
            if($result['status'] == self::RESULT_STATUS_SUCCESS){
                return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $result['payurl'],
                );
            }
        }
        else if(isset($response)) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response
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

        $this->CI->utils->debug_log("=====================vmpay callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================vmpay raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================vmpay json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['sdorderno'], null, null, null, $response_result_id);
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
            'sdorderno', 'status', 'total_fee', 'customerid', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================vmpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================vmpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::RESULT_STATUS_SUCCESS) {
            $this->writePaymentErrorLog("======================vmpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['total_fee'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================vmpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['sdorderno'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================vmpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $sign = md5($signStr);
        return $sign;
    }

    private function createSignStr($params) {
        $params = array(
            'version' => $params['version'],
            'customerid' => $params['customerid'],
            'total_fee' => $params['total_fee'],
            'sdorderno' => $params['sdorderno'],
            'notifyurl' => $params['notifyurl'],
            'returnurl' => $params['returnurl'],
        );
        $signStr = '';
        foreach($params as $key => $value) {
            $signStr.=$key."=".$value."&";
        }
        $signStr = $signStr.$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $signParams = array(
            'customerid' => $params['customerid'],
            'status' => $params['status'],
            'sdpayno' => $params['sdpayno'],
            'sdorderno' => $params['sdorderno'],
            'total_fee' => $params['total_fee'],
            'paytype' => $params['paytype'],
        );
        $signStr = '';
        foreach($signParams as $key => $value) {
            $signStr.=$key."=".$value."&";
        }
        $signStr = $signStr.$this->getSystemInfo('key');
        $sign = md5($signStr);
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    # -- Private functions --
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }
}