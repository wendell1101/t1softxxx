<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * realpay
 *
 * * REALPAY_PAYMENT_API, ID: 5184
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://119.29.115.76/preCreate
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_realpay extends Abstract_payment_api {
    const CALLBACK_SUCCESS = 'SUCCESS';
    const REQUEST_SUCCESS  = '0000';
    const RETURN_SUCCESS_CODE = 'SUCCESS';

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }

    # Implement these to specify pay type
    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();
        $params['head']['mchtId']       = $this->getSystemInfo('account');
        $params['head']['version']      = '20';
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['body']['orderId']      = $order->secure_id;
        $params['body']['orderTime']    = date('YmdHis');
        $params['body']['amount']       = $this->convertAmountToCurrency($amount); //分
        $params['body']['currencyType'] = $this->getSystemInfo('currencyType');
        $params['body']['goods']        = 'Deposit';
        $params['body']['notifyUrl']    = $this->getNotifyUrl($orderId);
        $params['body']['callBackUrl']  = $this->getReturnUrl($orderId);
        $params['body']['userId']       = $playerId;
        $params['sign']                 = $this->sign($params['body']);
        $this->CI->utils->debug_log('=====================realpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {
        $url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, true, $params['body']['orderId']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log("=====================realpay response", $response);
        $this->CI->utils->debug_log("=====================realpay decode_data", $decode_data);

        $msg = lang('Invalidate API response');
        if(!empty($decode_data['head']['respCode']) && ($decode_data['head']['respCode'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decode_data['body']['payUrl'],
            );
        }else {
            if(!empty($decode_data['head']['respMsg'])) {
                $msg = $decode_data['head']['respMsg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }


    ## This will be called when the payment is async, API server calls our callback page
    ## When that happens, we perform verifications and necessary database updates to mark the payment as successful
    ## Reference: sample code, callback.php
    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    ## This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================realpay params", $params);

        if($source == 'server' ){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================realpay raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================realpay json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['body']['orderId'], '', null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'orderId', 'tradeId', 'status', 'amount'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields['body'])) {
                $this->writePaymentErrorLog("=====================realpay checkCallbackOrder Missing parameter: [$f]", $fields['body']['status']);
                return false;
            }
        }

        # is signature authentic?
        if ($fields['sign'] != $this->validateSign($fields['body'])) {
            $this->writePaymentErrorLog('=====================realpay checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['body']['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================realpay checkCallbackOrder Payment status is not success", $fields['body']);
            return false;
        }

        if ($fields['body']['amount'] != $this->convertAmountToCurrency($order->amount)) {
            #because player need to enter amount at Alipay
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $this->CI->utils->debug_log('=====================realpay amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['body']['amount'], $notes);

            }
            else{
                $this->writePaymentErrorLog("=====================realpay Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['body']['orderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================realpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    # Reference: PHP Demo
    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    public function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if(empty($value)) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return $signStr.'key='.$this->getSystemInfo('key');
    }

    public function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if( ($key == 'sign' || empty($value))) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        $sign = md5($signStr);
        return $sign;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## After payment is complete, the gateway will send redirect back to this URL
    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount) {
        return number_format($amount * 100 , 0, '.', '');
    }
}