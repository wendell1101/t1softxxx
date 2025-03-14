<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * SHADD 刷得多V2
 *
 * * SHADDPAY_PAYMENT_API (6024)
 * * Abstract_payment_api_shaddpay
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://m18pay.com/api/transaction
 * * Account: ## Merchant Name ##
 * * Key: ## Merchant Access Token ##
 *
 * @category Payment
 * @copyright 2022 tot
 */
abstract class Abstract_payment_api_shaddpay extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'ok';
    const CALLBACK_STATUS_SUCCESS   = 'success';

	public function __construct($params = null) {
		parent::__construct($params);
	}

    protected abstract function configParams(&$params, $direct_pay_extra_info);
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $playerdetails = $this->CI->player_model->getAllPlayerDetailsById($playerId);
        $player_name = $playerdetails['firstName'];

        $params['out_trade_no'] = $order->secure_id;
        $params['paid_name']  = $player_name;
        $params['amount']       = $this->convertAmountToCurrency($amount);
        $params['notify_url']   = $this->getNotifyUrl($orderId);

        $this->CI->utils->debug_log('=====================shaddpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $access_token = $this->getSystemInfo('key');
        $this->_custom_curl_header = [
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: Bearer {$access_token}"
        ];

        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['out_trade_no']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log("=====================shaddpay processPaymentUrlFormPost response", $response);

        if(!empty($response['uri'])){
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['uri'],
            );
        }
        elseif (!empty($response['message'])){
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $response['message']
            );
        }
        else{
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidte API response')
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

    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $this->CI->utils->debug_log("=====================shaddpay callbackFrom $source params", $params);

        if($source == 'server'){
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        // Update order payment status and balance
        $success = true;

        // Update player balance based on order status
        // if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log(__METHOD__, "callbackFrom {$source} already received callback for order {$order->id}", $params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            // update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $params['transaction_id'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto browser callback ' . $this->getPlatformCode(), false);
            }
            elseif ($source == 'server') {
                if ($params['status'] == self::CALLBACK_STATUS_SUCCESS) {
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
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
        $required_fields = [
            'trade_no', 'amount', 'out_trade_no', 'status'
        ];

        foreach ($required_fields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================shaddpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================shaddpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass


        if ($fields['status'] != self::CALLBACK_STATUS_SUCCESS) {
            $this->writePaymentErrorLog("======================shaddpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================shaddpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['out_trade_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================shaddpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;

    } // End function checkCallbackOrder()

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    private function validateSign($params){
        $validSign = false;
	    if(!empty($params['_signature'])){
            $validSign = $params['_signature'];
            unset($params['_signature']);
        }

        $body = json_encode($params);
        $sign = base64_encode(hash_hmac('sha256', $body, $this->getSystemInfo('key'), true));

        if(hash_equals($sign, $validSign)){
            return true;
        }else{
            return false;
        }
    }

    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

}
