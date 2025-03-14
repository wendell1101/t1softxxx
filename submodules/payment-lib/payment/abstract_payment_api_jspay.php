<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * JSPAY 金顺支付
 *
 * * JSPAY_PAYMENT_API, ID: 5165
 * *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Key: ## Terminal ID##
 * * URL: http://js.011vip.cn:9090/jspay/payGateway.htm
 * * Extra Info:
 * > {
 * >    "jspay_priv_key": "## Private Key ##",
 * >    "jspay_pub_key": "## Public Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_jspay extends Abstract_payment_api {

    const RESULT_CODE_SUCCESS = '000';
    const RESULT_MSG_SUCCESS = '处理成功';
    const CALLBACK_SUCCESS = '01';

    const RETURN_SUCCESS_CODE = 'success';


    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'jspay_pub_key', 'jspay_priv_key');
        return $secretsInfo;
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();
        $params['application']          = "SubmitOrder";
        $params['version']              = "1.0.1";
        $params['merchantId']           = $this->getSystemInfo('account');
        $params['merchantOrderId']      = $order->secure_id;
        $params['merchantOrderAmt']     = $this->convertAmountToCurrency($amount);
        $params['merchantPayNotifyUrl'] = $this->getNotifyUrl($orderId);
        $params['merchantFrontEndUrl']  = $this->getReturnUrl($orderId);
        $params['accountType']          = "0";
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['orderTime']            = $orderDateTime->format('Ymdhis');
        $params['rptType']              = "1";
        $params['payMode']              = "0";
        $params['sign']                 = $this->sign($params);

        $this->CI->utils->debug_log('=====================jspay generatePaymentUrlForm params', $params);
        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormQRCode($params) {
        $url = $this->getSystemInfo('url');
        $msg = $params['sign'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $this->setCurlProxyOptions($ch);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeoutSecond());
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());
        $response = curl_exec($ch);

        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content     = substr($response, $header_size);
        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params, 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        if(stripos($response, 'xml') !== false){
            $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['merchantOrderId']);
        }
        else{
            $response_result_id = $this->submitPreprocess($params, $content, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['merchantOrderId']);
        }

        if(stripos($response, 'xml') !== false){
            $result = explode("|", $response);
            $resp_xml = base64_decode($result[0]);
            $result = $this->loadXmlResp($resp_xml);
            $result = $result['@attributes'];
            $this->CI->utils->debug_log('=====================jspay processPaymentUrlFormQRCode resp_xml', $result);

            if($result['respCode'] == self::RESULT_CODE_SUCCESS && $result['respDesc'] == self::RESULT_MSG_SUCCESS) {
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $result,
                );
            }
            else if(isset($result['respCode'])) {
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => '['.$result['respCode'].']'.$result['respDesc']
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
        elseif(stripos($response, '<html>') !== false){
            echo $content;
        }
        else{
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $content
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
        $this->CI->utils->debug_log("=====================jspay callbackFrom $source params", $params);

        if($source == 'server'){
            if(empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================jspay callbackFrom $source raw_post_data", $raw_post_data);
                $response  = explode("|", $raw_post_data);
                $resp_xml  = base64_decode($response[0]);
                $resp_sign = $response[1];
                $params    = $this->loadXmlResp($resp_xml);
                $params    = $params['@attributes'];
                $this->CI->utils->debug_log('=====================jspay callbackFrom $source parsed resp_xml', $params);
            }

            # is signature authentic?
            if (!$this->validateSign($resp_xml, $resp_sign)) {
                $this->writePaymentErrorLog('=====================jspay checkCallbackOrder Signature Error', $fields);
                return $result;
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

            $this->CI->sale_order->updateExternalInfo($order->id, $fields['payOrderId'], null, null, null, $response_result_id);
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
            'merchantOrderId', 'merId', 'version', 'encParam'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================jspay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass


        if ($fields['deductList']['payStatus'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================jspay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['deductList']['payAmt'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================jspay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['merchantOrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================jspay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    protected function getBankListInfoFallback() {
        return array(
            array('label' => '中国工商银行', 'value' => 'ICBC'),
            array('label' => '中国农业银行', 'value' => 'ABC'),
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '中国建设银行', 'value' => 'CCB'),
            array('label' => '中信银行', 'value' => 'CNCB'),
            array('label' => '交通银行', 'value' => 'BOCOM'),
            array('label' => '中国光大银行', 'value' => 'CEB'),
            array('label' => '华夏银行', 'value' => 'HXB'),
            array('label' => '上海浦东发展银行', 'value' => 'SPDB'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '广发银行', 'value' => 'GDB'),
            array('label' => '平安银行', 'value' => 'PAB'),
            array('label' => '招商银行', 'value' => 'CMB'),
            array('label' => '汉口银行', 'value' => 'HKBCHINA'),
            array('label' => '南京银行', 'value' => 'NJBC'),
            array('label' => '宁波银行', 'value' => 'NBBC'),
            array('label' => '温州银行', 'value' => 'WZCB'),
            array('label' => '上海银行', 'value' => 'BOS'),
            array('label' => '广州银行', 'value' => 'GZCB'),
            array('label' => '长沙银行', 'value' => 'CSCB'),
            array('label' => '恒丰银行', 'value' => 'EGBANK'),
            array('label' => '重庆三峡银行', 'value' => 'CCQTGB'),
            array('label' => '上海农商银行', 'value' => 'SHRCB'),
            array('label' => '北京农村商业银行', 'value' => 'BRCB'),
            array('label' => '浙商银行', 'value' => 'ZSBC'),
            array('label' => '广州农村商业银行', 'value' => 'GNXS'),
            array('label' => '渤海银行', 'value' => 'BOHC'),
            array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
            array('label' => '晋商银行', 'value' => 'SXJS'),
            array('label' => '北京银行', 'value' => 'BCCB'),
            array('label' => '尧都农商银行', 'value' => 'YDXH'),
            array('label' => '集友银行', 'value' => 'CYB'),
            array('label' => '深圳农商行', 'value' => 'SNXS'),
            array('label' => '杭州市商业银行', 'value' => 'HCCB'),
            array('label' => '稠州银行', 'value' => 'CZCB'),
            array('label' => '渣打银行', 'value' => 'SCB'),
            array('label' => '顺德农村商业银行', 'value' => 'SDE'),
            array('label' => '徽商银行', 'value' => 'HSBANK'),
            array('label' => 'BEA东亚银行', 'value' => 'BEAI'),
            array('label' => '民生银行', 'value' => 'CMBC'),
            array('label' => '湖南省农村信用社联合社', 'value' => 'HNNXS'),
            array('label' => '珠海农村信用合作社联社', 'value' => 'ZHNX'),
        );
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount*100, 0, '.', '');
    }

    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    # -- signing --
    # source: GatewayDemoPhp
    protected function sign($params) {
        $xml = $this->toXmlStr($params);
        $xmlStr = base64_encode($xml);

        openssl_sign(md5($xml, true), $signature, $this->getPrivKey());
        $signStr = base64_encode($signature);
        $msg = $xmlStr . "|" . $signStr;

        return $msg;
    }

    protected function validateSign($resp_xml, $resp_sign) {
        $valid = openssl_verify(md5($resp_xml, true), base64_decode($resp_sign), $this->getPubKey());
        return $valid;
    }

    protected function toXmlStr($params) {
        $xml = '<?xml version="1.0" encoding="utf-8" standalone="no"?><message ';
        foreach ($params as $key => $value) {
            $xml .= $key.'="'. $value .'" ';
        }
        $xml .= '/>';
        return $xml;
    }

    public function loadXmlResp($resultXml) {
        $xml_object = simplexml_load_string($resultXml);
        $xml_array = $this->object2array($xml_object);
        return $xml_array;
    }

    public function object2array($object) {
        return @json_decode(@json_encode($object),1);
    }


    private function getPubKey() {
        $jspay_pub_key = $this->getSystemInfo('jspay_pub_key');
        $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($jspay_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($pub_key);
    }

    private function getPrivKey() {
        $jspay_priv_key = $this->getSystemInfo('jspay_priv_key');
        $priv_key = '-----BEGIN PRIVATE KEY-----' . PHP_EOL . chunk_split($jspay_priv_key, 64, PHP_EOL) . '-----END PRIVATE KEY-----' . PHP_EOL;
        return openssl_pkey_get_private($priv_key);
    }
}