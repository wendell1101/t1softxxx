<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * STASHPAY
 *
 * * STASHPAY_PAYMENT_API, ID: 6418
 * * STASHPAY_WITHDRAWAL_PAYMENT_API, ID: 6419
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
abstract class Abstract_payment_api_stashpay extends Abstract_payment_api {
    const CALLBACK_SUCCESS     = 'SUCCESS';

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
        $params = array();
        $params['accountId']           = $this->getSystemInfo("accountId");
        $params['amount']              = $this->convertAmountToCurrency($amount);
        $params['clientTransactionId'] = $order->secure_id;
        $params['callbackUrl']         = $this->getNotifyUrl($orderId);
        $params['clientNotes']         = parent::DEPOSIT_API;
        $this->_setPlayerInfoToParams($playerId, $params);
        
        $this->CI->utils->debug_log("=====================stashpay  generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function _setPlayerInfoToParams($playerId, &$params, $indicatedAccountNumber = '') {
        $this->CI->load->model('player_model');
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);

        $userName = (isset($playerDetails[0]) && !empty($playerDetails[0]['username'])) ? $playerDetails[0]['username'] : 'no username';
        $firstName = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : '';
        $lastName = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName'])) ? $playerDetails[0]['lastName'] : '';
        
        if(empty($firstName) && empty($lastName)){
            $realName = $userName;
        }else{
            $realName = trim($firstName).' '.trim($lastName);
        }
        
        if(!empty($indicatedAccountNumber)){
            $accountNumber = $indicatedAccountNumber;
        }else{
            $phoneNumber =  (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : 'no phone';
            $accountNumber = $this->appendCounrtyCode($playerId, $phoneNumber);
        }

        $params['accountName'] = $realName;
        $params['accountNumber'] = $accountNumber;      
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
        $token = $this->_getBearerToken();
        $url = $this->getSystemInfo('url');
        $response = $this->processCurl($url, $params, $token, $params['clientTransactionId']);

        $this->CI->utils->debug_log('========================================stashpay processPaymentUrlFormPost response json to array', $response);
        
        if(isset($response['ok'], $response['data']) && $response['ok'] && !empty($response['data']['receiver']['accountQR'])){
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'base64_url' => base64_encode($response['data']['receiver']['accountQR']),
           );
        }

        return array(
            'success' => false,
            'type' => self::REDIRECT_TYPE_ERROR,
            'message' => $this->getErrorMsgWithResponse($response, lang('Invalidate API response')),
        );
    }

    protected function getErrorMsgWithResponse($response, $defaultErrorMsg = ''){
        if(!isset($response['errors']) || !is_array($response['errors'])){
            return $defaultErrorMsg;
        }
        $thirdPartyErrorsMsg = '';
        foreach ($response['errors'] as $error) {
            if(!isset($error['key'],$error['msg'])){
                return $defaultErrorMsg;
            }
            $thirdPartyErrorsMsg .= "{$error['key']} : {$error['msg']} ";
        } 
        return $thirdPartyErrorsMsg;
    }

    protected function processCurl($url, $params, $token = null, $secureId = null, $return_all = false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if(!empty($token)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$token)
            );    
        }else{
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
            ));
        }

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $secureId);
        $response = json_decode($response, true);

        $this->CI->utils->debug_log('=====================stashpay processCurl decoded response', $response, $response_result_id);

        if($return_all){
            $response_result = [
                $params, $response, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $params['clientTransactionId']
            ];
            return array($response, $response_result);
        }

        return $response;
    }

    protected function _getBearerToken() {
        $authUrl = $this->getSystemInfo('auth_url');
        $params = [
            "clientToken" => $this->getSystemInfo('account'),
            "clientSecret" => $this->getSystemInfo('key')
        ];
        $authResult = $this->processCurl($authUrl, $params);
        if(!empty($authResult['token'])){
            return $authResult['token'];
        }

        return null;
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['clientTransactionId'], '', null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = [
                "ok" => true,
                "data" => [
                    "id" => $params['clientTransactionId']
                ]
            ];
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
            'id', 'clientTransactionId', 'amount', 'status'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================stashpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================stashpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================stashpay Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['clientTransactionId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================stashpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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

}