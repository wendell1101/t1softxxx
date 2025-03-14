<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * BIBAO_otc 幣寶
 *
 * * BIBAO_DC_BANKCAR_PAYMENT_API, ID: 5221
 * * BIBAO_DC_ALIPAY_PAYMENT_API, ID: 5222
 * * BIBAO_DC_WEIXIN_PAYMENT_API, ID: 5223
 * * bibao_ALIPAY_H5_PAYMENT_API, ID: 5188
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://opoutox.gosafepp.com/api/caishen/coin/Login
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_bibao_otc extends Abstract_payment_api {
    const PAY_METHODS_ALIPAY    = "aliPay";
    const PAY_METHODS_BANKCAR   = "bankcard";
    const PAY_METHODS_WECHATPAY = "weChatpay";

    const REQUEST_TYPE_BUY_COINS  = 1;
    const REQUEST_TYPE_SELL_COINS = 2;
    const COIN = 'DC';

    const RESULT_CODE_SUCCESS     = 1;
    const CALLBACK_STATUS_SUCCESS = 2;
    const RESULT_MSG_SUCCESS      = 'true';

    const RETURN_SUCCESS_CODE = 'Success';


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

        $order     = $this->CI->sale_order->getSaleOrderById($orderId);
        $secure_id = $order->secure_id;
        $this->CI->load->model('player');
        $player    = $this->CI->player->getPlayerById($playerId);
        $username  = $player['username'];

        #----Create AddUser----
            #api url/MerCode/coin/AddUser
            $create_adduser_url = $this->getSystemInfo('addUserUrl');

            $params = array();
            $params['MerCode']   = $this->getSystemInfo('account');
            $params['Timestamp'] = $this->getMillisecond();
            $params['UserName']  = $username;
            $formData = $this->buildFormData($params);
            $param = $this->desEncrypt($formData,$this->getSystemInfo('key'));
            $key   = $this->md5Key_adduser_geraddress($params,$this->getSystemInfo('keyA'),$this->getSystemInfo('keyB'),$this->getSystemInfo('keyC'),false);
            $postData = [
                "param"=>$param,
                "key"=>$key
            ];
            $response = $this->submitGetForm($create_adduser_url, $postData, false, $secure_id);
            $decodeData = json_decode($response,true);
            $this->utils->debug_log('=====================bibao_otc submitGetForm create_adduser_url response', $decodeData);
            if($decodeData['Success']){
                #----Get Address----
                #api url/MerCode/coin/GetAddress
                $create_getaddress_url = $this->getSystemInfo('getAddressUrl');

                $data = array();
                $data['MerCode']   = $this->getSystemInfo('account');
                $data['Timestamp'] = $this->getMillisecond();
                $data['UserType']  = $this->getSystemInfo('UserType', '1');
                $data['UserName']  = $username;
                $data['CoinCode']  = self::COIN;
                $this->utils->debug_log('===================bibao_otc data', $data);
                $formData = $this->buildFormData($data);
                $param = $this->desEncrypt($formData,$this->getSystemInfo('key'));
                $key   = $this->md5Key_adduser_geraddress($data,$this->getSystemInfo('keyA'),$this->getSystemInfo('keyB'),$this->getSystemInfo('keyC'),true);
                $postData = [
                    "param"=>$param,
                    "key"=>$key
                ];

                $response_address = $this->submitGetForm($create_getaddress_url, $postData, false, $secure_id);
                $decodeData = json_decode($response_address,true);
                $this->utils->debug_log('=====================bibao_otc submitGetForm create_getaddress_url response_address', $decodeData);
                if(!$decodeData['Success']){
                    return array(
                        'success' => false,
                        'type' => self::REDIRECT_TYPE_ERROR,
                        'message' => lang('Bibao Create GetAddress Failed').': ['.$decodeData['Message'].']'.$decodeData['Success']
                    );
                }else{
                    $coincode = $decodeData['Data']['CoinCode'];
                    $address  = $decodeData['Data']['Address'];

                    $deposit_notes = ' coincode: '.$coincode.' | address: '.$address;
                    $this->utils->debug_log('=====================bibao_otc deposit_notes', $deposit_notes);

                    $handle['Address'] = $address;
                    $handle['CoinCode'] = $coincode;
                }
            }
            else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => lang('Bibao Create AddUser Failed').': ['.$decodeData['Message'].']'.$decodeData['Success']
                );
            }

        $submitparams = array();
        $submitparams['MerCode']   = $this->getSystemInfo('account');
        $submitparams['Timestamp'] = $this->getMillisecond();
        $submitparams['UserName']  = $username;
        $submitparams['Type']      = self::REQUEST_TYPE_BUY_COINS;
        $submitparams['Coin']      = self::COIN;
        $submitparams['Amount']    = $this->convertAmountToCurrency($amount,true);
        $submitparams['OrderNum']  = $order->secure_id;
        $this->configParams($submitparams, $order->direct_pay_extra_info);
        $submitparams['Key']       = $this->md5KeyB($submitparams,$this->getSystemInfo('keyA'),$this->getSystemInfo('keyB'),$this->getSystemInfo('keyC'));
        $this->CI->utils->debug_log('=====================bibao_otc generatePaymentUrlForm params', $submitparams);

        return $this->processPaymentUrlForm($submitparams);
    }

    protected function processPaymentUrlFormQRCode($params) {
        $key = $params['Key'];
        unset($params['Key']);
        $formData = $this->buildFormData($params);
        $param = $this->desEncrypt($formData,$this->getSystemInfo('key'));
        $postData = [
            "param"=>$param,
            "key"=>$key
        ];
        $this->CI->utils->debug_log('=====================bibao_otc processPaymentUrlFormQRCode postData', $postData);
        $response = $this->submitGetForm($this->getSystemInfo('url'), $postData, false, $params['OrderNum']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================bibao_otc processPaymentUrlFormQRCode submitGetForm Login response', $response);

        if($response['Code'] == self::RESULT_CODE_SUCCESS && $response['Success'] == self::RESULT_MSG_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['Data']['Url'].'/'.$response['Data']['Token'],
            );
        }
        else if($response['Message']) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['Code'].': '.$response['Message']
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

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->utils->debug_log('=========================bibao_otc getOrderIdFromParameters flds', $flds);
        if(empty($flds) || is_null($flds)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $flds = json_decode($raw_post_data, true);
        }
        if(isset($flds['OrderNum'])) {

            $txid = $flds['OrderNum'];
            $this->utils->debug_log('=====================bibao_otc getOrderIdFromParameters get transfer id', $txid);

            #deposit
            if(substr($txid, 0, 1) == 'D'){
                $this->utils->debug_log('=====================bibao_otc getOrderIdFromParameters deposit OrderNo', $txid);
                $order = $this->CI->sale_order->getSaleOrderBySecureId($txid);
                if(is_null($order)){
                    $this->utils->debug_log('=====================bibao_otc getOrderIdFromParameters cannot find order by txid', $flds);
                    return;
                }
                return $order->id;
            }
            else if(substr($txid, 0, 1) == 'W'){
                $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($txid);
                if(is_null($order)){
                    $this->utils->debug_log('=====================bibao_otc getOrderIdFromParameters cannot find order by txid', $flds);
                    return;
                }
                return $order['transactionCode'];
            }
        }
        else {
            $this->utils->debug_log('=====================bibao_otc getOrderIdFromParameters cannot get any transfer from webhook params', $flds);
            return;
        }
    }

    ## This will be called when the payment is async, API server calls our callback page
    ## When that happens, we perform verifications and necessary database updates to mark the payment as successful
    ## Reference: sample code, callback.php
    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    ## This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================bibao_otc params", $params);

        if($source == 'server' ){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================bibao_otc callbackFrom raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================bibao_otc callbackFrom json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['OrderNum'], null, null, null, $response_result_id);
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

    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'UserName', 'OrderId', 'OrderNum', 'Type', 'Coin', 'CoinAmount', 'LegalAmount', 'State1', 'State2', 'CreateTime', 'FinishTime', 'Remark', 'Price', 'Token', 'Sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================bibao_otc checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================bibao_otc checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['CoinAmount'] != $this->convertAmountToCurrency($order->amount,false)) {
            #because player need to enter amount at Alipay
            if($this->getSystemInfo('allow_callback_amount_diff')){

                $lastAmount = abs($this->convertAmountToCurrency($order->amount,false) - floatval($fields['CoinAmount'] * $fields['Price']));
                if($lastAmount > 1) {
                    $this->writePaymentErrorLog("=====================bibao_otc Payment amounts do not match, expected [$order->amount]", $fields ,$lastAmount);
                    return false;
                }
                $this->CI->utils->debug_log('=====================bibao_otc diff amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['CoinAmount'], $notes);
            }
            else{
                $this->writePaymentErrorLog("=====================bibao_otc Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['State1'] != self::CALLBACK_STATUS_SUCCESS) {
            $payStatus = $fields['State1'];
            $this->writePaymentErrorLog("=====================bibao_otc Payment was not successful, State1 is [$payStatus]", $fields);
            return false;
        }

        if ($fields['State2'] != self::CALLBACK_STATUS_SUCCESS) {
            $payStatus = $fields['State2'];
            $this->writePaymentErrorLog("=====================bibao_otc Payment was not successful, State2 is [$payStatus]", $fields);
            return false;
        }

        if ($fields['OrderNum'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================bibao_otc checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    public function getMillisecond() {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    public function buildFormData($data) {
        if(!is_array($data))
            return "";

        $result="";
        foreach ($data as $k=>$v) {
            $result.=sprintf("%s=%s&",$k,$v);
        }
        $result=rtrim($result,"&");
        return $result;
    }

    public function desEncrypt($params,$deskey) {
        $result = openssl_encrypt($params,"DES-CBC",$deskey,OPENSSL_RAW_DATA, $deskey);
        return strtoupper(bin2hex($result));
    }

    public function md5Key_adduser_geraddress($params,$keyA,$keyB,$keyC,$GetAddress=true) {
        $today = date("Ymd",time());
        if($GetAddress){
            $md5KeyB = md5($params['MerCode'].$params['UserType'].$params['CoinCode'].$keyB.$today);
        }else{
            $md5KeyB = md5($params['MerCode'].$params['UserName'].$keyB.$today);
        }
        $sign = $keyA.$md5KeyB.$keyC;
        return $sign;
    }

    public function md5KeyB($params,$keyA,$keyB,$keyC) {
        $today = date("Ymd",time());
        $md5KeyB = md5($params['MerCode'].$params['UserName'].$params['Type'].$params['OrderNum'].$keyB.$today);
        $sign = $keyA.$md5KeyB.$keyC;
        return $sign;
    }

    public function verifySignature($data) {
        $callback_sign = $data['Sign'];
        unset($data['FinishTime']);
        unset($data['Sign']);
        $signStr =  $this->buildFormData($data);
        $sign=strtolower(md5($signStr.$this->getSystemInfo('keyB')));
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }

    # -- signatures --
    # Reference: PHP Demo


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
    protected function convertAmountToCurrency($amount,$verifyAmount=true) {
        if($verifyAmount){
            $submitAmount = $amount/1.009;
            $this->CI->utils->debug_log("=====================bibao_otc convertAmountToCurrency submitAmount",$submitAmount);
            return number_format($submitAmount, 2, '.', '');
        }else{
            return number_format($amount, 2, '.', '');
        }
    }
}