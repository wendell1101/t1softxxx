<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * AIMAPAY 爱码支付
 * *
 * * 'AIMAPAY_ALIPAY_PAYMENT_API', ID: 5379
 * * 'AIMAPAY_ALIPAY_H5_PAYMENT_API', ID: 5380
 * * 'AIMAPAY_WEIXIN_PAYMENT_API', ID: 5381
 * * 'AIMAPAY_WEIXIN_H5_PAYMENT_API', ID: 5382
 * * 'AIMAPAY_WITHDRAWAL_PAYMENT_API', ID: 5385
 * * 'AIMAPAY_WITHDRAWAL_2_PAYMENT_API', ID: 5386
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

abstract class Abstract_payment_api_aimapay extends Abstract_payment_api {

    const PAYTYPE_ALIPAY = 'ALIPAY_NATIVE';
    const PAYTYPE_ALIPAY_H5 = 'ALIPAY_H5';
    const PAYTYPE_WEIXIN = 'WEIXIN_NATIVE';
    const PAYTYPE_WEIXIN_H5 = 'WEIXIN_H5';

	const RETURN_SUCCESS_CODE = '1';
	const RETURN_SUCCESS = 'success';
	const RETURN_FAILED = 'fail';

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
		$params['merId'] = $this->getSystemInfo("account");
		$params['orderNo'] = $order->secure_id;
        $params['amount'] = $this->convertAmountToCurrency($amount); //元
		$this->configParams($params, $order->direct_pay_extra_info); //$params['payType']
		$params['goodsName'] = 'Deposit';
        $params['notifyUrl'] = $this->getNotifyUrl($orderId);
		$params['returnUrl'] = $this->getReturnUrl($orderId);
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("==========================aimapay generatePaymentUrlForm", $params);
		return $this->processPaymentUrlForm($params);
	}


	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
		return array(
			'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true,
		);
	}

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
    	$url = $this->getSystemInfo('url').$this->getSystemInfo('qucode_url');
		$response = $this->submitPostForm($url, $params, false, $params['orderNo']);
		$decode_data = json_decode($response, true);
        $this->CI->utils->debug_log('========================================aimapay processPaymentUrlFormQRcode json_decode', $decode_data);

        $msg = lang('Invalidate API response');
		if($decode_data['code'] == self::RETURN_SUCCESS_CODE) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decode_data['data']['url'],
            );
        }else {
            if($decode_data['code'] != self::RETURN_SUCCESS_CODE && !empty($decode_data['msg'])) {
                $msg = $decode_data['msg'].": ".$decode_data['code'];
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

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================aimapay callbackFromServer server callbackFrom', $params);
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
			$result['message'] = self::RETURN_SUCCESS;
		} else {
			$result['message'] = $processed ? self::RETURN_SUCCESS : self::RETURN_FAILED;
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array('orderNo', 'amount','status');
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================aimapay missing parameter: [$f]", $fields);
				return false;
			}
		}
        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=======================aimapay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['status'] != self::RETURN_SUCCESS_CODE) {
			$payStatus = $fields['status'];
			$this->writePaymentErrorLog("=====================aimapay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
			$this->writePaymentErrorLog("=====================aimapay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['orderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================aimapay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }


		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}


	# -- notifyURL --
	public function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

    # -- returnURL --
	public function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

    # -- amount --
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

    # -- signatures --
    private function sign($params) {
        $signStr = $this->createSignStr($params);
		$sign = md5($signStr);
        return $sign;
	}

    private function createSignStr($params) {
        ksort($params);
		$signStr = '';
        foreach($params as $key => $value) {
            if( $key == 'sign'){
                continue;
            }
            $signStr .= $value;
        }
        $signStr .= $this->getSystemInfo('key');
		return $signStr;
    }

    private function validateSign($params) {
        $signStr = $this->createSignStr($params);
		$sign = strtoupper(md5($signStr));
		if($params['sign'] == $sign){
			return true;
		}
		else{
			return false;
		}
    }
}
