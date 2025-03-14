<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * 铭科支付
 *
 * * MKPAY_ALIPAY_PAYMENT_API, ID: 5646
 * * MKPAY_ALIPAY_H5_PAYMENT_API, ID: 5647
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.sckjzfbjk.xyz/api/createOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_mkpay extends Abstract_payment_api {

    const RESULT_CODE_SUCCESS = 0;
    const CALLBACK_STATUS_SUCCESS = 0;

    const RETURN_FAILED_CODE = 'failed';
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

        $params = array();
        $params['orderNo'] = $order->secure_id;
        $params['appId'] = $this->getSystemInfo('account');
		$params['money'] = $this->convertAmountToCurrency($amount);
        $params['returnUrl'] = $this->getReturnUrl($orderId);
        $params['notifyUrl'] = $this->getNotifyUrl($orderId);
        $params['productName'] = "pay";
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log('=====================mkpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['orderNo']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================mkpay processPaymentUrlFormRedirect response', $response);

        if($response['code'] == self::RESULT_CODE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['obj'],
            );
        }
        else {
            $msg = lang('Invalidte API response');
            if(isset($response['msg'])) {
                $msg =  $response['code'].': '.$response['msg'];
            }

            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

    protected function processPaymentUrlFormQrcode($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['orderNo']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================mkpay processPaymentUrlFormRedirect response', $response);

        if($response['code'] == self::RESULT_CODE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $response['obj'],
            );
        }
        else {
            $msg = lang('Invalidte API response');
            if(isset($response['msg'])) {
                $msg =  $response['code'].': '.$response['msg'];
            }

            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
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

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $this->CI->utils->debug_log("=====================mkpay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderNo'], null, null, null, $response_result_id);
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
            'orderNo', 'money', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================mkpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================mkpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

		if ( floatval($order->amount) != floatval($fields['money'])) {
			$this->writePaymentErrorLog("=====================mkpay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['orderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================mkpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        ksort($params);
        $signStr = '';
		foreach($params as $key => $value) {
			if(empty($value) || $key == 'sign') {
				continue;
			}
			$signStr .= "$key=$value&";
        }
        $signStr =$signStr .'key='.$this->getSystemInfo('key');
		return $signStr;
    }

    private function validateSign($params) {
        $data = $params;
        $signStr = $this->createSignStr($data);
        $sign = md5($signStr);
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