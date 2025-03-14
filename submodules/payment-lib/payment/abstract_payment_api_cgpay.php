<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * cgpay
 *
 * * CGPAY_PAYMENT_API, ID 6041
 * * CGPAY_USDT_TRC_PAYMENT_API  ID 6042
 * * CGPAY_USDT_ERC_PAYMENT_API  ID 6043
 * * CGPAY_WITHDRAWAL_PAYMENT_API ID 6044

 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_cgpay extends Abstract_payment_api {
	const PAYWAY_CGP	      = 'CGP';
	const PAYWAY_USDT_ERC	  = 'USDT_ERC20';
	const PAYWAY_USDT_TRC	  = 'USDT_TRC20';
	const RETURN_SUCCESS_CODE = 'success';
    const REQUEST_SUCCESS 	  = '0';

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
		$params['temp_orderId'] = $orderId;
		$params['temp_amount'] = $amount;
		$params['MerchantId'] = $this->getSystemInfo("account");
		$params['MerchantOrderId'] = $order->secure_id;
		$params['OrderDescription'] = 'deposit';
		$params['Amount'] = $this->convertAmountToCurrency($amount);
		$this->configParams($params, $order->direct_pay_extra_info);
		unset($params['temp_orderId'],$params['temp_amount']);
		$params['CallBackUrl'] = $this->getNotifyUrl($orderId);
		$params['Ip'] = $this->getClientIP();
		$params['Sign'] = $this->sign($params);

		return $this->processPaymentUrlForm($params);
	}

    protected function processPaymentUrlFormPost($params) {
    	$this->CI->utils->debug_log("=====================cgpay generatePaymentUrlForm", $params);
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['MerchantOrderId']);
        $this->CI->utils->debug_log('=====================cgpay processPaymentUrlFormURL received response', $response);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('=====================cgpay processPaymentUrlFormURL json to array', $response);

        $msg = lang('Invalidte API response');

		if(isset($response['ReturnCode']) && ($response['ReturnCode'] == self::REQUEST_SUCCESS)) {
			$orderId = $params['MerchantOrderId'];
			$order = $this->CI->sale_order->getSaleOrderBySecureId($orderId);
			//cgp : rmb = 1:1
            $this->CI->sale_order->createCryptoDepositOrder($order->id, $this->revertAmountToCurrency($params['Amount']) , 1, null, null, 'CGP');
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['Qrcode']
            );
        }
        else {
            if(isset($response['ReturnMessage']) && !empty($response['ReturnMessage'])) {
                $msg = $response['ReturnMessage'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

    protected function processPaymentUrlFormQRCode($params) {
        $amount = $params['AnchoredRMB'];
        $cryptoQty = $params['crypto_amount'];
        $validateRateResult = $this->validateDepositCryptoRate('USDT', $amount, $cryptoQty);

        if(!$validateRateResult['status']){
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $validateRateResult['msg']
            );
        }elseif($validateRateResult['rate'] != 0){
            $rate = $validateRateResult['rate'];
        }else{
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'crypto rate has errors'
            );
        }
        unset($params['rate'], $params['crypto_amount']);
        $this->CI->utils->debug_log("=====================cgpay crypto generatePaymentUrlForm", $params);
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['MerchantOrderId']);
        $this->CI->utils->debug_log('=====================cgpay processPaymentUrlFormURL received response', $response);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('=====================cgpay processPaymentUrlFormURL json to array', $response);

        $msg = lang('Invalidte API response');

		if(isset($response['ReturnCode']) && ($response['ReturnCode'] == self::REQUEST_SUCCESS)) {
			$orderId = $params['MerchantOrderId'];
			$order = $this->CI->sale_order->getSaleOrderBySecureId($orderId);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['CryptoWallet'], $response['OrderId'], $cryptoQty);
            $this->CI->sale_order->createCryptoDepositOrder($order->id, $cryptoQty, $rate, null, null,'USDT');
            $deposit_notes = '3rd Api Wallet address: '.$response['CryptoWallet'].' | '.' Real Rate: '.$rate . '|' . 'USDTcoin: ' . $cryptoQty;
            $this->CI->sale_order->appendNotes($order->id, $deposit_notes);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['Qrcode']
            );
        }
        else {
            if(isset($response['ReturnMessage']) && !empty($response['ReturnMessage'])) {
                $msg = $response['ReturnMessage'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
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

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================cgpay callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['MerchantOrderId'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields, &$processed = false) {

		$requiredFields = array('MerchantOrderId','PayAmount','PaymentId', 'Sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================cgpay missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================cgpay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		$crypto_amount = $this->convertAmountToCrypto($order->id);
        if ($crypto_amount != $this->revertAmountToCurrency($fields['PayAmount'])) {
            $this->writePaymentErrorLog("=====================cgpay Payment amounts do not match, expected ", $crypto_amount);
            return false;
        }

        if ($fields['MerchantOrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================cgpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }


        $processed = true; # processed is set to true once the signature verification pass

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- Private functions --
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	public function convertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 100000000);
        return number_format($amount * $convert_multiplier, 0, '.', '') ;
    }

    public function revertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 100000000);
        return number_format($amount / $convert_multiplier, 0, '.', '') ;
    }

	public function sign($params) {
		$signStr = $this->createSignStr($params);
        $sign = md5($signStr);
		return $sign;
	}

    public function verifySignature($params) {
    	$params = array_change_key_case($params, CASE_LOWER);
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign' || $key == 'rate' || $key == 'crypto_amount' || empty($value)) {
                continue;
            }
            $signStr .= "$value,";
        }
        $sign = strtoupper(md5($signStr.$this->getSystemInfo('key')));
        return $sign == $params['sign'];
    }

    public function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'Sign' || $key == 'rate' || $key == 'crypto_amount' || empty($value)) {
                continue;
            }
            $signStr .= "$value,";
        }
        $this->CI->utils->debug_log("=====================cgpay createSignStr", $signStr.$this->getSystemInfo('key'));
		return $signStr.$this->getSystemInfo('key');
	}

	protected function convertAmountToCrypto($orderId) {
        $cryptoOrder = $this->CI->sale_order->getCryptoDepositOrderBySaleOrderId($orderId);
        $cryptoAmount = $cryptoOrder->received_crypto;
        $this->CI->utils->debug_log("=======================WAASusdt convertAmountToCrypto,orderId",$cryptoAmount,$orderId);
        return number_format($cryptoAmount, 2, '.', '');
    }
}