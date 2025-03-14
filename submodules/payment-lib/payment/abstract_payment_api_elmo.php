<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * elmo
 *
 * * ELMO_PAYMENT_API, ID: 5894
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://testing.papayapay.me/qrcode/payer-info?id=transactionId
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_elmo extends Abstract_payment_api {
    const CODE_TYPE_ONLINEBANK = 'auto';
    const RESULT_STATUS_SUCCESS = '00';
    const STATUS_SUCCESSFUL = '02';

	const RETURN_SUCCESS_CODE = 'success';
	const ORDER_STATUS_SUCCESS = '1';

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

        $params = array();
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['merchantTransactionRef'] = $order->secure_id;
        $this->CI->utils->debug_log('=====================elmo generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $login_params = array();
        $login_params['username'] = $this->getSystemInfo('account');
        $login_params['password'] = $this->getSystemInfo('key');

        $login_response = $this->submitPostForm($this->getSystemInfo('login_url'), $login_params, true, $params['merchantTransactionRef']);

        $this->CI->utils->debug_log('=====================elmo processPaymentUrlFormPost login_response', $login_response);

        $transation_params = array();
        $transation_params['type'] = $params['type'];
        $transation_params['amount'] = $params['amount'];
        $transation_params['merchantTransactionRef'] = $params['merchantTransactionRef'];
        $transation_response = $this->processCurl($login_response, $transation_params, $params);

        $this->CI->utils->debug_log('=====================elmo processPaymentUrlFormPost transation_response', $transation_response);

        if($transation_response['status'] == self::RESULT_STATUS_SUCCESS) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['merchantTransactionRef']);
            $this->CI->sale_order->updateExternalInfo($order->id, $transation_response['merchantTransactionRef']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $this->getSystemInfo('url').$transation_response['id'],
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

    protected function processCurl($login_response, $transation_params, $params)
    {
        $login_response = json_decode($login_response,true);
        $ch = curl_init();
        $url = $this->getSystemInfo('transactions_url');
        $token = $login_response['tokens']['access']['token'];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transation_params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token)
        );

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $transation_params, 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($transation_params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['merchantTransactionRef']);


        $this->CI->utils->debug_log('=====================elmo processCurl response', $response);
        $response = json_decode($response, true);

        $this->CI->utils->debug_log('=====================elmo processCurl decoded response', $response);
        return $response;
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->CI->utils->debug_log('=====================elmo getOrderIdFromParameters flds', $flds);

        if(empty($flds)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $flds = json_decode($raw_post_data ,true);
            $this->utils->debug_log('======elmo getOrderIdFromParameters raw_post flds ' , $flds);
        }

        if(isset($flds['merchantTransactionRef'])) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($flds['merchantTransactionRef']);
            return $order->id;
        }
        else {
            $this->utils->debug_log('=====================elmo getOrderIdFromParameters cannot get merchantTransactionRef', $flds);
            return;
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

        $this->CI->utils->debug_log("=====================elmo callbackFrom $source params", $params);

        if($source == 'server' ){
            if(empty($params)){
                $raw_post_data = file_get_contents('php://input', 'r');
                $params = json_decode($raw_post_data, true);
            }

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['merchantTransactionRef'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                if($params['status'] == self::STATUS_SUCCESSFUL){
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
        $requiredFields = array(
            'amount', 'status', 'id'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================elmo checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================elmo checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['merchantTransactionRef'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================elmo checkCallbackTransaction order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        if ($fields['status'] != self::STATUS_SUCCESSFUL) {
            $this->writePaymentErrorLog("=====================elmo checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- Private functions --
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
