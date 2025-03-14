<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * SPEED
 *
 * * SPEED_PAYMENT_API, ID: 6085
 *
 * Required Fields:
 *
 * * URL:https://mgp-pay.com:8443/api/pay/V2
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_speed extends Abstract_payment_api {
const REPONSE_CODE_SUCCESS = '0';
const ORDER_STATUS_SUCCESS = "1";
const RETURN_SUCCESS_CODE = "SUCCESS";
const RETURN_FAILED_CODE = "FAIL";


    # Implement these for specific pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }
        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params['version'] = $this->getSystemInfo('version')? $this->getSystemInfo('version') : 'V2';
        $params['signType'] = "MD5";
        $params['merchantNo'] = $this->getSystemInfo("account");
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['noticeUrl'] = $this->getNotifyUrl($orderId);
        $params['orderNo'] = $order->secure_id;;
        $params['bizAmt'] = $this->convertAmountToCurrency($amount);
        $params['date'] = date("YmdHis");
        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log("=====================speed generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['orderNo']);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================speed processPaymentUrlFormPost response json to array', $response);

        $msg = lang('Invalidate API response');
        if( isset($response['code']) && $response['code'] == self::REPONSE_CODE_SUCCESS ){
            if(isset($response['detail']['PayURL']) && !empty($response['detail']['PayURL'])){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['detail']['PayURL'],
                );
            }if(isset($response['detail']['PayHtml']) && !empty($response['detail']['PayHtml'])){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['detail']['PayHtml'],
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => $msg."callback:".$response['msg']
                );
            }
        }else {
            if(isset($response['error']) && !empty($response['error'])) {
                $msg = $response['error'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $msg."callback:".$response['msg']
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

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $this->CI->utils->debug_log("=====================speed callbackFrom $source params", $params);

        if($source == 'server' ){
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderNo'], null, null, null, $response_result_id);
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
            $result['return_error'] = self::RETURN_FAILED_CODE;
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

    private function checkCallbackOrder($order, $fields, &$processed)
    {
        # does all required fields exist?
        $requiredFields = array('orderNo', 'orderAmt', 'bizAmt', 'status', 'sign');
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                    $this->writePaymentErrorLog("=========================speed checkCallbackOrder missing parameter: [$f]", $fields);
                    return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog("=========================speed checkCallbackOrder Signature Error", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass
        if (($fields['status'] != self::ORDER_STATUS_SUCCESS) ) {
            $this->writePaymentErrorLog("=========================speed checkCallbackOrder returncode was not successful", $fields);
           return false;
        }

        if ($fields['orderNo'] != $order->secure_id) {
            $expectedOrderId=$fields['orderNo'];
            $this->writePaymentErrorLog("=========================speed checkCallbackOrder Order IDs do not match, expected [$expectedOrderId]", $fields);
           return false;
        }

        if ($fields['bizAmt'] != $this->convertAmountToCurrency($order->amount)) {
            #because player need to enter amount at Alipay
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $this->CI->utils->debug_log('=====================speed amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['bizAmt'], $notes);
            }
            else{
                $this->writePaymentErrorLog("======================speed checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
                return false;
            }
        }
      # everything checked ok
      return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
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
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        $return_amount = number_format($amount * $convert_multiplier,2, '.', '') ;

        return $return_amount ;
    }


    public function sign($params) {
        $signStr = $this->createSignStr($params);
        // $this->CI->utils->debug_log("=====================speed sign signStr", $signStr);
        $sign = md5($signStr);
        // $this->CI->utils->debug_log("=====================speed sign", $sign);
        return $sign;
    }

    public function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if(empty($value) || $key == 'sign') {
                continue;
            }else {
                $signStr .=$key."=".$value."&";
            }
        }
        $signStr = rtrim($signStr, '&');
		$signStr .= $this->getSystemInfo('key');
        // $this->CI->utils->debug_log("=====================speed createSignStr", $signStr);

        return $signStr;
    }

    public function validateSign($params) {
        $sign = $params['sign'];
        unset($params['sign']);
        $signStr = $this->createSignStr($params);
        $signStr = md5($signStr);
        // $this->CI->utils->debug_log("=====================speed validateSign", $signStr);
        if($signStr == $sign) {
            return true;
        }else {
            return false;
        }
    }

    protected function getBankListInfoFallback() {
        $banklist=$this->getSystemInfo('bank_list');
        if(empty($banklist)){
            $banklist=array(
                array('label' => lang('BCA'), 'value' => 'YN_BCA_ID'),
                array('label' => lang('BNI'), 'value' => 'YN_BNI_ID'),
                array('label' => lang('BRI'), 'value' => 'YN_BRI_ID'),
                array('label' => lang('BTPN'), 'value' => 'YN_BTPN_ID'),
                array('label' => lang('CIMB_ID'), 'value' => 'YN_CIMB_ID'),
                array('label' => lang('DANAMON'), 'value' => 'YN_DANAMON_ID'),
                array('label' => lang('DANA'), 'value' => 'YN_DANA_ID'),
                array('label' => lang('DOKU'), 'value' => 'YN_DOKU_ID'),
                array('label' => lang('GOJEK'), 'value' => 'YN_GOJEK_ID'),
                array('label' => lang('JAGO'), 'value' => 'YN_JAGO_ID'),
                array('label' => lang('LinkAja'), 'value' => 'YN_LINKAJA_ID'),
                array('label' => lang('MANDIRI'), 'value' => 'YN_MANDIRI_ID'),
                array('label' => lang('MANDIRI_ENT'), 'value' => 'YN_MANDIRI_ID_ENT'),
                array('label' => lang('MAYBANK'), 'value' => 'YN_MAYBANK_ID'),
                array('label' => lang('OCBC'), 'value' => 'YN_OCBC_ID'),
                array('label' => lang('OVO'), 'value' => 'YN_OVO_ID'),
                array('label' => lang('PANIN'), 'value' => 'YN_PANIN_ID'),
                array('label' => lang('PERMATA'), 'value' => 'YN_PERMATA_ID'),
                array('label' => lang('QRIS'), 'value' => 'YN_QRIS_ID'),
                array('label' => lang('Telkomsel'), 'value' => 'YN_TELKOMSEL_ID'),
                array('label' => lang('UOB'), 'value' => 'YN_UOB_ID'),
                array('label' => lang('XL'), 'value' => 'YN_XL_ID'),
            );
            
        }
        return $banklist;
    }
}
