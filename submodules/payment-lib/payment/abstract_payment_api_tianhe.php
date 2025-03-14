<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * TIANHE
 *
 * * TIANHE_PAYMENT_API, ID: 5768
 * * TIANHE_ALIPAY_PAYMENT_API, ID: 5769
 * * TIANHE_WEIXIN_PAYMENT_API, ID: 5770
 * TIANHE_BANKCARD_PAYMENT_API, ID: 5794
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.22222269.com/index/unifiedorder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_tianhe extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'success';
    const PAY_RESULT_SUCCESS = 'CODE_SUCCESS';
    const RESULT_CODE_SUCCESS = '200';


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
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['callback_url'] = $this->getNotifyUrl($orderId);
        $params['success_url'] = $this->getReturnUrl($orderId);
        $params['error_url'] = $this->getReturnUrl($orderId);
        $params['out_uid'] = $playerId;
        $params['out_trade_no'] = $order->secure_id;
        $params['version'] = 'v1.0';
        $params['return_type'] = 'json';
        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log('=====================tianhe generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['out_trade_no']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log("=======================tianhe processPaymentUrlFormPost response", $response);

        if($response['code'] == self::RESULT_CODE_SUCCESS && $response['msg'] == self::RETURN_SUCCESS_CODE) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['url'],
            );
        }
        else if(!empty($response['msg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['code'].': '.$response['msg']
            );
        }
        else {
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

        $this->CI->utils->debug_log("=====================tianhe callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['out_trade_no'], null, null, null, $response_result_id);
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
            'out_trade_no','amount','sign','callbacks'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================tianhe Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================tianhe Signature Error', $fields);
            return false;
        }

        if ($this->convertAmountToCurrency($order->amount) != $fields['amount']) {
            $this->writePaymentErrorLog("=======================huidpay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['out_trade_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================tianhe checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        if ($fields['callbacks'] != self::PAY_RESULT_SUCCESS) {
            $payStatus = $fields['callbacks'];
            $this->writePaymentErrorLog("=====================tianhe Payment was not successful, payStatus is [$payStatus]", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }

	private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    public function createSignStr($params) {
        $signStr = '';
        ksort($params);
        foreach($params as $key => $value) {
            if( $key == 'sign' ) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= "key=".$this->getSystemInfo('key');
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

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

}
