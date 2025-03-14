<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * YUEPAY 粵支付
 *
 * * YUEPAY_PAYMENT_API, ID: 718
 * * YUEPAY_QUICKPAY_PAYMENT_API, ID: 720
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_yuepay extends Abstract_payment_api {
    const TUNNEL_CODE_QUICKPAY = '2012';
    const TUNNEL_CODE_BANK = '2013';
    const TUNNEL_CODE_UNIONPAY = '2018';
    const TUNNEL_PAYMENTTYPEID = '22';

    const RETURN_SUCCESS_CODE = '{"message":"成功","response":"00"}';
    const RETURN_FAILED_CODE = 'FAIL';
    const PAY_RESULT = '2';
    const REQUEST_SUCCESS ='0';

    # Implement these for specific pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $default = array(
            'method' => 'qdpay.pay.compay.router.pay',
            'format' => 'json',
            'version' => '2.0',
            'open_api_url' => $this->getSystemInfo("url"),
            'session' => $this->getSystemInfo("session_val"),
            'appid' => $this->getSystemInfo("account"),
            'secretkey' => $this->getSystemInfo("key")

        );

        $this->CI->utils->debug_log('=====================yuepay generatePaymentUrlForm default array', $default);
        $time = date('Y-m-d H:i:s'); # 时间格式：yyyy-MM-dd HH:mm:ss

        $params['method'] = 'qdpay.pay.compay.router.pay';
        $params['format'] = 'json';
        $params['v'] = '2.0';
        $params['open_api_url'] = $this->getSystemInfo("url");
        $params['session'] = $this->getSystemInfo("session_val");
        $params['appid'] = $this->getSystemInfo("account");
        $params['secretkey'] = $this->getSystemInfo("key");
        $params['timestamp'] = $time;

        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['callbackurl'] = $this->getNotifyUrl($orderId);
        $params['ordernumber'] = $order->secure_id;
        $params['fronturl'] = $this->getReturnUrl($orderId);
        $params['body'] = 'Deposit-'.$params['ordernumber'];
        $params['tunnelcode'] = '2012';
        $params['payextraparams'] = 'payextraparams';

        $this->configParams($params, $order->direct_pay_extra_info);
        $data = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        $encryptdata = $this->encrypt($params['secretkey'],$params['secretkey'],$data);
        $params['data'] = $encryptdata;
        //
        $params['sign'] = $this->sign($params,$data);

        if ($this->getSystemInfo("http_get")==true){
                return $this->get_Extra_info($default,$data,$params);
        }else{
                return $this->form_Extra_info($params);
        }

    }
    public function get_Extra_info ($default,$data,$params){

         $redirect_url = $this->methodInvoke($default,$data);
         $this->CI->utils->debug_log('=====================yuepay generatePaymentUrlForm params', $params);
         return $this->processPaymentUrlForm($redirect_url);

    }

    public function form_Extra_info($params){

        $params_array = array(
            'appid' => $this->getSystemInfo("account"),
            'method' => 'qdpay.pay.compay.router.pay',
            'format' => 'json',
            'data' => $params['data'],
            'v' => '2.0',
            'timestamp' => $params['timestamp'],
            'session' => $this->getSystemInfo("session_val"),
            'sign' => $params['sign'],
            'ordernumber' => $params['ordernumber']
        );
        $this->CI->utils->debug_log('=====================yuepay generatePaymentUrlForm params', $params);
        return $this->processPaymentUrlForm($params_array);
    }

    public function sign ($params,$data){
        $md5_str = $params['secretkey'].$params['appid'].$params['data'].$params['format'].$params['method'].$params['session'].$params['timestamp'].$params['v'].$params['secretkey'];
        $signstr = md5($md5_str);
       
        return $signstr;
    }

    protected function methodInvoke($default,$data){
        $encryptdata = $this->encrypt($default['secretkey'],$default['secretkey'],$data);
        $time = date('Y-m-d H:i:s'); # 时间格式：yyyy-MM-dd HH:mm:ss
        $md5_str = $default['secretkey'].$default['appid'].$encryptdata.$default['format'].$default['method'].$default['session'].$time.$default['version'].$default['secretkey'];
        $signstr = md5($md5_str);
    

        $getdata = "appid=".$default['appid']."&method=".$default['method']."&format=".$default['format']."&data=".$encryptdata."&v=".$default['version']."&amp;timestamp=".$time."&session=".$default['session']."&sign=" .$signstr;
     

        $url = $default['open_api_url']."?".$getdata.'&redirectflag=1';
        return $url;
    }

    //decrypt callback params
    protected function decrypt_callback_params($callback_params){
        $secret_key = $this->getSystemInfo("key");
        $data = $this->decrypt($secret_key,$secret_key,$callback_params);
        $json_len = (strpos($data,"}")+1);
        $json = substr($data, 0, $json_len);   //get json string
        $decrypt_data = json_decode($json,true);
        $this->CI->utils->debug_log('=====================yuepay decrypt_callback_params decrypt_data', $decrypt_data);
        return $decrypt_data;
    }

    public function encrypt($privateKey,$iv,$data){
        $encrypted = @mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $privateKey, $data, MCRYPT_MODE_CBC, $iv);
        return $this->encode($encrypted);
    }

    public function decrypt($privateKey,$iv,$data){
        $encryptedData = $this->decode($data);
        $decrypted = @mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $privateKey, $encryptedData, MCRYPT_MODE_CBC, $iv);
        return $decrypted;
    }

    public function encode($str){
    
        $base64str= base64_encode($str);
        $base64str = str_replace("+", "-",  $base64str);
        $base64str = str_replace("/","_",  $base64str);
        return $base64str;
    }

    public function decode($str){
        $str = str_replace("-", "+",  $str);
        $str = str_replace("_","/",  $str);
        $unbase64str=base64_decode($str);
  
        return $unbase64str;
    }

    # Submit POST form
    protected function processPaymentUrl($url) {
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_URL,
            'url' => $url
        );
    }

     protected function processPaymentUrlFormQRCode($params) {

        $ordernumber = $params['ordernumber'];
        unset($params['ordernumber']);
        $this->CI->utils->debug_log('=====================yuepay processPaymentUrlFormQRcode scan url', $this->getSystemInfo('url'));
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $ordernumber);
        $this->CI->utils->debug_log('========================================yuepay processPaymentUrlFormQRcode received response', $response);

        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================yuepay processPaymentUrlFormQRcode response[1] json to array', $decode_data);
        $msg = lang('Invalidte API response');

        if(!empty($decode_data['data']['qrcode']) && ($decode_data['ret'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $decode_data['data']['qrcode'],
            );
        }else {
            if(!empty($decode_data['message'])) {
                $msg = $decode_data['message'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

    /**
     * detail: This will be called when the payment is async, API server calls our callback page,
     * When that happens, we perform verifications and necessary database updates to mark the payment as successful
     *
     * @param int $orderId order id
     * @param array $params
     * @return array
     */
    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);

        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    /**
     * detail: This will be called when user redirects back to our page from payment API
     *
     * @param int $orderId order id
     * @param array $params
     * @return array
     */
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================yuepay callbackFrom $source params", $params);

        if($source == 'server'){
            $params['decryptData'] = $this->decrypt_callback_params($params['Data']);
            $this->CI->utils->debug_log('=======================yuepay callbackFromServer server deccrypt callback_params', $params);

            if(empty($params['decryptData'])){
                $row_post = file_get_contents("php://input");
                $this->CI->utils->debug_log('=======================yuepay callbackFromServer row_post', $row_post);

                $explode = explode('&', $row_post);
                $explode_arr= array();
                for($i=0;$i<count($explode);$i++){
                    $arr = explode('=', $explode[$i]);
                    for($j=0;$j<count($arr);$j++){
                         $explode_arr[$arr[0]]=$arr[1];
                    }
                }
                $this->CI->utils->debug_log('=======================yuepay callbackFromServer explode_arr',$explode_arr);

                $dataResult = $this->decrypt_callback_params($explode_arr['Data']);
                $this->CI->utils->debug_log('=======================yuepay file_get_contents params', $dataResult);
                $params = $explode_arr;
            }

            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        # Update order payment status and balance
        $success=true;

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
            $this->CI->sale_order->updateExternalInfo($order->id,
                $params['Ordernumber'], 'Third Party Payment (No Bank Order Number)', # no info available
                null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($success) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['message'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    /**
     * detail: Validates whether the callback from API contains valid info and matches with the order
     *
     * @return boolean
     */

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'Data', 'Sign', 'Ordernumber'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================yuepay missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['decryptData']['respcode'] != self::PAY_RESULT) {
            $payStatus = $fields['decryptData']['respcode'];
            $this->writePaymentErrorLog("=====================yuepay Payment was not successful, payStatus is [$payStatus]", $fields);
            return false;
        }

        if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['decryptData']['amount'] )
        ) {
            $this->writePaymentErrorLog("=====================yuepay Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['Ordernumber'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================yuepay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================yuepay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    public function getBankListInfoFallback() {
        return array(
            array('label' => '工商银行', 'value' => '01020000'),
            array('label' => '招商银行', 'value' => '03080000'),
            array('label' => '建设银行', 'value' => '01050000'),
            array('label' => '农业银行', 'value' => '01030000'),
            array('label' => '交通银行', 'value' => '03010000'),
            array('label' => '中国银行', 'value' => '01040000'),
            array('label' => '深发银行', 'value' => '03070000'),
            array('label' => '广发银行', 'value' => '03060000'),
            array('label' => '东莞银行', 'value' => '04256020'),
            array('label' => '浦发银行', 'value' => '03100000'),
            array('label' => '中信银行', 'value' => '03020000'),
            array('label' => '民生银行', 'value' => '03050000'),
            array('label' => '邮储银行', 'value' => '01000000'),
            array('label' => '兴业银行', 'value' => '03090000'),
            array('label' => '华夏银行', 'value' => '03040000'),
            array('label' => '平安银行', 'value' => '04100000'),
            array('label' => '广州银行', 'value' => '04135810'),
            array('label' => '南京银行', 'value' => '04243010'),
            array('label' => '光大银行', 'value' => '03030000'),
            array('label' => '上海银行', 'value' => '04012900'),
            array('label' => '深圳银行', 'value' => '04105840'),
            array('label' => '北京银行', 'value' => '04031000'),
            array('label' => '上海银行', 'value' => '04012900')
        );
    }

    # -- Private functions --
    /**
     * detail: After payment is complete, the gateway will invoke this URL asynchronously
     *
     * @param int $orderId
     * @return void
     */
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    /**
     * detail: After payment is complete, the gateway will send redirect back to this URL
     *
     * @param int $orderId
     * @return void
     */
    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    /**
     * detail: Format the amount value for the API
     *
     * @param float $amount
     * @return float
     */
    protected function convertAmountToCurrency($amount) {
        return number_format($amount*100, 0, '.', '');
    }

    public function verifySignature($data) {
        $callback_sign = $data['Sign'];
        unset($data['Sign']);
        $signStr = $data['Data'].$this->getSystemInfo('key');
        $sign = md5($signStr);
    
        if (strcasecmp($sign, $callback_sign) !== 0) {
           
            return false;
        }else{
            return true;
        }

    }
}
