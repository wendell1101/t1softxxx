<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * linkpay
 *
 *  LINKPAY_PAYMENT_API, ID: 5776
 *  LINKPAY_WITHDRAWAL_PAYMENT_API, ID: 5777
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://linkpay.surperpay.com/trade/unifiedOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_linkpay extends Abstract_payment_api {
    const RETURN_SUCCESS_CODE = 'success';
    const RESULT_STATUS_SUCCESS = '00000';
    const CALLBACK_SUCCESS_CODE = '00000';
    const CURRENCY = 'INR';

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

        $params['version']     = '1.0';
        $params['charset']     = 'UTF-8';
        $params['spid']        = $this->getSystemInfo("account");
        $params['spbillno']    = $order->secure_id;
        $params['lang']        = $this->getSystemInfo("lang",'hin');
        $params['country']     = $this->getSystemInfo("country",'IN');
        $params['currency']    = $this->getSystemInfo("currency",'INR');
        $params['tranAmt']     = $this->convertAmountToCurrency($amount); //分
        $params['backUrl']     = $this->getReturnUrl($orderId);
        $params['notifyUrl']   = $this->getNotifyUrl($orderId);
        $params['productName'] = 'Deposit';
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['signType']    = 'MD5';
        $params['sign']        = $this->sign($params);

        $this->CI->utils->debug_log('=====================linkpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    # Submit POST form
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['spbillno']);
        $this->CI->utils->debug_log('=====================linkpay processPaymentUrlFormPost response', $response);
        $result = json_decode($response,true);
        $this->CI->utils->debug_log('=====================linkpay processPaymentUrlFormPost decoded result', $result);

        if(isset($result['retcode']) && $result['retcode'] == self::RESULT_STATUS_SUCCESS) {
            return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_URL,
            'url' => $result['url'],
            );
        }
        else if(isset($result['retmsg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'Return code: '.$result['retcode'].'=> '.$result['retmsg']
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

        $this->CI->utils->debug_log("=====================linkpay callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================linkpay raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================linkpay json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['spbillno'], null, null, null, $response_result_id);
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
            'retcode', 'retmsg', 'spid', 'spbillno', 'transactionId', 'outTransactionId', 'currency', 'processCurrency', 'tranAmt', 'processAmount', 'result', 'signType', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================linkpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================linkpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['retcode'] != self::CALLBACK_SUCCESS_CODE) {
            $this->writePaymentErrorLog("======================linkpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['processAmount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================linkpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['spbillno'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================linkpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        // $this->utils->debug_log("===================linkpay sign signStr", $signStr);
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($value == null || $key == 'sign' || $key == 'signType') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $callback_sign = $params['sign'];
        unset($params['sign']);
        $sign = $this->sign($params);
        if($callback_sign == $sign){
            return true;
        }
        else{
            $this->utils->debug_log("===================linkpay validateSign signature is [$sign], match? ", $callback_sign);
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
        return number_format($amount*100, 0, '.', '');
    }
}