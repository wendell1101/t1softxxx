<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * AMBPAYMENT
 *
 * * AMBPAYMENT_PAYMENT_API, ID:5802
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: https://document.ambpayment.com/#api-AMB_PAYMENT_APIs-Callback_Url
 * * Key: ## Access Token ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_ambpayment extends Abstract_payment_api {
    const PAY_METHODS_ONLINE_BANK = "100";
    const CALLBACK_SUCCESS    = 'pay';
    const RETURN_SUCCESS_CODE = 0;

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
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'no firstName';
        $lastname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName']      : 'no lastName';

        $params = array();

        $params['amount']    = $this->convertAmountToCurrency($amount);
        $params['username']  = $this->getSystemInfo('account');
        $params['payer_account_first_name'] = $firstname;
        $params['payer_account_last_name'] = $lastname;
        $params['payer_account_first_name_en'] = $firstname;
        $params['payer_account_last_name_en'] = $lastname;
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['signature'] = $this->sign($params);
        $params['orderid']   = $order->secure_id;

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
        $url = $this->getSystemInfo('url');
        $orderid = $params['orderid'];
        unset($params['orderid']);
        $response = $this->submitPostForm($url, $params, true, $orderid);
        $decode_data = json_decode($response,true);

        $this->CI->utils->debug_log('=====================ambpayment processPaymentUrlFormQRcode response json to array', $decode_data);

        $msg = lang('Invalidte API response');
        if(isset($decode_data['code']) && ($decode_data['code'] == self::RETURN_SUCCESS_CODE)) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($orderid);
            $this->CI->sale_order->updateExternalInfo($order->id, $decode_data['result']['order']);
            return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_QRCODE,
                    'base64' => $decode_data['result']['value'],
                );
        }else {
            if(isset($decode_data['code'])) {
                $msg = $decode_data['code'].':'.$decode_data['message'];
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

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->CI->utils->debug_log('=====================ambpayment getOrderIdFromParameters flds', $flds);

        if(empty($flds)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $flds = json_decode($raw_post_data ,true);
            $this->utils->debug_log('======ambpayment getOrderIdFromParameters raw_post flds ' , $flds);
        }

        if(isset($flds['order'])) {
            $order = $this->CI->sale_order->getSaleOrderByExternalOrderId($flds['order']);
            return $order->id;
        }
        else {
            $this->utils->debug_log('=====================ambpayment getOrderIdFromParameters cannot get ref_no', $flds);
            return;
        }
    }

    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================ambpayment callbackFrom $source params", $params);

        if($source == 'server' ){
            if(empty($params)){
                $raw_post_data = file_get_contents('php://input', 'r');
                $params = json_decode($raw_post_data, true);
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
            $this->CI->sale_order->updateExternalInfo($order->id, null, null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                if($params['method'] == self::CALLBACK_SUCCESS){
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = $this->returnSuccessMessage();
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
            'amount', 'order', 'method'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================ambpayment checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================ambpayment checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================ambpayment checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['order'] != $order->external_order_id) {
            $this->writePaymentErrorLog("======================ambpayment checkCallbackOrder order IDs do not match, expected [$order->external_order_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    private function sign($params) {
        $signStr = 'username='.$params['username'].'&service_id='.$params['service_id'].'&amount='.$params['amount'];
        $sign = hash_hmac('sha256', $signStr, $this->getSystemInfo('key'));

        return $sign;
    }

    # -- signatures --
    private function validateSign($params) {
        $keys = array('method', 'id', 'service_id', 'amount', 'actualAmount', 'order', 'timestamp');
        $signStr = "";
        $signStr = 'method='.$params['method'].'&id='.$params['id'].'&service_id='.$params['service_id'].'&amount='.$params['amount'].'&actualAmount='.$params['actualAmount'].'&order='.$params['order'].'&timestamp='.$params['timestamp'];
        $sign = hash_hmac('sha256', $signStr, $this->getSystemInfo('key'));

        if($params['hash'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    protected function returnSuccessMessage() {
        $success_msg['code'] = self::RETURN_SUCCESS_CODE;
        $success_msg['message'] = "success";
        $success_msg['timestamp'] = time();
        $signStr = 'code='.self::RETURN_SUCCESS_CODE.'&message='.$success_msg['message'].'&timestamp='.$success_msg['timestamp'];
        $success_msg['hash'] = hash_hmac('sha256', $signStr, $this->getSystemInfo('key'));
        return json_encode($success_msg);
    }

    protected function getBankListInfoFallback() {
        return array(
            array('label' => 'Siam Commercial Bank', 'value' => 'SCB'),
            array('label' => 'KASIKORN BANK', 'value' => 'KBANK'),
            array('label' => 'BANK OF AYUDHYA', 'value' => 'BAY'),
            array('label' => 'BANGKOK BANK', 'value' => 'BBL'),
            array('label' => 'KIATNAKIN BANK', 'value' => 'KKP'),
            array('label' => 'GOVERNMENT HOUSING BANK', 'value' => 'GHB'),
            array('label' => 'CITIBANK THAILAND', 'value' => 'CITI'),
            array('label' => 'BANK FOR AGRICULTURE AND AGRICULTURAL COOPERATIVES', 'value' => 'BAAC'),
            array('label' => 'THANACHART BANK', 'value' => 'TBANK'),
            array('label' => 'GOVERNMENT SAVINGS BANK', 'value' => 'GSB'),
            array('label' => 'KRUNG THAI BANK', 'value' => 'KTB'),
            array('label' => 'MIZUHO CORPORATE BANK', 'value' => 'MIZUHO'),
            array('label' => 'ICBC(THAI) BANK', 'value' => 'ICBC'),
            array('label' => 'THAI CREDIT RETAIL BANK', 'value' => 'TCD'),
            array('label' => 'Islamic Bank of Thailand', 'value' => 'IBANK'),
            array('label' => 'TISCO BANK', 'value' => 'TISCO'),
            array('label' => 'STANDARD CHARTERED', 'value' => 'SCBT'),
            array('label' => 'LAND AND HOUSES BANK', 'value' => 'LHBANK'),
            array('label' => 'THE HONGKONG AND SHANGHAI BANKING', 'value' => 'HSBC'),
            array('label' => 'TMB BANK', 'value' => 'TMB'),
            array('label' => 'CIMB THAI BANK', 'value' => 'CIMB'),
            array('label' => 'UOB BANK', 'value' => 'UOB'),

        );
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