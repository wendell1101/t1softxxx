<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ftpay
 *
 * * FTPAY_PAYMENT_API, ID: 6260
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://yyds68.cc/Apipay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
abstract class Abstract_payment_api_ftpay extends Abstract_payment_api {
    const CURRENCY_CNY         = 'CNY';
    const PAY_TYPE             = 'runpay';
    const PAY_TYPE_USDT        = 'usdt';
    const BANK_NAME_TRC20      = 'USDT-TRC20';
    const REPONSE_CODE_SUCCESS = 1;
    const CALLBACK_SUCCESS     = 1;
    const RETURN_SUCCESS_CODE  = 'success';

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/x-www-form-urlencoded');
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
        $params['userid']      = $this->getSystemInfo('account');
        $params['orderno']     = $order->secure_id;
        $params['desc']        = 'deposit';
        $params['amount']      = $this->convertAmountToCurrency($amount);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['notifyurl']   = $this->getNotifyUrl($orderId);
        $params['backurl']     = $this->getReturnUrl($orderId);
        $params['paytype']     = $this->getSystemInfo('paytype', self::PAY_TYPE);
        $params['notifystyle'] = '2';
        $params['attach']      = $this->getSystemInfo('currency', self::CURRENCY_CNY);
        $params['userip']      = $this->getClientIP();
        $params['currency']    = $this->getSystemInfo('currency', self::CURRENCY_CNY);
        $params['sign']        = $this->sign($params);

        if ($this->getSystemInfo("paytype") == self::PAY_TYPE_USDT) {
            $params['bankname'] = self::BANK_NAME_TRC20;
        }

        $this->CI->utils->debug_log("=====================ftpay  generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['orderno']);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================ftpay processPaymentUrlFormPost response json to array', $response);

        $msg = lang('Invalidate API response');

        $isCrypto = ($this->getSystemInfo("paytype") == self::PAY_TYPE_USDT);

		if ($isCrypto) {
			$cryptoAmount = $params['amount'];
			list($crypto, $cryptoRate) = $this->CI->utils->convertCryptoCurrency($cryptoAmount, 'USDT', 'USDT', 'deposit');
        	$this->CI->utils->debug_log('=====================ftpay crypto', $crypto);
		}

        if( isset($response['status']) && $response['status'] == self::REPONSE_CODE_SUCCESS ){
            if(isset($response['payurl']) && !empty($response['payurl'])){

                if ($isCrypto) {
                    $order = $this->CI->sale_order->getSaleOrderBySecureId($params['orderno']);
                    $this->CI->sale_order->createCryptoDepositOrder($order->id, $cryptoAmount , $cryptoRate, null, null, 'USDT');
                    $deposit_notes = 'cryptoRate'. $cryptoRate .'USDTcoin: ' . $cryptoAmount;
                    $this->CI->sale_order->appendNotes($order->id, $deposit_notes);
                }

                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['payurl']
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => $msg
                );
            }
        }else {
            if(isset($response['error']) && !empty($response['error'])) {
                $msg = $response['error'];
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

        $raw_post_data = file_get_contents('php://input', 'r');
        $params = json_decode($raw_post_data, true);
        
        $this->CI->utils->debug_log("=====================ftpay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderno'], '', null, null, $response_result_id);
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
            'orderno', 'amount', 'status', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================ftpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================ftpay checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================ftpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($this->getSystemInfo("paytype") == self::PAY_TYPE_USDT) {
            //USDT
           $crypto_amount = $this->convertAmountToCrypto($order->id);
           if ($crypto_amount != $fields['amount']) {
               $this->writePaymentErrorLog("=====================ftpay Payment amounts do not match, expected ", $crypto_amount);
               return false;
           }

       } else {

            if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
                $this->writePaymentErrorLog("=====================ftpay Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
             }
       }

        if ($fields['orderno'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================ftpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    protected function convertAmountToCrypto($orderId) {
        $cryptoOrder = $this->CI->sale_order->getCryptoDepositOrderBySaleOrderId($orderId);
        $cryptoAmount = $cryptoOrder->received_crypto;
        $this->CI->utils->debug_log("=======================ftpay convertAmountToCrypto,orderId",$cryptoAmount,$orderId);
        return $cryptoAmount;
    }

    # -- signatures --
    # Reference: PHP Demo
    public function sign($params, $isValid = false) {

        if ($isValid) {
            $signStr = $this->createValidateStr($params);
        } else {
            $signStr = $this->createSignStr($params);
        }

        $sign = strtolower($signStr);
        return $sign;
    }

    public function createSignStr($params) {
        $signStr = $params['userid'].$params['orderno'].$params['amount'].$params['notifyurl'].$this->getSystemInfo('key');
        return md5($signStr);
    }

    public function createValidateStr($params) {
        $signStr = $params['currency'].$params['status'].$params['userid'].$params['orderno'].$params['amount'].$this->getSystemInfo('key');
        return md5($signStr);
    }

    public function validateSign($params) {
        $signature = $params['sign'];
        $sign = $this->sign($params, true);
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
        return number_format($amount, 2, '.', '');
    }

}