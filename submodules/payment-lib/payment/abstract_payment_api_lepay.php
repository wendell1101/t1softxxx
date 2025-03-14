<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * lepay
 *
 * sdk:  https://lepay.unionpay95516.cc/payapi/pay/sdk
 * doc: https://lepay.unionpay95516.cc/documentPro/#Access_flow
 *
 * * LEPAY_PAYMENT_API, ID: 166
 * * LEPAY_ALIPAY_PAYMENT_API, ID: 167
 * * LEPAY_WEIXIN_PAYMENT_API, ID: 168
 *
 * Required Fields:
 * * URL
 *
 * Field Values:
 * * URL: live : https://openapi.unionpay95516.cc/pre.lepay.api/order/add , sandbox: http://lepay.asuscomm.com/lepay.appapi/order/add.json
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_lepay extends Abstract_payment_api
{

    const CHANNEL_BANK = 1;
    const CHANNEL_ALIPAY = 4;
    const CHANNEL_WEIXIN = 3;
    const SDK_VERSION = '1.0.1';
    const REQUEST_ENCODING = 'UTF-8';
    const REQUEST_TYPE_WEB = 'web.pay';
    const REQUEST_TYPE_WAP = 'wap.pay';

    const DEFAULT_WEIXIN_CODE='wxpay.qrpay.gc.qdzg';
    const DEFAULT_ALIPAY_CODE='PT0007';

    const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAIL_CODE = 'fail';
    const P_ERRORCODE_PAYMENT_SUCCESS = '000000';

    protected $_cert;
    protected $_cert_pwd;
    protected $_encrypt_cert;

    public function __construct($params = null)
    {
        parent::__construct($params);

        $this->_cert = base64_decode($this->getSystemInfo('SDK_SIGN_CERT'));
        $this->_cert_pwd = $this->getSystemInfo('SDK_SIGN_CERT_PWD');
        $this->_encrypt_cert = base64_decode($this->getSystemInfo('SDK_ENCRYPT_CERT'));
    }

    private function __argSort($para)
    {
        ksort ( $para );
        reset ( $para );
        return $para;
    }

    private function __createLinkString($para, $sort, $encode)
    {
        if($para == NULL || !is_array($para))
            return "";

        $linkString = "";
        if ($sort) {
            $para = $this->__argSort ( $para );
        }
        while ( list ( $key, $value ) = each ( $para ) ) {
            if ($encode) {
                $value = urlencode ( $value );
            }
            $linkString .= $key . "=" . $value . "&";
        }
        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );

        return $linkString;
    }

    public function getPrivateKey()
    {
        openssl_pkcs12_read($this->_cert, $certs, $this->_cert_pwd);
        return $certs ['pkey'];
    }

    public function getPublicKey()
    {
        return $this->_encrypt_cert;
    }

    public function getSignCertId() {
        openssl_pkcs12_read($this->_cert, $certs, $this->_cert_pwd);
        $x509data = $certs['cert'];
        openssl_x509_read($x509data);
        $certdata = openssl_x509_parse($x509data);
        $cert_id = $certdata['serialNumber'];
        return $cert_id;
    }

    public function getCertIdByEncryptCert() {
        $x509data = $this->_encrypt_cert;
        openssl_x509_read($x509data);
        $certdata = openssl_x509_parse($x509data);
        $cert_id = $certdata['serialNumber'];
        return $cert_id;
    }

    /**
    * 签名
    *
    * @param String $params_str
    */
    private function __sign(&$params)
    {
        $params['certId'] = $this->getSignCertId();
        // $log->LogInfo ( '=====签名报文开始======' );
        if (isset($params['signature'])) {
            unset($params['signature']);
        }

        // 转换成key=val&串
        $params_str = $this->__createLinkString ( $params, true, false );
        // $log->LogInfo ( "签名key=val&...串 >" . $params_str );

        $params_sha1x16 = sha1 ( $params_str, false );
        // $log->LogInfo ( "摘要sha1x16 >" . $params_sha1x16 );

        $private_key = $this->getPrivateKey();
        // 签名
        $sign_falg = openssl_sign ( $params_sha1x16, $signature, $private_key, OPENSSL_ALGO_SHA1 );
        if ($sign_falg) {
            $signature_base64 = base64_encode ( $signature );
            // $log->LogInfo ( "签名串为 >" . $signature_base64 );
            $params ['signature'] = $signature_base64;
        } else {
            // $log->LogInfo ( ">>>>>签名失败<<<<<<<" );
        }
        // $log->LogInfo ( '=====签名报文结束======' );
    }

    private function __verify($params) {
        // global $log;
        // 公钥
        $public_key = $this->getPublicKey();
        $signature_str = $params ['signature'];
        unset ( $params ['signature'] );
        $params_str = $this->__createLinkString ( $params, true, false );
        //echo urldecode( $params_str  );
        // $log->LogInfo ( '报文去[signature] key=val&串>' . $params_str );
        //echo urldecode( $signature_str  );
        $signature = base64_decode (    $signature_str  );
        //  echo date('Y-m-d',time());
        $params_sha1x16 = sha1 ( $params_str, false );
        // $log->LogInfo ( '摘要shax16>' . $params_sha1x16 );
        $isSuccess = openssl_verify ( $params_sha1x16, $signature, $public_key  ,OPENSSL_ALGO_SHA1 );//
        // echo 'signature == '.$isSuccess;
        // $log->LogInfo ( $isSuccess ? '验签成功' : '验签失败' );
        return $isSuccess;
    }

    # Returns one of the constants defined above: CHANNEL_XXX
    abstract public function getChannelId();

    /**
     * For admin.og.local/cli/test_lepay/signature testing
     */
    public function getSignature(&$params){
        $this->__sign($params);
        return $params;
    }

    public function getOrderIdFromParameters($params)
    {
        $this->CI->utils->debug_log('getOrderIdFromParameters', $params);

        $orderId = null;
        //for fixed return url on browser
        if (isset($params['outTradeNo'])) {
            $outTradeNo = $params['outTradeNo'];

            $this->CI->load->model(array('sale_order'));
            $order = $this->CI->sale_order->getSaleOrderBySecureId($outTradeNo);

            $orderId = (!empty($order)) ? $order->id : NULL;
        }

        return $orderId;
    }

    /**
     *
     * detail: a static bank list information
     *
     * note: Reference: sample code, Mobaopay.Config.php
     *
     * @return array
     */
    public function getBankListInfoFallback()
    {
        return array(
            array('label' => 'PC收银台', 'value' => LEPAY_PAYMENT_API),
            array('label' => '手机微信扫码', 'value' => LEPAY_WEIXIN_PAYMENT_API),
            array('label' => '手机支付宝扫码', 'value' => LEPAY_ALIPAY_PAYMENT_API),
        );
    }

    protected function getBankId($direct_pay_extra_info)
    {
        # overwritten in qrcode implementation
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo) && array_key_exists('bank', $extraInfo)) {
                return $extraInfo['bank'];
            } else {
                return null;
            }
        }

        return null;
    }

    /**
     * Using lepay SDK method override parent method
     */
    public function submitPostForm($url, $params, $postJson=false) {

        try {
            $opts = $this->__createLinkString( $params, false, true );
            // $log->LogInfo ( "后台请求地址为>" . $url );
            // $log->LogInfo ( "后台请求报文为>" . $opts );

            $ch = curl_init ();
            curl_setopt ( $ch, CURLOPT_URL, $url );
            curl_setopt ( $ch, CURLOPT_POST, 1 );
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false ); // 不验证证书
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false ); // 不验证HOST
            curl_setopt ( $ch, CURLOPT_SSLVERSION, 1 ); // http://php.net/manual/en/function.curl-setopt.php页面搜CURL_SSLVERSION_TLSv1
            curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
                    'Content-type:application/x-www-form-urlencoded;charset=UTF-8'
            ) );
            curl_setopt ( $ch, CURLOPT_POSTFIELDS, $opts );
            curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
            $response = curl_exec ( $ch );
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errCode = curl_errno($ch);
            $error = curl_error($ch);
            curl_close ( $ch );
            // $log->LogInfo ( "后台返回结果为>" . $response );

            $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

            if($errCode){
                // $log->LogInfo ( "请求失败，报错信息>" . $errmsg );
                return null;
            }

            if( $statusCode != "200"){
                $errmsg = "http状态=" . curl_getinfo($ch, CURLINFO_HTTP_CODE);
                // $log->LogInfo ( "请求失败，报错信息>" . $errmsg );
                return null;
            }

            return $response;
        } catch (Exception $e) {
            $this->CI->utils->error_log('POST failed', $e);
        }
    }

    # -- override common API functions --
    ## Constructs an URL so that the caller can redirect / invoke it to make payment through this API
    ## See controllers/redirect.php for detail.
    ##
    ## Retuns a hash containing these fields:
    ## array(
    ##  'success' => true,
    ##  'type' => self::REDIRECT_TYPE_FORM,  ## constants defined in abstract_payment_api.php
    ##  'url' => $info['url'],
    ##  'params' => $params,
    ##  'post' => true
    ## );
    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null)
    {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $direct_pay_extra_info = $order->direct_pay_extra_info;

        $params = array();
        $params['version'] = self::SDK_VERSION;
        $params['encoding'] = self::REQUEST_ENCODING;
        //signature 會在AcpService裡面產生
        $params['mchId'] = $this->getSystemInfo('key');
        $params['cmpAppId'] = $this->getSystemInfo('secret');
        $params['returnUrl'] = $this->getReturnUrl($orderId);

        // @see https://lepay.unionpay95516.cc/documentPro/#sweepPayment
        $bank_id = $this->getBankId($direct_pay_extra_info);
        $bank_id = (empty($bank_id)) ? $this->getPlatformCode() : $bank_id;
        switch ($bank_id) {
            case LEPAY_WEIXIN_PAYMENT_API:
                $params['payTypeCode'] = $this->getSystemInfo('weixin_code', self::DEFAULT_WEIXIN_CODE);
                if($params['payTypeCode']==self::REQUEST_TYPE_WEB){
                    $params['payTypeCode'] = ($this->utils->is_mobile() ? self::REQUEST_TYPE_WAP : self::REQUEST_TYPE_WEB);
                }
                break;
            case LEPAY_ALIPAY_PAYMENT_API:
                $params['payTypeCode'] = $this->getSystemInfo('alipay_code', self::DEFAULT_ALIPAY_CODE);
                if($params['payTypeCode']==self::REQUEST_TYPE_WEB){
                    $params['payTypeCode'] = ($this->utils->is_mobile() ? self::REQUEST_TYPE_WAP : self::REQUEST_TYPE_WEB);
                }
                break;
            case LEPAY_PAYMENT_API:
            default:
                $params['payTypeCode'] = ($this->utils->is_mobile() ? self::REQUEST_TYPE_WAP : self::REQUEST_TYPE_WEB);
                break;
        }

        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['outTradeNo'] = $order->secure_id;
        $params['tradeTime'] = $orderDateTime->format('YmdHis');
        $params['reqReserved'] = '';
        $params['summary'] = 'deposit';
        $params['summaryDetail'] = 'deposit';
        $params['deviceIp'] = $this->getClientIP();
        $params['buyerId'] = $playerId;


        $this->__sign($params);

        $url = $this->getSystemInfo('url');
        $result_arr = $this->submitPostForm($url, $params, false, $params['outTradeNo']);

        $result_arr = json_decode($result_arr, true);
        if (empty($result_arr)) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => lang('Invalidte API response'),
            );
        }

        //check status first
        if (empty($result_arr) || !isset($result_arr['respCode']) || ((int)$result_arr['respCode'] !== 0)) {
            //wrong status
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => lang('Original error message').': '. @$result_arr['respMsg'],
            );
        }

		# Verify the signature
		if(!$this->__verify($result_arr)) {
			$this->utils->error_log("Signature verification failed");
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => "Signature verification failed",
            );
		}

        $result = [
            'success' => true
        ];
        switch ($bank_id) {
            case LEPAY_WEIXIN_PAYMENT_API:
            case LEPAY_ALIPAY_PAYMENT_API:
                if($params['payTypeCode']== self::REQUEST_TYPE_WEB || $params['payTypeCode']== self::REQUEST_TYPE_WAP){
                    $result['type'] = self::REDIRECT_TYPE_URL;
                    $result['url'] = ($params['payTypeCode'] === static::REQUEST_TYPE_WAP) ? $result_arr['h5OrderInfo'] : $result_arr['webOrderInfo'];
                }else{
                    $result['type'] = self::REDIRECT_TYPE_QRCODE;
                    $result['url'] = $result_arr['qrPath'];
                }
                break;
            case LEPAY_PAYMENT_API:
            default:
                $result['type'] = self::REDIRECT_TYPE_URL;
                $result['url'] = ($params['payTypeCode'] === static::REQUEST_TYPE_WAP) ? $result_arr['h5OrderInfo'] : $result_arr['webOrderInfo'];
                break;
        }

        return $result;
    }

    ## This will be called when the payment is async, API server calls our callback page
    ## When that happens, we perform verifications and necessary database updates to mark the payment as successful
    ## Reference: sample code, callback.php
    public function callbackFromServer($orderId, $params)
    {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    ## This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params)
    {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return array('success' => TRUE,'response_result_id' => $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id)
    {
        $result = array('success' => false, 'return_error' => self::RETURN_FAIL_CODE);
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
            return $result;
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['outTradeNo'], null, null, null, $response_result_id);
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

        return $result;
    }

    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = array(
            'mchId', 'amount', 'outTradeNo', 'payTypeOrderNo', 'orderNo', 'signature'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
                return false;
            }
        }

        // # is signature authentic?
        $whitetip = (array)$this->getSystemInfo('signature_check_whitetip');
        if(!empty($whitetip) && !in_array($this->CI->utils->getIP(), $whitetip)){
            $this->writePaymentErrorLog('Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($this->convertAmountToCurrency($order->amount) !=
            $this->convertAmountToCurrency($fields['amount'] / 100)
        ) {
            $this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null)
    {
        return array('success' => false); # direct pay not supported by this API
    }

    # Hide banklist by default, as this API does not support bank selection during form submit
    public function getPlayerInputInfo()
    {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## After payment is complete, the gateway will send redirect back to this URL
    private function getReturnUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount)
    {
        return number_format($amount * 100, 2, '.', '');
    }

    # -- signatures --
    private function getCustormId($playerId, $P_UserId)
    {
        return $playerId.'_'.md5($P_UserId.'|'.$this->getSystemInfo('key').'|'.$playerId);
    }
}
