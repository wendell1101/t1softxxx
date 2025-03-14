<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * PGTIANCI_V2
 *
 * PGTIANCI_WITHDRAWAL_V2_PAYMENT_API, ID: 5918
 *
 * Required Fields:
 * * URL
 * * Key
 * * uid (merchant ID)
 *
 * Field Values:
 * * URL        : https://tianciv990901.com/api/transaction
 * * Key        : ## Live key ##
 * * uid        : ## merchant ID ##
 *
 * @category Payment
 * @copyright 2022 tot
 */
abstract class Abstract_payment_api_pgtianci_v2 extends Abstract_payment_api {
    const RETURN_SUCCESS_CODE = 'ok';
    const STATUS_SUCCESSFUL = 'completed';

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

        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['callback_url'] = $this->getNotifyUrl($orderId);
        $params['out_trade_no'] = $order->secure_id;
        $this->configParams($params, $order->direct_pay_extra_info);
        $this->CI->utils->debug_log("=====================pgtianci_v2 generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->processCurl($params);
        if (empty($response)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================pgtianci_v2 raw_post_data", $raw_post_data);
            $response = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================pgtianci_v2 json_decode params", $params);
        }
        $this->CI->utils->debug_log('=====================pgtianci_v2 processCurl decoded response', $response);

        if(isset($response['success']) && $response['success']) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['out_trade_no']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['data']['out_trade_no']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data']['uri'],
            );
        }
        else {
            if(isset($response['status_code'])){
                $msg = $this->getReturnErrorMsg($response['status_code']);
            }else{
                $msg = lang('Invalidate API response');
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

    protected function processPaymentUrlFormQRCode($params) {
        $response = $this->processCurl($params);
        if (empty($response)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================pgtianci_v2 raw_post_data", $raw_post_data);
            $response = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================pgtianci_v2 json_decode params", $params);
        }
        $this->CI->utils->debug_log('=====================pgtianci_v2 processCurl decoded response', $response);

        if(isset($response['success']) && $response['success']) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['out_trade_no']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['data']['out_trade_no']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $response['data']['uri'],
            );
        }
        else {
            if(isset($response['status_code'])){
                $msg = $this->getReturnErrorMsg($response['status_code']);
            }else{
                $msg = lang('Invalidate API response');
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
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

    protected function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================pgtianci_v2 callbackFrom $source params", $params);

        if ($source == 'server') {
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================pgtianci_v2 raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data, true);
                $this->CI->utils->debug_log("=====================pgtianci_v2 json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['out_trade_no'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto browser callback ' . $this->getPlatformCode(), false);
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
        // Check required fields
        $requiredFields = [ 'state', 'out_trade_no', 'sign', 'amount' ];

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================pgtianci_v2 checkCallbackOrder field missing '$f'");
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('Signature Error', $fields);
            return false;
        }

        // processed is set to true once the signature verification pass
        $processed = true;

        if ($fields['state'] != self::STATUS_SUCCESSFUL) {
            $this->writePaymentErrorLog("=====================pgtianci_v2 checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        // Check amount (cash)
        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog('=====================pgtianci_v2 checkCallbackOrder amount mismatch', [ 'expected' => $order->amount, 'returned' => $cb_result['amount'] ], $fields);
            return false;
        }

        // Check order ID (customer_order_no)
        if ($fields['out_trade_no'] != $order->secure_id) {
            $this->writePaymentErrorLog('=====================pgtianci_v2 checkCallbackOrder order ID mismatch', [ 'expected' => $order->secure_id, 'returned' => $cb_result['orderId'] ], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    protected function processCurl($params) {
        $ch = curl_init();
        $url = $this->getSystemInfo('url');
        $token = $this->getSystemInfo('key');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token)
        );

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $content = substr($response, $header_size);

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'content', $content, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $content, $url, $content, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['out_trade_no']);

        $this->CI->utils->debug_log('=====================pgtianci_v2 processCurl response', $content);

        $response = json_decode($content, true);

        return $response;
    }

    protected function getBankListInfoFallback() {
        return array(
            array('label' => 'NGAN HANG TMCP A CHAU (ACB)', 'value' => 'ACB'),
            array('label' => 'NGAN HANG TMCP DAU TU VA PHAT TRIEN VIET NAM (BIDV)', 'value' => 'BIDV'),
            array('label' => 'NGAN HANG TMCP KY THUONG VIET NAM (TCB)', 'value' => 'TCB'),
            array('label' => 'NGAN HANG TMCP NGOAI THUONG VIET NAM (VIETCOMBANK)', 'value' => 'VCB'),
            array('label' => 'NGAN HANG TMCP CONG THUONG VIET NAM (VIETINBANK)', 'value' => 'VTB'),

        );
    }

    protected function validateSign($params) {
        $signStr = '';
        ksort($params);
        foreach($params as $key => $value) {
            if($key == 'sign' || $key == 'request_amount') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr = rtrim($signStr, '&');
        $signStr .= $this->getSystemInfo('key').$this->getSystemInfo('callback_token');
        $sign = md5($signStr);

        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    # -- Private functions --
    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 2, '.', '');
    }

    protected function getReturnErrorMsg($returnCode) {
        $msgs = array(
            '401' => lang('Token error'),
            '404' => lang('No data found'),
            '422' => lang('Incorrect data'),
            '429' => lang('Request too frequent'),
            '1000' => lang('The service is temporarily busy'),
            '1002' => lang('Your previous request is under progress'),
        );

        return array_key_exists($returnCode, $msgs) ? $msgs[$returnCode] : lang('Invalidate API response');
    }
}