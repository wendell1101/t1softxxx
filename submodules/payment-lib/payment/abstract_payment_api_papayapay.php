<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * PAPAYAPAY_PAYMENT_API
 *
 * * 'PAPAYAPAY_PAYMENT_API', ID 6060
 *
 * Required Fields:
 *
 * * URL: https://scb-staging.xyzonline.app/api/v1/create-qr
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_papayapay extends Abstract_payment_api {

    const QRCODE_RESULT_CODE_SUCCESS = '200';
    const SUCCESS_CODE      = '02_Paid';
    const RETURN_SUCCESS    = 'Success';

    # Implement these for specific pay type
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
        $params['qrCodeTransactionId'] = $order->secure_id;
        $params['amount'] = (float)$this->convertAmountToCurrency($amount); //元
        $params['currency'] = $this->getSystemInfo('currency','THB');
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['description']         = 'deposit';

        $this->CI->utils->debug_log("=====================papayapay generatePaymentUrlForm", $params);
        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormQRCode($params) {
        $response = $this->processCurl($this->getSystemInfo('url'), $params, $params['qrCodeTransactionId']);
        $response = json_decode($response, true);

        $this->CI->utils->debug_log('========================================response', $response);

        if(isset($response['statusCode']) && $response['statusCode'] == self::QRCODE_RESULT_CODE_SUCCESS) {
            return array(
                'success' => true,
                'type'    => self::REDIRECT_TYPE_URL,
                'url'     =>  $response['payurl']
            );
        }
        else if(isset($response['message']) && !empty($response['message'])) {
            return array(
                'success' => false,
                'type'    => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['message']
            );
        }
        else {
            return array(
                'success' => false,
                'type'    => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidte API response')
            );
        }
    }

    # Callback URI: /callback/fixed_process/<payment_api_id>
    public function getOrderIdFromParameters($flds){
        $this->utils->debug_log("=======================papayapay getOrderIdFromParameters", $flds);
        if (empty($flds)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================papayapay raw_post_data", $raw_post_data);
            $flds = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("=====================papayapay json_decode flds", $flds);
        }

        if (isset($flds['qrCodeTransactionId'])) {
           $this->CI->load->model(array('sale_order'));
           $order = $this->CI->sale_order->getSaleOrderBySecureId($flds['qrCodeTransactionId']);
           return $order->id;
        } else {
            $this->utils->debug_log('=====================papayapay callbackOrder cannot get any order_id when getOrderIdFromParameters', $flds);
            return;
        }
    }

    protected function processCurl($url, $params, $orderSecureId=NULL) {
        $ch = curl_init();
        $token = $this->getSystemInfo('key');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->CI->utils->encodeJson($params));
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
            'Content-Type:application/json',
            'Accept:application/json',
            'transactiontoken:'.$token)
        );
        $this->setCurlProxyOptions($ch);

        curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeoutSecond());
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());

        $response = curl_exec($ch);
        $this->CI->utils->debug_log('=========================submitPostForm curl content ', $response);

        $errCode = curl_errno($ch);
        $error = curl_error($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $content = substr($response, $header_size);

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        $statusText = $errCode . ':' . $error;
        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

        $response_result_content = is_array($content) ? json_encode($content) : $content;

        #save response result
        $response_result_id = $this->submitPreprocess($params, $response_result_content, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $orderSecureId);

        return $content;
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
        $this->CI->utils->debug_log("=======================papayapay callbackFromServer $source callbackFrom", $params);
        if($source == 'server'){
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
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;

        if ($processed) {
            $successJsonMsg['status']  = 200;
            $successJsonMsg['message'] = self::RETURN_SUCCESS;
            $result['message'] = json_encode($successJsonMsg);
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

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array('status', 'amount', 'currencyCode', 'qrCodeTransactionId', 'merchant');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================papayapay missing parameter: [$f]", $fields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::SUCCESS_CODE) {
            $payStatus = $fields['status'];
            $this->writePaymentErrorLog("=====================papayapay Payment was not successful, payStatus is [$payStatus]", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================papayapay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['qrCodeTransactionId'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================papayapay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        if ($fields['currencyCode'] != $this->getSystemInfo('currency')) {
            $currencyCode = $fields['currencyCode'];
            $this->writePaymentErrorLog("=====================papayapay Payment was not successful, currencyCode is [$currencyCode]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- amount --
    protected function convertAmountToCurrency($amount) {
        return number_format($amount , 2, '.', '');
    }

    # -- notifyURL --
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    # -- returnURL --
    public function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }
}
