<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * 1VNPAY
 * *
 * * _1VNPAY_PAYMENT_API, ID: 6018
 * * _1VNPAY_BANKTRANSFER_PAYMENT_API, ID: 6019
 * * _1VNPAY_MOMOPAY_PAYMENT_API, ID: 6020
 * * _1VNPAY_VIETTELPAY_PAYMENT_API, ID: 6021
 * * _1VNPAY_VIETTELPAYFIX_PAYMENT_API, ID: 6022
 * * _1VNPAY_SCRATCHCARD_PAYMENT_API, ID: 6023
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:https://pay.1vnpay.org/api/v1/fundtransfer
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_1vnpay extends Abstract_payment_api {

    const CHANNEL_BANK_QR       = 'bank_qr';
    const CHANNEL_BANK_TRANSFER = 'bank_transfer';
    const CHANNEL_MOMOPAY       = 'momo_qr';
    const CHANNEL_VIETTELPAY    = 'viettel_qr';
    const CHANNEL_VIETTELPAYFIX = 'viettel_fix_qr';
    const CHANNEL_SCRATCHCARD   = 'rcgcard_pc';

    const RESULT_CODE_SUCCESS = 'success';
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
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
        $phone     = (!empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '';

        $params = array();
        $params['merchant_no']  = $this->getSystemInfo('account');
        $params['order_no']     = $order->secure_id;
        $params['amount']       = $this->convertAmountToCurrency($amount);
        $params['Phone_number'] = $phone;
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['notify_url']   = $this->getNotifyUrl($orderId);
        $params['sign']         = $this->sign($params);
        $this->CI->utils->debug_log('=====================1vnpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $queryString = http_build_query($params);
        $postUrl = $this->getSystemInfo('url').'?'.$queryString;

        $this->CI->utils->debug_log("=====================1vnpay postUrl", $postUrl);

        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $postUrl,
            'params' => $params,
            'post' => false
        );
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

        $this->CI->utils->debug_log("=====================1vnpay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['order_no'], '', null, null, $response_result_id);
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
            'merchant_no', 'order_no', 'ylt_order_no', 'amount', 'channel', 'result_code', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================1vnpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================1vnpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['result_code'] != self::RESULT_CODE_SUCCESS) {
            $payStatus = $fields['result'];
            $this->writePaymentErrorLog("=====================1vnpay checkCallbackOrder Payment status was not successful, payStatus is [$payStatus]", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================1vnpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================1vnpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $sign = md5($params['merchant_no'].'|'.$params['order_no'].'|'.$params['amount'].'|'.$params['channel'].'|'.$this->getSystemInfo('key'));

        return $sign;
    }

    private function validateSign($params) {
        $sign = md5($params['merchant_no'].'|'.$params['order_no'].'|'.$params['ylt_order_no'].'|'.$params['amount'].'|'.$params['channel'].'|'.$this->getSystemInfo('key'));
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
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 0, '.', '') ;
    }
}