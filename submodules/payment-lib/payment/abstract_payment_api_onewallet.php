<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ONEWALLET
 *
 * * ONEWALLET_PAYMENT_API, ID: 5675
 * * ONEWALLET_2_PAYMENT_API, ID: 5682
 * * ONEWALLET_QRPAY_PAYMENT_API, ID: 5683
 * * ONEWALLET_QRPAY_2_PAYMENT_API, ID: 5684
 * * ONEWALLET_WITHDRAWAL_PAYMENT_API, ID: 5685
 * * ONEWALLET_WITHDRAWAL_2_PAYMENT_API, ID: 5686
 * * ONEWALLET_TRUEWALLET_PAYMENT_API, ID: 5962
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: https://api-tg.100scrop.tech/11-dca/SH/sendPay
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_onewallet extends Abstract_payment_api {
    const ORDER_STATUS_SUCCESS = 3;
    const ORDER_STATUS_MANUAL  = 4;
    const ORDER_STATUS_FAILED  = 5;

    const ORDER_TYPE_INTERNET_BANKING = '100';
    const ORDER_TYPE_QRPAY = 'QRPay';
    const ORDER_TYPE_TRUEWALLET = 'TrueWallet';

    const RETURN_CODE_SUCESS = 200;

    private $key;

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
        $params['sh_order_no']  = $order->secure_id;
        $params['order_amount'] = $this->convertAmountToCurrency($amount);
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['notify_url']   = $this->getNotifyUrl($orderId);

        $this->CI->utils->debug_log('============================OneWallet generatePaymentUrlForm params', $params);
        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormRedirect($params) {
        $payload = $this->encrypt($params);
        $this->CI->utils->debug_log("============================OneWallet processPaymentUrlFormQRCode encrypted payload", $payload);
        $response = $this->processCurl($this->getSystemInfo('url'), $payload, $params['sh_order_no']);

        $data = $this->decrypt($response);
        $this->CI->utils->debug_log("============================OneWallet processPaymentUrlFormQRCode decrypted", $data);

        if($data['success']) {
            $decrypted = $data['decrypted'];
            if($decrypted['error_code'] == self::RETURN_CODE_SUCESS) {
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $decrypted['data']['url'][0],
                );
            } else {
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => $decrypted['data']['message']
                );
            }
        } else {
            if(isset($data['error']['code']) && isset($data['error']['message'])){
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => '['.$data['error']['code'].']'.$data['error']['message']
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => 'Unknown Error'
                );
            }
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

        $this->CI->utils->debug_log("============================OneWallet callbackFrom $source params", $params);

        if(empty($params)){
            $params = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("============================OneWallet raw_post_data", $params);
        }
        $success = $this->decrypt($params)['success'];

        if(!$success) {
            return $result;
        } else {
            $params = $this->decrypt($params)['decrypted'];
        }

        if($source == 'server' ){
            if (!$order || !$this->checkCallbackOrder($order, $params)) {
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['sh_order_no'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($success) {
            $result['message'] = $this->encrypt(['result' => 'success']);
        } else {
            $result['return_error'] = $this->encrypt(['result' => 'error']);
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'sh_order_no', 'order_status', 'order_amount', 'paid_amount', 'request_amount'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("============================OneWallet checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['order_status'] != self::ORDER_STATUS_SUCCESS) {
            $this->writePaymentErrorLog("============================OneWallet checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if($this->getSystemInfo('use_order_amount_in_check_callback')){
            if ($fields['order_amount'] != $this->convertAmountToCurrency($order->amount)) {
                $this->writePaymentErrorLog("============================OneWallet Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
        }else{
            if ($fields['paid_amount'] != $this->convertAmountToCurrency($order->amount)) {
                #because player need to enter amount at Alipay
                if($this->getSystemInfo('allow_callback_amount_diff')){
                    $this->CI->utils->debug_log('============================OneWallet amount not match expected [$order->amount]');
                    $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                    $this->CI->sale_order->fixOrderAmount($order->id, $fields['paid_amount'], $notes);
                }
                else{
                    $this->writePaymentErrorLog("============================OneWallet Payment amounts do not match, expected [$order->amount]", $fields);
                    return false;
                }
            }
        }

        if ($fields['sh_order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("============================OneWallet checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    protected function encrypt($params) {
        $key = base64_decode($this->getSystemInfo('key'));
        $iv = random_bytes(16);
        $value = openssl_encrypt(json_encode($params, JSON_UNESCAPED_SLASHES), 'AES-128-CBC', $key, 0, $iv);
        $encrypted = base64_encode(json_encode(['iv' => base64_encode($iv), 'value' => $value], JSON_UNESCAPED_SLASHES));
        return $encrypted;
    }

    protected function decrypt($params) {
        $result = ['success' => false];
        if(!empty($params)){
            $payload = json_decode(base64_decode($params), true);
            if (!empty($payload['error_code'])) {
                $error_data = $payload['data'];
                $result = [
                    'success' => false,
                    'error' => [
                        'code' => $payload['error_code'],
                        'message' => $error_data['message'],
                    ]
                ];
            } else {
                $key = base64_decode($this->getSystemInfo('key'));
                $iv = base64_decode($payload['iv']);
                $decrypted = json_decode(openssl_decrypt($payload['value'], 'AES-128-CBC', $key, 0, $iv), true);
                $result = [
                    'success' => true,
                    'decrypted' => $decrypted
                ];
            }
        }
        return $result;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## After payment is complete, the gateway will send redirect back to this URL
    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    protected function processCurl($url, $params, $orderSecureId=NULL, $return_all=false) {
        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            $this->setCurlProxyOptions($ch);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeoutSecond());
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());

            $response = curl_exec($ch);
            $this->CI->utils->debug_log('=========================processCurl curl content ', $response);

            $errCode     = curl_errno($ch);
            $error       = curl_error($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $content     = substr($response, $header_size);

            curl_close($ch);
            $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

            #withdrawal lock processing
            if(substr($orderSecureId, 0, 1) == 'W' && $errCode == '28') {   //curl_errno means timeout
                // $content = '{"lock": true, "msg": "Ready to lock processing withdrawal order. curl error message: errCode = '.$errCode.' - '.$error.'" }';
                $content = array('lock' => true, 'msg' => 'Ready to lock processing withdrawal order. curl error message: errCode = '.$errCode.' - '.$error);
            }

            $response_result_content = is_array($content) ? json_encode($content) : $content;
            #save response result
            $response_result_id = $this->submitPreprocess($params, $response_result_content, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $orderSecureId);

            if($return_all){
                $response_result = [
                    $params, $response_result_content, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $orderSecureId
                ];
                $this->CI->utils->debug_log('=========================processCurl return_all response_result', $response_result);
                return array($content, $response_result);
            }
            return $content;
        } catch (Exception $e) {
            $this->CI->utils->error_log('POST failed', $e);
        }
    }
}

