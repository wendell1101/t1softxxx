<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * JHP
 *
 * * JHP_PAYMENT_API, ID: 5591
 * * JHP_ALIPAY_H5_PAYMENT_API, ID: 5592
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.fastpay365.com/pgw.shtml
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_jhp extends Abstract_payment_api {
    const ORDER_STATUS_SUCCESS = "710010";
    const RETURN_SUCCESS_CODE = "success";
    const PAY_TYPE_ID_BANKCARD = "11";
    const PAY_TYPE_ID_ALIPAY_H5 = "20";

    public function __construct($params = null) {
        parent::__construct($params);
    }
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
        $timestamp=time();

        $params = array();
        $params['payFromAccount'] = $this->getSystemInfo('account');
        $params['name'] = $player['username'];
        $params['faceMoney'] = $this->convertAmountToCurrency($amount);
        $params['timestamp'] = $timestamp;
        $params['inOrderId'] = $order->secure_id;
        $params['inNotifyUrl'] = $this->getNotifyUrl($orderId);
        $params['inJumpUrl'] = $this->getReturnUrl($orderId);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign'] = $this->sign($params);
        $params['clientIp'] = $this->getClientIp();
        $params['Uid']   = $playerId;

        $this->CI->utils->debug_log('=====================jhp generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $this->getSystemInfo('url'),
            'params' => $params,
            'post' => true
        );
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    ## This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================jhp callbackFrom $source params", $params);

        if($source == 'server' ){
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        $success = true;

        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            # update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $params['transactionId'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback '.$this->getPlatformCode().', result: '. $params['pay_result'], false);
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
            'transactionId','orderId', 'requestMoney', 'successMoney', 'result', 'timestamp','sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================jhp Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['result'] != self::ORDER_STATUS_SUCCESS) {
            $this->writePaymentErrorLog('=====================jhp Payment was not successful', $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount);

        if ($fields['successMoney'] != $check_amount) {
            $this->writePaymentErrorLog("======================jhp Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        }
        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    # Reference: PHP Demo
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    private function createSignStr($params) {
        $signStr = '';
        ksort($params);
        foreach($params as $key => $value) {
            if(empty($value) || $key == 'sign' || $key == 'wap' || $key == 'clientIp' || $key == 'Uid' ||  $key == 'beginTime'
                             || $key == 'endTime' || $key == 'payCardType') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= "key=".$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $sign = $this->sign($params);
        if($params['sign'] == $sign)
            return true;
        else
            return false;
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

