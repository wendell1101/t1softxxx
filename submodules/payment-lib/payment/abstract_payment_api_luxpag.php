<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * luxpag
 *
 * * LUXPAG_PAYMENT_API, ID: 5930
 * * LUXPAG_PIX_PAYMENT_API, ID: 5931
 * * LUXPAG_CASH_PAYMENT_API, ID: 5932
 * * LUXPAG_VOUCHER_PAYMENT_API, ID: 5933
 * * LUXPAG_WITHDRAWAL_PAYMENT_API', 5937);
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://developer.luxpag.com/cn/reference/checkout-redirect.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_luxpag extends Abstract_payment_api {

    const CHANNEL_TRANSFER   = 'DepositExpress';
    const CHANNEL_PIX        = 'Pix';
    const CHANNEL_CASH       = 'Boleto';
    const CHANNEL_VOUCHER    = 'Lotopay';

    const RESULT_CODE_SUCCESS = "10000";
	const RETURN_SUCCESS_CODE = 'success';
	const PAY_RESULT_SUCCESS = 'SUCCESS';

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

        $params = array();
        $params['app_id']           = $this->getSystemInfo("account");
        $params['timestamp']        = $orderDateTime->format('Y-m-d H:i:s');
        $params['out_trade_no']     = $order->secure_id;
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['order_currency']   = 'BRL';
        $params['order_amount']     = $this->convertAmountToCurrency($amount);
        $params['subject']          = 'Deposit';
        $params['trade_type']       = 'WEB';
        $params['notify_url']       = $this->getNotifyUrl($orderId);
        $params['return_url']       = $this->getReturnUrl($orderId);
        $params['buyer_id']         = $playerId;

		$this->CI->utils->debug_log("====================luxpag generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

    protected function processPaymentUrlFormRedirect($params) {

        $url = $this->getSystemInfo('url');
        $response = $this->processCurl($params, $url);

        if(isset($response['code']) && !empty($response['code']) && $response['code'] == self::RESULT_CODE_SUCCESS) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['out_trade_no']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['out_trade_no']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['web_url'],
            );
        }
        else if(isset($response['code']) && !empty($response['code']) && isset($response['msg']) && !empty($response['msg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => '['.$response['code'].']: '.$response['msg']
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

        $this->CI->utils->debug_log("=====================luxpag callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================luxpag raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================luxpag json_decode data", $params);
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
		$requiredFields = array('amount', 'out_trade_no', 'trade_status','app_id');

        $this->CI->utils->debug_log("=====================luxpag json_decode params", $params);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $params)) {
				$this->writePaymentErrorLog("=====================luxpag missing parameter: [$f]", $params);
				return false;
			}
		}

        # is signature authentic?
        if (!$this->validateSign($params)) {
            $this->writePaymentErrorLog("=====================luxpag checkCallbackOrder verify signature Error", $params);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass


        $amount = $this->convertAmountToCurrency($order->amount);
        if ($params['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================luxpag checkCallbackOrder Payment amount do not match, expected [$amount]", $params);
            return false;
        }

        if ($params['out_trade_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================luxpag checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $params);
            return false;
        }

        if ($params['trade_status'] != self::PAY_RESULT_SUCCESS) {
            $this->writePaymentErrorLog("======================luxpag checkCallbackOrder Payment status is not success", $params);
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

    private function validateSign($params) {
        $headers = $this->CI->input->request_headers();
        $this->CI->utils->debug_log("=====================luxpag callback Headers params", $headers);

        foreach ($headers as $key => $value) {
            if(strpos($key,'Signature') !== false){
                $hmac = $value;
            }
        }
        $sign = hash_hmac('sha256', json_encode($params), $this->getSystemInfo('key'));
        $this->CI->utils->debug_log("=====================luxpag callback Headers sign", $sign, $params);
        if ( $hmac == $sign ) {
            return true;
        } else {
            return false;
        }
    }

    public function processCurl($params) {
        $ch = curl_init();
        $url = $this->getSystemInfo('url');
        $token = base64_encode($this->getSystemInfo("account").':'.$this->getSystemInfo('key'));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        $headers = [
            'Content-Type: application/json',
            'Authorization: Basic ' . $token
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['out_trade_no']);

        $this->CI->utils->debug_log('=====================luxpag processCurl response', $response);
        $response = json_decode($response, true);

        $this->CI->utils->debug_log('=====================luxpag processCurl decoded response', $response);
        return $response;
    }
}
