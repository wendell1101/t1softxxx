<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * PAY668
 *
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://portal.hkdintlpay.com
 * * Account: ## MerchantID ##
 * * Key: ## Token ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_pay668 extends Abstract_payment_api {


    const RETURN_SUCCESS_CODE = '0';
    const DEPOSIT_CALLBACK = '2';
    const RESPONSE_MESSAGE = "success";
    const WITHDRAWAL_SUCCESS_CODE="1";



    public function __construct($params = null) {
        parent::__construct($params);
		$this->_custom_curl_header = array('Content-Type:application/x-www-form-urlencoded');
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
        $player = $this->CI->player->getPlayerById($playerId);
        $params = array();
        $params['merchno']        = $this->getSystemInfo("account");
        $params['orderId']   = $order->secure_id;
        $params['payType']   = $this->getSystemInfo("payType");

        $params['amount']            = $this->convertAmountToCurrency($amount);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['requestCurrency']          = $this->getSystemInfo("Currency");
        $params['asyncUrl']          = $this->getNotifyUrl($orderId);
        $params['syncUrl']              = $this->getReturnUrl($orderId);
        $params['requestTime']              = date('YmdHis');
        $params['apiVersion']              = $this->getSystemInfo("apiVersion");
        $params['sign']              = $this->sign($params);

        $this->CI->utils->debug_log('=====================pay668 generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['orderId']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('========================================pay668 processPaymentUrlFormPost response json to array', $response);
        $msg = lang('Invalidate API response');

        if( isset($response['code']) && $response['code'] == self::RETURN_SUCCESS_CODE ){
            if(isset($response['payUrl']) && !empty($response['payUrl'])){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['payUrl']
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => $response['msg']
                );
            }
        }else {
            if(isset($response['msg']) && !empty($response['msg'])) {
                $msg = $response['msg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
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

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================pay668 callbackFrom $source params", $params);

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
            $external_id = isset($params['orderId']) ? $params['orderId'] : null;
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderId'], $external_id, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RESPONSE_MESSAGE;
        } else {
            $result['return_error'] = 'Error';
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
            'merchno', 'orderId', 'payType', 'amount', 'requestCurrency','payType','sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================pay668 checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================pay668 checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::DEPOSIT_CALLBACK) {
            $this->writePaymentErrorLog("======================pay668 checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================pay668 checkCallbackOrder amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['orderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================pay668 checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }
        $this->CI->utils->debug_log("=====================pay668 everything checked ok");

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    protected function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        // $this->CI->utils->debug_log("=====================pay668 signstring md5", $sign);

        return $sign;
    }

    protected function createSignStr($params) {
        ksort($params);
        $signStr = "";
        foreach($params as $key=>$value) {
            if(!empty($value)){
                $signStr .=$key."=". $value."&";
            }
        }
        $signStr = $signStr."secretKey=".$this->getSystemInfo('key');
        // $this->CI->utils->debug_log("=====================pay668 key", $this->getSystemInfo('key'));
        // $this->CI->utils->debug_log("=====================pay668 signstring", $signStr);

        return $signStr;
    }

    protected function validateSign($params) {
        ksort($params);
        $signStr = "";
        foreach($params as $key=>$value) {
            if(!empty($value)&&$key!='sign'){
                $signStr .=$key."=". $value."&";
            }
        }
        $signStr = $signStr."secretKey=".$this->getSystemInfo('key');

        $sign = md5($signStr);
        // $this->CI->utils->debug_log("=====================pay668 signStr", $signStr);
        // $this->CI->utils->debug_log("=====================pay668 sign", $sign);

        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
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