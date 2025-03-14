<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * SEVPAY
 * * http://merchant.777office.com/
 *
 * * SEVPAY_PAYMENT_API, ID: 910
 * * SEVPAY_QUICKPAY_PAYMENT_API, ID: 911
 * * SEVPAY_ALIPAY_PAYMENT_API, ID: 912
 * * SEVPAY_WITHDRAWAL_PAYMENT_API, ID: 5057
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.paynow777.com/merchanttransfer
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_sevpay extends Abstract_payment_api {

    const BANK_QUICKPAY   = "DBCARD";
    const BANK_JDPAY      = "JDPay";
    const BANK_QQPAY      = "QQpay";
    const BANK_WEIXIN     = "Wechat";
    const BANK_ALIPAY     = "Alipay";

    const CALLBACK_SUCCESS = '000';
    const RETURN_SUCCESS_CODE = 'true';


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
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $player = $this->CI->player->getPlayerById($playerId);

        $params = array();
        $params['Merchant']  = $this->getSystemInfo('account');
        $params['Currency']  = $this->getSystemInfo('currency', 'CNY'); #CNY IDR
        $params['Reference'] = $order->secure_id;
        $params['Amount']    = $this->convertAmountToCurrency($amount);
        $params['timestamp'] = $orderDateTime->format('YmdHis');
        $params['Datetime']  = $orderDateTime->format('Y-m-d h:i:sA');
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['Language']  = $this->getSystemInfo('language', 'zh-cn'); #bur: Burmese, en-us: English, id-id: Indonesian, ms-my: Malay, th: Thai, vi-vn: Vietnamese, zh-cn: Simplified Chinese
        $params['FrontURI']  = $this->getReturnUrl($orderId);
        $params['BackURI']   = $this->getNotifyUrl($orderId);
        $params['ClientIP']  = $this->getClientIP();
        $params['Customer']  = $player['username'];
        $params['Key']       = $this->sign($params);
        unset($params['timestamp']);
        $this->CI->utils->debug_log('=====================sevpay generatePaymentUrlForm params', $params);
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
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================sevpay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['ID'], '', null, null, $response_result_id);
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
            'Merchant', 'Reference', 'Amount', 'Currency', 'Status', 'Key'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================sevpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================sevpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass


        if ($fields['Status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================sevpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['Amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================sevpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['Reference'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================sevpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    public function sign($params) {
        $signStr =
            $params['Merchant'].$params['Reference'].$params['Customer'].$params['Amount'].
            $params['Currency'].$params['timestamp'].
            $this->getSystemInfo('key').$params['ClientIP'];
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    private function validateSign($params) {
        $signStr =
            $params['Merchant'].$params['Reference'].$params['Customer'].$params['Amount'].
            $params['Currency'].$params['Status'].
            $this->getSystemInfo('key');
        $sign = strtoupper(md5($signStr));
        if($params['Key'] == $sign){
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

    protected function convertAmountToCurrency($amount){
        $convert_multiplier = $this->getSystemInfo('convert_multiplier') ? $this->getSystemInfo('convert_multiplier') : 1;
        return number_format($amount * $convert_multiplier, 2, '.', '');
    }
}