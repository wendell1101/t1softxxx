<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * EASYPAY
 *
 * * EASYPAY_PAYMENT_API, ID: 651
 * * EASYPAY_ALIPAY_PAYMENT_API, ID: 652
 * * EASYPAY_WEIXIN_PAYMENT_API, ID: 653
 * * EASYPAY_QQPAY_PAYMENT_API, ID: 654
 * * EASYPAY_QUICKPAY_PAYMENT_API, ID: 655
 * * EASYPAY_JDPAY_PAYMENT_API, ID: 656
 * * EASYPAY_WITHDRAWAL_PAYMENT_API, ID: 657
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

abstract class Abstract_payment_api_easypay extends Abstract_payment_api {


	const DEFAULTNANK_BANK = '1001';
	const DEFAULTNANK_WEIXIN = '1003';
	const DEFAULTNANK_ALIPAY = '1009';
	const DEFAULTNANK_WEIXIN_H5 = '1011';
	const DEFAULTNANK_ALIPAY_H5 = '1012';
	const DEFAULTNANK_QUICKPAY = '1013';
	const DEFAULTNANK_QQPAY = '1015';
	const DEFAULTNANK_QQPAY_H5 = '1017';
	const DEFAULTNANK_JDPAY = '1027';
	const DEFAULTNANK_JDPAY_H5 = '1062';

	const RETURN_SUCCESS_CODE = 'ok';


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
		$datestring=date('Ymd');

        $params['v_mid'] = $this->getSystemInfo("account");
        $params['v_oid'] = $datestring.'-'.$this->getSystemInfo("account").'-'.ltrim($order->secure_id, 'D');  //日期-商户编号-商户流水
        $params['v_rcvname'] = $this->getSystemInfo("account");
		$params['v_rcvaddr'] = $this->getSystemInfo("account");
		$params['v_goodsname'] = 'deposit';
        $params['v_goodsdescription'] = 'deposit';
        $params['v_rcvtel'] = $this->getSystemInfo("account");
        $params['v_rcvpost'] = $this->getSystemInfo("account");
        $params['v_qq'] = $this->getSystemInfo("account");
        $params['v_amount'] = $this->convertAmountToCurrency($amount);
        $params['v_ymd'] = date('Ymd');
        $params['v_orderstatus'] = '1';
        $params['v_ordername'] = $this->getSystemInfo("account");
        $params['v_bankno'] = '';
        $params['v_moneytype'] = '0'; //0为人民币，1为美元，2为欧元，3为英
        $params['v_url'] = $this->getNotifyUrl($orderId);
		$params['v_noticeurl'] = $this->getReturnUrl($orderId);

		$this->configParams($params, $order->direct_pay_extra_info);

		//$params['sign_type'] = 'RSA-S';
		$params['v_sign'] = $this->sign($params);


		$this->CI->utils->debug_log('=========================easypay generatePaymentUrlForm', $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
		$params='['.json_encode($params).']';
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
		# CURL post the data to Dinpay
		//$postString = http_build_query($params);
		$postString='['.json_encode($params).']';
        $this->CI->utils->debug_log('=========================easypay processPaymentUrlFormQRCode sacn url: ', $this->getSystemInfo('url'));
		$curlConn = curl_init($this->getSystemInfo('url'));
		curl_setopt ($curlConn, CURLOPT_POST, 1);
		curl_setopt($curlConn, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curlConn, CURLOPT_HEADER, false);
		curl_setopt($curlConn, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curlConn, CURLOPT_POSTFIELDS, $postString);

		# Need to specify the referer when doing CURL submit. since we use redirect 2nd url, we can take the HTTP_HOST
		curl_setopt($curlConn, CURLOPT_REFERER, "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

		$curlResult = curl_exec($curlConn);
		$curlSuccess = (curl_errno($curlConn) == 0);

		$this->CI->utils->debug_log('=========================easypay processPaymentUrlFormQRCode curlSuccess', $curlSuccess, $curlResult);

		$errorMsg=null;

		if($curlSuccess) {
			if(is_null(json_decode($curlResult))){
				return print_r($curlResult);
			}

			$result = $this->parseResultJson($curlResult);

			if(array_key_exists('ResDesc', $result)) {
				$errorMsg = $result['ResDesc'];
			}

		} else {
			# curl error
			$errorMsg = curl_error($curlConn);
		}

		curl_close($curlConn);

		return array(
			'success' => false,
			'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
			'message' => $errorMsg
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
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================easypay callbackFrom $source params", $params);

        if($source == 'server'){
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['v_oid'], $params['v_bankno'], null, null, $response_result_id);
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
			'v_pagecode', 'v_mid', 'v_oid', 'v_orderid', 'v_bankno', 'v_result', 'v_value', 'v_realvalue', 'v_qq', 'v_telephone', 'v_goodsname', 'v_goodsdescription', 'v_extmsg', 'v_resultmsg', 'v_sign'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=========================easypay checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if ($fields['v_sign']!=$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=========================easypay checkCallbackOrder validateSign Error', $fields);
			return false;
		}

		$callbackValid = true; # callbackValid is set to true once the signature verification pass

		if ($fields['v_result'] != "2000") {
			$this->writePaymentErrorLog('=========================easypay checkCallbackOrder result['.$fields['v_result'].'] payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['v_value']) {
			$this->writePaymentErrorLog("=========================easypay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}
		$order_num=ltrim($order->secure_id, 'D');
		if (substr($fields['v_oid'],16,12) != $order_num) {
			$this->writePaymentErrorLog("=========================easypay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
			array('value' => '308584000013', 'label' => '招商银行'),
			array('value' => '102100099996', 'label' => '中国工商银行'),
			array('value' => '105100000017', 'label' => '中国建设银行'),
			array('value' => '103100000026', 'label' => '中国农业银行'),
			array('value' => '104100000004', 'label' => '中国银行'),
			array('value' => '310290000013', 'label' => '浦发银行'),
			array('value' => '301290000007', 'label' => '交通银行'),
			array('value' => '306581000003', 'label' => '广东发展银行'),
			array('value' => '302100011000', 'label' => '中信银行'),
			array('value' => '303100000006', 'label' => '中国光大银行'),
			array('value' => '309391000011', 'label' => '兴业银行'),
			array('value' => '313584099990', 'label' => '平安银行'),
			array('value' => '304100040000', 'label' => '华夏银行'),
			array('value' => '403100000004', 'label' => '中国邮政储蓄')
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
		$params_keys = array(
			'v_pagecode', 'v_mid', 'v_oid', 'v_amount', 'v_ymd', 'v_bankno'
		);
		$signStr = $this->createSignStr($params,$params_keys);
		$sign = strtoupper(hash('sha1', $signStr));

		return $sign;
	}

	private function validateSign($params) {
		$params_keys = array(
			'v_pagecode', 'v_mid', 'v_oid', 'v_orderid', 'v_result', 'v_value'
		);
		$signStr = $this->createSignStr($params,$params_keys);
		$sign = strtoupper(hash('sha1', $signStr));

		return $sign;
	}

	public function createSignStr($params,$params_keys) {
		$signStr = "";
		foreach ($params_keys as $key) {
			if (array_key_exists($key, $params)) {
				$signStr .= $key."=".$params[$key]."&";
			}
		}
		$signStr = rtrim($signStr,"&");
		$signStr .= $this->getSystemInfo('key');

		return $signStr;
	}

	public function parseResultJson($resultJson) {
		$arr =  json_decode($resultJson, true);
		$this->utils->debug_log("============================easypay Callback Source Param: ", $arr[0]);
		return $arr[0];
	}
}