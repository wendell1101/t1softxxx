<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * ANGELPAY
 * https://angelpay168.com
 *
 * * ANGELPAY_PAYMENT_API, ID: 5018
 * * ANGELPAY_ALIPAY_PAYMENT_API, ID: 5019
 * * ANGELPAY_ALIPAY_H5_PAYMENT_API, ID: 5020
 * * ANGELPAY_WEIXIN_PAYMENT_API, ID: 5021
 * * ANGELPAY_WEIXIN_H5_PAYMENT_API, ID: 5022
 * * ANGELPAY_QQPAY_PAYMENT_API, ID: 5023
 * * ANGELPAY_QQPAY_H5_PAYMENT_API, ID: 5024
 * * ANGELPAY_JDPAY_PAYMENT_API, ID: 5025
 * * ANGELPAY_JDPAY_H5_PAYMENT_API, ID: 5026
 * * ANGELPAY_UNIONPAY_PAYMENT_API, ID: 5027
 * * ANGELPAY_QUICKPAY_PAYMENT_API, ID: 5028
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://angtz.com/api/pay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_angelpay extends Abstract_payment_api {

    const CHANNEL_ONLINEBANK = 'onlinebank';
    const CHANNEL_ALIPAY     = 'alipay_qrcode';
    const CHANNEL_ALIPAY_H5  = 'alipay_app';
    const CHANNEL_WEIXIN     = 'wechat_qrcode';
    const CHANNEL_WEIXIN_H5  = 'wechat_app';
    const CHANNEL_QQPAY      = 'qq_qrcode';
    const CHANNEL_QQPAY_H5   = 'qq_app';
    const CHANNEL_JDPAY      = 'jd_qrcode';
    const CHANNEL_JDPAY_H5   = 'jd_app';
    const CHANNEL_UNIONPAY   = 'yl_qrcode';
    const CHANNEL_QUICKPAY   = 'yl_nocard';

    const DEVICE_PC    = 'web';
    const DEVICE_PHONE = 'wap';

    const CALLBACK_SUCCESS = 'SUCCESS';
    const RETURN_SUCCESS_CODE = 'OK';


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

        $this->CI->load->model(array('player'));
        $order  = $this->CI->sale_order->getSaleOrderById($orderId);
        $player = $this->CI->player->getPlayerById($playerId);

        $params = array();
        $params['customerno']       = $this->getSystemInfo('account');
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['customerbillno']   = $order->secure_id;
        $params['orderamount']      = $this->convertAmountToCurrency($amount);
        $params['customerbilltime'] = $orderDateTime->format('Y-m-d H:i:s');
        $params['notifyurl']        = $this->getNotifyUrl($orderId);
        $params['returnurl']        = $this->getReturnUrl($orderId);
        $params['ip']               = $this->getClientIP();
        $params['devicetype']       = $this->CI->utils->is_mobile() ? self::DEVICE_PHONE : self::DEVICE_PC;
        $params['customeruser']     = $player['username'];
        $params['sign']             = $this->sign($params);

        $this->CI->utils->debug_log("======================angelpay generatePaymentUrlForm params", $params);
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

        $this->CI->utils->debug_log("=======================angelpay callbackFrom $source params", $params);

        if($source == 'server'){
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderno'], null, null, null, $response_result_id);
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
            'orderno', 'customerno', 'customerbillno', 'customerbilltime', 'preorderamount', 'orderamount', 'paystatus', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================angelpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog("=======================angelpay checkCallbackOrder Signature Error", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['paystatus'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("=======================angelpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['orderamount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=======================angelpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['customerbillno'] != $order->secure_id) {
            $this->writePaymentErrorLog("=======================angelpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $sign = md5($signStr);
        return $sign;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if(empty($value) || $key == 'sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= "key=".$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        if($params['sign'] == $sign){
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
        return number_format($amount, 2, '.', '');
    }
}