<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * KANPAY
 *
 * * KANPAY_PAYMENT_API, ID: 791
 * * KANPAY_ALIPAY_PAYMENT_API, ID: 792
 * * KANPAY_QQPAY_PAYMENT_API, ID: 793
 * * KANPAY_WEIXIN_PAYMENT_API, ID: 794
 * * KANPAY_BDPAY_PAYMENT_API, ID: 795
 * * KANPAY_JDPAY_PAYMENT_API, ID: 796
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Sha key
 *
 * Field Values:
 *
 * * Extra Info:
 * > {
 * >    "sellerEmail" : "## Seller email address, system will show you when the merchant opens ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

abstract class Abstract_payment_api_kanpay extends Abstract_payment_api {

	//PRODUCT_ID
	const DEFAULTNANK_BANK = '907';

	//BUS_NO
	const DEFAULTNANK_WEIXIN = '902';
	const DEFAULTNANK_ALIPAY = '933';
	const DEFAULTNANK_QQPAY = '908';
	const DEFAULTNANK_JDPAY = '910';
	const DEFAULTNANK_BDPAY = '909';

	const DEFAULTNANK_WEIXIN_WAP = '901';
	const DEFAULTNANK_ALIPAY_WAP = '933';
	const DEFAULTNANK_QQPAY_WAP = '905';

	const RETURN_SUCCESS_CODE = 'ok';
    const RECALL_SUCCESS_CODE = '00';
    const CHECK_SUCCESS_CODE = 'SUCCESS';


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
        $params['pay_applydate'] =  date("Y-m-d h:i:s");
		$params['pay_amount'] = $this->convertAmountToCurrency($amount);
		$params['pay_productname'] ='deposit';
		$params['pay_callbackurl'] = $this->getReturnUrl($orderId);
		$params['pay_notifyurl'] = $this->getNotifyUrl($orderId);
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['pay_md5sign'] = $this->sign($params);

		$this->CI->utils->debug_log('=========================kanpay generatePaymentUrlForm', $params);

		return $this->processPaymentUrlForm($params);
	}


	protected function processPaymentUrlFormPost($params) {

		$url=$this->getSystemInfo('url');

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $url,
			'params' => $params,
			'post' => true,
		);
	}

	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	## Reference: sample code, callback.php
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
		$this->utils->debug_log('callbackFrom' . ucfirst($source) . ': [' . $orderId .'], params:', $params);

		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		if (!$order) {
			$this->utils->error_log("Order ID [$orderId] not found.");
			return $result;
		}

		$callbackValid = false;
		$paymentSuccessful = $this->checkCallbackOrder($order, $params, $callbackValid); # $callbackValid is also assigned

		# Do not print success msg if callback fails integrity check
		if(!$callbackValid) {
			return $result;
		}

		# Do not proceed to update order status if payment failed, but still print success msg as callback response
		if(!$paymentSuccessful) {
			$result['return_error'] = self::RETURN_SUCCESS_CODE;
			return $result;
		}

		# We can respond with ack to callback now
		$success = true;
		$result['message'] = self::RETURN_SUCCESS_CODE;

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
			$this->CI->sale_order->updateExternalInfo($order->id,
				$params['orderid'], null,
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		# This $success marks whether the order status update is successful
		$result['success'] = $success;

		if ($source == 'browser') {
			$result['next_url'] = '/iframe_module/iframe_viewCashier';
			$result['go_success_page'] = true;
		}

		return $result;
	}

	# returns true if callback is valid and payment is successful
	# sets the $callbackValid parameter if callback is valid
	private function checkCallbackOrder($order, $fields, &$callbackValid) {
		# does all required fields exist?
		$requiredFields = array(
			'memberid', 'orderid', 'amount', 'transaction_id', 'datetime', 'returncode', 'attach', 'sign'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=========================kanpay checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}



		# is signature authentic?
		if ($fields['sign']!=$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=========================kanpay checkCallbackOrder validateSign Error', $fields);
			return false;
		}

		$callbackValid = true; # callbackValid is set to true once the signature verification pass

		if ($fields['returncode'] != self::RECALL_SUCCESS_CODE) {
			$this->writePaymentErrorLog('=========================kanpay checkCallbackOrder result['.$fields['v_result'].'] payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['amount']) {
			$this->writePaymentErrorLog("=========================kanpay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['orderid'] != $order->secure_id) {
			$this->writePaymentErrorLog("=========================kanpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
	public function sign($data) {
		ksort($data);
		$signStr = $this->createSignStr($data);
		$signStr .="key=" .$this->getSystemInfo('key');

		$sign=strtoupper(md5($signStr));
		return $sign;
	}

	private function validateSign($data) {
		ksort($data);
		$origin_sign = $data['sign'];
		$signStr = $this->createSignStr($data);
		$signStr .="key=" .$this->getSystemInfo('key');

		$sign=strtoupper(md5($signStr));

		return $sign;
	}

	public function createSignStr($params) {
		$signStr = "";
		foreach ($params as $key=>$value) {
            if($key != "pay_md5sign" && $key != "pay_productname" && $key != "sign" && $key != "attach"){
			    $signStr .= $key."=".$value."&";
            }
		}
		return $signStr;
	}
}