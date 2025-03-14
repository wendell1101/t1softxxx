<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * GIGLOBAL
 *
 * * GIGLOBAL_ALIPAY_PAYMENT_API, ID: 5595
 * * GIGLOBAL_ALIPAY_H5_PAYMENT_API, ID: 5596
 * * GIGLOBAL_WEIXIN_PAYMENT_API, ID: 5597
 * * GIGLOBAL_QQPAY_PAYMENT_API, ID: 5598
 * * GIGLOBAL_QUICKPAY_PAYMENT_API, ID: 5599
 * * GIGLOBAL_QUICKPAY_H5_PAYMENT_API, ID: 5600
 * * GIGLOBAL_UNIONPAY_PAYMENT_API, ID: 5601
 * * GIGLOBAL_JDPAY_PAYMENT_API, ID: 5602
 * 
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://gp.gi-global.com:817/order/initOrder.aspx
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_giglobal extends Abstract_payment_api {    
    const ORDER_STATUS_SUCCESS  = "2";
    const RETURN_SUCCESS_CODE   = "SUCCESS";        
    const ORDERTYPE_ALIPAY      = "101";
    const ORDERTYPE_ALIPAY_H5   = "102";
    const ORDERTYPE_WEIXIN      = "104";
    const ORDERTYPE_QQPAY       = "108";
    const ORDERTYPE_QUICKPAY    = "111";
    const ORDERTYPE_QUICKPAY_H5 = "112";
    const ORDERTYPE_UNIONPAY    = "113";
    const ORDERTYPE_JDPAY       = "114";
    const ORDERTYPE_BANK        = "117";

    public function __construct($params = null) {
        parent::__construct($params);
    }
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
        $params['version'] = '1.0';
        $params['custid'] = $this->getSystemInfo('account');
        $params['ordercode'] = $order->secure_id;
        $this->configParams($params, $order->direct_pay_extra_info);           
        $params['amount'] = $this->convertAmountToCurrency($amount);        
        $params['backurl'] = $this->getNotifyUrl($orderId);
        $params['fronturl'] = $this->getReturnUrl($orderId);        
        $params['sign'] = $this->sign($params);        
            
        $this->CI->utils->debug_log('=====================giglobal generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
        $url = $this->getSystemInfo('url');        
        $response = $this->submitPostForm($url, $params, true, $params['ordercode']);        
        $decode_data = json_decode($response,true);        
        $msg = lang('Invalidte API response');
        $signature = $this->sign($decode_data);

        if((!empty($decode_data['sign'])) && ($signature == $decode_data['sign'])){
            if(!empty($decode_data['codeurl']) && ($decode_data['code'] == self::RETURN_SUCCESS_CODE)) {
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $decode_data['codeurl'],
                );
            }
        }
        else {
            if(!empty($decode_data['MSG'])) {
                    $msg = $decode_data['MSG'];
                }
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => $msg
            );
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

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================giglobal callbackFrom $source params", $params);
        
        if($source == 'server' ){            
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================giglobal raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("=====================giglobal json_decode params", $params);        
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        $success = true;

        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            # update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $params['custid'], '', null, null, $response_result_id);
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
            'custid','ordercode','amount', 'orderstatus','sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================giglobal Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================giglobal Signature Error', $fields);
            return false;
        }
        
        if ($fields['orderstatus'] != self::ORDER_STATUS_SUCCESS) {
            $this->writePaymentErrorLog('=====================giglobal Payment was not successful', $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount);

        if ($fields['amount'] != $check_amount) {
            $this->writePaymentErrorLog("======================giglobal Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        } 

        if ($fields['ordercode'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================giglobal checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
            if( $key == 'sign' || $key == 'MSG' || $key == 'orderstatus') {
                continue;
            }                                
            $signStr .= "$key=$value&";
        }
        $signStr .= "key=".$this->getSystemInfo('key');        
        return $signStr;
    }

    private function validateSign($params) {
        $sign = $this->sign($params);           
        if($params['sign'] == $sign)
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
        return number_format($amount * 100, 0, '.', '');
    }
}

