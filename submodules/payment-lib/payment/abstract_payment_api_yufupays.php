<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * YUFUPAYS 优付
 *
 * * YUFUPAYS_PAYMENT_API, ID: 5687
 * * YUFUPAYS_ALIPAY_PAYMENT_API, ID: 5687
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.yufupays.com/Pay/GateWay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_yufupays extends Abstract_payment_api {
	const ORDERTYPE_TYPE_ONLINEBANK = '1000';
	const ORDERTYPE_TYPE_ALIPAY = '992';

	const RETURN_SUCCESS_CODE = 'ok';
	const ORDER_STATUS_SUCCESS = '0';

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
        $username  = (isset($playerDetails[0]) && !empty($playerDetails[0]['username']))      ? $playerDetails[0]['username']      : 'no username';

        $params = array();
        $params['parter'] = $this->getSystemInfo('account');
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['value'] = $this->convertAmountToCurrency($amount);
        $params['orderid'] = $order->secure_id;
        $params['callbackurl'] = $this->getNotifyUrl($orderId);
        $params['hrefbackurl'] = $this->getReturnUrl($orderId);
        $params['payerIp'] = $this->getClientIp();
        $params['PayerRealName'] = $username;
        //$params['ReceiveQrCodeUrl'] = $this->getReturnUrl($orderId);

        if($this->CI->utils->is_mobile()) {
            $params['device'] = 'wap';
        }else{
            $params['device'] = 'pc';
        }

        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log('=====================yufupays generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlForm($params) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => false, # sent using GET
		);
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

        $this->CI->utils->debug_log("=====================yufupays callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderid'], null, null, null, $response_result_id);
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
            'orderid','opstate','ovalue','sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================yufupays Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================yufupays Signature Error', $fields);
            return false;
        }

        if ($fields['opstate'] != self::ORDER_STATUS_SUCCESS) {
            $this->writePaymentErrorLog('=====================yufupays Payment was not successful', $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount);

        if ($fields['ovalue'] != $check_amount) {
            $this->writePaymentErrorLog("======================yufupays Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        }

        if ($fields['orderid'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================yufupays checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }


        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }

	private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    private function createSignStr($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            if( $key == 'parter' || $key == 'type' || $key == 'value' || $key == 'orderid') {
            	$signStr .= "$key=$value&";
            }else if ( $key == 'callbackurl' ){
            	$signStr .= "$key=$value";
            }
        }
        $signStr .= $this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            if( $key == 'orderid' || $key == 'opstate' ) {
            	$signStr .= "$key=$value&";
            }else if ( $key == 'ovalue' ){
            	$signStr .= "$key=$value";
            }
        }
        $signStr .= $this->getSystemInfo('key');
        $sign = md5($signStr);

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

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

}
