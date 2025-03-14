<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * MONEYMATRIX
 *
 * * MONEYMATRIX_PAYMENT_API, ID: 5077
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.moneymatrix.com/api/v1/Hosted/InitDeposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_moneymatrix extends Abstract_payment_api {

    const RESULT_CODE_SUCCESS = 1;
    const RESULT_MSG_SUCCESS = 'Success';

    const CALLBACK_SUCCESS = 1;
    const RETURN_SUCCESS_CODE = 'OK';


    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }

    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order         = $this->CI->sale_order->getSaleOrderById($orderId);
        $player        = $this->CI->player_model->getPlayer(array('playerId' => $playerId));
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
        $username  = (isset($playerDetails[0]) && !empty($playerDetails[0]['username']))      ? $playerDetails[0]['username']      : 'no username';
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'no firstName';
        $lastname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName']      : 'no lastName';
        $birthdate = (isset($playerDetails[0]) && !empty($playerDetails[0]['birthdate']))     ? $playerDetails[0]['birthdate']     : '2000-01-01';
        $address   = (isset($playerDetails[0]) && !empty($playerDetails[0]['address']))       ? $playerDetails[0]['address']       : 'no address';
        $city      = (isset($playerDetails[0]) && !empty($playerDetails[0]['city']))          ? $playerDetails[0]['city']          : 'no city';
        $zipcode   = (isset($playerDetails[0]) && !empty($playerDetails[0]['zipcode']))       ? $playerDetails[0]['zipcode']       : '1000';
        $country   = (isset($playerDetails[0]) && !empty($playerDetails[0]['country']))       ? $playerDetails[0]['country']       : 'CN';
        $phone     = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '8615551234567';
        $email     = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : 'sample@example.com';
        $registrationIP = (isset($playerDetails[0]['registrationIP']) && filter_var($playerDetails[0]['registrationIP'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) ? $playerDetails[0]['registrationIP'] : '127.0.0.1';

        $params = array();
        $params['CustomerId']            = $playerId;
        $params['CustomerGroups']        = 'VIP';
        $params['MerchantReference']     = $order->secure_id;
        $params['Channel']               = $this->CI->utils->is_mobile() ? 'Mobile' : 'Desktop';
        $params['FirstName']             = $firstname;
        $params['LastName']              = $lastname;
        $params['EmailAddress']          = $email;
        $params['BirthDate']             = DateTime::createFromFormat('Y-m-d', $birthdate)->format('m/d/Y');
        $params['PaymentMethod']         = ''; #empty to cashier
        $params['Amount']                = $this->convertAmountToCurrency($amount);
        $params['Currency']              = $this->getSystemInfo('currency','USD');
        $params['CountryCode']           = 'CN'; #$country
        $params['IpAddress']             = $this->getClientIP();
        $params['RegistrationIpAddress'] = $registrationIP;
        $params['RegistrationDate']      = DateTime::createFromFormat('Y-m-d H:i:s', $player['createdOn'])->format('m/d/Y');
        $params['Address']               = $address;
        $params['City']                  = $city;
        $params['State']                 = 'no State';
        $params['PhoneNumber']           = $phone;
        $params['PostalCode']            = $zipcode;
        $params['SuccessUrl']            = $this->getReturnUrl($orderId);
        $params['FailUrl']               = $this->getReturnFailUrl($orderId);
        $params['CallbackUrl']           = $this->getNotifyUrl($orderId);
        $params['CancelUrl']             = $this->getReturnFailUrl($orderId);
        $params['AllowPaySolChange']     = true; #To get fully listed cashier, pass this parameter as “true”.
        $params['MerchantId']            = $this->getSystemInfo('account');
        $params['Signature']             = $this->sign($params);

        $this->CI->utils->debug_log("=====================moneymatrix generatePaymentUrlForm params", $params);

        return $this->processPaymentUrlForm($params);
    }


    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url').'InitDeposit', $params, true, $params['MerchantReference']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log("=====================moneymatrix processPaymentUrlFormPost response", $response);

        if($response['ResponseCode'] == self::RESULT_CODE_SUCCESS && $response['ResponseMessage'] == self::RESULT_MSG_SUCCESS) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['MerchantReference']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['TransactionCode']);

            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['CashierUrl'],
            );
        }
        else if($response['ResponseCode'] > 10) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => '['.$response['ResponseCode'].']: '.$response['ResponseMessage']
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
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

        $this->CI->utils->debug_log("=====================moneymatrix callbackFrom $source params", $params);

        if($source == 'server' ){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================moneymatrix raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================moneymatrix json_decode params", $params);
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
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->CI->sale_order->updateExternalInfo($order->id, $params['TransactionReference'], null, null, null, $response_result_id);
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
            'TransactionCode', 'PaymentVendor', 'RequestedAmount', 'Currency', 'Status', 'ResponseCode', 'Signature'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================moneymatrix checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog("======================moneymatrix checkCallbackOrder Signature Error", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['Status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================moneymatrix checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount);
        if ($fields['ConfirmedAmount'] != $check_amount) {
            $this->writePaymentErrorLog("======================moneymatrix checkCallbackOrder Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        }

        if ($fields['MerchantReference'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================moneymatrix checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $key = utf8_encode($this->getSystemInfo('key'));
        $signStr = utf8_encode($this->createSignStr($params));

        $sign = hash_hmac('sha512', $signStr, $key);
        $sign = $this->hexToBase64($sign);
        return $sign;
    }

    private function createSignStr($params) {
        $signStr = $params['MerchantId'];
        $keys = array('MerchantReference', 'PaymentMethod', 'CustomerId', 'Amount', 'Currency');
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $key."='".$params[$key]."';";
            }
        }
        return $signStr.$this->getSystemInfo('key');
    }

    private function hexToBase64($hex){
        $result = "";
        foreach(str_split($hex, 2) as $pair){
            $result .= chr(hexdec($pair));
        }
        return base64_encode($result);
    }

    private function validateSign($params) {
        $signStr = $params['MerchantId'];
        $keys = array('TransactionCode', 'PaymentVendor', 'RequestedAmount', 'Currency', 'Status', 'ResponseCode');
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                if($key == 'RequestedAmount'){
                    $signStr .= $key."='".$this->convertAmountToCurrency($params[$key])."';";
                }
                else{
                    $signStr .= $key."='".$params[$key]."';";
                }
            }
        }

        $key = utf8_encode($this->getSystemInfo('key'));
        $signStr = utf8_encode($signStr.$this->getSystemInfo('key'));
        $sign = hash_hmac('sha512', $signStr, $key);
        $sign = $this->hexToBase64($sign);

        if($params['Signature'] == $sign){
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

    protected function getReturnFailUrl($orderId) {
        return parent::getCallbackUrl('/callback/show_error/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 2, '.', '');
    }
}