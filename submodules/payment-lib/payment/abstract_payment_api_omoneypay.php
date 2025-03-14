<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * MoneyPay聚合支付
 * upay.omoneypay.com
 *
 * * OMONEYPAY_ALIPAY_PAYMENT_API, ID: 5626
 * *
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_omoneypay extends Abstract_payment_api
{
    const PAY_TYPE_QRCODE     = "800201"; //扫码支付
    const PAY_TYPE_H5         = "800209"; //扫码支付
    const OUT_CHANNEL_ALIPAY  = "alipay"; //支付宝

    const RESULT_CODE_SUCCESS = "00";

    const RETURN_FAILED_CODE = 'FAIL';
    const RETURN_SUCCESS_CODE = 'SUCCESS';

    public function __construct($params = null)
    {
        parent::__construct($params);
    }

    # Implement these to specify pay type
    abstract protected function configParams(&$params, $direct_pay_extra_info);
    abstract protected function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null)
    {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();
       
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['spid']             = $this->getSystemInfo('account');
        $params['notify_url']       = $this->getNotifyUrl($orderId);
        $params['pay_show_url']    = $this->getReturnUrl($orderId);
        $params['sp_billno']        = $order->secure_id;
        $params['spbill_create_ip'] = $this->getClientIP();
        $params['tran_time']        = $orderDateTime->format('YmdHis');//yyyyMMddhhmmss
        $params['tran_amt']         = $this->convertAmountToCurrency($amount); //分
        $params['cur_type']         = "CNY";
        $params['item_name']        = $order->secure_id;
        $params['sign']             = $this->sign($params);
        $this->CI->utils->debug_log('=====================omoneypay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormQRCode($params)
    {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['sp_billno']);
        $response_encode = $this->getSystemInfo("response_mb_convert_encode");
        $response = mb_convert_encoding($response, $response_encode, "UTF-8");
   
        $responseArray = $this->parseResultXML($response);
        $this->CI->utils->debug_log('=====================omoneypay processPaymentUrlFormQRCode response', $responseArray);
        if ($responseArray['retcode'] == self::RESULT_CODE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $responseArray['payinfo'],
            );
        } elseif (isset($responseArray['retmsg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $responseArray['retmsg']
            );
        } else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
            );
        }
    }

    public function parseResultXML($resultXml)
    {
        $obj = simplexml_load_string($resultXml);
        $arr = $this->CI->utils->xmlToArray($obj);
        return $arr;
    }
    

    public function callbackFromServer($orderId, $params)
    {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    public function callbackFromBrowser($orderId, $params)
    {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id)
    {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================omoneypay callbackFrom $source params", $params);

        $raw_post_data = file_get_contents('php://input', 'r');
        parse_str($raw_post_data, $params);
        
        if ($source == 'server') {
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['listid'], null, null, null, $response_result_id);
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
            $result['message'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
        }
        
        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = array(
            'retcode', 'sp_billno', 'pay_type', 'tran_amt', 'sign', 'listid'
        );
       
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================omoneypay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================omoneypay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['retcode'] != self::RESULT_CODE_SUCCESS) {
            $this->writePaymentErrorLog('=====================omoneypay checkCallbackOrder Payment status is not success', $fields);
            return false;
        }

        if ($fields['tran_amt'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================omoneypay checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['sp_billno'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================omoneypay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }


        # everything checked ok
        return true;
    }

    public function directPay($order = null)
    {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    protected function sign($params)
    {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    private function createSignStr($params)
    {
        ksort($params);
        $signStr = "";
        foreach ($params as $key => $val) {
            if ($val === null || $key === "sign") {
                continue;
            }
            $signStr .= $key.'='.$params[$key].'&';
        }
        return $signStr.'key='.$this->getSystemInfo('key');
    }

    private function validateSign($params)
    { 
        $callbackSign = $params['sign'];
        $withoutParams = array_flip(['sign','retcode','retmsg','sign_type','ver','input_charset','sign_key_index']);
    
        $params = array_diff_key($params, $withoutParams);
            
        ksort($params);
        $signStr = "";
        foreach ($params as $key => $val) {
            $signStr .= $key.'='.$params[$key].'&';
        }
    
        $signStr .= 'key='.$this->getSystemInfo('key');
        $sign = md5($signStr);

        if ($callbackSign == $sign) {
            return true;
        } else {
            return false;
        }
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## After payment is complete, the gateway will send redirect back to this URL
    private function getReturnUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount)
    {
        return number_format($amount * 100, 0, '.', ''); //1元=100分
    }
}
