<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * SCRATCHCARD
 * *
 * * SCRATCHCARD_PAYMENT_API, ID: 5322
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://27.72.146.73:8080/api/CardCallBack/CreateCardRequestV2
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_scratchcard extends Abstract_payment_api {

    const CALLBACK_SUCCESS = '00';
    const RETURN_SUCCESS_CODE = '00|Success';

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $player = $this->CI->player->getPlayerById($playerId);

        $params = array();
        $params['requestId'] = $order->secure_id;
        $params['nccCode']   = $this->getSystemInfo('nccCode');
        $params['gameCode']  = $this->getSystemInfo('gameCode');
        $params['account']   = $player['username'];
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['type']      = '1';
        $params['accessKey'] = $this->getSystemInfo('key');

        $params['data'] = base64_encode($this->getPlaninText($params));

        return $this->handlePaymentFormResponse($params);
    }

    protected function handlePaymentFormResponse($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['requestId']);
        $response = json_decode($response, true);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================scratchcard processPaymentUrlFormPost response', $response);

        if(isset($response['IsError'])){
            if($response['IsError']){
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => '['.$response['ErrorCode'].']'.$response['Message'],
                );
            }
            else{
                $data = array();
                $data['Message']   = $response['Message'];
                $this->CI->utils->debug_log("=====================scratchcard handlePaymentFormResponse params", $data);

                $collection_text_transfer = '';
                $collection_text = $this->getSystemInfo("collection_text_transfer", array(''));
                if(is_array($collection_text)){
                    $collection_text_transfer = $collection_text;
                }
                $is_not_display_recharge_instructions = $this->getSystemInfo('is_not_display_recharge_instructions');

                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_STATIC,
                    'data' => $data,
                    'hide_timeout' => true,
                    'collection_text_transfer' => $collection_text_transfer,
                    'is_not_display_recharge_instructions' => $is_not_display_recharge_instructions
                );
            }
        }
        else{
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
            );
        }
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->CI->utils->debug_log('=====================scratchcard getOrderIdFromParameters flds', $flds);

        if(isset($flds['data'])) {
            $plaintext_array = explode("|", base64_decode($flds['data']));
            $this->utils->debug_log('=====================scratchcard getOrderIdFromParameters data decoded', $plaintext_array);
            $order = $this->CI->sale_order->getSaleOrderBySecureId($plaintext_array[0]);
            return $order->id;
        }
        else {
            $this->utils->debug_log('=====================scratchcard getOrderIdFromParameters cannot get data', $flds);
            return;
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

    private function callbackFrom($source, $orderId, $flds, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================scratchcard callbackFrom $source data", $flds['data']);

        if($source == 'server'){
            $plaintext_array = explode("|", base64_decode($flds['data']));

            $params = array();
            $params['requestId'] = $plaintext_array[0];
            $params['gameAcc']   = $plaintext_array[1];
            $params['cardValue'] = $plaintext_array[2];
            $params['errorCode'] = $plaintext_array[3];
            $params['message']   = $plaintext_array[4];
            $params['type']      = $plaintext_array[5];
            $params['accessKey'] = $plaintext_array[6];
            $params['signature'] = $plaintext_array[7];
            $this->CI->utils->debug_log("=====================scratchcard callbackFrom $source data decoded params", $params);


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
                #redirect to success/fail page according to return params
                if($params['errorCode'] == self::CALLBACK_SUCCESS){
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
                else{
                    $this->CI->sale_order->declineSaleOrder($order->id, 'auto server callback declined ' . $this->getPlatformCode() . ': ' . $params['message'], false);
                }
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
            'requestId', 'cardValue', 'errorCode', 'signature'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================scratchcard checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================scratchcard checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        #check cardValue only when success, failed value will be 0
        if($params['errorCode'] == self::CALLBACK_SUCCESS){
            if ($this->convertCardValueToAmount($fields['cardValue']) != $order->amount) {
                $this->writePaymentErrorLog("======================scratchcard checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['requestId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================scratchcard checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    public function getAmountHint(){
        $hint = parent::getAmountHint();

        $deduct_rate = $this->getSystemInfo('deduct_rate', 0);
        $convert_rate = ($this->CI->utils->getConfig('fix_currency_conversion_rate')) ? $this->CI->utils->getConfig('fix_currency_conversion_rate') : 1;
        $hint = lang('cashier.amount_deduct.hint').': $<span id="deducted_amount">0</span>
            <script>
            $("ul.dropdown-menu").on("click", function() {
                cardValue = $("input[name=\'cardValue\']").val();
                rate = '.(1 - $deduct_rate).';
                convert_rate = '.$convert_rate.';
                real_amount = cardValue*rate/convert_rate;
                $("#deducted_amount").text(real_amount.toFixed(2));
                $("input[name=\'deposit_amount\']").val(real_amount.toFixed(2));
            });
            </script>
        ';
        $hint = $this->getSystemInfo('deduct_rate_hint', $hint);

        return $hint;
    }

    # -- signatures --
    protected function getPlaninText($params) {
        $signStr = $this->createSignStr($params);
        $sign = hash_hmac('sha256', $signStr, $this->getSystemInfo('secret'));
        $this->CI->utils->debug_log("===================scratchcard sign signStr [$signStr], signature is [$sign]");
        $plainText = $signStr.'|'.$sign;
        return $plainText;
    }

    private function createSignStr($params) {
        $keys = array('requestId', 'nccCode', 'gameCode', 'account', 'cardNumber', 'serialNumber', 'cardValue', 'provider', 'type', 'accessKey');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $params[$key].'|';
            }
        }
        $signStr = rtrim($signStr, '|');
        return $signStr;
    }

    private function validateSign($params) {
        $keys = array('requestId', 'gameAcc', 'cardValue', 'errorCode', 'message', 'type', 'accessKey');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $params[$key].'|';
            }
        }
        $signStr = rtrim($signStr, '|');
        $sign = hash_hmac('sha256', $signStr, $this->getSystemInfo('secret'));

        if($params['signature'] == $sign){
            return true;
        }
        else{
            $this->writePaymentErrorLog("===================scratchcard sign signStr [$signStr], signature is [$sign]", $params['signature']);
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

    protected function convertCardValueToAmount($cardValue) {
        #as Betwin68 required, they want to deduct 35% amount from player
        $deduct_rate = $this->getSystemInfo('deduct_rate', 0);
        $convert_rate = ($this->CI->utils->getConfig('fix_currency_conversion_rate')) ? $this->CI->utils->getConfig('fix_currency_conversion_rate') : 1;
        $amount = $cardValue * (1 - $deduct_rate) / $convert_rate;

        return number_format($amount, 2, '.', '');
    }
}