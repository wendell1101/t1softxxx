<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * STARSPAY
 *

 * * STARSPAY_WITHDRAWAL_PAYMENT_API', 5965);
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://developer.starspay.com/cn/reference/checkout-redirect.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_starspay extends Abstract_payment_api {
    const REQUEST_SUCCESS     = 200;
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const PAY_RESULT_SUCCESS  = '1';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
        $detailInfo['merchant_ref'] = $order->secure_id;
        $detailInfo['product'] = 'BrazilQR';
        $detailInfo['amount'] = $this->convertAmountToCurrency($amount);

        $params = array();
        $params['merchant_no'] = $this->getSystemInfo("account");
        $params['params'] = json_encode($detailInfo);
        $params['sign_type'] = 'MD5';
        $params['timestamp'] = time();
        $params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("====================starspay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

    protected function processPaymentUrlFormRedirect($params) {
        $jsonData = json_decode($params['params'],true);
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $jsonData['merchant_ref']);
        $decoded = json_decode($response, true);
        $decodedJsonData = json_decode($decoded['params'], true);
        $this->CI->utils->debug_log('=====================starspay processPaymentUrlFormPost response', $decodedJsonData);

        if(isset($decoded['code']) && !empty($decoded['code']) && $decoded['code'] == self::REQUEST_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decodedJsonData['payurl'],
            );
        }
        else if(isset($decoded['code']) && !empty($decoded['code']) && isset($decoded['message']) && !empty($decoded['message'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => '['.$decoded['code'].']: '.$decoded['message']
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

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->utils->debug_log("=======================starspay getOrderIdFromParameters", $flds);
        if(empty($flds) || is_null($flds)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }else{
            $params = json_decode($flds['params'],true);
        }

        if (isset($params['merchant_ref'])) {
            $this->CI->load->model(array('sale_order'));
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['merchant_ref']);
            return $order->id;
        }
        else {
            $this->utils->debug_log("=======================starspay callbackOrder cannot get any order_id when getOrderIdFromParameters", $flds);
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

        $this->CI->utils->debug_log("=====================starspay callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================starspay raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================starspay json_decode data", $params);
            }

            if(isset($params['params']) && !empty($params['params'])){
                $jsonParams = json_decode($params['params'],true);
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
            $this->CI->sale_order->updateExternalInfo($order->id, null, null, null, null, $response_result_id);
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

	private function checkCallbackOrder($order, $params, &$processed = false) {
		$requiredFields = array('merchant_ref', 'system_ref', 'amount', 'status');

        if(isset($params['params'])){
            $jsonData = json_decode($params['params'],true);
        }else{
            $jsonData = array();
        }

        $this->CI->utils->debug_log("=====================starspay json_decode params", $params);

        foreach ($requiredFields as $f) {
           if (!array_key_exists($f, $jsonData)) {
                $this->writePaymentErrorLog("=======================starspay  checkCallbackOrder missing parameter: [$f]", $params);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass


        $amount = $this->convertAmountToCurrency($order->amount);
        if ($jsonData['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================starspay checkCallbackOrder Payment amount do not match, expected [$amount]", $params);
            return false;
        }

        if ($jsonData['merchant_ref'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================starspay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $params);
            return false;
        }

        if ($jsonData['status'] != self::PAY_RESULT_SUCCESS) {
            $this->writePaymentErrorLog("======================starspay checkCallbackOrder Payment status is not success", $params);
            return false;
        }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 0, '.', '');
	}

    public function sign($params) {
        $signStr = $this->getSystemInfo("account").$params['params'].$params['sign_type'].$params['timestamp'].$this->getSystemInfo('key');
        $signature = md5($signStr);
        return $signature;
    }

    public function verifySignature($params) {
        $callback_sign = $this->getSystemInfo("account").$params['params'].$params['sign_type'].$params['timestamp'].$this->getSystemInfo('key');
        $sign= md5($callback_sign);
        return $sign == $params['sign'];
    }
}
