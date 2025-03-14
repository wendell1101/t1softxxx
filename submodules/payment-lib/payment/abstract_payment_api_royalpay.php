<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * Royal PAY royalpay
 * *
 * * ROYALPAY_PAYMENT_API, ID: 6024
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:https://api.bee-earning.com/order/order/submit
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_royalpay extends Abstract_payment_api {

    const PAYTYPE_BANK    = 1;
    const RESULT_CODE_FAILED  = '1111';
    const RESULT_CODE_SUCCESS = '0000';
    const CALLBACK_CODE_SUCCESS  = 1;
    const RETURN_SUCCESS_CODE = 'OK';

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

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);

        $firstname = (!empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : '';
        $lastname  = (!empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName']      : '';
        $emailAddr = (!empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : '';
        $phone     = (!empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '';

        $params = array();
        $params['amount']      = $this->convertAmountToCurrency($amount);
        $params['channelId']   = $this->getSystemInfo('account');
        $params['channleOid']  = $order->secure_id;
        $params['email']       = $emailAddr;
        $params['firstName']   = $firstname;
        $params['mobile']      = $phone;
        $params['notifyUrl']   = $this->getNotifyUrl($orderId);
        $params['remark']      = 'Topup';
        $params['timestamp']   = (int)$this->getMillisecond();
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign']        = $this->sign($params);
        $this->CI->utils->debug_log('=====================royalpay generatePaymentUrlForm params', $params);
        return $this->processPaymentUrlForm($params);
    }

    public function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return $t2 .   ceil( ($t1 * 1000) );
    }

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['channleOid']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================royalpay processPaymentUrlFormQRCode response', $response);

        if($response['code'] == self::RESULT_CODE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data']['payUrl']
            );
        }
        else if($response['code'] == self::RESULT_CODE_FAILED) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['message'].': '.$response['code']
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

    // protected function getBankListInfoFallback() {
    //     return array(
    //         array('value' => 'AGR', 'label' => 'AGRIBANK'),
    //         array('value' => 'BIDV', 'label' => 'BIDV BANK'),
    //         array('value' => 'EIB', 'label' => 'EXIMBANK'),
    //         array('value' => 'GPB', 'label' => 'GP BANK'),
    //         array('value' => 'HDB', 'label' => 'HD BANK'),
    //         array('value' => 'MB', 'label' => 'MBBANK'),
    //         array('value' => 'NAB', 'label' => 'NAMA BANK'),
    //         array('value' => 'ACB', 'label' => 'NGAN HANG A CHAU'),
    //         array('value' => 'VAB', 'label' => 'VAB BANK'),
    //         array('value' => 'OJB', 'label' => 'OCEANBANK'),
    //         array('value' => 'PGB', 'label' => 'PGBANK'),
    //         array('value' => 'OCB', 'label' => 'PHUONGDONG BANK'),
    //         array('value' => 'STB', 'label' => 'SACOMBANK'),
    //         array('value' => 'SGB', 'label' => 'SAIGONBANK'),
    //         array('value' => 'SCB', 'label' => 'SCB'),
    //         array('value' => 'SHB', 'label' => 'SHB BANK'),
    //         array('value' => 'TCB', 'label' => 'TECHCOMBANK'),
    //         array('value' => 'TPB', 'label' => 'TIENPHONG BANK'),
    //         array('value' => 'VIB', 'label' => 'VIB BANK'),
    //         array('value' => 'VCB', 'label' => 'VIETCOMBANK'),
    //         array('value' => 'CTG', 'label' => 'VIETINBANK'),
    //         array('value' => 'VPB', 'label' => 'VPBANK'),
    //         array('value' => 'ABB-K', 'label' => 'ABBANK'),
    //         array('value' => 'Dong', 'label' => 'DongABank')
    //     );
    // }

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

        $this->CI->utils->debug_log("=====================royalpay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['channleOid'], '', null, null, $response_result_id);
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
            'amount', 'channelId', 'channleOid', 'status', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================royalpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================royalpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_CODE_SUCCESS) {
            $payStatus = $fields['result'];
            $this->writePaymentErrorLog("=====================royalpay checkCallbackOrder Payment status was not successful, payStatus is [$payStatus]", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================royalpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['channleOid'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================royalpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        $signStr = $params['channelId'].$params['channleOid'].$params['amount'].$this->getSystemInfo('key');
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
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 2, '.', '') ;
    }
}