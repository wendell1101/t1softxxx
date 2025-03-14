<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * ALOGATEWAY
 *
 * * ALOGATEWAY_PAYMENT_API, ID: 994
 * * ALOGATEWAY_ALIPAY_PAYMENT_API, ID: 995
 * * ALOGATEWAY_UNIONPAY_PAYMENT_API, ID: 996
 * * ALOGATEWAY_WITHDRAWAL_PAYMENT_API, ID: 5002
 * * ALOGATEWAY_P2P_PAYMENT_API, ID: 5005
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://payment.cdc.alogateway.co/ChinaDebitCard
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_alogateway extends Abstract_payment_api {

    const BANKCODE_P2P      = "LBT";
    const BANKCODE_ALIPAY   = "ALIPAY";
    const BANKCODE_UNIONPAY = "UNIONPAY";

    const RESULT_CODE_SUCCESS = 1;
    const RESULT_MSG_SUCCESS = 'succcess';

    const CALLBACK_SUCCESS = 'A0';
    const RETURN_SUCCESS_CODE = 'SUCCESS';


    public function __construct($params = null) {
        parent::__construct($params);
    }

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
        $address   = (isset($playerDetails[0]) && !empty($playerDetails[0]['address']))       ? $playerDetails[0]['address']       : 'no address';
        $city      = (isset($playerDetails[0]) && !empty($playerDetails[0]['city']))          ? $playerDetails[0]['city']          : 'no city';
        $zipcode   = (isset($playerDetails[0]) && !empty($playerDetails[0]['zipcode']))       ? $playerDetails[0]['zipcode']       : '1000';
        $country   = (isset($playerDetails[0]) && !empty($playerDetails[0]['country']))       ? $playerDetails[0]['country']       : 'CN';
        $phone     = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '8615551234567';
        $email     = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : 'sample@example.com';

        $params = array();
        $params['apiversion']            = '3';
        $params['version']               = '11';
        $params['merchant_account']      = $this->getSystemInfo('account');
        $params['merchant_order']        = $order->secure_id;
        $params['merchant_product_desc'] = 'Topup';
        $params['first_name']            = $firstname;
        $params['last_name']             = $lastname;
        $params['address1']              = $address;
        $params['city']                  = $city;
        $params['zip_code']              = $zipcode;
        $params['country']               = $country;
        $params['phone']                 = $phone;
        $params['email']                 = $email;
        $params['amount']                = $this->convertAmountToCurrency($amount);
        $params['currency']              = $this->getSystemInfo('currency','CNY');
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['ipaddress']             = $this->getClientIP();
        $params['return_url']            = $this->getReturnUrl($orderId);
        $params['server_return_url']     = $this->getNotifyUrl($orderId);
        $params['control']               = $this->sign($params);

        $this->CI->utils->debug_log('=====================alogateway generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $this->getSystemInfo('url'),
            'params' => $params,
            'post' => true,
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

    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================alogateway params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['transactionid'], '', null, null, $response_result_id);
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
            'transactionid', 'merchant_order', 'amount', 'currency', 'status', 'control'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================alogateway checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================alogateway checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================alogateway checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount);
        if ($fields['amount'] != $check_amount) {
            $this->writePaymentErrorLog("======================alogateway checkCallbackOrder Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        }

        if ($fields['merchant_order'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================alogateway checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = hash_hmac('SHA1', $signStr, $this->getSystemInfo('key'));
        return $sign;
    }

    private function createSignStr($params) {
        $keys = array('merchant_account', 'amount', 'currency', 'first_name', 'last_name', 'address1', 'city', 'zip_code', 'country', 'phone', 'email', 'merchant_order', 'merchant_product_desc', 'return_url');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $params[$key];
            }
        }

        return $signStr;
    }

    private function validateSign($params) {
        $keys = array('transactionid', 'merchant_order', 'amount', 'currency', 'bank_transactionid', 'status');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $params[$key];
            }
        }
        $sign = hash_hmac('SHA1', $signStr, $this->getSystemInfo('key'));

        if($params['control'] == $sign){
            return true;
        }
        else{
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

    protected function convertAmountToCurrency($amount) {
        return number_format($amount * 100, 0, '.', '');
    }

    public function getPlayerDetails($playerId) {
        $this->CI->load->model(array('player_model'));
        $player = $this->CI->player_model->getPlayerDetails($playerId);
        return $player;
    }
}