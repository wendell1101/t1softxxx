<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * PAYMERO
 * https://api-docs.paymero.io
 *
 * * PAYMERO_PAYMENT_API, ID: 5718
 * * PAYMERO_P2P_PAYMENT_API, ID: 5719
 * * PAYMERO_QUICKPAY_PAYMENT_API, ID: 5720
 * * PAYMERO_QRCODE_PAYMENT_API, ID: 5721
 * * PAYMERO_WITHDRAWAL_PAYMENT_API, ID: 5723
 * *
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: https://service-api.paymero.io/
 * * Key: ## API Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_paymero extends Abstract_payment_api {

    const DEVICE_PC    = 'WEB';
    const DEVICE_MOBILE = 'H5';
    const RESULT_CODE_SUCCESS = 'success';
    const CALLBACK_SUCCESS = 'SUCCESS';
    const CALLBACK_FAIL = 'FAIL';
    const RETURN_SUCCESS_CODE = 'OK';


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

        $this->CI->load->model(array('player'));
        $order  = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();
        $params['amount']           = $this->convertAmountToCurrency($amount,$order->created_at);
        $params['orderId']          = $order->secure_id;
        $params['returnUrl']        = $this->getReturnUrl($orderId);
        $params['notifyUrl']        = $this->getNotifyUrl($orderId);
        $params['currency']         = $this->getSystemInfo('currency','CNY');
        $params['productName']      = 'deposit';
        $params['PMID']             = $this->getSystemInfo('account');

        $this->configParams($params, $order->direct_pay_extra_info);

        $this->CI->utils->debug_log("======================paymero generatePaymentUrlForm params", $params);
        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $response = $this->processCurl($params);
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_HTML,
            'html' => $response,
        );
    }

    protected function processCurl($params,$transId=NULL,$return_all=false) {
        $url = $this->getSystemInfo('url');
        $token = $this->getSystemInfo('key');
        $this->_custom_curl_header = array(
            'Content-Type: application/x-www-form-urlencoded',
            'x-api-key: '.$token
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_custom_curl_header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeoutSecond());
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());
        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $header = substr($response, 0, $header_size);
        $content = substr($response, $header_size);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $content, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

        #withdrawal lock processing
        if(substr($transId, 0, 1) == 'W' && $errCode == '28') { //curl_errno means timeout
            $content = array('lock' => true, 'msg' => 'Ready to lock processing withdrawal order. curl error message: errCode = '.$errCode.' - '.$error);
        }
        $response_result_content = is_array($content) ? json_encode($content) : $content;

        $this->CI->utils->debug_log('url', $url, 'params', $params, 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        #save response result
        $response_result_id = $this->submitPreprocess($params, $content, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['orderId']);
           if($return_all){
            $response_result = [
                $params, $response_result_content, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $transId
            ];
            $this->CI->utils->debug_log('=========================paymero  return_all response_result', $response_result);
            return array($content, $response_result);
        }


        return $content;
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

        $this->CI->utils->debug_log("=======================paymero callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================paymero raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data, true);
                $this->CI->utils->debug_log("=====================paymero json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderId'], null, null, null, $response_result_id);
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
            'amount', 'currency', 'orderId', 'transactionStatus'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================paymero checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['transactionStatus'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("=======================paymero checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount,$order->created_at)) {
            $this->writePaymentErrorLog("=======================paymero checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['orderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("=======================paymero checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    # -- private helper functions --
    public function getBankListInfoFallback() {
        return array(
            array('label' => '中国工商银行' , 'value' => 'ICBC'),
            array('label' => '中国农业银行' , 'value' => 'ABC'),
            array('label' => '中国银行' , 'value' => 'BOC'),
            array('label' => '广发银行' , 'value' => 'GDB'),
            array('label' => '邮储银行' , 'value' => 'PSBC'),
            array('label' => '民生银行' , 'value' => 'CMBC'),
        );
    }


    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- Private functions --
    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount, $orderDateTime) {
        if($this->getSystemInfo('use_usd_currency')){
            if(is_string($orderDateTime)){
                $orderDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $orderDateTime);
            }
            $amount = $this->gameAmountToDBByCurrency($amount, $this->utils->getTimeForMysql($orderDateTime),'USD','CNY');
            $this->CI->utils->debug_log('=====================paymero convertAmountToCurrency use_usd_currency', $amount);
        }
        return number_format($amount, 2, '.', '');
    }
}