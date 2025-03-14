<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * FORTUNEPAY
 *
 * * FORTUNEPAY_PAYMENT_API, ID: 6537
 * * FORTUNEPAY_WITHDRAWAL_PAYMENT_API, ID: 6538
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
 * @copyright 2013-2023 tot
 */
abstract class Abstract_payment_api_fortunepay extends Abstract_payment_api {
    const CALLBACK_SUCCESS = 'S';
    const REQUEST_SUCCESS = 'success';
    const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAIL_CODE = 'fail';

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
        $params['merchant_key'] = $this->getSystemInfo("key");
        $params['preset'] = $this->getSystemInfo("account");
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['ref'] = $order->secure_id;
        
        $this->CI->utils->debug_log("=====================FORTUNEPAY  generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitGetForm($this->getSystemInfo('url'), $params, true, $params['ref']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================FORTUNEPAY processPaymentUrlFormPost response', $response);

        if(!empty($response['status']) && $response['status'] == self::REQUEST_SUCCESS && !empty($response['url_link'])){
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['url_link']
            );
        }

        if(!empty($response['status']) && !empty($response['message'])){
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['message']
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
        $this->utils->debug_log('=====================FORTUNEPAY callback params', $params);

        if (isset($params['ex_ref'])) {
            $this->CI->load->model(array('sale_order'));
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['ex_ref']);
            return $order->id;
        }
        else {
            $this->utils->debug_log('=====================FORTUNEPAY callbackOrder cannot get any order_id when getOrderIdFromParameters', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['ex_ref'], '', null, null, $response_result_id);
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
            $result['return_error'] = self::RETURN_FAIL_CODE;
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
            'txn_id', 'ex_ref', 'amount', 'status', 'processor', 'signature'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================FORTUNEPAY checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================FORTUNEPAY checkCallbackOrder Signature Error', $fields);
            return false;
        }
     
        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================FORTUNEPAY checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================FORTUNEPAY Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['ex_ref'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================FORTUNEPAY checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
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

    protected function validateSign($params) {
        if (!isset($params['txn_id'], $params['ex_ref'], $params['status'], $params['processor'], $params['amount'])) {
            return false;
        }

        $txnId = $params['txn_id'];
        $exRef = $params['ex_ref'];
        $status = $params['status'];
        $processor = $params['processor'];
        $amount = $params['amount'];
        $merchantSecretKey = $this->getSystemInfo('secret');
    
        if (floor($amount) == $amount) {
            $amount = number_format($amount, 1, '.', '');
        }
    
        $signatureString = "{$txnId}:{$exRef}:{$status}:{$processor}:{$amount}:{$merchantSecretKey}";
    
        $calculatedSignature = hash('sha256', $signatureString);
    
        return $calculatedSignature === $params['signature'];
    }

}