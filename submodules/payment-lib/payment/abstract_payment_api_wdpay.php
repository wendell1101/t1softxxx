<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * FORTUNEPAY
 *
 * define('WDPAY_PIX_PAYMENT_API', 6572);
 * define('WDPAY_PIX_WITHDRAWAL_PAYMENT_API', 6573);
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## Access key ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
abstract class Abstract_payment_api_wdpay extends Abstract_payment_api {
    const CALLBACK_SUCCESS_CODE = '2';
    const REQUEST_SUCCESS_CODE = '200';
    const RETURN_SUCCESS_CODE = '200';
    const RETURN_PARMAS_ERROR_CODE = '300';
    const RETURN_FAIL_CODE = '500';

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
        $params = [];
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['channelType'] = $this->getSystemInfo("channelType", 'PIX');
        $params['externalOrderId'] = $order->secure_id;
        $params['notifyUrl'] = $this->getNotifyUrl($orderId);
        $params['userIdentity'] = $playerId;
        $params['inputCpf'] = $this->getSystemInfo("inputCpf", 0);
        $params['timestamp'] = 

        $_access_key = $this->getSystemInfo('account');
        $_timestamp = (int)(microtime(true)*1000);
        $_nonce = $this->guidv4();

        $params['access_key'] = $_access_key;
        $params['timestamp'] = $_timestamp;
        $params['nonce'] = $_nonce;

        $this->_custom_curl_header = [
            'sign: '. $this->sign($params),
            'access_key: ' .$_access_key,
            'timestamp: ' .$params['timestamp'],
            'nonce: ' .$params['nonce'],
            'Content-Type: application/json;charset=utf-8',
        ];
        
        $this->CI->utils->debug_log("=====================wdpay  generatePaymentUrlForm", $params);

        unset($params['access_key']);
        unset($params['timestamp']);
        unset($params['nonce']);

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['externalOrderId']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================wdpay processPaymentUrlFormPost response', $response);

        if(!empty($response['code']) && $response['code'] == self::REQUEST_SUCCESS_CODE && !empty($response['success']) && !empty($response['data'])){
            $cashierUrl = $this->utils->safeGetArray($response['data'], 'cashierUrl', '');
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $cashierUrl
            );
        }

        if(!empty($response['code'])){
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['msg']
            );
        }
        
        return array(
            'success' => false,
            'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
            'message' => lang('Invalidate API response')
        );
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

    public function getOrderIdFromParameters($params) {
        if(empty($params) || is_null($params) || is_array($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = $raw_post_data;
        }

        $params = json_decode($params,true);
        $this->utils->debug_log('=====================wdpay callback params', $params);

        if (isset($params['externalOrderId'])) {
            $this->CI->load->model('sale_order');
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['externalOrderId']);
            return $order->id;
        }
        else {
            $this->utils->debug_log('=====================wdpay callbackOrder cannot get any order_id when getOrderIdFromParameters', $params);
            return;
        }
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if(empty($params) || is_null($params)){
			$raw_post_data = file_get_contents('php://input', 'r');
        	$params = json_decode($raw_post_data, true);
		}

        if($source == 'server' ){
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                $result['json_result']['code'] = self::RETURN_PARMAS_ERROR_CODE;
                $result['json_result']['success'] = false;
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
            $externalOrderId = $this->CI->utils->safeGetArray($params, 'orderId', '');
            $this->CI->sale_order->updateExternalInfo($order->id, $externalOrderId, '', null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['json_result']['code'] = self::RETURN_SUCCESS_CODE;
            $result['json_result']['success'] = true;
       } else {
            $result['json_result']['code'] = self::RETURN_FAIL_CODE;
            $result['json_result']['success'] = false;
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
            'orderAmount', 'orderActualAmount', 'orderStatusCode', 'externalOrderId'
        );
        if(!is_array($fields)){
            $this->writePaymentErrorLog("=====================wdpay checkCallbackOrder wrong value", $fields);
            return false;
        }
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================wdpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $headers = $this->CI->input->request_headers();
        $this->CI->utils->debug_log("=====================wdpay checkCallbackOrder headers", $headers);

        # is signature authentic?
        if (!$this->validateSign($fields, $headers)) {
            $this->writePaymentErrorLog('=====================wdpay checkCallbackOrder Signature Error', $fields);
            return false;
        }
     
        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['orderStatusCode'] != self::CALLBACK_SUCCESS_CODE) {
            $this->writePaymentErrorLog("======================wdpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }
        
        if ($fields['externalOrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================wdpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        if ($fields['orderAmount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================wdpay Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }
        $amount = $fields['orderAmount'];
        $callbackAmount = $fields['orderActualAmount'];
        if ($callbackAmount != $amount) {
            if ($this->getSystemInfo('allow_callback_amount_diff')) {
                $percentage = $this->getSystemInfo('diff_amount_percentage', null);
                $limitAmount = $this->getSystemInfo('diff_limit_amount', null);

                $percentageAmt = !empty($percentage) ? $amount * ($percentage / 100) : null;
                $diffAmtPercentage = !empty($percentageAmt) ? abs($amount - $callbackAmount) : null;

                $this->CI->utils->debug_log("=====================wdpay checkCallbackOrder amount details", 'percentage', $percentage, 'limitAmount', $limitAmount, 'percentageAmt', $percentageAmt, 'diffAmtPercentage', $diffAmtPercentage);

                if ($percentageAmt !== null && $diffAmtPercentage > $percentageAmt) {
                    $this->writePaymentErrorLog("=====================wdpay checkCallbackOrder Payment amounts ordAmt - payAmt > $percentage Percentage, expected [$amount] callbackAmount [$callbackAmount] diffAmtPercentage [$diffAmtPercentage]", $fields);
                    return false;
                }

                $diffAmount = abs($amount - $callbackAmount);
                if ($limitAmount !== null && $diffAmount >= $limitAmount) {
                    $this->writePaymentErrorLog("=====================wdpay checkCallbackOrder Payment amounts ordAmt - payAmt > limit $limitAmount, expected [$amount] callbackAmount [$callbackAmount] diffAmount [$diffAmount]", $fields);
                    return false;
                }

                if ($this->getSystemInfo('convert_callback_diff_amount')) {
                    $callbackAmount = $callbackAmount / $this->getSystemInfo('convert_multiplier', 1);
                }

                $notes = $order->notes . " | callback diff amount, origin was: " . $amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $callbackAmount, $notes);
            } else {
                $this->writePaymentErrorLog("======================wdpay checkCallbackOrder amount not match expected [$amount] callback amount [$callbackAmount]", $fields);
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
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 2, '.', '');
    }

    public function sign($params) {
		$signStr =  $this->createSignStr($params);
        $secretKey = $this->getSystemInfo('key');
        $sign = base64_encode(hash_hmac("sha1", $signStr, $secretKey, TRUE));
		return $sign;
	}

    private function createSignStr($params) {
        ksort($params);
        $paramString = '';
        foreach ($params as $key => $value) {
            $paramString .= $key.'='.  $value   .'&';
        }
        $paramString = substr($paramString,0,-1);
        return $paramString;

	}

    protected function validateSign($params, $headers) {
        if (!isset($headers['Timestamp'], $headers['Nonce'])) {
            return false;
        }
        $params['access_key'] = $this->getSystemInfo('account');
        $params['timestamp'] = $headers['Timestamp'];
        $params['nonce'] = $headers['Nonce'];
        $sign = $this->sign($params);
        if(empty($headers['Sign'])){
            return false;
        }
        return $sign == $headers['Sign'];
    }

    protected function guidv4($data = null) {
        // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
        $data = $data ?: openssl_random_pseudo_bytes(16);
        assert(strlen($data) == 16);
    
        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    
        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

}
