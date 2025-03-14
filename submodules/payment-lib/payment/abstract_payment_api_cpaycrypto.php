<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * CPAYCRYPTO
 *
 * * CPAYCRYPTO_USDT_PAYMENT_API, ID: 6223
 *
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
 * @category Payment
 * @copyright 2013-2023 tot
 */
abstract class Abstract_payment_api_cpaycrypto extends Abstract_payment_api {

    const RETURN_SUCCESS_CODE = 'success';
    const REPONSE_CODE_SUCCESS = 0;
    const PAY_RESULT_SUCCESS = '14';

    const CPAYCRYPTO_USDT = "USDT";
    
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
        $this->configParams($params, $order->direct_pay_extra_info);

        $params['merchantId'] = $this->getSystemInfo("account");
        $params['merchantTradeNo'] = $order->secure_id;
        $params['createTime'] = $orderDateTime->format('Ymdhis');
        $params['userId'] = $playerId;
        // $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['cryptoCurrency'] = self::CPAYCRYPTO_USDT; 
        $params['callBackURL'] = $this->getNotifyUrl($orderId);
        $params['successURL']   = $this->getReturnUrl($orderId);
        $params['sign']         = $this->sign($params);

		$this->CI->utils->debug_log("=====================cpaycrypto generatePaymentUrlForm", $params);


		return $this->processPaymentUrlForm($params);
	}


    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['merchantTradeNo']);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================cpaycrypto processPaymentUrlFormPost response json to array', $response);

        list($crypto, $cryptoRate) = $this->CI->utils->convertCryptoCurrency($params['amount'], 'USDT', 'USDT', 'deposit');
        $this->CI->utils->debug_log('=====================cpaycrypto crypto', $crypto);

        $msg = lang('Invalidate API response');
        if(isset($response['code']) && $response['code'] == self::REPONSE_CODE_SUCCESS ){

            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['merchantTradeNo']);
            $this->CI->sale_order->createCryptoDepositOrder($order->id, $params['amount'] , $cryptoRate, null, null, self::CPAYCRYPTO_USDT);
            $deposit_notes = 'cryptoRate'. $cryptoRate .'USDTcoin: ' . $params['amount'];
            $this->CI->sale_order->appendNotes($order->id, $deposit_notes);

            if(isset($response['data']['cashierURL']) && !empty($response['data']['cashierURL'])){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['data']['cashierURL'],
                );
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

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $params = json_decode($raw_post_data,true);
            }

            $this->CI->utils->debug_log("=====================cpaycrypto callbackFromServer params", $params);
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

		# Update order payment status and balance
		$success=true;

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
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['message'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array('merchantId', 'orderStatus', 'actualAmount', 'receivedAmount', 'fee');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================cpaycrypto missing parameter: [$f]", $fields);
				return false;
			}
		}

        $cryptoDetails = $this->CI->sale_order->getCryptoDepositOrderBySaleOrderId($order->id);
        $crypto = $cryptoDetails->received_crypto;
        $currency = $cryptoDetails->crypto_currency;
        if ($fields['actualAmount'] != $crypto) {
            $this->writePaymentErrorLog("=====================cpaycrypto checkCallbackOrder Payment crypto is wrong, expected [$crypto]", $fields);
            return false;
        }

        if ($fields['cryptoCurrency'] != $currency) {
            $this->writePaymentErrorLog("=====================cpaycrypto checkCallbackOrder Payment currency is wrong, expected [$currency]", $fields);
            return false;
        }

        # is signature authentic?
        if ($fields["sign"] != $this->verifySign($fields)) {
        	$this->writePaymentErrorLog('=========================cpaycrypto checkCallback signature Error', $fields["sign"]);
        	return false;
        }   

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['orderStatus'] != self::PAY_RESULT_SUCCESS) {
            $this->writePaymentErrorLog('=====================cpaycrypto checkCallbackOrder payment was not successful', $fields);
            return false;
        }

        # everything checked ok
        return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

    //no use
    public function getCryptoRate($amount){
        
        $crypto_url = $this->getSystemInfo('exchange_rate_url');

        $params = array();
        $params['merchantId'] = $this->getSystemInfo("account");
        $params['sourceCurrency'] = "BRL";
        $params['targetCurrency'] = self::CPAYCRYPTO_USDT;
        $params['purchaseType'] = 1;
        $params['amount'] = $amount;
        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log("=====================cpaycrypto getCryptoRate", $params);

        return $this->submitGetForm($crypto_url, $params, true, null);
    }

	# -- Private functions --
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 1, '.', '');
	}

    public function sign($params) {

        ksort($params);
        
        $url = '';
        if (is_array($params) && count($params)>0) {
            foreach ($params as $k => $v) {
            $url = $url . "{$k}={$v}&";
            }
        }

        $key = $this->getSystemInfo('key');
        $url = $url.'key='.$key;
        $secret = $key;
        
        return hash_hmac("sha256", $url, $secret);
    }

    public function verifySign($params){

        unset($params['sign']);
        return $this->sign($params);
    }

}
