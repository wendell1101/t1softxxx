<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * NEWABCPAY 新ABC支付
 *
 * * NEWABCPAY_PAYMENT_API, ID: 5519
 * * NEWABCPAY_UNIONPAY_PAYMENT_API, ID: 5520
 * *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://abczhifu888.com/pay
 * * Extra Info:
 * > {
 * >    "newabcpay_priv_key": "## Private Key ##",
 * >    "newabcpay_pub_key": "## Public Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_newabcpay extends Abstract_payment_api {

    const PAYSIGN_ONLINEBANK = 'unionGateway';
    const PAYSIGN_UNIONPAY = 'unionFast';

    const RESULT_CODE_SUCCESS = "0000";
    const RESULT_STATUS_SUCCESS = 'SUCCESS';
    const RETURN_SUCCESS_CODE = 'SUCCESS';


    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement these to specify pay type
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
        $params['merchantNo'] = $this->getSystemInfo('account');
        $this->configParams($params, $order->direct_pay_extra_info); //$params['paySign']
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['merchantOrderNo'] = $order->secure_id;
        $params['nonceStr'] = $this->randomStr(32);
        $params['signType'] = 'HMAC_SHA256';
        $params['notifyUrl'] = $this->getReturnUrl($orderId);
        $params['callbackUrl'] = $this->getNotifyUrl($orderId);
        $params['signMsg'] = $this->sign($params);

        $this->CI->utils->debug_log('=====================newabcpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    # Implement processPaymentUrlForm
	protected function processPaymentUrlFormPost($params) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true
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


	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderid, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================newabcpay callbackFromServer server callbackFrom', $params);
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

		# Update order payment status and balance
		$success = true;

		# Update player balance based on order status
		# if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
		$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderid);
		if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
			$this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
			if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
				$this->CI->sale_order->setStatusToSettled($orderid);
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
			$result['message'] = $processed ? self::RETURN_SUCCESS_CODE : "FAILED";
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}


	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array(
			'responseCode', 'merchantOrderNo', 'amount', 'payStatus', 'signMsg'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================newabcpay checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================newabcpay checkCallbackOrder signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['responseCode'] != self::RESULT_CODE_SUCCESS) {
			$this->writePaymentErrorLog('=====================newabcpay checkCallbackOrder payment was not successful', $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['amount'])) {
			$this->writePaymentErrorLog("=====================cocozf Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['payStatus'] != self::RESULT_STATUS_SUCCESS) {
			$this->writePaymentErrorLog('=====================newabcpay checkCallbackOrder payment was not successful', $fields);
			return false;
        }

        if ($fields['merchantOrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================cocozf checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		# everything checked ok
		return true;
	}

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }


    # -- signing --
    protected function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(hash_hmac('sha256',$signStr,$this->getSystemInfo("key")));
        return $sign;
    }

    # -- validateSign --
    public function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $signature = $params['signMsg'];

        if (!hash_equals(hash_hmac('sha256', $signStr, $this->getSystemInfo("key")), $signature)) {
            return false;
        }
        return true;
    }

    protected function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'signMsg') {
                continue;
            }
            if($key == 'callbackUrl' || $key == 'notifyUrl') {
                $value = urlencode($value);
            }
            $signStr .= "$key=$value&";
        }
        return rtrim($signStr, '&');
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

    public function randomStr($length) {
	    $str="abcdefghijklmnoparstuvwxyz12345678901234567890";
	    $result=substr(str_shuffle($str),0,$length);
	    return $result;
	}
}