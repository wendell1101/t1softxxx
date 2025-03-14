<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * TPAY
 *
 * * TPAY_PAYMENT_API, ID: 5724
 * * TPAY_WITHDRAWAL_PAYMENT_API, ID: 5725
 * * TPAY_PAYMENT_API_QRCODE, ID: 5726
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.tapexdd12.com/app/pay/pay.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_tpay extends Abstract_payment_api {
	const ORDERTYPE_TYPE_ONLINEBANK = '1';
    const DEVICE_PC    = 'web';
    const DEVICE_PHONE = 'wap';
	const RETURN_SUCCESS_CODE = 'success';
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

        $params = array();
        $params['appid'] = $this->getSystemInfo('account');
        $params['forApi'] = '1';
        $params['cliIP'] = $this->getClientIp();
        $params['cliNA'] = $this->CI->utils->is_mobile() ? self::DEVICE_PHONE : self::DEVICE_PC;
        $params['uid'] = $this->uuid();
        $params['order'] = $order->secure_id;
        $params['gid'] = '0';
        $params['price'] = $this->convertAmountToCurrency($amount);
        $params['mode'] = '2';
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['notifyUrl'] = urlencode($this->getNotifyUrl($orderId));
        $params['sn'] = $this->sign($params);

        $this->CI->utils->debug_log('=====================tpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlFormRedirect($params);
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

    protected function processPaymentUrlFormRedirect($params) {
        $url = $this->getSystemInfo('url');
        $response = $this->submitGetForm($url, $params, false, $params['order']);

        $decoded = json_decode($response, true);

        $this->CI->utils->debug_log('=====================tpay processPaymentUrlFormQRcode received response', $response);
        $this->CI->utils->debug_log('=====================tpay processPaymentUrlFormQRcode response json to array', $decoded);

        if(isset($decoded['e']) && $decoded['e']==self::ORDER_STATUS_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decoded['d']['h5'],
                );
        } elseif(isset($decoded['e']) && isset($decoded['m'])) {
            $error_msg = "[".$decoded['e']."]".$decoded['m'];
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $error_msg
            );
        } else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
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

        $this->CI->utils->debug_log("=====================tpay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['order'], null, null, null, $response_result_id);
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
            'order','amount','sn'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================tpay Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================tpay Signature Error', $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount);

        if ($fields['amount'] != $check_amount) {
            $this->writePaymentErrorLog("======================tpay Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        }

        if ($fields['order'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================tpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'notifyUrl' || $key == 'sn' || $key == 'freePrice' ) {
                continue;
            }
            $urlencodeValue=urlencode($value);
            $signStr .= "$key=$urlencodeValue";
        }
        $signStr .= "secret=".$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sn') {
                continue;
            }
            $urlencodeValue=urlencode($value);
            $signStr .= "$key=$urlencodeValue";
        }
        $signStr .= "secret=".$this->getSystemInfo('key');
        $sign = md5($signStr);

        if($params['sn'] == $sign)
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

    public function uuid(){
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s', str_split(bin2hex($data), 16));
    }

}
