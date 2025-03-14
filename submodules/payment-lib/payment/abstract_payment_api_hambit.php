<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * HAMBIT
 *
 * * HAMBIT_PAYMENT_API, ID: 6315
 * * HAMBIT_PAYMENT_API_WITHDRAWAL, ID: 6316

 * Required Fields:
 *
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_hambit extends Abstract_payment_api {

    const REPONSE_CODE_SUCCESS = '200';
    const DEPOSIT_CALLBACK_SUCCESS_CODE = '2';
    const WITHDRAWAL_CALLBACK_SUCCESS_CODE = '8';
    const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE = 'failed';
    const WITHDRAWAL_CALLBACK_ORDER_STATUS_CODE = '8';

    # Implement these for specific pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function __construct($params = null) {
        parent::__construct($params);
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $this->CI->utils->debug_log("=====================hambit origin_amount", $amount);

        $params['amount'] = strval($amount);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['externalOrderId'] = $order->secure_id;
        $params['notifyUrl'] = $this->getNotifyUrl($orderId);
        $params['returnUrl'] = $this->getReturnUrl($orderId);

        #head params
        $params['access_key'] = $this->getSystemInfo("account");
        $params['timestamp'] = number_format(microtime(true) * 1000, 0, '', '');
        $params['nonce'] = $this->createUUID();//

        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log("=====================hambit generatePaymentUrlForm", $params);
        $this->CI->utils->debug_log("=====================hambit new_amount", $amount);

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {
        $this->_custom_curl_header = array(
            'Content-Type:application/json',
            'access_key:'.$params['access_key'],
            'timestamp: ' . $params['timestamp'],
            'nonce: ' . $params['nonce'],
            'sign: ' . $params['sign'],
        );
        $unset_params = ['access_key','timestamp','nonce','sign'];
        foreach ($unset_params as $key) {
            unset($params[$key]);
        }
        

        $this->CI->utils->debug_log('========================================hambit processPaymentUrlFormPost params', $params);
        $this->CI->utils->debug_log('========================================hambit processPaymentUrlFormPost _custom_curl_header', [$this->_custom_curl_header]);

        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['externalOrderId']);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================hambit processPaymentUrlFormPost response json to array', $response);

        $msg = lang('Invalidate API response');
        if( isset($response['code']) && $response['code'] == self::REPONSE_CODE_SUCCESS ){
            if(isset($response['data']['cashierUrl']) && !empty($response['data']['cashierUrl'])){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['data']['cashierUrl'],
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => $msg
                );
            }
        }else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $msg
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

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $this->CI->utils->debug_log("=====================hambit callbackFrom $source params", $params);

        if($source == 'server' ){
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['externalOrderId'], null, null, null, $response_result_id);
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

    private function checkCallbackOrder($order, $fields, &$processed)
    {

        $head_params =[
            "access_key"=>$this->getSystemInfo('access_key'),//本幾紀錄商户号
            "timestamp"=>$_SERVER["HTTP_TIMESTAMP"],
            "nonce"=>$_SERVER["HTTP_NONCE"],
            "sign"=>$_SERVER["HTTP_SIGN"],
        ];
        # does all required fields exist?
        $requiredFields = array('currencyType', 'orderAmount', 'orderId', 'orderStatus','payParam','externalOrderId','payType','orderStatusCode');
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                    $this->writePaymentErrorLog("=========================hambit checkCallbackOrder missing parameter: [$f]", $fields);
                    return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields,$head_params)) {
            $this->writePaymentErrorLog("=========================hambit checkCallbackOrder Signature Error", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass
        if ($fields['orderStatusCode'] != self::DEPOSIT_CALLBACK_SUCCESS_CODE) {
            $this->writePaymentErrorLog("=========================hambit checkCallbackOrder returncode was not successful", $fields);
           return false;
        }

        if ($fields['externalOrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("=========================hambit checkCallbackOrder Order IDs do not match, expected [expectedOrderId]", $fields);
           return false;
        }

        if ($fields['orderAmount'] != $this->convertAmountToCurrency($order->amount)) {
            #because player need to enter amount at Alipay
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $this->CI->utils->debug_log('=====================hambit amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['processAmount'], $notes);
            }
            else{
                $this->writePaymentErrorLog("======================hambit checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
                return false;
            }
        }
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

    protected function sign($params) {
        $signStr = $this->createSignStr($params);
        $secret_key= $this->getSystemInfo("key");
        $sign = base64_encode(hash_hmac("sha1", $signStr, $secret_key, $raw_output=TRUE));
        return $sign;
    }

    protected function createSignStr($params) {

        ksort($params);
        $this->CI->utils->debug_log("=====================hambit new_params", $params);

        $signStr = '';
        foreach($params as $key => $value) {
            $signStr .= "$key=$value&";
        }

        $signStr = rtrim($signStr, '&');
        $this->CI->utils->debug_log("=====================hambit signStr", $signStr);
        return $signStr;
    }

    protected function validateSign($params,$head_params) {
        $callback_sign = $head_params['sign'];
        unset($head_params['sign']);
        $head_params['access_key'] = $this->getSystemInfo("account");
        $new_params = $params+$head_params;
        $signStr = $this->createSignStr($new_params);

        $secret_key= $this->getSystemInfo("key");
        $sign = base64_encode(hash_hmac("sha1", $signStr, $secret_key, $raw_output=TRUE));
        $this->CI->utils->debug_log("=====================hambit validateSign", [$callback_sign,$sign]);
        if($callback_sign == $sign){
            return true;
        }else{
            return false;
        }
    }

    protected function createUUID(){
        $uuid = md5(uniqid(mt_rand(), true));
        return $uuid;
    }
}
