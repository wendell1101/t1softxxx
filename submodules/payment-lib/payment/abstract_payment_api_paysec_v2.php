<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * PAYSEC_V2
 *
 * * PAYSEC_BANKTRANSFER_PAYMENT_API, ID: 556
 * * PAYSEC_QQPAY_V2_PAYMENT_API, ID: 629
 * * PAYSEC_WEIXIN_V2_PAYMENT_API, ID: 630
 * * PAYSEC_IDR_VA_PAYMENT_API, ID: 631
 * * PAYSEC_QUICKPAY_PAYMENT_API, ID: 821
 * * PAYSEC_V2_UNIONPAY_PAYMENT_API, ID: 5073
 * * PAYSEC_V2_ALIPAY_PAYMENT_API, ID: 5074
 * ------------------------------------
 * * PAYSEC_WITHDRAWAL_V2_PAYMENT_API, ID: 559
 * *
 * Required Fields:
 * * Account
 * * Secret
 * * URL
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Secret: ## Merchant Key ##
 * * URL: https://payment.allpay.site/api/transfer/v1/payIn/sendTokenForm
 * * TOKEN URL: https://payment.allpay.site/api/transfer/v1/payIn/requestToken
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_paysec_v2 extends Abstract_payment_api {

    const CURRENCY = 'CNY';
    const RESULT_CODE_FAILED  = 'FAILURE';
    const RESULT_CODE_SUCCESS = 'SUCCESS';
    const CALLBACK_FAILED  = 'FAILED';
    const CALLBACK_SUCCESS = 'SUCCESS';
    const RETURN_SUCCESS_CODE = 'OK';
    const CALLBACK_STATUS_FAILED = 'FAILED';


    const CHANNEL_BANKTRANSFER = 'BANK_TRANSFER';
    const CHANNEL_WEIXIN       = 'WECHAT';
    const CHANNEL_QQPAY        = 'QQPAY';
    const CHANNEL_ALIPAY       = 'ALIPAY';
    const CHANNEL_UNIONPAY     = 'UNIONPAY_QR';

    const BANK_QUICKPAY = 'QUICKPAY';


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
        $params['version']  = '3.0';
        $params['merchantCode'] = $this->getSystemInfo('account');
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['notifyURL']  = $this->getNotifyUrl($orderId);
        $params['returnURL'] = $this->getReturnUrl($orderId);
        $params['orderAmount']  = $this->convertAmountToCurrency($order->amount);
        $params['orderTime'] = time();
        $params['cartId'] = $order->secure_id;
        $params['currency'] = $this->getSystemInfo('currency', self::CURRENCY);
        $params['signature']  = $this->sign($params);
        $submit = array(
            "header" => array(
                "version" => $params['version'],
                "merchantCode" => $params['merchantCode'],
                "signature" => $params['signature']
            ),
            "body" => array(
                "channelCode" => $params['channelCode'],
                "notifyURL"   => $params['notifyURL'],
                "returnURL"   => $params['returnURL'],
                "orderAmount" => $params['orderAmount'],
                "orderTime"   => $params['orderTime'],
                "cartId"      => $params['cartId'],
                "currency"    => $params['currency']
            )
        );
        if (isset($params['bankCode'])) {
            $submit['body']['bankCode'] = $params['bankCode'];
        }
        $this->CI->utils->debug_log("========================paysec_v2 generatePaymentUrlForm submit", $submit);

        $response = $this->getToken($submit);
        $result = json_decode($response, true);


        if(isset($result['header']['status'])) {
            if($result['header']['status'] == self::RESULT_CODE_SUCCESS) {
                $token['token'] = $result['body']['token'];
                return $this->processPaymentUrlForm($token);
            }
            else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => 'Get token failed. ['.$result['header']['statusMessage']['code'].']'.$result['header']['statusMessage']['statusMessage']
                );
            }
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Get token failed.')
            );
        }
    }

    # Implement processPaymentUrlForm
    protected function processPaymentUrlFormPost($params) {
        return array(
            'success' => true,
            'type'    => self::REDIRECT_TYPE_FORM,
            'url'     => $this->getSystemInfo('url'),
            'params'  => $params,
            'post'    => true,
        );
    }

    protected function processPaymentUrlFormQRCode($params) {}

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

        $this->CI->utils->debug_log("========================paysec_v2 callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("========================paysec_v2 raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data, true);
                $this->CI->utils->debug_log("========================paysec_v2 json_decode params", $params);
            }
            if(isset($params['status'])) {
                if($params['status'] == self::CALLBACK_FAILED){
                    $result['return_error_msg'] = self::RETURN_SUCCESS_CODE;
                    $this->writePaymentErrorLog("========================paysec_v2 callback status is failed", $fields);
                    return $result;
                }
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['transactionReference'], null, null, null, $response_result_id);
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
            #redirect to success/fail page according to return params
            if($params['status'] == self::CALLBACK_STATUS_FAILED){
                $this->CI->utils->debug_log("========================paysec_v2 callbackFrom browser status return FAILED", $params);
                $result['success'] = false;
                $result['message'] = lang('error.payment.failed');
            }
            $result['next_url'] = $this->getPlayerBackUrl();
        }
        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'orderAmount', 'transactionReference', 'cartId', 'status', 'signature'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("========================paysec_v2 checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog("========================paysec_v2 checkCallbackOrder Signature Error", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("========================paysec_v2 checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ( abs($fields['orderAmount'] - $this->convertAmountToCurrency($order->amount)) > 1 ) {
            $this->writePaymentErrorLog("========================paysec_v2 checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['cartId'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================paysec_v2 checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    private function getToken($params) {
        $url = $this->getSystemInfo('token_url', 'https://payment.allpay.site/api/transfer/v1/payIn/requestToken');
        $this->_custom_curl_header = array('Content-Type: application/json');

        $response = $this->submitPostForm($url, $params, true, $params['body']['cartId']);

        return $response;
    }

    protected function sign($params) {
        $signStr = $this->createSignStr($params);
        $hashVal = hash('sha256', $signStr);
        $salt = str_replace ("$2a$12$","", $this->getSystemInfo('secret'));
        $signature = $this->passwordHashToCrypt($hashVal,$this->getSystemInfo('secret'));
        $loc = $this->strposX($signature, "$", 3);
        $sdata = str_replace($salt, "", substr($signature, $loc));

        return $sdata;
    }

    private function createSignStr($params) {
        $params['merchantCode'] = $this->getSystemInfo('account');
        $keys = array('cartId', 'orderAmount', 'currency', 'merchantCode', 'version', 'status');
        $signArr = array();
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signArr[$key] = $params[$key];
            }
        }
        return implode(";", $signArr);
    }

    private function strposX($haystack, $needle, $n = 0) {
        $offset = 0;

        for ($i = 0; $i < $n; $i++) {
            $pos = strpos($haystack, $needle, $offset);

            if ($pos !== false) {
                $offset = $pos + strlen($needle);
            } else {
                return false;
            }
        }

        return $offset;
    }

    private function validateSign($params) {
        $sign = $this->sign($params);
        if($params['signature'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    public function getNotifyUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function getReturnUrl($orderId)
    {
        return $this->getBrowserCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function getBrowserCallbackUrl($uri) {
        return $this->CI->utils->site_url_with_http($uri, $this->getSystemInfo('browser_callback_host'));
    }

    protected function convertAmountToCurrency($amount) {
        if($this->getSystemInfo('use_usd_currency')) {
            $amount = $this->gameAmountToDBByCurrency($amount, $this->utils->getTodayForMysql(), 'USD', 'CNY');
        }

        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 2, '.', '');
    }

    public function getBankListInfoFallback() {
        $currency  = $this->getSystemInfo('currency', self::CURRENCY);
        switch ($currency) {
            case 'CNY':
                return array(
                    array('label' => '_json: {"1": "China Citic Bank" , "2": "中信银行"}', 'value' => 'CITIC'),
                    array('label' => '_json: {"1": "Industrial Bank" , "2": "兴业银行"}', 'value' => 'CIB'),
                    array('label' => '_json: {"1": "China Guangfa Bank" , "2": "广发银行"}', 'value' => 'GDB'),
                    array('label' => '_json: {"1": "China Merchants Bank" , "2": "招商银行"}', 'value' => 'CMB'),
                    array('label' => '_json: {"1": "China Postal Savings Bank" , "2": "中国邮政储蓄银行"}', 'value' => 'PSBC'),
                    array('label' => '_json: {"1": "Bank of China" , "2": "中国银行"}', 'value' => 'BOC'),
                    array('label' => '_json: {"1": "Agricultural Bank of China" , "2": "中国农业银行"}', 'value' => 'ABC'),
                    array('label' => '_json: {"1": "China Everbright Bank" , "2": "中国光大银行"}', 'value' => 'CEB'),
                    array('label' => '_json: {"1": "China Construction Bank" , "2": "中国建设银行"}', 'value' => 'CCB'),
                    array('label' => '_json: {"1": "Ping An Bank" , "2": "平安银行"}', 'value' => 'PAB'),
                    array('label' => '_json: {"1": "Bank of Communication" , "2": "交通银行"}', 'value' => 'BCOM'),
                    array('label' => '_json: {"1": "Industrial and Commercial Bank of China" , "2": "中国工商银行"}', 'value' => 'ICBC'),
                    array('label' => '_json: {"1": "Shanghai Pudong Development Bank" , "2": "浦发银行"}', 'value' => 'SPDB'),
                    array('label' => '_json: {"1": "China Minsheng Bank" , "2": "中国民生银行"}', 'value' => 'CMBC'),
                    array('label' => '_json: {"1": "China Union Pay" , "2": "中国银联"}', 'value' => 'QUICKPAY'),
                );
                break;
            case 'THB':
                return array(
                    array('label' => lang('_json: {"1": "Siam Commercial Bank" , "2": "Siam Commercial Bank", "6": "ธนาคารไทยพาณิชย์"}'), 'value' => 'SCB_THB'),
                    array('label' => lang('_json: {"1": "Krung Thai Bank" , "2": "Krung Thai Bank", "6": "ธนาคารกรุงไทย"}'), 'value' => 'KTB_THB'),
                    array('label' => lang('_json: {"1": "BKrungsri (Bank of Ayudhya Public Company Limited)" , "2": "Krungsri (Bank of Ayudhya Public Company Limited)", "6": "ธนาคารกรุงศรีอยุธยา"}'), 'value' => 'BAY_THB'),
                    array('label' => lang('_json: {"1": "UOBT" , "2": "UOBT", "6": "ธนาคารยูโอบี จำกัด"}'), 'value' => 'UOB_THB'),
                    array('label' => lang('_json: {"1": "Karsikorn Bank (K-Bank)" , "2": "Karsikorn Bank (K-Bank)", "6": "ธนาคารกสิกร"}'), 'value' => 'KKB_THB'),
                    array('label' => lang('_json: {"1": "Bangkok Bank" , "2": "Bangkok Bank", "6": "ธนาคารกรุงเทพ"}'), 'value' => 'BBL_THB'),
                );
                break;
            case 'IDR':
                return array(
                    array('label' => 'Mandiri Bank', 'value' => 'MDR_IDR'),
                    array('label' => 'Bank Negara Indonesia', 'value' => 'BNI_IDR'),
                    array('label' => 'Bank Central Asia', 'value' => 'BCA_IDR'),
                    array('label' => 'Bank Rakyat Indonesia', 'value' => 'BRI_IDR'),
                    array('label' => 'Permata Bank', 'value' => 'PMB_IDR'),
                    array('label' => 'CIMB Clicks Indonesia', 'value' => 'CIMB_IDR'),
                    array('label' => 'Danamon Bank', 'value' => 'DMN_IDR'),
                    array('label' => 'BTN Bank', 'value' => 'BTN_IDR'),
                );
                break;
            case 'VND':
                return array(
                    array('label' => 'Asia Commercial Bank', 'value' => 'ACB_VND'),
                    array('label' => 'VIETCOM Bank', 'value' => 'VCB_VND'),
                    array('label' => 'VIETIN Bank', 'value' => 'VTN_VND'),
                    array('label' => 'SACOM Bank', 'value' => 'SACOM_VND'),
                    array('label' => 'BIDV Bank', 'value' => 'BIDV_VND'),
                    array('label' => 'Techcom Bank', 'value' => 'TECHOM_VND'),
                    array('label' => 'Dong A Bank', 'value' => 'DNG_VND'),
                    array('label' => 'Exim Bank', 'value' => 'EXIM_VND'),
                );
                break;
            case 'PHP':
                return array(
                    array('label' => 'Banco de Oro', 'value' => 'BDO'),
                    array('label' => 'MetroBank', 'value' => 'MTB'),
                );
                break;
            default:
                return array();
                break;
        }
    }
}