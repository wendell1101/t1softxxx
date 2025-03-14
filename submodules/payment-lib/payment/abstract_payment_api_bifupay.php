<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * BIFUPAY
 *
 * * BIFUPAY_PAYMENT_API, ID: 5859
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.xingshengtrade.com/PaymentGetway/OrderRquest
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_bifupay extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'OK';
    const RESULT_CODE_SUCCESS = 'SUCCESS';
    const PAY_TYPE_BANK = 'BANK_PAY';
    const RESPONSE_SUCCESS = "true";

	public function __construct($params = null) {
		parent::__construct($params);
	}

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();

        $params['merchantId'] = $this->getSystemInfo('account');
        $params['merchantOrderId'] = $order->secure_id;
        $params['orderAmount'] = $this->convertAmountToCurrency($amount);

        $this->configParams($params, $order->direct_pay_extra_info);

        $params['notifyUrl'] = $this->getNotifyUrl($orderId);
        $params['returnUrl'] = $this->getReturnUrl($orderId);
        $params['ip'] = $this->getClientIp();

        $params['remark'] = 'deposit';
        $params['jsonResult'] = '1';
        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log('=====================bifupay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['merchantOrderId']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================bifupay processPaymentUrlFormRedirect response', $response);

        if(isset($response['Success']) && $response['Success'] == self::RESPONSE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['Qrcode'],
            );
        }
        else if(isset($response['ErrorMessage'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['ErrorCode'].': '.$response['ErrorMessage']
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

    public function handlePaymentFormResponse($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['merchantOrderId']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================bifupay handlePaymentFormResponse response', $response);
        if(isset($response['Success'])){
            if($response['Success']){
                $data = array();
                $data['Name'] = $response['BankName'];
                $data['Bank'] = $response['BankType'];
                $data['Account'] = $response['BankAccount'];
                $data['Amount'] = $response['PayAmount'];
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
        }else {
            if(!empty($response['ErrorMessage'])) {
                $msg = $response['ErrorMessage'];
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

    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================bifupay callbackFrom $source params", $params);

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['merchantOrderId'], null, null, null, $response_result_id);
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
            'merchantId','systemOrderId','merchantOrderId','orderAmount','remark','channelType','ip','sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================bifupay Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================bifupay Signature Error', $fields);
            return false;
        }

        if ($this->convertAmountToCurrency($order->amount) != $fields['orderAmount']) {
            $this->writePaymentErrorLog("=====================bifupay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['merchantOrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================bifupay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }

	private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    public function createSignStr($params) {
        $signStr = '';
        $signStr = 'merchantId='.$params['merchantId'].'&merchantOrderId='.$params['merchantOrderId'].'&orderAmount='.$params['orderAmount'].'&notifyUrl='.$params['notifyUrl'].'&channelType='.$params['channelType'].'&remark='.$params['remark'].'&ip='.$params['ip'].$this->getSystemInfo('key');
        return $signStr;
    }

    public function callbackCreateSignStr($params) {
        $signStr = '';
        $signStr = 'merchantId='.$params['merchantId'].'&merchantOrderId='.$params['merchantOrderId'].'&orderAmount='.$params['orderAmount'].'&systemOrderId='.$params['systemOrderId'].'&channelType='.$params['channelType'].'&remark='.$params['remark'].'&ip='.$params['ip'].$this->getSystemInfo('key');
        return $signStr;
    }

    public function validateSign($params) {
        $signStr = $this->callbackCreateSignStr($params);
        $sign = md5($signStr);
        if($params['sign'] == $sign)
            return true;
        else
            return false;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- Private functions --
    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function convertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        $convert_decimals = $this->getSystemInfo('convert_decimals', 2);
        return number_format($amount * $convert_multiplier, $convert_decimals, '.', '') ;
    }

}
