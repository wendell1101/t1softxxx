<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * YUNSHENG
 *
 * * YUNSHENG_ALIPAY_PAYMENT_API, ID: 5209
 * * YUNSHENG_ALIPAY_H5_PAYMENT_API, ID: 5210
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://103.255.45.66:2222/Payment/
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_yunsheng extends Abstract_payment_api {
    const TRANSTYPE_ALIPAY    = "10";
    const TRANSTYPE_ALIPAY_H5 = "11";
    const TRANSTYPE_WEIXIN    = "20";
    const TRANSTYPE_WEIXIN_H5 = "21";

    const RETURN_SUCCESS_CODE = 'true';


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
        $params['appid']     = $this->getSystemInfo('account');
        $params['orderno']   = $order->secure_id;
        $params['uid']       = $playerId;
        $params['username']  = $player['username'];
        $params['amount']    = $this->convertAmountToCurrency($amount);
        $params['grade']     = "0";
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['noticeurl'] = $this->getNotifyUrl($orderId);
        $params['sign']      = $this->sign($params);
        $this->CI->utils->debug_log('=====================yunsheng generatePaymentUrlForm params', $params);

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

        $this->CI->utils->debug_log("=====================yunsheng callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, null, '', null, null, $response_result_id);
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

    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'appid', 'orderno', 'amount', 'transamount', 'settlementamount', 'processtime', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================yunsheng checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================yunsheng checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================yunsheng checkCallbackOrder amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['transamount'] != $this->convertAmountToCurrency($order->amount)) {
            if($this->getSystemInfo('allow_callback_amount_diff')){
                if ($fields['transamount'] < $this->convertAmountToCurrency($order->amount)) {
                    $this->CI->utils->debug_log('=====================yunsheng checkCallbackOrder amount not match expected [$order->amount], allow callback amount diff');
                    $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                    $this->CI->sale_order->fixOrderAmount($order->id, $fields['transamount'], $notes);
                }
            }
            else{
                $this->writePaymentErrorLog("=====================yunsheng checkCallbackOrder transamount do not match, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['orderno'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================yunsheng checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $sign = hash('sha256', $signStr);
        
        return $sign;
    }

    private function createSignStr($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'noticeurl' || $key == 'sign' ) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'appkey='.$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $keys = array('appid', 'orderno', 'amount', 'transamount', 'settlementamount', 'processtime');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $key.'='.$params[$key].'&';
            }
        }
        $signStr .= 'appkey='.$this->getSystemInfo('key');
        $sign = hash('sha256', $signStr);
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