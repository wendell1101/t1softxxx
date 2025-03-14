<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * gcpay
 * *
 * * GCPAY_PAYMENT_API, ID: 6194
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://drt1iji2j13.gopay001.com/createpay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_gcpay extends Abstract_payment_api {
    const RESULT_CODE_SUCCESS = '0';
    const CALLBACK_SUCCESS = '2';
    const RETURN_SUCCESS_CODE = 'SUCCESS';
    const RETURN_FAIL_CODE = 'FAIL';
    const ONLINEBANK_CHANNEL = 'BankSmall';

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
        $playerDetails = $this->getPlayerDetails($playerId);
        $username  = (isset($playerDetails[0]) && !empty($playerDetails[0]['username']))      ? $playerDetails[0]['username']      : 'no username';
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'no firstName';
        $lastname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName']      : 'no lastName';

        $params = array();
        $params['mch_id']        = $this->getSystemInfo('account');
        $params['out_order_no']  = $order->secure_id;
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['amount']   	 = $this->convertAmountToCurrency($amount);
        $params['notify_url']    = $this->getNotifyUrl($orderId);
        $params['return_url']    = $this->getReturnUrl($orderId);
        $params['client_ip']     = $this->getClientIp();
        $params['payer_name']    = $firstname.$lastname;
        $params['timestamp']     = time();
        $params['version']     	 = 'v1.0';
        $params['sign']          = $this->sign($params);

        $this->CI->utils->debug_log('=====================gcpay generatePaymentUrlForm params', $params);
        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormQRCode($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['out_order_no']);
        $response = json_decode($response, true);

        $this->CI->utils->debug_log('=====================gcpay processPaymentUrlFormQRCode response', $response);
        if(isset($response['code']) && $response['code'] == self::RESULT_CODE_SUCCESS) {
    		return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data']['pay_url'],
        	);
        }
        else if(isset($response['code']) && !empty($response['code']) && isset($response['message']) && !empty($response['message'])){
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['code'].': '.$response['message']
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

    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================gcpay callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input');
                $this->CI->utils->debug_log("=====================gcpay raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data, true);
                $this->CI->utils->debug_log("=====================gcpay json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['out_order_no'], null, null, null, $response_result_id);
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

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'mch_id', 'out_order_no', 'pay_status', 'amount', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================gcpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================gcpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['pay_status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================gcpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================gcpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['out_order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================gcpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    public function createSignStr($params) {
        ksort($params);
        $param = [];
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null || $key == 'sign' ) {
                continue;
            }
            array_push($param, $key . '=' . $value);
        }
        $queryString = implode('&', $param);
        $signStr = $queryString.$this->getSystemInfo('key');
        return $signStr;
    }

    public function validateSign($params) {
        $sign = md5($this->createSignStr($params));
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    # -- Private functions --
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    public function getPlayerDetails($playerId) {
        $this->CI->load->model(array('player_model'));
        $player = $this->CI->player_model->getPlayerDetails($playerId);
        return $player;
    }
}