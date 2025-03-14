<?php

require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * QINGPAY
 * http://pay.qingzhuzi.com/qingpay.php
 *
 * QINGPAY_PAYMENT_API
 *
 * Prerequisites :
 * * UID
 * * Merchant Key
 *
 * @category Payment
 * @copyright 2013-2022 tot-Bryson
 */

abstract Class Abstract_payment_api_qingpay extends Abstract_payment_api
{

    const TYPE_URL_PARAMS = 1;
    const TYPE_CALLBACK_PARAMS = 2;
    const TYPE_REQUEST_SIGNATURE_PARAMS = 3;
    const TYPE_RESPONE_SIGNATURE_PARAMS = 4;

    private $rules = [
        self::TYPE_URL_PARAMS => ['c', 'money', 'bank', 'order', 'notify', 'return', 'uid', 'md5'],
        self::TYPE_CALLBACK_PARAMS => ['money', 'bank', 'order', 'md5'],
        self::TYPE_REQUEST_SIGNATURE_PARAMS => ['money', 'bank', 'order', 'notify', 'uid', 'key'],
        self::TYPE_RESPONE_SIGNATURE_PARAMS => ['bank', 'money', 'order', 'notify', 'uid', 'key']
    ];

    public function __construct($params = null)
    {
        parent::__construct($params);
        # Populate $info with the following keys
        # url, key, account, secret, system_info
        $this->info = $this->getInfoByEnv();
    }

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null)
    {
        # For second url redirection
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        # read some parameters from config
        $params['c'] = 'qingpay';
        $params['order'] = $order->secure_id;
        $params['key'] = $this->getSystemInfo('key');
        $params['uid'] = $this->getSystemInfo('account');
        $params['notify'] = $this->getNotifyUrl($order->id);
        $params['return'] = $this->getReturnUrl($order->id);
        $params['money'] = $this->convertAmountToCurrency($order->amount);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['md5'] = $this->sign($params);

        return $this->processPaymentUrlForm($params);

    }

    # Submit POST form
    protected function processPaymentUrlFormPost($params)
    {
        return array(
            'success' => true,
            'type'    => self::REDIRECT_TYPE_FORM,
            'url'     => $this->getSystemInfo('url'),
            'params'  => $params,
            'post'    => true,
        );
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params)
    {
        return array(
            'success' => true,
            'type'    => self::REDIRECT_TYPE_URL,
            'url'     => $this->getSystemInfo('url') . "?" . $this->buildHttpQueryStr($params, self::TYPE_URL_PARAMS)
        );
    }

    ## This will be called when the payment is async, API server calls our callback page
    ## When that happens, we perform verifications and necessary database updates to mark the payment as successful
    public function callbackFromServer($orderId, $params)
    {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }
    ## This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params)
    {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }
    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id)
    {
        $this->utils->debug_log('=================qingp callbackFrom' . ucfirst($source) . ': [' . $orderId .'], params:', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        if (!is_array($params) || empty($params)) {
            $flds = json_decode(file_get_contents('php://input'), true);
            $params = array_merge($params, $flds);
            $this->utils->debug_log('=================qingp json callback decode', $params);
        }

        $processed = false;

        if($source == 'browser') {
            $processed = true;
        }
        else {
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed) ) {
                return $result;
            }
        }

        # Update order payment status and balance
        $this->CI->sale_order->startTrans();
        # Update player balance based on order status
        # if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            $external_order_id = isset($params['body']) ? $params['body']['reference'] : $params['oid'];

            # update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $external_order_id, null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }
        $success = $this->CI->sale_order->endTransWithSucc();
        $result['success'] = $success;
        if ($success) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
        }
        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }
        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = $this->rules[self::TYPE_CALLBACK_PARAMS];

        foreach ($requiredFields as $_params) {
            if (!array_key_exists($_params, $fields)) {
                $this->writePaymentErrorLog("=====================qingp missing parameter: [$_params]", $fields);
                return false;
            }
        }

        $data['bank'] = $fields['bank'];
        $data['order'] = $order->secure_id;
        $data['key'] = $this->getSystemInfo('key');
        $data['uid'] = $this->getSystemInfo('account');
        $data['notify'] = $this->getNotifyUrl($order->id);
        $data['money'] = $this->convertAmountToCurrency($fields['money']);

        $localSignature = $this->callbackSign($data);

        # is signature authentic?
        if ($fields['md5'] != $localSignature) {
            $this->writePaymentErrorLog("=====================qingp check callback sign error, signature is [$localSignature], match? ", $fields);
            return false;
        }

        if ($fields['order'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================qingp order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        if ($this->convertAmountToCurrency($order->amount) != $this->convertAmountToCurrency($fields['money'])) {
            $this->writePaymentErrorLog("=====================qingp Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null)
    {
        return array('success' => false); # direct pay not supported by this API
    }

    public function getNotifyUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function getReturnUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount)
    {
        return number_format($amount, 2, '.', '');
    }

    # -- private helper functions --
    /**
     * @name	generate signature
     * @param	sourceData
     * @return	signature
     */
    public function sign($params)
    {
        $preEncodeStr = $this->buildHttpQueryStr($params, self::TYPE_REQUEST_SIGNATURE_PARAMS);
        $signature = md5($preEncodeStr);

        return $signature;
    }

    private function callbackSign($params)
    {
        $preEncodeStr = $this->buildHttpQueryStr($params, self::TYPE_RESPONE_SIGNATURE_PARAMS);
        return md5($preEncodeStr);
	}

    private function buildHttpQueryStr($params, $type)
    {
        $_rule = $this->rules[$type];

        $encodeArray = [];
        foreach ($_rule as $_params) {
            if (isset($params[$_params])) {
                $encodeArray[$_params] = $params[$_params];
            }
        }
        return urldecode(http_build_query($encodeArray));
    }
}