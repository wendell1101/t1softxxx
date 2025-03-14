<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ANTOPAY 云安付
 * https://pay.antopay.com/antopay.html
 *
 * ANTOPAY_PAYMENT_API, ID: 235
 * ANTOPAY_ALIPAY_PAYMENT_API, ID: 236
 * ANTOPAY_WEIXIN_PAYMENT_API, ID: 237
 * ANTOPAY_TENPAY_PAYMENT_API, ID: 238
 * ANTOPAY_UNIONPAY_PAYMENT_API, ID: 402
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_antopay extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'ok';
	const RETURN_FAILED_CODE = 'orderstatus!=0';
	const OPSTAT_SUCCESS = '1';

	public abstract function getBankType($direct_pay_extra_info);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params['partner'] = $this->getSystemInfo("account");
		$params['banktype'] = $this->getBankType($order->direct_pay_extra_info);
		$params['paymoney'] = $this->convertAmountToCurrency($amount);
		$params['ordernumber'] = $order->secure_id;
		$params['callbackurl'] = $this->getNotifyUrl($orderId);
		$params['hrefbackurl'] = $this->getReturnUrl($orderId);
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log('======================================antopay generatePaymentUrlForm: ', $params);
		return $this->processPaymentUrlForm($params);
	}

    protected function processPaymentUrlForm($params) {
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $this->getSystemInfo('url'),
            'params' => $params,
            'post' => false,
        );
    }

	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		# According to documentation, callback from browser cannot be used to change order status. We need to rely on callback from server.
		# Return success here.
		return array('success' => true, 'next_url' => $this->getPlayerBackUrl());
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			return $result;
		}

		# Update order payment status and balance
		$success=true;

		# Update player balance based on order status
		# if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
		$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
		if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
			$this->CI->utils->debug_log('====================callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
			if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
				$this->CI->sale_order->setStatusToSettled($orderId);
			}
		} else {
			# update player balance
			$this->CI->sale_order->updateExternalInfo($order->id, $params['sysorderid'], null, null, null, $response_result_id);
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
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		# does all required fields exist?
		$requiredFields = array(
			'ordernumber', 'orderstatus', 'paymoney', 'sign'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->verify($fields, $fields['sign'])) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		# check parameter values: orderStatus, tradeAmt, orderNo, merchNo
		# is payment successful?
		if ($fields['orderstatus'] != self::OPSTAT_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		# does amount match?
		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['paymoney']))
		) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		$this->CI->utils->debug_log('====================antopay checkCallbackOrder all passed, return true');
		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	public function getBankListInfoFallback() {
		return array(
			array('label' => '京东支付', 'value' => 'MSJD'),
			array('label' => '工商银行', 'value' => 'ICBC'),
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '农业银行', 'value' => 'ABC'),
			array('label' => '建设银行', 'value' => 'CCB'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '交通银行', 'value' => 'BOCO'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '民生银行', 'value' => 'CMBC'),
			array('label' => '光大银行', 'value' => 'CEB'),
			array('label' => '平安银行', 'value' => 'PINGANBANK'),
			array('label' => '广发银行', 'value' => 'GDB'),
			array('label' => '中信银行', 'value' => 'CTTIC'),
			array('label' => '中国邮政', 'value' => 'PSBS'),
			array('label' => '北京银行', 'value' => 'BCCB')
		);
	}

	# -- Private functions --
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	private function prepareSign($data) {
		$array = array();
		$keys = array('partner', 'banktype', 'paymoney', 'ordernumber', 'callbackurl');
		foreach ($keys as $key) {
			array_push($array, $key . '=' . $data[$key]);
		}
		return implode($array, '&');
	}

	public function sign($data) {
		$dataStr = $this->prepareSign($data);
		$singStr = $dataStr . $this->getSystemInfo('key');
		$signature = MD5($singStr);
		return $signature;
	}

	public function prepareVerify($data) {
		$array = array();
		$keys = array('partner', 'ordernumber', 'orderstatus', 'paymoney');
		foreach ($keys as $key) {
			array_push($array, $key . '=' . $data[$key]);
		}
		return implode($array, '&');
	}

	public function verify($data, $signature) {
		$dataStr = $this->prepareVerify($data);
		$signature = MD5($dataStr.$this->getSystemInfo('key'));
		if ($data['sign'] == $signature) {
			return true;
		} else {
			return false;
		}
	}
}
