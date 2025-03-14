<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * HUPAY 互匯
 * *
 * * HUPAY_PAYMENT_API, ID: 5389
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://gateway.726pay.com/api/v1/order
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_hupay extends Abstract_payment_api {

    const PAYTYPE_ONLINEBANK = "WY";

    const CALLBACK_SUCCESS = 'success';
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
        date_default_timezone_set('PRC');

        $params = array();
        $params['merchant_code'] = $this->getSystemInfo('account');
        $params['order_no'] = $order->secure_id;
        $params['order_amount'] = $this->convertAmountToCurrency($amount); //元
        $this->configParams($params, $order->direct_pay_extra_info); //$params['pay_type']  $params['bank_code']
        $params['order_time'] = time();
        $params['customer_ip'] = $this->getClientIP();
        $params['notify_url'] = $this->getNotifyUrl($orderId);
        $params['return_url'] = $this->getReturnUrl($orderId);
        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log('=====================hupay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    # Implement processPaymentUrlForm
    protected function processPaymentUrlFormPost($params) {
    	$url = $this->getSystemInfo('url');
        $this->CI->utils->debug_log('=====================hupay processPaymentUrlFormPost url',$url);

		$response = $this->submitPostForm($url, $params, false, $params['order_no']);
		$this->CI->utils->debug_log('========================================hupay processPaymentUrlFormPost received response', $response);

		$decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================hupay processPaymentUrlFormPost response[1] json to array', $decode_data);
		$msg = lang('Invalidate API response');


		if($decode_data['is_success'] == TRUE) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decode_data['url'],
            );
        }else {
            if($decode_data['is_success'] != TRUE && isset($decode_data['msg'])){
                $msg = $decode_data['is_success'].": ".$decode_data['msg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }


    protected function processPaymentUrlFormQRCode($params) {
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
        $this->CI->utils->debug_log("=====================hupay callbackFrom params", $params);

        if($source == 'server'){
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['trade_no'], null, null, null, $response_result_id);
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
            'merchant_code', 'notify_type', 'order_no', 'order_amount', 'order_time', 'trade_no', 'trade_time', 'trade_status','sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================hupay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================hupay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['notify_type'] != 'back_notify') {
            $this->writePaymentErrorLog("======================hupay checkCallbackOrder Payment notify_type is not back_notify", $fields);
            return false;
        }


        if ($fields['trade_status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================hupay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['order_amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================hupay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================hupay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- bankInfo --
	protected function getBankListInfoFallback() {
		return array(
			array('label' => '工商银行', 'value' => 'ICBC'),
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '建设银行', 'value' => 'CCB'),
			array('label' => '农业银行', 'value' => 'ABC'),
			array('label' => '交通银行', 'value' => 'BOCM'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '广东发展银行', 'value' => 'GDB'),
			array('label' => '中信银行', 'value' => 'CITIC'),
			array('label' => '民生银行', 'value' => 'CMBC'),
			array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '华夏银行', 'value' => 'HXBANK'),
			array('label' => '平安银行', 'value' => 'SPABANK'),
			array('label' => '光大银行', 'value' => 'CEB'),
			array('label' => '北京银行', 'value' => 'BJBANK'),
		);
    }


    # -- signatures --
    private function sign($params) {
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
            else{
                $signStr .= "$key=$value&";
            }
        }
        return $signStr."key=".$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        $sign = $this->sign($params);
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    # -- Private functions --
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }
}