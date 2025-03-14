<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * today
 *
 * * TODAY_PAYMENT_API (6107)
 * * Abstract_payment_api_today
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.tdaypay.com/gateway/base/biz
 * * Account: ## Merchant Name ##
 * * Key: ## Merchant Access Token ##
 *
 * @category Payment
 * @copyright 2022 tot
 */
abstract class Abstract_payment_api_today extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'ok';
    const RESPONSE_STATUS_SUCCESS   = '00000';
    const CALLBACK_STATUS_SUCCESS   = 'SUCCESS';

	public function __construct($params = null) {
		parent::__construct($params);
	}

    protected abstract function configParams(&$params, $direct_pay_extra_info);
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);

        $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName'] : 'none';
        $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName'] : 'none';
        $phone      = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '';
        $email      = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email'] : '';
        $pixNumber  = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))    ? $playerDetails[0]['pix_number'] : 'none';

        $params = array(
            'mchOrderId' => $order->secure_id,
            'amount' => $this->convertAmountToCurrency($amount),
            'currency' => $this->getSystemInfo('currency','BRL'),
            'productinfo' => 'deposit',
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'phone' => $phone,
            'dynamic' => 1,
            'docType' => 'PIX',
            'docNumber' => $pixNumber,
            'callbackUrl' => $this->getNotifyUrl($orderId)
        );

        $params['headers'] = array(
            'serviceName' => $this->getSystemInfo('serviceName','api.pay'),
            'method'      => $this->getSystemInfo('method','pay'),
            'mchId'       => $this->getSystemInfo('account'),
            'signType'    => $this->getSystemInfo('signType','SHA512'),
            'timestamp'   => time()
        );

        $this->CI->utils->debug_log('=====================today generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $header = $params['headers'];
        unset($params['headers']);

        $headers = array(
            "serviceName: ".$this->getSystemInfo('serviceName','api.pay'),
            "content-type: Content-Type: application/json",
            "method: ".$this->getSystemInfo('method','pay'),
            "mchId: ".$this->getSystemInfo('account'),
            "signType: ".$this->getSystemInfo('signType','SHA512'),
            "timestamp: ". $header['timestamp'],
            "sign: ". $this->sign($params,$header)
        );

        $this->_custom_curl_header = $headers;

        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['mchOrderId']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log("=====================today processPaymentUrlFormPost response", $response, $headers);

        if($response['resultCode'] == self::RESPONSE_STATUS_SUCCESS && isset($response['data'][0]['checkoutUrl']) && !empty($response['data'][0]['checkoutUrl'])){
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data'][0]['checkoutUrl'],
            );
        }else if(isset($response['errorMsg']) && !empty($response['errorMsg'])){
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $response['errorMsg']
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => lang('Invalidte API response')
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

        $this->CI->utils->debug_log("=====================today callbackFrom $source params", $params);

        if($source == 'server'){
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['mchOrderId'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto browser callback ' . $this->getPlatformCode(), false);
            }
            elseif ($source == 'server') {
                if ($params['orderStatus'] == self::CALLBACK_STATUS_SUCCESS) {
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
        $required_fields = [
            'mchOrderId', 'orderId', 'bankId', 'orderStatus', 'amount'
        ];

        foreach ($required_fields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================today checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================today checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass


        if ($fields['orderStatus'] != self::CALLBACK_STATUS_SUCCESS) {
            $this->writePaymentErrorLog("======================today checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================today checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['mchOrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================today checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;

    } // End function checkCallbackOrder()

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    /**
     * detail: getting the signature
     *
     * @param array $data
     * @return  string
     */
    public function sign($params,$header) {
        $signStr = $this->createSignStr($params,$header);
        $sign = hash('SHA512',$signStr);
        return $sign;
    }

    public function validateSign($params){
        $params_sign = $_SERVER['HTTP_SIGN'];
        $signStr =  json_encode($params).$this->getSystemInfo('key');
        $sign = hash('SHA512',$signStr);
        if($params_sign == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    private function createSignStr($params,$header) {
        $encode_params = json_encode($params);
        $signStr =  $header['mchId'].
                    $header['serviceName'].
                    $header['method'].
                    $header['timestamp'].
                    $header['signType'].
                    $encode_params.
                    $this->getSystemInfo('key');
        return $signStr;
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
