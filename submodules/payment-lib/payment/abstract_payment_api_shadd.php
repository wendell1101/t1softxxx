<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * SHADD 刷得多
 *
 * * SHADD_ALIPAY_PAYMENT_API (5778)
 * * Abstract_payment_api_shadd
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://4536251.net/api/transaction
 * * Account: ## Merchant Name ##
 * * Key: ## Merchant Access Token ##
 *
 * @see     payment_api_shadd_alipay.php
 * @category Payment
 * @copyright 2022 tot
 */
abstract class Abstract_payment_api_shadd extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'success';
    const PAY_RESULT_SUCCESS = 'CODE_SUCCESS';
    const RESULT_CODE_SUCCESS = '200';
    const CALLBACK_STATUS_SUCCESS   = 'success';
    const CALLBACK_STATUS_PROGRESS  = 'progress';
    const CALLBACK_STATUS_FAILED    = 'failed';
    const CALLBACK_STATUS_VERIFYING = 'verifying';
    public $DEBUG = true;

	public function __construct($params = null) {
		parent::__construct($params);
	}

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $playerdetails = $this->CI->player_model->getAllPlayerDetailsById($playerId);
        $player_name = $playerdetails['firstName'];

        $params = array();

        $params['trade_no']     = $order->secure_id;
        $params['amount']       = $this->convertAmountToCurrency($amount);
        $params['notify_url']   = $this->getNotifyUrl($orderId);
        $params['ip']           = $this->getClientIP();
        $params['player_name']  = $player_name;

        $this->CI->utils->debug_log(__METHOD__, 'SHADD generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlFormPost($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $access_token = $this->getSystemInfo('key');
        $this->_custom_curl_header = [
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: Bearer {$access_token}"
        ];

        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['trade_no']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log(__METHOD__, "SHADD processPaymentUrlFormPost response", $response);

        if ($response['code'] == self::RESULT_CODE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['uri'],
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => "{$response['code']}: {$response['message']} | " .
                    "Errors: " . json_encode($response['errors'])
            );
        }
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

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $this->CI->utils->debug_log(__METHOD__, "SHADD callbackFrom $source params", $params);

        if($source == 'server'){
            $check_res = $this->checkCallbackOrder($order, $params, $processed);
            if (!$order || $check_res['error'] != 0) {
                return $result;
            }
        }

        // Update order payment status and balance
        $success = true;

        // Update player balance based on order status
        // if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log(__METHOD__, "callbackFrom {$source} already received callback for order {$order->id}", $params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            // update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $params['transaction_id'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto browser callback ' . $this->getPlatformCode(), false);
            }
            elseif ($source == 'server') {
                if ($params['status'] == self::CALLBACK_STATUS_SUCCESS) {
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
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
        $info_env = $this->getInfoByEnv();
        $error = 0x11;
        $err_mesg = "checkCallbackOrder exec incomplete";

        try {
            // Check for required fields
            $required_fields = [
                'receive_amount', 'status', 'trade_no', 'transaction_id', 'signature'
            ];

            foreach ($required_fields as $rf) {
                if (!isset($fields[$rf])) {
                    throw new Exception("Callback field missing: {$f}", 0x12);
                }
            }

            // Check for signature
            $signature_expected = $this->signature_for_server_callback($fields, $info_env);
            if ($signature_expected != $fields['signature']) {
                $this->writePaymentErrorLog('Wrong signature for server callback', $fields);
                $this->CI->utils->debug_log('Signature mismatch', 'given', $fields['signature'], 'expected', $secure_expected);
                throw new Exception('Signature mismatch', 0x13);
            }

            $processed = true;

            // Check amount
            if ($this->convertAmountToCurrency($order->amount) != $fields['receive_amount']) {
                throw new Exception('Amount mismatch', 0x14);
            }

            // Check trade_no == secure_id
            if ($fields['trade_no'] != $order->secure_id) {
                throw new Exception('Secure_id mismatch', 0x15);
            }

            // Point of success
            $error = 0x0;
            $err_mesg = '';

        }
        catch (Exception $ex) {
            $ex_code = $ex->getCode();
            $error = $ex_code;
            $err_mesg = $ex->getMessage();

            $this->CI->utils->debug_log(__METHOD__, "SHADD: checkCallbackOrder", "exception", $err_mesg);
            $this->writePaymentErrorLog("SHADD checkCallbackOrder: {$err_mesg}", $fields);
        }
        finally {
            $ret = [ 'error' => $error, 'mesg' => $err_mesg ];
            if ($this->DEBUG) {
                $this->CI->utils->debug_log(__METHOD__, "SHADD:checkCallbackOrder", "return", $err_mesg);
            }

            return $ret;
        }
    } // End function checkCallbackOrder()

    public function signature_for_server_callback($params, $info_env) {
        $access_token = $info_env['key'];

        unset($params['signature']);
        ksort($params);

        $plain = json_encode($params);
        $signature = md5($plain . $access_token);
        if ($this->DEBUG) {
            $this->utils->debug_log(__METHOD__, [ 'plain_text' => $plain, 'signature' => $signature ]);
        }

        return $signature;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

}
