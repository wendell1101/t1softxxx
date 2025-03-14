<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * PAYZOD
 *
 * * PAYZOD_QRCODE_PAYMENT_API, ID: 5621
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://dev.payzod.com/api/qr/
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_payzod extends Abstract_payment_api {
    const RETURN_FAIL_CODE  = "888";
    const RETURN_SUCCESS_CODE  = "000";
    const RETURN_SUCCESS_MSG   = "success";
    const RETURN_FAIL_MSG   = "invalid transaction";
    const RESPONSE_SUCCESS_CODE  = "001";
    const PAYTYPE = "QR";

    public function __construct($params = null) {
        parent::__construct($params);
    }
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
        $params['merchant_id'] = $this->getSystemInfo('account');
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['ref_no'] = $order->secure_id;
        $params['ref_date'] = date('YmdHis');
        $params['passkey'] = $this->sign($params);
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['merchant_name'] = $this->getSystemInfo('merchant_name');
        $this->CI->utils->debug_log('=====================payzod generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {

        $url = $this->getSystemInfo('url');
        $this->CI->utils->debug_log("=====================payzod processPaymentUrlFormPost URL", $url);
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $url,
            'params' => $params,
            'post' => true,
        );
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->CI->utils->debug_log('=====================payzod getOrderIdFromParameters flds', $flds);

        if(isset($flds['ref_no'])) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($flds['ref_no']);
            return $order->id;
        }
        else {
            $this->utils->debug_log('=====================payzod getOrderIdFromParameters cannot get ref_no', $flds);
            return;
        }
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    ## This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================payzod callbackFrom $source params", $params);

        if($source == 'server' ){
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['merchant_id'], '', null, null, $response_result_id);
            if ($source == 'browser') {
                $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback '.$this->getPlatformCode().', result: '. $params['response_msg'], false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $resultContent = ['responseCode' => self::RETURN_SUCCESS_CODE,'responseMesg' => self::RETURN_SUCCESS_MSG];
            $result['message'] = json_encode($resultContent);
        } else {
            $resultContent = ['responseCode' => self::RETURN_FAIL_CODE,'responseMesg' => self::RETURN_FAIL_MSG];
            $result['message'] = json_encode($resultContent);
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {

        $requiredFields = array('ref_no','ref_date','passkey','amount', 'response_code');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================payzod Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================payzod Signature Error', $fields);
            return false;
        }

        if ($fields['response_code'] != self::RESPONSE_SUCCESS_CODE) {
            $this->writePaymentErrorLog('=====================payzod Payment was not successful', $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount);

        if ($fields['amount'] != $check_amount) {
            $this->writePaymentErrorLog("======================payzod Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        }

        if ($fields['ref_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================payzod checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }


        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    # Reference: PHP Demo
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    private function createSignStr($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            if( $key == 'passkey' || $key == 'merchant_id' || $key == 'paytype') {
                continue;
            }
            $signStr .= $value;
        }
        $signStr .= $this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            if( $key == 'ref_no' ||  $key == 'ref_date') {
                $signStr .= $value;
            }
        }
        $signStr .= $this->getSystemInfo('key');
        $sign = md5($signStr);
        if($params['passkey'] == $sign)
            return true;
        else
            return false;
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
        return number_format($amount, 2, '.', '');
    }
}

