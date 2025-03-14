<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * pay4wallet
 *
 * * PAY4WALLET_PAYMENT_API, ID: 6070
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.p4f.com/1.0/wallet/process
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_pay4wallet extends Abstract_payment_api {
    const RESULT_CODE_SUCCESS = 201;
	const RETURN_SUCCESS_CODE = 'OK';
	const PAY_RESULT_SUCCESS = 201;

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
        $params['amount']            = $this->convertAmountToCurrency($amount);
        $params['merchantInvoiceId'] = $order->secure_id;
        $params['language']          = $this->getSystemInfo("language");
        $params['currency']          = $this->getSystemInfo("currency");
        $params['okUrl']             = $this->getReturnUrl($orderId);
        $params['notOkUrl']          = $this->getReturnUrlFail($orderId);
        $params['confirmationUrl']       = $this->getNotifyUrl($orderId);
        $this->configParams($params, $order->direct_pay_extra_info);
		$this->CI->utils->debug_log("====================pay4wallet generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

    protected function processPaymentUrlFormRedirect($params) {

        $url = $this->getSystemInfo('url');
        $response = $this->processCurl($params, $url);

        if(isset($response['code']) && !empty($response['code']) && $response['code'] == self::RESULT_CODE_SUCCESS) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['merchantInvoiceId']);
            $this->CI->sale_order->updateExternalInfo($order->id, $params['merchantInvoiceId']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['url'],
            );
        }
        else if(isset($response['code']) && !empty($response['code']) && isset($response['message']) && !empty($response['message'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => '['.$response['code'].']: '.$response['message']
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

        $this->CI->utils->debug_log("=====================pay4wallet callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================pay4wallet raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================pay4wallet json_decode data", $params);
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
		$requiredFields = array('Amount', 'MerchantInvoiceId', 'Status','TransactionId','Sign');

        $this->CI->utils->debug_log("=====================pay4wallet json_decode params", $params);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $params)) {
				$this->writePaymentErrorLog("=====================pay4wallet missing parameter: [$f]", $params);
				return false;
			}
		}

        # is signature authentic?
        if (!$this->validateSign($params)) {
            $this->writePaymentErrorLog("=====================pay4wallet checkCallbackOrder verify signature Error", $params);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass


        $amount = $this->convertAmountToCurrency($order->amount);
        if ($params['Amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================pay4wallet checkCallbackOrder Payment amount do not match, expected [$amount]", $params);
            return false;
        }

        if ($params['MerchantInvoiceId'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================pay4wallet checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $params);
            return false;
        }

        if ($params['Status'] != self::PAY_RESULT_SUCCESS) {
            $this->writePaymentErrorLog("======================pay4wallet checkCallbackOrder Payment status is not success", $params);
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

    protected function getReturnUrlFail($orderId) {
        return parent::getCallbackUrl('/callback/browser/failed/' . $this->getPlatformCode() . '/' . $orderId);
    }

	protected function convertAmountToCurrency($amount) {
        if(!empty($this->getSystemInfo("convert_amount_to_currency_unit"))){
            $convert_amount_to_currency_unit = $this->getSystemInfo("convert_amount_to_currency_unit");
        }else{
            $convert_amount_to_currency_unit = 1;
        }
        return number_format($amount *  $convert_amount_to_currency_unit, 2, '.', '');
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
            'merchantId: '.$this->getSystemInfo("account"),
            'hash: ' .$this->sign($params),
            'Content-Type: application/json',
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['merchantInvoiceId']);

        $this->CI->utils->debug_log('=====================pay4wallet processCurl response', $response);
        $response = json_decode($response, true);

        $this->CI->utils->debug_log('=====================pay4wallet processCurl decoded response', $response);
        return $response;
    }

    public function sign($data) {
        $amount = $data['amount'] * 100;
        $signStr = $this->getSystemInfo('account').$amount.$data['merchantInvoiceId'].$this->getSystemInfo('key');
        $sign = hash_hmac('sha256', $signStr, $this->getSystemInfo('hashkey'));
        return $sign;
    }

    public function validateSign($data) {
        $amount = $data['Amount'] * 100;
        $signStr = $this->getSystemInfo('account').$amount.$data['MerchantInvoiceId'].$data['Status'];
        $sign = hash_hmac('sha256', $signStr, $this->getSystemInfo('hashkey'));
        if ( $data['Sign'] == strtoupper($sign)) {
            return true;
        } else {
            return false;
        }
    }
}
