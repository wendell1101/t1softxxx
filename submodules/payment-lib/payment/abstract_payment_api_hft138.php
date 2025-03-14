<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * HFT138 浩付通
 *
 * * HFT138_ALIPAY_PAYMENT_API, ID: 5342
 * * HFT138_ALIPAY_H5_PAYMENT_API, ID: 5343
 * * HFT138_UNIONPAY_PAYMENT_API, ID: 5344
 *
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
 * @copyright 2017-2022 tot
 */

abstract class Abstract_payment_api_hft138 extends Abstract_payment_api {


	const BANKCODE_ALIPAY = '903';
    const BANKCODE_ALIPAY_WAP = '904';

	const BANKCODE_UNIONPAY = '911';

	const RETURN_SUCCESS = 'OK';
    const RECALL_SUCCESS_CODE = '00';


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
		$params['pay_memberid'] = $this->getSystemInfo("account");
        $params['pay_orderid'] =  $order->secure_id;
        $params['pay_amount'] = $this->convertAmountToCurrency($amount); //元
        $params['pay_applydate'] =  date("Y-m-d h:i:s");
		$this->configParams($params, $order->direct_pay_extra_info); //$params['pay_bankcode']
		$params['pay_notifyurl'] = $this->getNotifyUrl($orderId);
		$params['pay_callbackurl'] = $this->getReturnUrl($orderId);
		$params['pay_md5sign'] = $this->sign($params);

		$this->CI->utils->debug_log('=========================hft138 generatePaymentUrlForm', $params);
		return $this->processPaymentUrlForm($params);
	}


	protected function processPaymentUrlFormPost($params) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
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
            $this->CI->utils->debug_log('=======================hft138 callbackFromServer server callbackFrom', $params);
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['transaction_id'], null, null, null, $response_result_id);
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

	private function checkCallbackOrder($order, $fields, &$callbackValid) {
		# does all required fields exist?
		$requiredFields = array(
			'memberid', 'orderid', 'amount', 'transaction_id', 'datetime', 'returncode', 'sign'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=========================hft138 checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if ($this->validateSign($fields)) {
			$this->writePaymentErrorLog('=========================hft138 checkCallbackOrder validateSign Error', $fields);
			return false;
		}

		$callbackValid = true; # callbackValid is set to true once the signature verification pass

		if ($fields['returncode'] != self::RECALL_SUCCESS_CODE) {
			$this->writePaymentErrorLog('=========================hft138 checkCallbackOrder result['.$fields['v_result'].'] payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['amount']) {
			$this->writePaymentErrorLog("=========================hft138 checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['orderid'] != $order->secure_id) {
			$this->writePaymentErrorLog("=========================hft138 checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- private helper functions --
	protected function getBankListInfoFallback() {
		return array(
			array('label' => '工商银行', 'value' => 'icbc'),
			array('label' => '中国银行', 'value' => 'boc'),
			array('label' => '招商银行', 'value' => 'cmb'),
			array('label' => '广发银行', 'value' => 'gdb'),
			array('label' => '中信银行', 'value' => 'cncb'),
			array('label' => '光大银行', 'value' => 'ceb'),
			array('label' => '农业银行', 'value' => 'abc'),
			array('label' => '建设银行', 'value' => 'ccb'),
			array('label' => '交通银行', 'value' => 'comm'),
			array('label' => '兴业银行', 'value' => 'cib'),
			array('label' => '民生银行', 'value' => 'cmbc')
		);
	}

	public function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	public function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	# -- signing --
	public function sign($params) {
		$signStr = $this->createSignStr($params);
		$sign = strtoupper(md5($signStr));
		return $sign;
	}

	public function createSignStr($params) {
		ksort($params);
		$signStr = "";
		foreach ($params as $key=>$value) {
            if($key != "pay_md5sign" && $key != "sign" && $key != "attach"){
			    $signStr .= $key."=".$value."&";
            }
		}
		$signStr .="key=" .$this->getSystemInfo('key');

		return $signStr;
	}

	private function validateSign($params) {
		$origin_sign = $params['sign'];

		$signStr = $this->createSignStr($params);
		$sign = strtoupper(md5($signStr));

		if($origin_sign == $sign){
			return true;
		}
		else{
			return false;
		}
    }

}