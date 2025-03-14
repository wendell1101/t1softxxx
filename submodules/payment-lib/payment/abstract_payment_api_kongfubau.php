<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * KONGFUBAU 共富宝
 *
 * * KONGFUBAU_ALIPAY_PAYMENT_API, ID: 5631
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: https://kongfubau.com/api/transaction
 * * Key: ## Access Token ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_kongfubau extends Abstract_payment_api
{
    const CALLBACK_SUCCESS = 'success';
    const RETURN_SUCCESS_CODE = 'ok';


    public function __construct($params = null)
    {
        parent::__construct($params);
    }

    # Implement these to specify pay type
    abstract protected function configParams(&$params, $direct_pay_extra_info);
    abstract protected function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null)
    {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }
        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();
        $params['amount']       = $this->convertAmountToCurrency($amount);
        $params['trade_no'] = $order->secure_id;
        $params['notify_url']   = $this->getNotifyUrl($orderId);
        $this->configParams($params, $order->direct_pay_extra_info);
        $this->CI->utils->debug_log('=====================kongfubau generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    # Implement processPaymentUrlForm
    protected function processPaymentUrlFormRedirect($params)
    {
        $response = $this->processCurl($params);

        if (isset($response['uri']) && $response['success']) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['trade_no']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['trade_no']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['uri'],
            );
        } elseif (isset($response['message'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $response['message']
            );
        } else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
            );
        }
    }

    public function callbackFromServer($orderId, $params)
    {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    public function callbackFromBrowser($orderId, $params)
    {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    private function callbackFrom($source, $orderId, $params, $response_result_id)
    {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $this->CI->utils->debug_log("=====================kongfubau callbackFrom $source params", $params);

        if ($source == 'server') {
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
            $this->CI->sale_order->updateExternalInfo($order->id, null, null, null, null, $response_result_id);
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

    private function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = array(
            'receive_amount', 'trade_no', 'status', 'transaction_id' , 'signature'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================kongfubau checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================kongfubau checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================kongfubau checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['receive_amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================kongfubau checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['trade_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================kongfubau checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null)
    {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    private function validateSign($params)
    {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $params['signature'] === $sign;
    }

    private function createSignStr($params)
    {
        unset($params['signature']);
        ksort($params);
        $token = $this->getSystemInfo('key');
        $signStr = json_encode($params).$token;
        return $signStr;
    }

    # -- Private functions --
    private function getNotifyUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount)
    {
        return number_format($amount, 2, '.', '');
    }

    protected function processCurl($params)
    {
        $ch = curl_init();
        $url = $this->getSystemInfo('url');
        $token = $this->getSystemInfo('key');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.$token)
        );

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params, 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['trade_no']);


        $this->CI->utils->debug_log('=====================kongfubau processCurl response', $response);
        $response = json_decode($response, true);

        $this->CI->utils->debug_log('=====================kongfubau processCurl decoded response', $response);
        return $response;
    }
}
