<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ZUPAY
 *
 * * ZUPAY_PAYMENT_API, ID: 707
 * * ZUPAY_WEIXIN_PAYMENT_API, ID: 708
 * * ZUPAY_ALIPAY_PAYMENT_API, ID: 709
 * * ZUPAY_QQPAY_PAYMENT_API, ID: 710
 * * ZUPAY_JDPAY_PAYMENT_API, ID: 711
 * * ZUPAY_UNIONPAY_PAYMENT_API, ID: 712
 * * ZUPAY_QUICKPAY_PAYMENT_API, ID: 713
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

abstract class Abstract_payment_api_zupay extends Abstract_payment_api {

	//BUS_NO
	const DEFAULTNANK_WEIXIN = '1004';
	const DEFAULTNANK_ALIPAY = '992';
	const DEFAULTNANK_QQPAY = '1593';
	const DEFAULTNANK_JDPAY = '1008';
    const DEFAULTNANK_UNIONPAY = '1007';
    const DEFAULTNANK_QUICKPAY = '2087';

	const DEFAULTNANK_WEIXIN_WAP = '1005';
	const DEFAULTNANK_ALIPAY_WAP = '1006';
	const DEFAULTNANK_QQPAY_WAP = '1594';
	const DEFAULTNANK_QUICKPAY_H5 = '2088';

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
		list($s1, $s2)	=	explode(' ', microtime());
		list($ling, $haomiao)=	explode('.', $s1);
		$haomiao    = substr($haomiao,0,3);
		$requestId  = date("YmdHis",$s2).$haomiao; //商户订单号.必填(不能含有特殊字符)
		$order_id   = $requestId;

		$params = array();
		$datestring=date('YmdHis');
        $params['pay_version'] ='vb1.0';
        $params['pay_memberid'] = $this->getSystemInfo("account");
        $params['pay_orderid'] = $order->secure_id;
        $params['pay_applydate'] =$datestring;
        $params['pay_notifyurl'] = $this->getNotifyUrl($orderId);
        $params['pay_amount'] = $this->convertAmountToCurrency($amount);
        //$params['return_url'] = $this->getReturnUrl($orderId);

		$this->configParams($params, $order->direct_pay_extra_info);

		$this->CI->utils->debug_log('=========================zupay params before sign', $params);

        $params['pay_md5sign'] =$this->sign($params);

		$this->CI->utils->debug_log('=========================zupay generatePaymentUrlForm', $params);

		return $this->processPaymentUrlForm($params);
	}


	# Submit POST form
	protected function processPaymentUrlFormPost($datas) {

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $datas,
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
				$success = $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		# This $success marks whether the order status update is successful
		$result['success'] = $success;

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	# returns true if callback is valid and payment is successful
	# sets the $callbackValid parameter if callback is valid
	private function checkCallbackOrder($order, $fields, &$callbackValid) {
		# does all required fields exist?
		$requiredFields = array(
			'orderid', 'ovalue', 'sysorderid', 'opstate', 'attach', 'sign'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=========================zupay checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}



		# is signature authentic?
		if ($fields['sign']!=$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=========================zupay checkCallbackOrder validateSign Error', $fields);
			return false;
		}

		$callbackValid = true; # callbackValid is set to true once the signature verification pass

		if ($fields['opstate'] != "0") {
			$this->writePaymentErrorLog('=========================zupay checkCallbackOrder result['.$fields['opstate'].'] payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['ovalue']) {
			$this->writePaymentErrorLog("=========================zupay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['orderid'] != $order->secure_id) {
			$this->writePaymentErrorLog("=========================zupay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
			array('label' => '中信银行', 'value' => '962'),
			array('label' => '中国银行', 'value' => '963'),
			array('label' => '中国农业银行', 'value' => '964'),
			array('label' => '中国建设银行', 'value' => '965'),
			array('label' => '中国工商银行', 'value' => '967'),
			array('label' => '浙商银行', 'value' => '968'),
			array('label' => '浙江稠州商业银行', 'value' => '969'),
			array('label' => '招商银行', 'value' => '970'),
			array('label' => '邮政储蓄', 'value' => '971'),
			array('label' => '兴业银行', 'value' => '972'),
			array('label' => '顺德农村信用合作社', 'value' => '973'),
            array('label' => '深圳发展银行', 'value' => '974'),
            array('label' => '上海银行', 'value' => '975'),
            array('label' => '上海农村商业银行', 'value' => '976')
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

        $params_key = array(
			'pay_memberid', 'pay_bankcode', 'pay_amount', 'pay_orderid', 'pay_notifyurl'
		);
		$signStr =$this->createSignStr($params,$params_key);
		$signStr .=$this->getSystemInfo('key');

		$sign=md5($signStr);

	
		return $sign;
	}

	private function validateSign($params) {

        $params_key = array(
			'orderid', 'opstate', 'ovalue'
		);
		$signStr =$this->createSignStr($params,$params_key);
		$signStr .=$this->getSystemInfo('key');

		$sign=md5($signStr);

		return $sign;
	}

	public function createSignStr($params,$params_key) {

		$signStr="";
		foreach ($params_key as $key) {

			$signStr .= $key."=".$params[$key]."&";

		}
		$signStr=rtrim($signStr,"&");


		return $signStr;
	}

	private function validateResult($param) {
		# validate signature (skip this check for now, always fail)
		if ($param['respCode'] != "00") {
			$this->writePaymentErrorLog("============================zupay payment failed, ResCode = [".$param['respCode']."], ResDesc = [".$param['respMsg']."], Params: ", $param);
			return false;
		}else{

			return true;
		}

	}

	public function parseResultJson($result) {

			$arr =  json_decode($result, true);
			$this->utils->debug_log("============================zupay Callback Source Param: ", $arr);
			return $arr;

	}



}