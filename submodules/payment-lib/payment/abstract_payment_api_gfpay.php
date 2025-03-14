<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * GFPAY
 *
 * * GFPAY_WEIXIN_PAYMENT_API, ID: 5365
 * * GFPAY_WEIXIN_H5_PAYMENT_API, ID: 5366
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://gfpay.co/mwpay/api.php?do=CreateOrder
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_gfpay extends Abstract_payment_api {

    const RESULT_CODE_SUCCESS = 0;
    const CALLBACK_STATUS_SUCCESS = 'success';

    const RETURN_FAIL_CODE = 'FAIL';
    const RETURN_SUCCESS_CODE = '0|ok';

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }

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
        $params['mch_id']       = $this->getSystemInfo('account');
        $params['cp_order_no']  = $order->secure_id;
        $params['order_uid']    = $playerId;
        $params['order_amount'] = $this->convertAmountToCurrency($amount);
        $params['notify_url']   = $this->getNotifyUrl($orderId);
        $params['sign']         = $this->sign($params);
        $this->CI->utils->debug_log('=====================gfpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['cp_order_no']);

        $this->CI->utils->debug_log('=====================gfpay processPaymentUrlFormPost response', $response);

        if(strpos($response, 'html') !== false){
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_HTML, # will be redirected to a view for error display
                'html' => $response,
            );
        }

        $response = json_decode($response, true);
        if(isset($response['retcode'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => '['.$response['retcode'].']: '.$response['retdesc']
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
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

        $this->CI->utils->debug_log("=====================gfpay callbackFrom $source params", $params);

        if($source == 'server' ){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================gfpay raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================gfpay json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['mach_order_id'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                if($params['retdesc'] == self::CALLBACK_STATUS_SUCCESS){
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
                else {
                    $this->CI->sale_order->declineSaleOrder($order->id, 'auto server callback declined ' . $this->getPlatformCode() .': '. $params['retdesc'], false);
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

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'retcode', 'retdesc', 'mach_order_id', 'mach_c_order_id', 'mach_amount'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================gfpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================gfpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['mach_amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================gfpay checkCallbackOrder amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['mach_c_order_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================gfpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $asciiStr = $this->string2ASCII($signStr);
        $sign = strtoupper(md5($asciiStr));
        return $sign;
    }

    private function createSignStr($params) {
        $keys = array('mch_id', 'cp_order_no', 'order_uid', 'order_amount');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $key.'='.$params[$key].'&';
            }
        }
        $signStr = rtrim($signStr, '&').$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $keys = array('mach_order_id', 'mach_c_order_id', 'mach_customer_id', 'mach_amount', 'mach_pay_date');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $key.'='.$params[$key].'&';
            }
        }
        $signStr = rtrim($signStr, '&').$this->getSystemInfo('key');
        $asciiStr = $this->string2ASCII($signStr);
        $sign = strtoupper(md5($asciiStr));

        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    private function string2ASCII($string){
        $ASCIIstring = '';
        for ($i = 0; $i < strlen($string); $i++){
            $ASCIIstring .= dechex(ord($string[$i]));
        }
        return $ASCIIstring;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 0, '.', '');
    }
}