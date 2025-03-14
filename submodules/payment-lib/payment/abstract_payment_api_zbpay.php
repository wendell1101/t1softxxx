<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ZBPAY 众宝支付
 * https://merchant.zbpay365.com/
 *
 * * ZBPAY_PAYMENT_API, ID: 221
 * * ZBPAY_ALIPAY_PAYMENT_API, ID: 222
 * * ZBPAY_WEIXIN_PAYMENT_API, ID: 223
 * * ZBPAY_QQPAY_PAYMENT_API, ID: 395
 * * ZBPAY_JDPAY_PAYMENT_API, ID: 641
 * * ZBPAY_UNIONPAY_PAYMENT_API, ID: 642
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Secret Key
 *
 * Field Values:
 *
 * * URL: https://gateway.zbpay365.com/GateWay/Pay
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_zbpay extends Abstract_payment_api {
    const RETURN_SUCCESS_CODE = 'success';
    const PAYMENT_RESULT_SUCCESS = 1;

    const PAYTYPE_WEIXIN    = "1000";
    const PAYTYPE_WEIXIN_H5 = "1002";
    const PAYTYPE_ALIPAY    = "1003";
    const PAYTYPE_ALIPAY_H5 = "1004";
    const PAYTYPE_QQPAY     = "1005";
    const PAYTYPE_QQPAY_H5  = "1006";
    const PAYTYPE_JDPAY     = "1007";
    const PAYTYPE_JDPAY_H5  = "1008";
    const PAYTYPE_UNIONPAY  = "1009";

    public function __construct($params = null) {
        parent::__construct($params);
    }

    protected abstract function configParams(&$params, $direct_pay_extra_info);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();
        $params['merchantid'] = $this->getSystemInfo("account");
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['orderid'] = $order->secure_id;
        $params['notifyurl'] = $this->getNotifyUrl($orderId);
        $params['request_time'] = date('YmdHis');
        $params['returnurl'] = $this->getReturnUrl($orderId);
        $params['israndom'] = $this->getSystemInfo("israndom") ? $this->getSystemInfo("israndom") : "N" ; #如果值为Y，则启用订单风控保护规则,不传递此参数默认为启用，即Y，如无需启用请传入N; 传入Y会随机调整金额范围为0.1-0.9之间一位小数
        $this->configParams($params, $order->direct_pay_extra_info); # set banktype

        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log('=====================zbpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    # Implement processPaymentUrlForm
    protected function processPaymentUrlFormPost($params) {
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $this->getSystemInfo('url'),
            'params' => $params,
            'post' => true,
        );
    }

    protected function processPaymentUrlFormQRCode($params) {}

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    public function callbackFromBrowser($orderId, $params) {
        # Parameters callback from browser is discarded
        return array('success' => true, 'next_url' => $this->getPlayerBackUrl(), 'go_success_page' => true);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================zbpay params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['systemorderid'], '', null, null, $response_result_id);
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

    # returns true if callback is valid and payment is successful
    # sets the $callbackValid parameter if callback is valid
    private function checkCallbackOrder($order, $fields, &$callbackValid) {
        # does all required fields exist?
        $requiredFields = array(
            'orderid', 'result', 'amount', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================zbpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================zbpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $callbackValid = true; # callbackValid is set to true once the signature verification pass

        if ($fields['result'] != self::PAYMENT_RESULT_SUCCESS) {
            $this->writePaymentErrorLog('Payment was not successful', $fields);
            return false;
        }

        if(isset($fields['sourceamount']) && ($this->convertAmountToCurrency($order->amount) != $fields['sourceamount'])){
            $this->writePaymentErrorLog("======================zbpay checkCallbackOrder Payment sourceamount is wrong, expected [$order->amount] +- 0.1~0.9", $fields);
            return false;

        }
        else if($this->convertAmountToCurrency($order->amount) != $fields['amount']) {
            $this->writePaymentErrorLog("======================zbpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['orderid'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================zbpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- private helper functions --
    protected function getBankListInfoFallback() {
        return array(
            array('label' => 'PC 微信扫码', 'value' => '1004'),
            array('label' => '手机微信扫码', 'value' => '1007'),
            array('label' => '手机支付宝扫码', 'value' => '1006'),
            array('label' => 'PC 支付宝扫码', 'value' => '992'),
            array('label' => 'QQ 扫码', 'value' => '993'),
            array('label' => '中信银行', 'value' => '962'),
            array('label' => '中国银行', 'value' => '963'),
            array('label' => '中国农业银行', 'value' => '964'),
            array('label' => '中国建设银行', 'value' => '965'),
            array('label' => '中国工商银行', 'value' => '967'),
            array('label' => '招商银行', 'value' => '970'),
            array('label' => '邮政储蓄', 'value' => '971'),
            array('label' => '兴业银行', 'value' => '972'),
            array('label' => '上海农村商业银行', 'value' => '976'),
            array('label' => '浦东发展银行', 'value' => '977'),
            array('label' => '南京银行', 'value' => '979'),
            array('label' => '民生银行', 'value' => '980'),
            array('label' => '交通银行', 'value' => '981'),
            array('label' => '杭州银行', 'value' => '983'),
            array('label' => '广东发展银行', 'value' => '985'),
            array('label' => '光大银行', 'value' => '986'),
            array('label' => '东亚银行', 'value' => '987'),
            array('label' => '北京银行', 'value' => '989'),
        );
    }

    private function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    # -- signing --
    private function sign($params) {
        $keys = array('merchantid', 'paytype', 'amount', 'orderid', 'notifyurl', 'request_time');
        $signStr = '';
        foreach($keys as $key) {
            $signStr .= $key.'='.$params[$key].'&';
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        $sign = md5($signStr);
      
        return $sign;
    }

    private function validateSign($params) {
        $keys = array('orderid', 'result', 'amount', 'systemorderid', 'completetime');
        $signStr = '';
        foreach($keys as $key) {
            $signStr .= $key.'='.$params[$key].'&';
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        $sign = md5($signStr);
        $valid = (0 === strcasecmp($params['sign'], $sign));
     
        return $valid;
    }
}
