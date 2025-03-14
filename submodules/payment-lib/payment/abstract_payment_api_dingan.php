<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * DINGAN 定安科技
 *
 * * DINGAN_QUICKPAY_PAYMENT_API, ID: 5443
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://gg77.tv/api/json
 * * Account: ## MerchantID ##
 * * Key: ## Token ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_dingan extends Abstract_payment_api {

    const PAYWAY_QUICKPAY = 7; //要 int 類型

    const REQUEST_SUCCESS = '0';
    const CALLBACK_SUCCESS = '2';
    const RETURN_SUCCESS_CODE = 'success';


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
        $player = $this->CI->player->getPlayerById($playerId);


        $params = array();
        $params['uid']        = $this->getSystemInfo('account');
        $params['price']      = (float)$this->convertAmountToCurrency($amount); //元
        $this->configParams($params, $order->direct_pay_extra_info); //$params['pay_way']
        $params['notify_url'] = $this->getNotifyUrl($orderId);
        $params['return_url'] = $this->getReturnUrl($orderId);
        $params['order_id']   = $order->secure_id;
        $params['order_uid']  = $player['username'];
        $params['order_name'] = $order->secure_id;
        $params['goods_name'] = 'Deposit';
        $params['key']        = $this->sign($params);
        $this->CI->utils->debug_log('=====================dingan generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['order_id']);
        $this->CI->utils->debug_log('=====================dingan processPaymentUrlFormURL received response', $response);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('=====================dingan processPaymentUrlFormURL json to array', $response);

		if($response['code'] >= self::REQUEST_SUCCESS) {
            if(!empty($response['data']['pay_url'])){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_HTML,
                    'html' => $response['data']['pay_url']
                );
            }else{
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['url']
                );
            }
        }else if($response['code'] < self::REQUEST_SUCCESS && isset($response['code'])) {
            $msg = 'dingan failed code: ['.$response['code'].'] ,Msg: '.$response['msg'];
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $msg
            );
        }
        else {
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

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================dingan callbackFrom params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['order_id'], $external_id, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($success) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
			$result['message'] = $processed ? self::RETURN_SUCCESS_CODE : 'failed';
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
            'order_id', 'price', 'key', 'status'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================dingan checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================dingan checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
			$payStatus = $fields['status'];
			$this->writePaymentErrorLog("=====================dingan checkCallbackOrder Payment was not successful, payStatus is [$payStatus]", $fields);
            return false;
        }

        if ($fields['price'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================dingan checkCallbackOrder amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['order_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================dingan checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    protected function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtolower(md5($signStr));
        return $sign;
    }

    private function createSignStr($params) {
        $params['token'] = $this->getSystemInfo('key');
        $keys = array(
            'bank_card_no' => $params['bank_card_no'],
            'goods_name' => $params['goods_name'],
            'notify_url' => $params['notify_url'],
            'order_id' => $params['order_id'],
            'order_uid' => $params['order_uid'],
            'pay_way' => $params['pay_way'],
            'price' => $params['price'],
            'return_url' => $params['return_url'],
            'token' => $params['token'],
            'uid' => $params['uid']
        );
        $signStr = "";
        foreach($keys as $key=>$value) {
            $signStr.=$key."=".$value."&";
        }
        $signStr = rtrim($signStr, '&');
        return $signStr;
    }

    private function validateSign($params) {
        $params['token'] = $this->getSystemInfo('key');
        $keys = array(
            'api_order_id' => $params['api_order_id'],
            'order_id' => $params['order_id'],
            'order_uid' => $params['order_uid'],
            'price' => $params['price'],
            'status' => $params['status'],
            'token' => $params['token']
        );
        $signStr = "";
        foreach($keys as $key=>$value) {
            $signStr.=$key."=".$value."&";
        }
        $signStr = rtrim($signStr, '&');
        $sign = strtolower(md5($signStr));
        if($params['key'] == $sign){
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