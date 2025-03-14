<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * SGpay
 *
 * * SGPAY_PAYMENT_API, ID: 5653
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://47.75.187.107/api/pay/getQrPms
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_sgpay extends Abstract_payment_api {
    const TYPE_ONLINE_BANK = 'bank4';

    const RESULT_CODE_SUCCESS = "1";
    const CALLBACK_STATUS_SUCCESS = "10000";

    const RETURN_SUCCESS_CODE = '1';
    const RETURN_FAIL_CODE = '';


    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
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
        $params['order_id'] = $order->secure_id;
        $params['money'] = $this->convertAmountToCurrency($amount); //分
        $this->configParams($params, $order->direct_pay_extra_info); //$params['type']
        $params['appid'] = $this->getSystemInfo('account');
        $params['c_url'] = urlencode($this->getNotifyUrl($orderId));
        $params['time'] = time();
        $params['token'] = $this->sign($params);

        $this->CI->utils->debug_log('=====================sgpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['order_id']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================sgpay processPaymentUrlFormRedirect response', $response);

        if($response['code'] == self::RESULT_CODE_SUCCESS) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['order_id']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['data']['order_id']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data']['url'],
            );
        }
        else if(isset($response['msg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => '['.$response['code'].']: '.$response['msg']
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

        $this->CI->utils->debug_log("=====================sgpay callbackFrom $source params", $params);

        if($source == 'server' ){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================sgpay callbackFrom $source raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================sgpay callbackFrom $source json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['pay_order_id'], null, null, null, $response_result_id);
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
            $result['return_error'] = self::RETURN_FAIL_CODE;
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
            'token', 'money', 'old_money', 'pay_order_id'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================sgpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================sgpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['money'] != $this->convertAmountToCurrency($order->amount)) {

            if ($this->getSystemInfo('allow_callback_amount_diff')) {
                $diffAmount = abs($this->convertAmountToCurrency($order->amount) - floatval($fields['money']));
                if ($diffAmount > 100) {
                    $this->writePaymentErrorLog("=====================sgpay checkCallbackOrder Payment amounts ordAmt - payAmount > 1, expected [$order->amount]", $fields, $diffAmount);
                    return false;
                }
            }else {
                return false;
            }

            if ($fields['old_money'] != $this->convertAmountToCurrency($order->amount)) {
                $this->writePaymentErrorLog("======================sgpay checkCallbackOrder payment amount is wrong, expected [". $order->amount. "]", $fields['old_money']);
                return false;
            }


        }

        if ($fields['pay_order_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================sgpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $sign = md5($signStr);
        return $sign;
    }

    private function createSignStr($params) {
        $params = array(
            'order_id' => $params['order_id'],
            'money' => $params['money'],
            'type' => $params['type'],
            'time' => $params['time'],
            'appid' => $params['appid']
        );
        $signStr = '';
        foreach($params as $value) {
            $signStr .= "$value";
        }
        return $signStr.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
		$signStr = '';
        $key = array(
            'order_id' => $params['order_id'],
            'money' => $params['money'],
            'old_money' => $params['old_money'],
            'pay_order_id' => $params['pay_order_id'],
            'time' => $params['time'],
            'appid' => $this->getSystemInfo('account')
        );
		foreach($key as $value) {
			$signStr .= "$value";
		}
		$sign = md5($signStr.$this->getSystemInfo('key'));
		if($params['token'] == $sign){
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
        return number_format($amount * 100, 2, '.', '');
    }
}