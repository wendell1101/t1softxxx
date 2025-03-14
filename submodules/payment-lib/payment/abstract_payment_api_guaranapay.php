<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * GUARANAPAY
 *
 * * GUARANAPAY_PAYMENT_API, ID: 5992
 *
 * Required Fields:
 *
 * * URL:https://gateway.guaranapay.com/pg/dk/order/create
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_guaranapay extends Abstract_payment_api {

    const PAYTYPE_CPF = 'CPF';
    const REPONSE_CODE_SUCCESS = '0000';
    const ORDER_STATUS_SUCCESS = 'success';
    const ORDER_STATUS_FAILED = 'failure';
    const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE = 'failed';

    # Implement these for specific pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['appId'] = $this->getSystemInfo("account");
        $params['currency'] = $this->getSystemInfo("currency");
        $params['merTransNo'] = $order->secure_id;
        $params['notifyUrl'] = $this->getNotifyUrl($orderId);
        $params['returnUrl'] = $this->getReturnUrl($orderId);

        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log("=====================guaranapay generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['merTransNo']);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================guaranapay processPaymentUrlFormPost response json to array', $response);

        $msg = lang('Invalidate API response');
        if(isset($response['data']['resultCode']) && $response['data']['resultCode'] == self::REPONSE_CODE_SUCCESS && isset($response['data']['url'])) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data']['url'],
            );
        }else {
            if(isset($response['msg']) && !empty($response['msg'])) {
                $msg = $response['msg'];
            }
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
        $this->CI->utils->debug_log("=====================guaranapay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['merTransNo'], null, null, null, $response_result_id);
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
        # does all required fields exist?
        $requiredFields = array('merTransNo', 'amount', 'processAmount', 'transStatus', 'sign');
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                    $this->writePaymentErrorLog("=========================guaranapay checkCallbackOrder missing parameter: [$f]", $fields);
                    return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog("=========================guaranapay checkCallbackOrder Signature Error", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass
        if ($fields['transStatus'] != self::ORDER_STATUS_SUCCESS) {
            $this->writePaymentErrorLog("=========================guaranapay checkCallbackOrder returncode was not successful", $fields);
           return false;
        }

        if ($fields['merTransNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("=========================guaranapay checkCallbackOrder Order IDs do not match, expected [$expectedOrderId]", $fields);
           return false;
        }

        if ($fields['processAmount'] != $this->convertAmountToCurrency($order->amount)) {
            #because player need to enter amount at Alipay
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $this->CI->utils->debug_log('=====================guaranapay amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['processAmount'], $notes);
            }
            else{
                $this->writePaymentErrorLog("======================guaranapay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
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

    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = hash('sha256', $signStr);
        return $sign;
    }

    public function createSignStr($params) {
        ksort($params);
        $signStr = '';
        $extInfoSignStr = '';
        foreach($params as $key => $value) {
            if($value == null || $key == 'sign') {
                continue;
            }elseif ($key == 'extInfo') {
                ksort($params['extInfo']);
                foreach ($params['extInfo'] as $extInfoKey => $extInfoValue) {
                    $extInfoSignStr .= "$extInfoKey=$extInfoValue&";
                }
                $value = rtrim($extInfoSignStr, '&');
            }
            $signStr .= "$key=$value&";
        }
        return $signStr.'key='.$this->getSystemInfo('key');
    }

    public function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($value == null || $key == 'sign') {
                continue;
            }elseif ($key == 'extInfo') {
                ksort($params['extInfo']);
                foreach ($params['extInfo'] as $extInfoKey => $extInfoValue) {
                    $extInfoSignStr .= "$extInfoKey=$extInfoValue&";
                }
                $value = rtrim($extInfoSignStr, '&');
            }
            $signStr .= "$key=$value&";
        }

        $signStr .= 'key='.$this->getSystemInfo('key');
        $sign = hash('sha256', $signStr);
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }
}
