<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * LUOYIPAY 罗伊支付
 *
 * * LUOYIPAY_PAYMENT_API, ID: 5861
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://lyabc.xyz/luoyi/merchant/payment/order
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_luoyipay extends Abstract_payment_api {
    const PAY_METHODS_BANKCARD = "1";
    const PAY_METHODS_ALIPAY = "2";
    const PAY_METHODS_WECHAT = "3";
    const CALLBACK_STATUS_SUCCESS = 0;
    const CALLBACK_STATUS_ERROR = -1;
    const RETURN_SUCCESS_CODE = 'OK';
    const CALLBACK_SUCCESS    = 'pay';
    const STATUS_PAY = '1';
    const STATUS_UNPAY = '0';

    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null)
    {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $this->configParams($params, $order->direct_pay_extra_info); // $params['channelId']
        $params['merchantId'] = $this->getSystemInfo('account');
        $params['merchantOrderNo'] = $order->secure_id;
        $params['notifyUrl'] = $this->getNotifyUrl($orderId) ?: '127.0.0.1';
        $params['randomCode'] = $this->generateRandomCode();
        $params['requestIp'] = $this->getClientIp();

        $sign_sequence = ['amount','channelId','merchantId','merchantOrderNo','notifyUrl','randomCode','requestIp'];
        $params['sign'] = $this->sign($params, $sign_sequence);


        $this->CI->utils->debug_log('=====================luoyipay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function generateRandomCode() {
        $randomCode = mt_rand(1000000000, 9999999999);
        return $randomCode;
    }

    protected function processPaymentUrlFormPost($params)
    {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['merchantOrderNo']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log("=======================luoyipay processPaymentUrlFormPost response", $response);
        if (is_array($response)) {
            if ($response['status'] == self::CALLBACK_STATUS_SUCCESS && $response['msg'] == self::RETURN_SUCCESS_CODE) {
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['url'],
                );
            } elseif (!empty($response['msg'])) {
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => $response['msg']
                );
            } else {
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => lang('Invalidate API response')
                );
            }
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

        $this->CI->utils->debug_log("=====================luoyipay callbackFrom $source params", $params);

        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        if ($source == 'server') {
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        $success = true;

        if($params['payStatus'] != self::STATUS_PAY) {

            $result['success'] = $success;
            $result['message'] = self::RETURN_SUCCESS_CODE;
            return $result;
        } else if($params['payStatus'] == self::STATUS_PAY) {

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
                $this->CI->sale_order->updateExternalInfo($order->id, $params['merchantOrderNo'], null, null, null, $response_result_id);
                if ($source == 'browser') {
                    $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
                } elseif ($source == 'server') {
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
            }
        }

        # Update order payment status and balance

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
            'merchantId', 'merchantOrderNo', 'payStatus', 'payTime', 'amount', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================luoyipay Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        $sign_sequence = ['amount','merchantId','merchantOrderNo','payStatus','payTime'];
        if (!$this->validateSign($fields, $sign_sequence)) {
            $this->writePaymentErrorLog('=====================luoyipay Signature Error', $fields);
            return false;
        }

        if ($this->convertAmountToCurrency($order->amount) != $fields['amount']) {
            $this->writePaymentErrorLog("=======================luoyipay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['merchantOrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================luoyipay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }

    private function sign($params, $sequence)
    {
        $signStr = $this->createSignStr($params, $sequence);
        $sign = md5($signStr);
        return $sign;
    }

    public function createSignStr($params, $sequence)
    {
        $signStr = '';

        foreach ($sequence as $key) {
            if ($key == 'sign') {
                continue;
            }
            $signStr .= "$key=".$params[$key]."&";

        }

        $signStr .= "key=".$this->getSystemInfo('key');
        return $signStr;
    }

    public function validateSign($params, $sequence)
    {
        $sign = $this->sign($params, $sequence);
        if ($params['sign'] == $sign) {
            return true;
        } else {
            return false;
        }
    }

    public function directPay($order = null)
    {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- Private functions --
    protected function getNotifyUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount)
    {
        return number_format($amount*100, 0, '.', '');
    }
}
