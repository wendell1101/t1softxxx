<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * JUSTRUNS
 *
 * * JUSTRUNS_WEIXIN_PAYMENT_API, ID: 5668
 * * JUSTRUNS_ALIPAY_PAYMENT_API, ID: 5669
 * * JUSTRUNS_BANKCARD_PAYMENT_API, ID: 5670
 * * JUSTRUNS_ALIPAY_H5_PAYMENT_API, ID: 5671
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.justruns3.com/hr/facade/order/merchant/requestOrder
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_justruns extends Abstract_payment_api {
    const ORDER_STATUS_SUCCESS  = "FINISH";
    const RETURN_SUCCESS_CODE   = "OK";
    const CHANNELCODE_WEIXIN    = "WXPAY";
    const CHANNELCODE_ALIPAY    = "ALIPAY";
    const CHANNELCODE_BANKCARD  = "ALIPAYTOBANK";
    const CHANNELCODE_ALIPAY_H5 = "ALIPAYH5";

    public function __construct($params = null) {
        parent::__construct($params);
    }
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'application_id');
        return $secretsInfo;
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['merchantId'] = $this->getSystemInfo('account');
        $params['merchantOrderId'] = $order->secure_id;
        $params['noticeUrl'] = $this->getNotifyUrl($orderId);
        $params['is304'] = 'true';
        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log('=====================justruns generatePaymentUrlForm params', $params);

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

        $this->CI->utils->debug_log("=====================justruns callbackFrom $source params", $params);

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================justruns raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("=====================justruns json_decode params", $params);
        }

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['merchantOrderId'], '', null, null, $response_result_id);
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
            'orderRequestAmount','merchantOrderId','orderStatus','sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================justruns Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================justruns Signature Error', $fields);
            return false;
        }

        if ($fields['orderStatus'] != self::ORDER_STATUS_SUCCESS) {
            $this->writePaymentErrorLog('=====================justruns Payment was not successful', $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount);

        if ($fields['orderRequestAmount'] != $check_amount) {
            $this->writePaymentErrorLog("======================justruns Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        }

        if ($fields['merchantOrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================justruns checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }


        $processed = true; # processed is set to true once the signature verification pass

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
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    private function createSignStr($params) {
        $signStr = '';
        ksort($params);
        foreach($params as $key => $value) {
            if( $key == 'sign' || $key == 'is304') {
                continue;
            }
            $signStr .= "$key=$value";
        }
        $signStr .= "APIKEY=".$this->getSystemInfo('key');
        return strtoupper($signStr);
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
        return number_format($amount, 0, '.', '');
    }
}

