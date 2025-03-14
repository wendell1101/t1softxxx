<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * worldpay
 *
 * * WORLDPAY_PAYMENT_API, ID: 6233
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://mio.oceanp168.com/api/createOrder
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_worldpay extends Abstract_payment_api {
    const CURRENCY_CNY         = 1;
    const CHANNEL_BANK         = 3;
    const CALLBACK_SUCCESS     = 2;
    const REPONSE_CODE_SUCCESS = true;
    const RETURN_SUCCESS_CODE  = 'ok';

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('accept:application/json','Content-Type:application/json');
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
        $params['Amount']             = $this->convertAmountToCurrency($amount);
        $params['CurrencyId']         = $this->getSystemInfo('currency', self::CURRENCY_CNY);
        $params['IsTest']             = "false";
        $params['PayerKey']           = $playerId.'-'.$this->uuid();
        $params['PaymentChannelId']   = $this->getSystemInfo('Channel', self::CHANNEL_BANK);
        $params['ShopInformUrl']      = $this->getNotifyUrl($orderId);
        $params['ShopOrderId']        = $order->secure_id;
        $params['ShopReturnUrl']      = $this->getReturnUrl($orderId);
        $params['ShopUserLongId']     = $this->getSystemInfo("account");
        $params['EncryptValue']       = $this->sign($params);

        $this->CI->utils->debug_log("=====================worldpay  generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['ShopOrderId']);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================worldpay processPaymentUrlFormPost response json to array', $response);

        $msg = lang('Invalidate API response');
        if( isset($response['Success']) && $response['Success'] == self::REPONSE_CODE_SUCCESS ){
            if(isset($response['PayUrl']) && !empty($response['PayUrl'])){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['PayUrl']
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => $msg
                );
            }
        }else {
            if(isset($response['ErrorMessage']) && !empty($response['ErrorMessage'])) {
                $msg = $response['ErrorMessage'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $msg
            );
        }
    }

    ## This will be called when the payment is async, API server calls our callback page
    ## When that happens, we perform verifications and necessary database updates to mark the payment as successful
    ## Reference: sample code, callback.php
    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    ## This will be called when user redirects back to our page from payment API
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
        $this->CI->utils->debug_log("=====================worldpay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['ShopOrderId'], '', null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'Amount', 'AmountPaid', 'CurrencyId', 'OrderStatusId', 'EncryptValue', 'PaymentChannelId', 'ShopOrderId'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================worldpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================worldpay checkCallbackOrder Signature Error', $fields['EncryptValue']);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['OrderStatusId'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================worldpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['Amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================worldpay Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['ShopOrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================worldpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    # Reference: PHP Demo
    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper($signStr);
        return $sign;
    }

    public function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'IsTest' && is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            if($key == 'EncryptValue' || !isset($value)) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'HashKey='.$this->getSystemInfo('key');
        return hash('sha256', strtolower($signStr));
    }

    public function validateSign($params) {
        $signature = $params['EncryptValue'];
        unset($params['EncryptValue']);
        $sign = $this->sign($params);
        if ( $signature == $sign ) {
            return true;
        } else {
            return false;
        }    
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
        return floatval(number_format($amount, 2, '.', ''));
    }

    public function uuid(){
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s', str_split(bin2hex($data), 4));
	}

}