<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * YUBAO
 *
 * * YUBAO_ALIPAY_PAYMENT_API, ID: 5652
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.zjyiruibao.net/AliPayment.php
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_yubao extends Abstract_payment_api {
    const RETURN_SUCCESS_MSG   = "success";
    const RESPONSE_SUCCESS_CODE  = "1";
    const PRODUCT_ID = "AliPay";

    public function __construct($params = null) {
        parent::__construct($params);
    }
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'HashKey', 'HashIV');
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
        $params['HashKey'] = $this->getSystemInfo('HashKey');
        $params['HashIV'] = $this->getSystemInfo('HashIV');
        $params['MerTradeID'] = $order->secure_id;
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['MerUserID'] = $playerId;
        $params['Amount'] = $this->convertAmountToCurrency($amount);
        $params['VerifyCode'] = $this->sign($params);
        $this->CI->utils->debug_log('=====================yubao generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {

        $url = $this->getSystemInfo('url');
        $this->CI->utils->debug_log("=====================yubao processPaymentUrlFormPost URL", $url);
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $url,
            'params' => $params,
            'post' => true,
        );
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->CI->utils->debug_log('=====================yubao getOrderIdFromParameters flds', $flds);
        $raw_post_data = file_get_contents('php://input', 'r');
        $flds = json_decode($raw_post_data, true);

        if(isset($flds['MerTradeID'])) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($flds['MerTradeID']);
            return $order->id;
        }
        else {
            $this->utils->debug_log('=====================yubao getOrderIdFromParameters cannot get MerTradeID', $flds);
            return;
        }
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    ## This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================yubao callbackFrom $source params", $params);

        if($source == 'server' ){
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================yubao callbackFrom raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("=====================yubao callbackFrom json_decode params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['MerTradeID'], '', null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_MSG;
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

        $requiredFields = array('RtnCode','MerTradeID','MerUserID','Amount','Validate');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================yubao Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================yubao Signature Error', $fields);
            return false;
        }

        if ($fields['RtnCode'] != self::RESPONSE_SUCCESS_CODE) {
            $this->writePaymentErrorLog('=====================yubao Payment was not successful', $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount);

        if ($fields['Amount'] != $check_amount) {
            $this->writePaymentErrorLog("======================yubao Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        }

        if ($fields['MerTradeID'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================yubao checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }


        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    # Reference: PHP Demo
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    private function createSignStr($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            if( $key == 'VerifyCode' || $key == 'HashKey' || $key == 'HashIV') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= "ValidateKey=".$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $signStr = 'ValidateKey='.$this->getSystemInfo('key').'&HashKey='.$this->getSystemInfo('HashKey');

        foreach($params as $key => $value) {
            switch ($key) {
                case 'RtnCode':
                    $signStr .= "&RtnCode=$value";
                    break;
                case 'MerTradeID':
                    $signStr .= "&TradeID=$value";
                    break;
                case 'MerUserID':
                    $signStr .= "&UserID=$value";
                    break;
                case 'Amount':
                    $signStr .= "&Money=$value";
                    break;
                default:
                    break;
            }
        }

        $sign = md5($signStr);
        if($params['Validate'] == $sign)
            return true;
        else
            return false;
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
        return number_format($amount, 0, '.', '');
    }
}

