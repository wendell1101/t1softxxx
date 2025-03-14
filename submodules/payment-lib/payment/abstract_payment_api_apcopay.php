<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * APCOPAY_PAYMENT_API
 *
 * * 'APCOPAY_PAYMENT_API', ID 6063
 *
 * Required Fields:
 *
 * * URL: https://www.apsp.biz/MerchantTools/MerchantTools.svc/BuildXMLToken
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_apcopay extends Abstract_payment_api {

    const RESULT_CODE_SUCCESS = 'OK';
    const SUCCESS_CODE      = 'OK';
    const RETURN_SUCCESS    = 'Success';

    # Implement these for specific pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

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
        $playerDetails = $this->getPlayerDetails($playerId);
        $username  = (isset($playerDetails[0]) && !empty($playerDetails[0]['username']))      ? $playerDetails[0]['username']      : '';
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : '';
        $lastname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName']      : '';
        $phone     = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '';
        $email     = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email']         : '';

        $params = array();
        $submit = array();
        $params['ProfileID']    = $this->getSystemInfo("ProfileID");
        $params['ActionType']   = $this->getSystemInfo("ActionType");
        $params['Value']        = $this->convertAmountToCurrency($amount);
        $params['Curr']         = $this->getSystemInfo("currency", '986');
        $params['Lang']         = $this->getSystemInfo("Lang", 'pt');
        $params['ORef']         = $order->secure_id;
        $params['status_url']   = urlencode($this->getNotifyUrl($orderId));
        $params['RedirectionURL'] = $this->getReturnUrl($orderId);
        $params['UDF1']         = '';
        $params['UDF2']         = '';
        $params['UDF3']         = '';
        $params['TEST']         = $this->getSystemInfo("TEST", '');
        $params['ClientAcc']    = $username;
        $params['RegName']      = $lastname.' '.$firstname;
        $params['Email']        = $email;
        $params['MobileNo']     = $phone;
        $this->configParams($params, $order->direct_pay_extra_info);
        $submit['ORef']         =  $order->secure_id;
        $submit['MerchID']      =  $this->getSystemInfo("account");
        $submit['MerchPass']    =  $this->getSystemInfo('key');
        $submit['XMLParam']     =  $this->array2xml($params);
        $submit['ORef']         = $order->secure_id;
        return $this->processPaymentUrlForm($submit);
    }

    protected function processPaymentUrlFormRedirect($params) {
        $orderId = $params['ORef'];
        unset($params['ORef']);
        $this->CI->utils->debug_log("=====================apcopay generatePaymentUrlForm", $params);
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $orderId );
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================apcopay processPaymentUrlFormRedirect response', $response);
        $msg = lang('Invalidte API response');
        if($response['Result'] == self::RESULT_CODE_SUCCESS) {
            $url = $response['BaseURL'].$response['Token'];
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $url,
            );
        }
        else {
            $msg = lang('Invalidte API response');
            if(isset($response['ErrorMsg'])) {
                $msg =  $response['ErrorMsg'];
            }

            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

    /**
     * detail: This will be called when the payment is async, API server calls our callback page,
     * When that happens, we perform verifications and necessary database updates to mark the payment as successful
     *
     * @param int $orderId order id
     * @param array $params
     * @return array
     */
    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    /**
     * detail: This will be called when user redirects back to our page from payment API
     *
     * @param int $orderId order id
     * @param array $params
     * @return array
     */
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $this->CI->utils->debug_log("==============================apcopay callbackFromServer server callbackFrom", $params);

        $hash = substr($params['params'] ,19 ,32);
        $compareString = str_replace($hash ,$this->getSystemInfo('hashKey') ,$params['params']);
        $this->CI->utils->debug_log("============Signature", $hash,md5($compareString));

        if ($hash != md5($compareString)) {
            $this->writePaymentErrorLog('=====================apcopay checkCallbackOrder Signature Error', $hash);
            return false;
        }

        $params = $this->parseResultXML($params);

        if($source == 'server'){
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
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;

        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    /**
     * detail: Validates whether the callback from API contains valid info and matches with the order
     *
     * @return boolean
     */

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array('ORef', 'Result', 'Value');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================apcopay missing parameter: [$f]", $fields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['Result'] != self::SUCCESS_CODE) {
            $payStatus = $fields['Result'];
            $this->writePaymentErrorLog("=====================apcopay Payment was not successful, payStatus is [$payStatus]", $fields);
            return false;
        }

        if ($fields['Value'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================apcopay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['ORef'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================apcopay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- amount --
    protected function convertAmountToCurrency($amount) {
        return number_format($amount , 2, '.', '');
    }

    # -- notifyURL --
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    # -- returnURL --
    public function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    #For XML
    public function array2xml($values){
        if (!is_array($values) || count($values) <= 0) {
            return false;
        }
        $hashKey = $this->getSystemInfo('hashKey');
        $xml = '<Transaction hash='."'".$hashKey."'".">";
        foreach ($values as $key => $val) {
            if($key == 'status_url'){
                 $xml .= "<". $key ." urlEncode=\"true\"". ">" . $val . "</" . $key . ">";
            }
            else if(!empty($val)){
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }else{
                $xml .= "<" . $key . "/>";
            }
        }
        $xml .= "</Transaction>";
        $this->CI->utils->debug_log(' =========================apcopay array2xml', $xml);
        return $xml;
    }

    protected function parseResultXML($resultXml){
        $obj = simplexml_load_string($resultXml['params']);
        $arr = $this->CI->utils->xmlToArray($obj);
        return $arr;
    }

    public function getPlayerDetails($playerId) {
        $this->CI->load->model(array('player_model'));
        $player = $this->CI->player_model->getPlayerDetails($playerId);
        return $player;
    }
}
