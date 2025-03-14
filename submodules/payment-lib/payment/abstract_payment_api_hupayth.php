<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * HUPAYTH
 *
 * * HUPAYTH_PROMPTPAY_PAYMENT_API, ID: 5890
 * * HUPAYTH_WANGGUAN4_PAYMENT_API, ID: 5891
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.huvnd.com/pay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_hupayth extends Abstract_payment_api {

    const PAYMETHOD_PROMPTPAY   = "promptpay";
    const PAYMETHOD_WANGGUAN4   = "wangguan4";
    const CALLBACK_STATUS_SUCCESS = "1";

    const RETURN_FAIL_CODE = 'FAIL';
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
        $player = $this->CI->player->getPlayerById($playerId);

        $params = array();
        $params['amount']     = $this->convertAmountToCurrency($amount);
        $params['merchant']      = $this->getSystemInfo('account');
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['outtradeno'] = $order->secure_id;
        $params['notifyurl']  = $this->getNotifyUrl($orderId);
        $params['returnurl']  = $this->getReturnUrl($orderId);
        $params['sign']       = $this->sign($params);

        $this->CI->utils->debug_log('=====================hupayth generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $this->getSystemInfo('url'),
            'params' => $params,
            'post' => true,
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

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================hupayth callbackFrom $source params", $params);

        if($source == 'server' ){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================hupayth callbackFrom $source raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================hupayth callbackFrom $source json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['outtradeno'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                if ($params['status'] == self::CALLBACK_STATUS_SUCCESS){
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
                else {
                    $this->CI->sale_order->declineSaleOrder($order->id, 'auto server callback declined ' . $this->getPlatformCode(), false);
                }
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
            'outtradeno', 'amount', 'status', 'ramount', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================hupayth checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================hupayth checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        $check_amount = $this->convertAmountToCurrency($order->amount);
        if ($fields['amount'] != $check_amount) {
            $this->writePaymentErrorLog('======================hupayth checkCallbackOrder Payment amount is wrong, expected <= '. $check_amount, $fields);
            return false;
        }

        if ($fields['outtradeno'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================hupayth checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign') {
                continue;
            }
            $encodeValue = strtolower(urlencode($value));
            $signStr .= "$key=$encodeValue&";
        }
        return $signStr.'secret='.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign') {
                continue;
            }
            $encodeValue = strtolower(urlencode($value));
            $signStr .= "$key=$encodeValue&";
        }
        $signStr .='secret='.$this->getSystemInfo('key');
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
        return number_format($amount, 2, '.', '');
    }
}