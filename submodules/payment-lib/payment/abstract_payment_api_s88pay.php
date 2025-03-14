<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * s88pay
 * *
 * * S88PAY_PAYMENT_API, ID: 6158
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * json: https://a.s88pay.im/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class abstract_payment_api_s88pay extends Abstract_payment_api {
    const BUSICODE_BANK      = 'PTHB07';
    const RESULT_CODE_SUCCESS = 2;
    const RETURN_SUCCESS_CODE = 'success';

    public function __construct($params = null) {
        parent::__construct($params);
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
        $params = array();
        $params['merchant_code']          = $this->getSystemInfo('account');
        $params['merchant_api_key']       = $this->getSystemInfo('api_key');
        $params['transaction_code']       = $order->secure_id;
        $params['transaction_timestamp']  = time();
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['transaction_amount']     = $this->convertAmountToCurrency($amount);
        $params['user_id']                = $playerId;
        $params['currency_code']          = $this->getSystemInfo('currency');
        $post_params['key']               = $this->sign($params);
        $this->CI->utils->debug_log('=====================s88pay generatePaymentUrlForm params', $params);
        return $this->processPaymentUrlForm($post_params);
    }

    protected function processPaymentUrlFormPost($params) {
        $url = $this->getSystemInfo('url').'/'.$this->getSystemInfo('account').'/v2/dopayment';
        $this->CI->utils->debug_log("=====================s88pay post url", $url);
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $url,
            'params' => $params,
            'post' => false, # sent using GET
        );
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    public function getOrderIdFromParameters($flds)
    {
        if (empty($flds)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================s88pay raw_post_data", $raw_post_data);
            $flds = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================s88pay json_decode flds", $flds);
        }

        $decrypt_data = $this->encrypt_decrypt('decrypt', $flds['key']);
        if (isset($decrypt_data['transaction_code'])) {
            $this->CI->load->model(array('sale_order'));
            $order = $this->CI->sale_order->getSaleOrderBySecureId($decrypt_data['transaction_code']);
            return $order->id;
        } else {
            $this->utils->debug_log('=====================s88pay callbackOrder cannot get any order_id when getOrderIdFromParameters', $flds);
            return;
        }
    }

    public function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================s88pay callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================s88pay raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data, true);
                $this->CI->utils->debug_log("=====================s88pay json_decode params", $params);
            }

            if(isset($params['key'])){
                $params = $this->encrypt_decrypt('decrypt', $params['key']);
            }else{
                return false;
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['transaction_code'], '', null, null, $response_result_id);
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
            'transaction_status', 'transaction_code', 'transaction_amount'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================s88pay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass


        if ($fields['transaction_amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================s88pay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['transaction_code'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================s88pay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        if ($fields['transaction_status'] != self::RESULT_CODE_SUCCESS) {
            $this->writePaymentErrorLog('=====================cpay checkCallbackOrder payment was not successful', $fields);
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
        $sign = $this->encrypt_decrypt('encrypt', $signStr);

        return $sign;
    }

    public function createSignStr($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign' ) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr = rtrim($signStr, '&');
        return $signStr;
    }

    public function encrypt_decrypt($action, $string) {
        $output = false;
        $encrypt_method = "AES-256-CBC";
        $secret_key = $this->getSystemInfo('api_key');
        $secret_iv = $this->getSystemInfo('api_secret');
        // hash
        $key = substr(hash('sha256', $secret_key, true), 0, 32);

        $iv = substr(hash('sha256', $secret_iv), 0, 16);
        if ( $action == 'encrypt' ) {
            $output = openssl_encrypt($string, $encrypt_method, $key, OPENSSL_RAW_DATA, $iv);
            $output = base64_encode($output);
        } else if( $action == 'decrypt' ) {
           $decrypt_str = openssl_decrypt(base64_decode(urldecode($string)), $encrypt_method, $key, OPENSSL_RAW_DATA, $iv);
           foreach (explode('&', $decrypt_str) as $chunk) {
                $param = explode("=", $chunk);
                $output[$param[0]] = $param[1];
            }
        }
        return $output;
    }

    # -- Private functions --
    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    public function getBankListInfoFallback() {
        $currency  = $this->getSystemInfo('currency');
        switch ($currency) {
            case 'THB':
                return array(
                    array('label' => lang('_json: {"1": "Bank of Ayudhya (Krungsri)" , "2": "Bank of Ayudhya (Krungsri)", "6": "ธนาคารกรุงศรีอยุธยา"}'), 'value' => '106'),
                    array('label' => lang('_json: {"1": "Bangkok Bank" , "2": "Bangkok Bank", "6": "ธนาคารกรุงเทพ"}'), 'value' => '101'),
                    array('label' => lang('_json: {"1": "CIMB Thai" , "2": "CIMB Thai", "6": "ธนาคาร ซีไอเอ็มบี ไทย"}'), 'value' => '112'),
                    array('label' => lang('_json: {"1": "Government Savings Bank" , "2": "Government Savings Bank", "6": "ธนาคารออมสิน"}'), 'value' => '114'),
                    array('label' => lang('_json: {"1": "Karsikorn Bank (K-Bank)" , "2": "Karsikorn Bank (K-Bank)", "6": "ธนาคารกสิกร"}'), 'value' => '102'),
                    array('label' => lang('_json: {"1": "Kiatnakin Bank" , "2": "Kiatnakin Bank", "6": "ธนาคารเกียรตินาคิน"}'), 'value' => '111'),
                    array('label' => lang('_json: {"1": "Krung Thai Bank" , "2": "Krung Thai Bank", "6": "ธนาคารกรุงไทย"}'), 'value' => '104'),
                    array('label' => lang('_json: {"1": "Siam Commercial Bank" , "2": "Siam Commercial Bank", "6": "ธนาคารไทยพาณิชย์"}'), 'value' => '103'),
                    array('label' => lang('_json: {"1": "TMB Bank Public Company Limited" , "2": "TMB Bank Public Company Limited", "6": "ธนาคารทหารไทย"}'), 'value' => '105'),
                );
                break;
            default:
                return array();
                break;
        }
    }
}