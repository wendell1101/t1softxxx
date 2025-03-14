<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * WEALTHPAY
 * http://merchant.topasianpg.co
 *
 * * WEALTHPAY_PAYMENT_API, ID: 5309
 * * WEALTHPAY_PROMPTPAY_PAYMENT_API, ID: 5741
 * * WEALTHPAY_WITHDRAWAL_PAYMENT_API, ID: 5742
 * * WEALTHPAY_TRUEWALLET_PAYMENT_API, ID: 5997
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.topasianpg.co/merchant/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_wealthpay extends Abstract_payment_api {

    const CURRENCY = "THB";
    const DEPOSIT_CHANNEL_EBANKING   = '1';
    const DEPOSIT_CHANNEL_PROMPTPAY  = '3';
    const DEPOSIT_CHANNEL_MOMO       = '10';
    const DEPOSIT_CHANNEL_ZALO       = '11';
    const DEPOSIT_CHANNEL_VAOFFLINE  = '20';
    const DEPOSIT_CHANNEL_TRUEWALLET = '22';
    const DEPOSIT_CHANNEL_OVO        = '23';
    const DEPOSIT_CHANNEL_GOPAY      = '24';
    const ORDER_STATUS_CREATED = '0000';
    const ORDER_STATUS_SUCCESS = '0001';
    const ORDER_STATUS_PROCESS = '0002';

    const RETURN_FAIL_CODE = 'FAIL';
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
        $params['MerchantCode']    = $this->getSystemInfo('account');
        $params['TransactionID']   = $order->secure_id;
        $params['MemberID']        = $playerId;
        $params['CurrencyCode']    = $this->getSystemInfo('currency', self::CURRENCY);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['Amount']          = $this->convertAmountToCurrency($amount);
        $params['RedirectURL']     = $this->getReturnUrl($orderId);
        $params['CallbackURL']     = $this->getNotifyUrl($orderId);
        $params['ClientIP']        = $this->getClientIP();
        $params['TransactionTime'] = $orderDateTime->format('Y-m-d H:i:s');
        $params['Signature']       = $this->sign($params);
        $this->CI->utils->debug_log('=====================wealthpay generatePaymentUrlForm params', $params);

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

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['TransactionID']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================wealthpayv2 processPaymentUrlFormPost response', $response);

        if(isset($response['Status']) && $response['Status'] == self::ORDER_STATUS_CREATED) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['TransactionID']);
            $this->CI->sale_order->updateExternalInfo($order->id, $params['TransactionID']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['RedirectURL'],
            );
        }
        else if(isset($response['Message'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => '['.$response['Status'].']: '.$response['Message']
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

        $this->CI->utils->debug_log("=====================wealthpay callbackFrom $source params", $params);

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
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                # update player balance
                $this->CI->sale_order->updateExternalInfo($order->id, $params['ID'], null, null, null, $response_result_id);
                #redirect to success/fail page according to return params
                if($params['Status'] == self::ORDER_STATUS_SUCCESS){
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['return_error'] = self::RETURN_FAIL_CODE;
        }

        if ($source == 'browser') {
            $this->CI->utils->debug_log("=====================wealthpay callbackFrom $source params Successful", $params['Successful']);
            #redirect to success/fail page according to return params
            if(isset($params['Successful'])){
                $result['success'] = filter_var($params['Successful'], FILTER_VALIDATE_BOOLEAN);
                $result['message'] = $params['Message'];
            }
            $this->CI->utils->debug_log("=====================wealthpay callbackFrom $source result success", $result['success']);
            $result['next_url'] = $this->getPlayerBackUrl();
        }

        return $result;
    }

    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'MerchantCode', 'TransactionID', 'Amount', 'ID', 'Status', 'Signature'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================wealthpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================wealthpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        $amount = $this->convertAmountToCurrency($order->amount);
        if ($fields['Amount'] != $amount) {
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $diffAmount = abs(str_replace(',', '', $amount) - str_replace(',', '', $fields['Amount']));
                if ($diffAmount >= 1) {
                    $this->writePaymentErrorLog("=====================wealthpay checkCallbackOrder Payment amounts ordAmt - payAmt > 1, expected [$order->amount]", $fields ,$diffAmount);
                    return false;
                }
                $this->CI->utils->debug_log("=====================wealthpay checkCallbackOrder amount not match expected [$order->amount]",$fields);
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, str_replace(',', '', $fields['Amount']), $notes);
            }else{
                $this->writePaymentErrorLog("======================wealthpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['TransactionID'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================wealthpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # Config in extra_info will overwrite this one
    public function getBankListInfoFallback() {
        $currency  = $this->getSystemInfo('currency',self::CURRENCY);
        switch ($currency) {
            case 'THB':
                return array(
                    array('label' => lang('_json: {"1": "Bank of Ayudhya (Krungsri)" , "2": "Bank of Ayudhya (Krungsri)", "6": "ธนาคารกรุงศรีอยุธยา"}'), 'value' => 'BAY'),
                    array('label' => lang('_json: {"1": "Bangkok Bank" , "2": "Bangkok Bank", "6": "ธนาคารกรุงเทพ"}'), 'value' => 'BBL'),
                    array('label' => lang('_json: {"1": "CIMB Thai" , "2": "CIMB Thai", "6": "ธนาคาร ซีไอเอ็มบี ไทย"}'), 'value' => 'CIMBT'),
                    array('label' => lang('_json: {"1": "Government Savings Bank" , "2": "Government Savings Bank", "6": "ธนาคารออมสิน"}'), 'value' => 'GSB'),
                    array('label' => lang('_json: {"1": "Karsikorn Bank (K-Bank)" , "2": "Karsikorn Bank (K-Bank)", "6": "ธนาคารกสิกร"}'), 'value' => 'KBANK'),
                    array('label' => lang('_json: {"1": "Kiatnakin Bank" , "2": "Kiatnakin Bank", "6": "ธนาคารเกียรตินาคิน"}'), 'value' => 'KKB'),
                    array('label' => lang('_json: {"1": "Krung Thai Bank" , "2": "Krung Thai Bank", "6": "ธนาคารกรุงไทย"}'), 'value' => 'KTB'),
                    array('label' => lang('_json: {"1": "Siam Commercial Bank" , "2": "Siam Commercial Bank", "6": "ธนาคารไทยพาณิชย์"}'), 'value' => 'SCB'),
                    array('label' => lang('_json: {"1": "TMB Bank Public Company Limited" , "2": "TMB Bank Public Company Limited", "6": "ธนาคารทหารไทย"}'), 'value' => 'TMB'),
                );
                break;
            case 'VND':
                return array(
                    array('label' => 'Asia Commercial Bank', 'value' => 'ACB'),
                    array('label' => 'DongA Bank', 'value' => 'DAB'),
                    array('label' => 'Vietcombank', 'value' => 'VCB'),
                    array('label' => 'Vietinbank', 'value' => 'VTB'),
                    array('label' => 'Sacombank', 'value' => 'SACOM'),
                    array('label' => 'Techcombank', 'value' => 'TCB'),
                );
                break;
            case 'IDR':
                return array(
                    array('label' => 'BANK CAPITAL', 'value' => 'BCA'),
                    array('label' => 'BANK MANDIRI (PERSERO)', 'value' => 'Mandiri'),
                    array('label' => 'BANK NEGARA INDONESIA', 'value' => 'BNI'),
                );
                break;
            default:
                return array();
                break;
        }
    }

    # -- signatures --
    protected function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(hash('sha256', $signStr));
        return $sign;
    }

    private function createSignStr($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'Signature' || $key == 'DepositType' || $key == 'DepositChannel' ) {
                continue;
            }
            $signStr .= $value;
        }
        return $signStr.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        $signStr =
            $params['MerchantCode'].$params['CurrencyCode'].$params['BankCode'].$params['TransactionID'].$params['Amount'].
            $params['TransactionTime'].$params['ID'].$params['Status'].$params['Message'].
            $this->getSystemInfo('key');
        $sign = strtoupper(hash('sha256', $signStr));
        if($params['Signature'] == $sign){
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
        $convert_rate = 1;
        if($this->CI->utils->getConfig('fix_currency_conversion_rate')){
            $convert_rate = $this->CI->utils->getConfig('fix_currency_conversion_rate');
            $this->writePaymentErrorLog("======================wealthpay convertAmountToCurrency fix_currency_conversion_rate", $convert_rate);
        }
        if(!empty($this->getSystemInfo('convert_multiplier'))){
            $convert_rate = $this->getSystemInfo('convert_multiplier');
        }

        return number_format($amount * $convert_rate, 2, '.', ',');
    }
}