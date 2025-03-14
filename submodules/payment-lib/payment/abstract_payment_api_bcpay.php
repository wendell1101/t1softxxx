<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * BCPAY
 *
 * * BCPAY_PAYMENT_API, ID: 6085
 *
 * Required Fields:
 *
 * * URL:https://gateway.bcpay.com/pg/dk/order/create
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_bcpay extends Abstract_payment_api {

    const PAYTYPE_CPF = 'CPF';
    const REPONSE_CODE_SUCCESS = '0';
    const ORDER_STATUS_SUCCESS_1 = '3';
    const ORDER_STATUS_SUCCESS_2 = '2';
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
        $playerdetail = $this->CI->player_model->getPlayerDetails($playerId);

        $firstname = 'none';
        $lastname = 'none';
        $pix_number = 'none';
        $username = 'none';
        
        if(!empty($playerdetail)){
            $firstname = (isset($playerdetail[0]) && !empty($playerdetail[0]['firstname']))? $playerdetail[0]['firstname'] : 'no firstName';
            $lastname   = (isset($playerdetail[0]) && !empty($playerdetail[0]['lastname']))? $playerdetail[0]['lastname'] : 'no lastName';
            $pix_number  = (isset($playerdetail[0]) && !empty($playerdetail[0]['pix_number']))? $playerdetail[0]['pix_number'] : 'none';
            $username    = (isset($playerdetail[0]) && !empty($playerdetail[0]['username']))? $playerdetail[0]['username'] : 'none';
        }
        
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['appId'] = $this->getSystemInfo("account");
        $params['currency'] = $this->getSystemInfo("currency");
        $params['merOrderNo'] = $order->secure_id;
        $params['notifyUrl'] = $this->getNotifyUrl($orderId);
        $params['returnUrl'] = $this->getReturnUrl($orderId);
        $params['devedor'] = array(
            'cpf'  => $pix_number,
            'nome' => $username,
        );
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log("=====================bcpay generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['merOrderNo']);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================bcpay processPaymentUrlFormPost response json to array', $response);

        $msg = lang('Invalidate API response');
        if( isset($response['code']) && $response['code'] == self::REPONSE_CODE_SUCCESS ){
            if(isset($response['data']['params']['url']) && !empty($response['data']['params']['url'])){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['data']['params']['url'],
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => $msg
                );
            }
        }else {
            if(isset($response['error']) && !empty($response['error'])) {
                $msg = $response['error'];
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
        $this->CI->utils->debug_log("=====================bcpay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['merOrderNo'], null, null, null, $response_result_id);
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
        $requiredFields = array('orderStatus', 'orderNo', 'merOrderNo', 'amount', 'sign');
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                    $this->writePaymentErrorLog("=========================bcpay checkCallbackOrder missing parameter: [$f]", $fields);
                    return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog("=========================bcpay checkCallbackOrder Signature Error", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass
        if (($fields['orderStatus'] != self::ORDER_STATUS_SUCCESS_1) && ($fields['orderStatus'] != self::ORDER_STATUS_SUCCESS_2)) {
            $this->writePaymentErrorLog("=========================bcpay checkCallbackOrder returncode was not successful", $fields);
           return false;
        }

        if ($fields['merOrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("=========================bcpay checkCallbackOrder Order IDs do not match, expected [$expectedOrderId]", $fields);
           return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            #because player need to enter amount at Alipay
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $this->CI->utils->debug_log('=====================bcpay amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['processAmount'], $notes);
            }
            else{
                $this->writePaymentErrorLog("======================bcpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
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
        $devInfoSignStr = '';
        foreach($params as $key => $value) {
            if($value == null || $key == 'sign') {
                continue;
            }elseif ($key == 'extra') {
                ksort($params['extra']);
                foreach ($params['extra'] as $extInfoKey => $extInfoValue) {
                    $extInfoSignStr .= "$extInfoKey=$extInfoValue&";
                }
                $value = rtrim($extInfoSignStr, '&');
            }elseif ($key == 'devedor') {
                ksort($params['devedor']);
                $value = json_encode($params['devedor']);
            }
            $signStr .= "$key=$value&";
        }
        $this->writePaymentErrorLog("=========================signStr ", $signStr.'key='.$this->getSystemInfo('key'));
        
        return $signStr.'key='.$this->getSystemInfo('key');
    }

    public function validateSign($params) {
        ksort($params);
        $signStr = '';
        $extInfoSignStr = '';
        $devInfoSignStr = '';
        foreach($params as $key => $value) {
            if($value == null || $key == 'sign') {
                continue;
            }elseif ($key == 'extra') {
                ksort($params['extra']);
                foreach ($params['extra'] as $extInfoKey => $extInfoValue) {
                    $extInfoSignStr .= "$extInfoKey=$extInfoValue&";
                }
                $value = rtrim($extInfoSignStr, '&');
            }elseif ($key == 'devedor') {
                ksort($params['devedor']);
                $value = json_encode($params['devedor']);
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
