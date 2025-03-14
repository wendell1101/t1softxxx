<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * goopago
 *
 * * GOOPAGO_PAYMENT_API, ID: 6280
 * 
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_goopago extends Abstract_payment_api {

	const RETURN_SUCCESS_CODE = 'success';
    const RESULT_CODE_SUCCESS = 'SUCCESS';
    const COUNTRY_CODE = '55';

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
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
        $pixNumber = (!empty($playerDetails[0]['pix_number'])) ? $playerDetails[0]['pix_number'] : 'none';

		// $emailAddr = (isset($playerDetails[0]) && !empty($playerDetails[0]['email'])) ? $playerDetails[0]['email'] : 'no email';
        $params = array();

        $this->configParams($params, $order->direct_pay_extra_info);
        
        $this->_custom_curl_header = array('tmId:'.$params['payment_method'],
                                           'Content-Type:application/json');

        $params['mchId']      = $this->getSystemInfo('account');        
        $params['nonceStr']   = $this->nonceStr(rand(10, 31));
        $params['mchOrderNo'] = $order->secure_id;
        $params['notifyUrl']  = $this->getNotifyUrl($orderId);
        $params['payType']    = $this->getSystemInfo('pay_type')[$params['payment_method']];
        $params['amount']     = $this->convertAmountToCurrency($amount);
        $params['body']       = "Deposit";
        $params['email']      = $this->getSystemInfo('email');
        $params['idNumber']   = $pixNumber;

        if($params['payment_method'] != 'br_auto'){
            $params['appId'] = $this->getSystemInfo('appId')[$params['payment_method']];
        }

        unset($params['payment_method']);
        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log('=====================goopago generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['mchOrderNo']);
        $response = json_decode($response, true);

        if( isset($response['resCode']) && $response['resCode'] == self::RESULT_CODE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['url'],
            );
        }
        else{
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['errDes']
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

        $this->CI->utils->debug_log("=====================goopago callbackFrom $source params", $params);

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderId'], null, null, null, $response_result_id);
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
            'status', 'amount', 'orderId', 'mchId', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================goopago Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================goopago Signature Error', $fields);
            return false;
        }

        if ($this->convertAmountToCurrency($order->amount) != $fields['amount'] ) {
            $this->writePaymentErrorLog("=======================goopago checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['mchOrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================goopago checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        if ($fields['status'] != 2) {
            $payStatus = $fields['status'];
            $this->writePaymentErrorLog("=====================goopago Payment was not successful, status is [$payStatus]", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }

	protected function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        $this->CI->utils->debug_log("=====================goopago signStr log", $signStr);
        $this->CI->utils->debug_log("=====================goopago sign log", $sign);
        return $sign;
    }

    public function createSignStr($params) {
        $signStr = '';
        ksort($params);
        foreach($params as $key => $value) {
            
            if( $key == 'fee' && $value == "0"){
                $signStr .= "$key=$value&";
            }

            if( $key == 'sign'|| empty($value)) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        return $signStr;
    }

    public function validateSign($params) {
        $sign = $this->sign($params);
        if($params['sign'] == $sign)
            return true;
        else
            return false;
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

    public function convertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * 100 * $convert_multiplier, 0, '.', '') ;
    }
    protected function nonceStr($len){
        $str = md5(time());
        $token = substr($str, 0, $len);
        return $token;
    }

}
