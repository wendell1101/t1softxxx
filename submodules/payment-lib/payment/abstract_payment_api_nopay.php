<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * nopay
 *
 * * NOPAY_USDT_PAYMENT_API, ID: 6332
 * * NOPAY_USDC_PAYMENT_API, ID: 6333
 * * NOPAY_BTC_PAYMENT_API, ID: 6334
 * * NOPAY_ETH_PAYMENT_API, ID: 6335
 * * NOPAY_USDT_TRC_WITHDRAWAL_PAYMENT_API, ID: 6339
 * * NOPAY_USDT_ERC_WITHDRAWAL_PAYMENT_API, ID: 6340
 * * NOPAY_USDC_TRC_WITHDRAWAL_PAYMENT_API, ID: 6341
 * * NOPAY_USDC_ERC_WITHDRAWAL_PAYMENT_API, ID: 6342
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
 * @copyright 2013-2024 tot
 */
abstract class Abstract_payment_api_nopay extends Abstract_payment_api {

    const COIN_USDT = "USDT"; //TRC20 ERC20
    const COIN_USDC = "USDC"; //TRC20 ERC20
    const COIN_BTC = "BTC"; //Bitcoin
    const COIN_ETH = "ETH"; //ERC20

    const RESPONSE_SUCCESS_CODE  = '0';
    const RESPONSE_SUCCESS_MSG  = 'SUCCESS';
    const WITHDRAWAL_RESPONSE_STATE  = '1';
    const WITHDRAWAL_CALLBACK_STATE  = '3';
    const DEPOSIT_CALLBACK_STATE = '3';

    public function __construct($params = null) {
        parent::__construct($params);
        $headers = array(
            "content-type: Content-Type: application/json",
            "version: v1",
            "appId:".$this->getSystemInfo('account')
        );
        $this->_custom_curl_header = $headers;
    }

    public function processHeaders($params){
        $headers = array(
            "content-type: Content-Type: application/json",
            "version: v1",
            "appId:".$this->getSystemInfo('account')
        );

        $this->_custom_curl_header = $headers;
        return $headers;
    }

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
    
        $params = array();
        $params['merchantOrderNo'] = $order->secure_id;
        $params['merchantMemberNo'] = $order->secure_id;
        $params['language'] = $this->getSystemInfo("language", "en");
        $params['rateType'] = $this->getSystemInfo("rateType", 1);;
        $this->configParams($params, $order->direct_pay_extra_info);

        $params['amount'] = $amount;
        $params['rate'] = $this->getSystemInfo("rate", 1);
        $params['currencyAmount'] = $amount;
        $params['currency'] = $this->getSystemInfo("currency");
        $params['notifyUrl'] = $this->getNotifyUrl($orderId);
        $params['timestamp'] = time();
        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log("=====================nopay  generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {
        $orderId = $params['merchantOrderNo'];

        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $orderId);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================nopay submitPostForm response', $response);
        $msg = lang('Invalidate API response');
        if( isset($response['code']) && $response['code'] == self::RESPONSE_SUCCESS_CODE ){
            if(!empty($response['data']) && isset($response['data']['url'])){
                $order = $this->CI->sale_order->getSaleOrderBySecureId($orderId);

                $address = $response['data']['address'];
                $externalOrderId = $response['data']['orderNO'];
                $depositNotes = 'Wallet address: '.$address.' | External Order Id: '.$externalOrderId;
                $this->CI->utils->debug_log('=====================nopay usdt depositNotes', $depositNotes);
                $this->CI->sale_order->updateExternalInfo($order->id, $externalOrderId, '', $address);

                $result= array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['data']['url']
                );
                return $result;
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => $msg
                );
            }
        }else {
            if(isset($response['msg']) && !empty($response['msg'])) {
                $msg = $response['msg'];
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

        if (empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        
        $this->CI->utils->debug_log("=====================nopay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['merchantOrderNo'], '', null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RESPONSE_SUCCESS_MSG;
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
            'appId', 'merchantOrderNo', 'amount', 'state', 'serviceFee', 'timestamp', 'sign'
        );
        $this->CI->utils->debug_log("=====================nopay fields",$fields);

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================nopay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================nopay checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }

        if ($fields['state']!= self::DEPOSIT_CALLBACK_STATE) {
            $errorCode=$fields['state'];
            $ErrorMsg=$errorCode.":".$this->getErrorMsg($fields['state']);
            $this->writePaymentErrorLog("=====================nopay checkCallbackOrder callback state ErrorCode[$ErrorMsg]",$fields);
            return false;
        }

        $extraInfo = $this->getExtraInfoById($order->system_id);
        $amount = $order->amount;
        $callbackAmount = $fields['amount'];

        $this->CI->utils->debug_log("=====================nopay checkCallbackOrder amount", $amount, 'callbackAmount',$callbackAmount);
        if ($callbackAmount != $amount) {
            if($extraInfo['allow_callback_amount_diff']){
                $percentage = isset($extraInfo['diff_amount_percentage']) ? $extraInfo['diff_amount_percentage'] : null;
                $limit_amount = isset($extraInfo['diff_limit_amount']) ? $extraInfo['diff_limit_amount'] : null;

                if (!empty($percentage)) {
                    $percentage_amt = str_replace(',', '', $amount) * ($percentage / 100);
                    $diffAmtPercentage = abs(str_replace(',', '', $amount) - $percentage_amt);

                    $this->CI->utils->debug_log("=====================nopay checkCallbackOrder amount details",$percentage, $limit_amount,$percentage_amt,$diffAmtPercentage);

                    if ($callbackAmount < $diffAmtPercentage) {
                        $this->writePaymentErrorLog("=====================nopay checkCallbackOrder Payment amounts ordAmt - payAmt > $percentage Percentage, expected [$amount] diffAmtPercentage [$diffAmtPercentage]", $fields);
                        return false;
                    }
                }

                if (!empty($limit_amount)) {
                    $diffAmount = abs($amount - floatval($callbackAmount));
                    if ($diffAmount >= $limit_amount) {
                        $this->writePaymentErrorLog("=====================nopay checkCallbackOrder Payment amounts ordAmt - payAmt > 1, expected [$amount] diffAmount [$diffAmount]", $fields);
                        return false;
                    }
                }

                $notes = $order->notes . " | callback diff amount, origin was: " . $amount;
                $this->CI->sale_order->fixOrderAmount($order->id, str_replace(',', '', $callbackAmount), $notes);
            }
            else{
                $this->writePaymentErrorLog("======================nopay checkCallbackOrder amount not match expected [$amount] callback amount [$callbackAmount]", $fields);
                return false;
            }
        }

        if ($fields['merchantOrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================nopay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }
        $this->CI->utils->debug_log("=====================nopay every ok");

        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }

    public function getExtraInfoById($systemId){
        $this->CI->load->model('external_system');
        $this->CI->utils->debug_log("====================callback getExtraInfoById:", $systemId);
        $systemInfo = $this->CI->external_system->getSystemById($systemId);
        $extraInfoJson = (!isset($systemInfo->live_mode) || $systemInfo->live_mode) ? $systemInfo->extra_info : $systemInfo->sandbox_extra_info;
        $extraInfo = json_decode($extraInfoJson, true) ?: array();

        $this->CI->utils->debug_log("====================callback extraInfo:", $extraInfo);
        return $extraInfo;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    # Reference: PHP Demo
    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = hash("sha256", $signStr);
        return $sign;
    }

    public function createSignStr($params) {
        if(isset($params['crypto_amount'])){
            unset($params['crypto_amount']);
        }
        $signStr = "";
        $params['appId'] = $this->getSystemInfo('account');
        ksort($params);//按key字母升序排序
        foreach ($params as $k=>$v){
            if(!empty($v) || $v == 0){
                $signStr .="&$k=$v";
            }
        }
        $signStr .="&key=".$this->getSystemInfo('key');
        $signStr = trim($signStr,'&');
        return $signStr;
    }

    public function validateSign($params) {
        $signature = $params['sign'];
        unset($params['sign']);
        $sign = $this->sign($params);

        if ( $signature == $sign ) {
            return true;
        } else {
            return false;
        }    
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    protected function getNotifyUrl($orderId) {
        $callback_currency = $this->getRequestCallbackCurrency();
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId . '/null/' . $callback_currency);
    }

    ## After payment is complete, the gateway will send redirect back to this URL
    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount) {
        // return floatval(number_format($amount * 100, 2, '.', ''));
        return $amount;
    }

    // ==========================================withdrawal use========================================================= 
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                $bankInfo[$system_bank_type_id] = $bankInfoItem;
            }
            $this->utils->debug_log("==================nopay withdrawal usdt_erc bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '42'  => 'usdt_erc',
            );
            $this->utils->debug_log("====================nopay withdrawal usdt_erc bank info from extra_info: ", $bankInfo);
        }
        return $bankInfo;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================nopay json_decode result", $result);

        if(isset($result['code'])) {
            if($result['code'] == self::RESPONSE_SUCCESS_CODE) {
                $message = "nopay withdrawal response successful, code:[".$result['code']."]: ".$result['msg'];
                return array('success' => true, 'message' => $message);
            }
            $message = "nopay withdrawal response failed. [code]: ".$result['code'].$result['msg'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['msg']){
            $message = 'nopay withdrawal response: '.$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "nopay decoded fail.");
    }

    public function getErrorMsg($errorCode){
        switch($errorCode){
            case '0':
                return '待充币';
            case '1':
                return '确认中';
            case '2':
                return '待上分';
            case '3':
                return '成功';
            case '4':
                return '失败';
            case '5':
                return '超时失败';
        }
    }
}