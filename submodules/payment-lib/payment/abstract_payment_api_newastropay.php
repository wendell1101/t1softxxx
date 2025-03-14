<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * * NEWASTROPAY_PAYMENT_API, ID: 6214
 * * NEWASTROPAY_WITHDRAWAL_PAYMENT_API, 6215
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_newastropay extends Abstract_payment_api {
    const CURRENCY                = 'USD';
    const COUNTRY                 = 'IN';
    const COUNTRY_CODE            = '91';


    const RETURN_SUCCESS_CODE     = 'success';
    const PAY_RESULT_SUCCESS      = 'PENDING';
    const CALLBACK_RESULT_SUCCESS = 'APPROVED';

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

        $user_params['merchant_user_id'] = $playerId;

        $product_params['mcc']           = $this->getSystemInfo('account');
        $product_params['merchant_code'] = $this->getSystemInfo('merchant_code', $orderId);
        $product_params['description']   = $this->getSystemInfo('description', 'Deposit');


        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $params['amount']                = $this->convertAmountToCurrency($amount);
        $params['currency']              = $this->getSystemInfo("currency",self::CURRENCY);
        $params['country']               = $this->getSystemInfo("country",self::COUNTRY);
        $params['merchant_deposit_id']   = $order->secure_id;
        $params['callback_url']          = $this->getNotifyUrl($orderId);
        $params['user']                  = $user_params;
        $params['product']               = $product_params;

        if($this->getSystemInfo('sendred_url')){
            $params['redirect_url'] = $this->getReturnUrl($orderId);
        }

        $this->configParams($params, $order->direct_pay_extra_info);
        $this->CI->utils->debug_log("=====================NEWASTROPAY generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $response = $this->processCurl($params, $this->getSystemInfo('url'), $params['merchant_deposit_id']);
        $this->CI->utils->debug_log('=====================NEWASTROPAY processPaymentUrlFormPost response', $response);
        $msg = '';
        if(isset($response['status']) && $response['status'] == self::PAY_RESULT_SUCCESS){
            if(isset($response['url']) && !empty($response['url']))
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['url'],
            );
        }
        else if(isset($response['description']) && !empty($response['description'])) {
            $msg = $response['description'];
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
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
    public function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server' ){
            if(empty($params)){
                $raw_post_data = file_get_contents('php://input', 'r');
                $params = json_decode($raw_post_data, true);
            }

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['merchant_deposit_id'], null, null, null, $response_result_id);
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
            $result['return_error'] = self::RETURN_SUCCESS_CODE;
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

    public function checkCallbackOrder($order, $fields, &$processed = false) {

        $requiredFields = array('status', 'merchant_deposit_id', 'deposit_external_id');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================NEWASTROPAY missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================NEWASTROPAY checkCallbackOrder signature Error', $fields);
            return false;
        }

        if ($fields['status'] != self::CALLBACK_RESULT_SUCCESS) {
            $payStatus = $fields['status'];
            $this->writePaymentErrorLog("=====================NEWASTROPAY checkCallbackOrder Payment was not successful, payStatus is [$payStatus]", $fields);
            return false;
        }

        if ($fields['merchant_deposit_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================NEWASTROPAY checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }
    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- public functions --
    /**
     * detail: After payment is complete, the gateway will invoke this URL asynchronously
     *
     * @param int $orderId
     * @return void
     */
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    /**
     * detail: After payment is complete, the gateway will send redirect back to this URL
     *
     * @param int $orderId
     * @return void
     */
    public function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    /**
     * detail: Format the amount value for the API
     *
     * @param float $amount
     * @return float
     */
    public function convertAmountToCurrency($amount) {
        return number_format($amount, 0, '.', '');
    }

    public function sign($data) {
        $signStr = json_encode($data);
        $sign = hash_hmac('sha256', $signStr, $this->getSystemInfo('secret_key'));

        $this->CI->utils->debug_log('=====================NEWASTROPAY signStr', $signStr);
        $this->CI->utils->debug_log('=====================NEWASTROPAY sign', $sign);

        return $sign;
    }

    public function validateSign($params) {
        $headers = $this->CI->input->request_headers();
        $this->CI->utils->debug_log("=====================NEWASTROPAY callback Headers params", $headers);

        foreach ($headers as $key => $value) {
            if(strpos($key,'Signature') !== false){
                $hmac = $value;
            }
        }

        $sign = hash_hmac('sha256', json_encode($params), $this->getSystemInfo('secret_key'));
        $this->CI->utils->debug_log("=====================NEWASTROPAY callback Headers sign", $sign, $params);
        if ( $hmac == $sign ) {
            return true;
        } else {
            return false;
        }
    }

    public function processCurl($params, $url, $out_trade_no, $return_all=false) {
        $signature = $this->sign($params);
        $ch        = curl_init();
        $apiKey    = $this->getSystemInfo('key');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Merchant-Gateway-Api-Key:'.$apiKey,
            'Signature:'.$signature
            )
        );

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $out_trade_no);

        $this->CI->utils->debug_log('=====================NEWASTROPAY processCurl response', $response);

        if($return_all){
            $response_result = [
                $params, $response, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $params['merchant_cashout_id']
            ];
            return array($response, $response_result);
        }

        $response = json_decode($response, true);

        $this->CI->utils->debug_log('=====================NEWASTROPAY processCurl decoded response', $response);

        return $response;
    }

}