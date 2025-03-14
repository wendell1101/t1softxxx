<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ZOTAPAY
 *
 * * ZOTAPAY_PAYMENT_API, ID: 367
 * * ZOTAPAY_ALIPAY_PAYMENT_API, ID: 427
 * * ZOTAPAY_WEIXIN_PAYMENT_API, ID: 428
 * * ZOTAPAY_QQPAY_PAYMENT_API, ID: 429
 * * ZOTAPAY_UNIONPAY_PAYMENT_API, ID: 430
 * * ZOTAPAY_CREDITCARD_PAYMENT_API, ID: 5460
 * * ZOTAPAY_UNIONPAY_2_PAYMENT_API, ID: 5571
 *
 * Required Fields:
 *
 * * URL
 * *
 * * Extra Info:
 * > {
 * >    "EndpointID" : "",
 * >    "orderCurrency" : "## USD, CNY, or JPY. Fill one of these 3 words. ##",
 * >    "callback_host" : ""
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_zotapay extends Abstract_payment_api {
    const ORDER_STATUS_SUCCESS = 'APPROVED';
    const RETURN_SUCCESS_CODE = 'OK';
    const RETURN_FAILED_CODE = 'failed';
    const RESPONSE_SUCCESS = 200;
    # Implement these for specific pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    /**
     * detail: Constructs an URL so that the caller can redirect / invoke it to make payment through this API, See controllers/redirect.php for detail.
     *
     * @param int $orderId order id
     * @param int $playerId player id
     * @param float $amount amount
     * @param string $orderDateTime
     * @param int $playerPromoId
     * @param string $enabledSecondUrl
     * @param int $bankId
     * @return array
     */
    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $this->CI->load->model(array('player_model'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);

        $firstname = (!empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'none';
        $lastname  = (!empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName']      : 'none';
        $emailAddr = (!empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : 'sample@nothing.com';
        $phone     = (!empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '8615551234567';
        $address   = (!empty($playerDetails[0]['address']))       ? $playerDetails[0]['address']       : 'none';
        $region    = (!empty($playerDetails[0]['region']))        ? $playerDetails[0]['region']        : 'none';
        $city      = (!empty($playerDetails[0]['city']))          ? $playerDetails[0]['city']          : 'none';
        $zipcode   = (!empty($playerDetails[0]['zipcode']))       ? $playerDetails[0]['zipcode']       : '00000';

        $params['merchantOrderID']     = $order->secure_id;
        $params['merchantOrderDesc']   = 'Deposit';
        $params['orderAmount']         = $this->convertAmountToCurrency($amount,$order->created_at);
        $params['orderCurrency']       = $this->getSystemInfo('orderCurrency');
        $params['customerEmail']       = $emailAddr;
        $params['customerFirstName']   = $firstname;
        $params['customerLastName']    = $lastname;
        $params['customerAddress']     = mb_substr($address, 0, 15, "utf-8"); #according to zotapay, can only accept 15 chars of Chinese
        $params['customerCountryCode'] = 'CN';
        $params['customerCity']        = $city;
        $params['customerState']       = '';
        $params['customerZipCode']     = $zipcode;
        $params['customerPhone']       = $phone;
        $params['customerIP']          = $this->getClientIp();
        $params['customerBankCode']    = '';
        $params['redirectUrl']         = $this->getReturnUrl($orderId);
        $params['callbackUrl']         = $this->getNotifyUrl($orderId);
        $params['checkoutUrl']         = $this->CI->utils->getPlayerDepositUrl();
        $params['customParam']         = '';
        $params['signature']           = $this->sign($params);

        $this->CI->utils->debug_log("=====================zotapay generatePaymentUrlForm", $params);
        return $this->processPaymentUrlForm($params);
    }



    # Submit POST form
    protected function processPaymentUrl($params) {
        $this->_custom_curl_header = ["Content-Type: application/json"];
        $url = $this->getSystemInfo('url'). $this->getSystemInfo('EndpointID');
        $response = $this->submitPostForm($url, $params, true, $params['merchantOrderID']);


        $decodedResult = json_decode($response, true);
        $this->CI->utils->debug_log('=====================zotapay processPaymentUrlForm decode response', $decodedResult);

        if($decodedResult['code'] == self::RESPONSE_SUCCESS && array_key_exists("depositUrl", $decodedResult["data"])){
            return array(
               'success' => true,
               'type' => self::REDIRECT_TYPE_URL,
               'url' => $decodedResult['data']['depositUrl'],
            );
        } elseif(!empty($decodedResult['code'])) {
            return array(
               'success' => false,
               'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
               'message' => "[".$decodedResult['code']."] ".$decodedResult['message']
            );
        } else {
            return array(
               'success' => false,
               'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
               'message' => lang('Invalidate API response')
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

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================zotapay callbackFrom $source params", $params);

        if ($source == 'server') {
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================zotapay raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data, true);
                $this->CI->utils->debug_log("=====================zotapay json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderID'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $extra_note = 'auto server callback ' . $this->getPlatformCode();
                $this->CI->sale_order->approveSaleOrder($order->id, $extra_note, false);
            }
        }

        $result['success'] = $success;

        if ($success) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['message'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
            $result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
        }


        if ($source == 'browser') {
            $this->CI->utils->debug_log('=======================zotapay callbackFromBrowser params', $params);

            if ($params['status'] == self::ORDER_STATUS_SUCCESS) {
                $result['success'] = true;
                $result['message'] = self::RETURN_SUCCESS_CODE;
                $result['next_url'] = $this->getPlayerBackUrl();
                $result['go_success_page'] = true;
            } else {
                $result['success'] = false;
                $result['message'] = 'Status:'.$params['status'].', Message:'.$params['errorMessage'].', OrderId: '.$params['merchantOrderID'];
                $result['next_url'] = $this->getReturnUrlFail($orderId);
            }
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'status','merchantOrderID','orderID','signature','amount','customerEmail'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================zotapay missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if ($fields["signature"] != $this->verifySign($fields)) {
            $this->writePaymentErrorLog('=========================zotapay checkCallback signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        $amount = $this->convertAmountToCurrency($order->amount,$order->created_at);
        if ($fields['amount'] != $amount) {
            $this->writePaymentErrorLog("======================zotapay checkCallbackOrder payment amount is wrong, expected [". $amount. "]", $fields['amount']);
            return false;
        }

        if ($fields['status'] != self::ORDER_STATUS_SUCCESS) {
            $payStatus = $fields['status'];
            $this->writePaymentErrorLog("=====================zotapay Payment was not successful, payStatus is [$payStatus]", $fields);
            return false;
        }

        if ($fields['merchantOrderID'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================zotapay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- Private functions --
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## If payment fail, the gateway will send redirect back to this URL
    protected function getReturnUrlFail($orderId) {
        return parent::getCallbackUrl('/callback/browser/fail/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount, $orderDateTime) {
        if($this->getSystemInfo('use_usd_currency')){
            if(is_string($orderDateTime)){
                $orderDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $orderDateTime);
            }
            $amount = $this->gameAmountToDBByCurrency($amount, $this->utils->getTimeForMysql($orderDateTime), 'USD', 'CNY');
        }
        return number_format($amount, 2, '.', '');
    }

    public function sign($params) {
        $endpointID = $this->getSystemInfo('EndpointID');
        $merchantOrderID = $params['merchantOrderID'];
        $orderAmount = $params['orderAmount'];
        $customerEmail = $params['customerEmail'];
        $key = $this->getSystemInfo('key');

        $signStr =  $endpointID.$merchantOrderID.$orderAmount.$customerEmail.$key;
        $sign = openssl_digest($signStr, 'sha256');

        return $sign;
    }

    # -- verifySign --
    public function verifySign($params) {
        $endpoint = $this->getSystemInfo('EndpointID');
        $orderID = $params['orderID'];
        $merchantOrderID = $params['merchantOrderID'];
        $status = $params["status"];
        $amount = $params['amount'];
        $email = $params['customerEmail'];
        $key = $this->getSystemInfo('key');

        $signStr =  $endpoint.$orderID.$merchantOrderID.$status.$amount.$email.$key;
        $callbackSign = openssl_digest($signStr, 'sha256');

        return $callbackSign;
    }
}
