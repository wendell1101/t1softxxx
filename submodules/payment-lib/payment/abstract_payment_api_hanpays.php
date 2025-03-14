<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * HANPAYS
 *
 * * HANPAYS_ALIPAY_PAYMENT_API, ID: 5779
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.hanpays.co/data/api/hanshi/receivables
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_hanpays extends Abstract_payment_api {
    const PAYTYPE_ALIPAY = 'alipay';
    const PAYTYPE_BANKCARD = 'alipayCard';
    const PAYTYPE_ONLINEBANK = 'gateway';
    const CALLBACK_SUCCESS = '1';
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
        $params['scode'] = $this->getSystemInfo('account');
        $params['orderid'] = $order->secure_id;
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['productname'] = 'deposit';
        $params['memo'] = 'deposit';
        $params['currcode'] = 'CNY';
        $params['amount']  = floatval($this->convertAmountToCurrency($amount));
        $params['return_url'] = $this->getReturnUrl($orderId);
        $params['callback'] = $this->getNotifyUrl($orderId);
        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log('=====================HANPAYS generatePaymentUrlForm params', $params);
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

        $this->CI->utils->debug_log("=====================HANPAYS callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================HANPAYS raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================HANPAYS json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderid'], null, null, null, $response_result_id);
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
            'orderid', 'status', 'amount','realAmount','sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================HANPAYS checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================HANPAYS checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================HANPAYS checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != floatval($this->convertAmountToCurrency($order->amount))) {
            $this->writePaymentErrorLog("======================HANPAYS checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['orderid'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================HANPAYS checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $signStr = '';
        $signStr = $params['scode'].'|'.$params['orderid'].'|'.$params['amount'].'|'.$params['currcode'].'|'.$params['callback'].'&'.$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $signStr = '';
        $signStr = $this->getSystemInfo('account').'|'.$params['orderid'].'|'.$params['amount'].'|'.$params['status'].'|'.$params['realAmount'].'&'.$this->getSystemInfo('key');
        $sign = md5($signStr);
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    # -- Private functions --
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    # -- private helper functions --
    protected function getBankListInfoFallback() {
        return array(
            array('value' => '0105', 'label' => '中国农业银行'),
            array('value' => '0111', 'label' => '北京银行'),
            array('value' => '0110', 'label' => '中国银行'),
            array('value' => '0129', 'label' => '交通银行'),
            array('value' => '0128', 'label' => '上海银行'),
            array('value' => '0103', 'label' => '中国建设银行'),
            array('value' => '0109', 'label' => '中国光大银行'),
            array('value' => '0107', 'label' => '兴业银行'),
            array('value' => '0101', 'label' => '招商银行'),
            array('value' => '0106', 'label' => '民生银行'),
            array('value' => '0118', 'label' => '中信银行'),
            array('value' => '0131', 'label' => '广发银行'),
            array('value' => '0123', 'label' => '华夏银行'),
            array('value' => '0102', 'label' => '中国工商银行'),
            array('value' => '0114', 'label' => '平安银行'),
            array('value' => '0117', 'label' => '中国邮政储蓄银行'),
            array('value' => '0116', 'label' => '上海农商银行'),
            array('value' => '0104', 'label' => '上海浦东发展银行'),
            array('value' => '0135', 'label' => '浙商银行')
        );
    }
}