<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * jm
 *
 * * 'JM_ALIPAY_PAYMENT_API', ID 5266
 * * 'JM_ALIPAY_H5_PAYMENT_API', ID 5267
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
abstract class Abstract_payment_api_jm extends Abstract_payment_api {

    const CODE_TYPE_ALIPAY = 1003;
    const CODE_TYPE_ALIPAY_H5 = 1004;

    const RETURN_SUCCESS_CODE = '1';
    const RETURN_SUCCESS = 'success';


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
		$params['customer'] = $this->getSystemInfo("account");
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['orderid'] = $order->secure_id;
		$params['asynbackurl'] = $this->getNotifyUrl($orderId);
		$params['request_time'] = date("YmdHis");
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================jm generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
    }


	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
	    $url = $this->getSystemInfo('url');
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $url,
			'params' => $params,
			'post' => true,
		);
	}


	/**
	 * detail: This will be called when the payment is async, API server calls our callback page,
	 * When that happens, we perform verifications and necessary database updates to mark the payment as successful
	 *
	 * @param int $orderId order id
	 * @param array $params
	 * @return array
	 */
	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	/**
	 * detail: This will be called when user redirects back to our page from payment API
	 *
	 * @param int $orderId order id
	 * @param array $params
	 * @return array
	 */
	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================jm callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['order_no'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS;
		} else {
			$result['message'] = "FAIL";
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	/**
	 * detail: Validates whether the callback from API contains valid info and matches with the order
	 *
	 * @return boolean
	 */

	private function checkCallbackOrder($order, $fields, &$processed = false) {

		$requiredFields = array('orderid','result','amount','systemorderid','completetime');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================jm missing parameter: [$f]", $fields);
				return false;
			}
		}
		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================jm checkCallbackOrder Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['result'] != self::RETURN_SUCCESS_CODE) {
			$payStatus = $fields['result'];
			$this->writePaymentErrorLog("=====================jm Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================jm checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['orderid'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================jm checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
    }


	# -- amount --
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

    # -- asynbackurl異步通知 --
    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    # -- signatures --
     private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtolower(md5($signStr));
        return $sign;
    }

    private function createSignStr($params) {
		$params = array("customer"=>$params['customer'],"banktype"=>$params['banktype'],"amount"=>$params['amount'],"orderid"=>$params['orderid'],"asynbackurl"=>$params['asynbackurl'],"request_time"=>$params['request_time']);

		$signStr = '';
        foreach($params as $key => $value) {
            $signStr.=$key."=".$value."&";
        }
        $signStr = $signStr."key=".$this->getSystemInfo('key');
		return $signStr;
    }

    private function validateSign($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            $signStr.=$key."=".$value."&";
        }
        $signStr = $signStr."key=".$this->getSystemInfo('key');
		$sign = strtolower(md5($signStr));
		if($sign == $sign){
			return true;
		}
		else{
			return false;
		}
	}
}


