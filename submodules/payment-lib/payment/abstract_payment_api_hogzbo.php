<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * HOGZBO 宏智博
 *
 *
 * * HOGZBO_UNIONPAY_PAYMENT_API, ID: 635
 * * HOGZBO_QQPAY_H5_PAYMENT_API, ID: 636
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Sha key
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

abstract class Abstract_payment_api_hogzbo extends Abstract_payment_api {

	const PAYTYPE_ALIPAY = '1';
    const PAYTYPE_ALIPAY_WAP = '1';
	const PAYTYPE_WEIXIN = '2';
    const PAYTYPE_WEIXIN_WAP = '2';
	const PAYTYPE_QQPAY = '8';
    const PAYTYPE_QQPAY_WAP = '8';

	const REQUEST_SUCCESS_CODE = '00';
	const CALLBACK_SUCCESS_CODE_APP = '1';
	const CALLBACK_SUCCESS_CODE_BANK = '2';
	const RETURN_SUCCESS_CODE = 'success ';

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
        $params['merchno'] =  $this->getSystemInfo("account");
		$params['amount'] = $this->convertAmountToCurrency($amount);
        $params['traceno'] = $order->secure_id;
        $params['notifyUrl'] = $this->getNotifyUrl($orderId);
        $params['settleType'] = '1';
        $params['returnurl']=$this->getReturnUrl($orderId);
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['signature'] = $this->sign($params);

		$this->CI->utils->debug_log('=========================hogzbo generatePaymentUrlForm', $params);

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
		# CURL post the data to Dinpay
		$postString = http_build_query($params);
		$curlConn = curl_init($this->getSystemInfo('url'));
		curl_setopt($curlConn, CURLOPT_HEADER, false);
		curl_setopt($curlConn, CURLOPT_POST, 1);
		curl_setopt($curlConn, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curlConn, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curlConn, CURLOPT_POSTFIELDS, $postString);

		# Need to specify the referer when doing CURL submit. since we use redirect 2nd url, we can take the HTTP_HOST
		curl_setopt($curlConn, CURLOPT_REFERER, "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

		$curlResult = curl_exec($curlConn);
		$curlSuccess = (curl_errno($curlConn) == 0);


		$this->CI->utils->debug_log('curlSuccess', $curlSuccess, $curlResult);

		$errorMsg=null;
		if($curlSuccess) {
			$curlResult = iconv('GBK//IGNORE', 'UTF-8', $curlResult);

			# parses return XML result into array, validate it, and get QRCode URL
			## Parse xml array
			$JsonResult = $this->parseResultJson($curlResult);

			## Flatten the parsed xml array
			$result = $JsonResult;
			$this->CI->utils->debug_log('============================hogzbo JsonResult to be flattened',$JsonResult);
			## Validate result data
			$curlSuccess = $this->validateResult($result);

			if ($curlSuccess) {
				## All good, return with qrcode link
				if (array_key_exists('barCode', $result)) {
					$qrCodeUrl = urldecode($result['barCode']);
				}else {
					$curlSuccess = false;
				}
			}

			if(array_key_exists('respCode', $result)) {
				$errorMsg = "respCode = [".$result["respCode"]."], message = [".$result["message"]."]";
			}
		} else {
			# curl error
			$errorMsg = curl_error($curlConn);
		}

		curl_close($curlConn);

		if($curlSuccess) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $qrCodeUrl,
			);
		} else {
			$this->CI->utils->debug_log('============================hogzbo payment request is FAIL',$curlResult);
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $errorMsg
			);
		}
	}

	private function validateResult($param) {
		# validate signature (skip this check for now, always fail)

		# validate success code
		if ($param['respCode'] != self::REQUEST_SUCCESS_CODE) {
			$this->utils->error_log("============================hogzbo payment failed, respCode = [".$param['respCode']."], message = [".$param['message']."], Params: ", $param);
			return false;
		}

		return true;
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


		if($source == 'server' ){
				$callbackValid = false;
				$paymentSuccessful = $this->checkCallbackOrder($order, $params, $callbackValid); # $callbackValid is also assigned

				# Do not proceed to update order status if payment failed, but still print success msg as callback response
				if(!$paymentSuccessful) {
					$result['return_error'] = self::RETURN_SUCCESS_CODE;
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['traceno'], null, null, $response_result_id);
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
    private function checkCallbackOrder($order, $fields, &$processed = false) {
		if(array_key_exists('payType', $fields)){
			$requiredFields = array(
				'transDate', 'transTime', 'merchno', 'merchName', 'amount', 'traceno','payType','orderno','status','signature'
			);
		}else{
			$requiredFields = array(
				'merchno', 'amount', 'traceno','orderno', 'channelOrderno', 'status', 'signature'
			);
		}

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=========================hogzbo checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }
        $callbackSign = $this->validateSign($fields);

		# is signature authentic?
		if ($fields['signature'] != $callbackSign) {
			$this->writePaymentErrorLog("=====================hogzbo check callback sign error, signature is [$callbackSign], match? ", $fields);
			return false;
		}

        if ( $order->amount != $fields['amount'])
	    {
			$this->writePaymentErrorLog("=====================hogzbo checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        # does order_no match?
        if ($fields['traceno'] !== $order->secure_id) {
            $this->writePaymentErrorLog("=========================hogzbo checkCallbackOrder Order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		if(array_key_exists('payType', $fields)){
			if ($fields['status'] !== self::CALLBACK_SUCCESS_CODE_APP) {
				switch($fields['status']){
					case '0':
						$this->writePaymentErrorLog("=========================hogzbo checkCallbackOrder Order status [尚未支付], expected [".$fields['status']."]", $fields);
						return false;
						break;
					case '2':
						$this->writePaymentErrorLog("=========================hogzbo checkCallbackOrder Order status [支付失败], expected [".$fields['status']."]", $fields);
						return false;
						break;
					default:
						$this->writePaymentErrorLog("=========================hogzbo checkCallbackOrder Order status [未处理 或 无效订单], expected [".$fields['status']."]", $fields);
						return false;
						break;
				}

				$this->writePaymentErrorLog("=========================hogzbo checkCallbackOrder Order IDs do not match, expected [$order->secure_id]", $fields);
				return false;
			}
		}else{
			if ($fields['status'] !== self::CALLBACK_SUCCESS_CODE_BANK) {
				switch($fields['status']){
					case '1':
						$this->writePaymentErrorLog("=========================hogzbo checkCallbackOrder Order status [尚未支付], expected [".$fields['status']."]", $fields);
						return false;
						break;
					case '3':
						$this->writePaymentErrorLog("=========================hogzbo checkCallbackOrder Order status [支付失败], expected [".$fields['status']."]", $fields);
						return false;
						break;
					default:
						$this->writePaymentErrorLog("=========================hogzbo checkCallbackOrder Order status [未处理 或 无效订单], expected [".$fields['status']."]", $fields);
						return false;
						break;
				}

				$this->writePaymentErrorLog("=========================hogzbo checkCallbackOrder Order IDs do not match, expected [$order->secure_id]", $fields);
				return false;
			}

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
			array('label' => '招商银行', 'value' => '3001'),
			array('label' => '工商银行', 'value' => '3002'),
			array('label' => '建设银行', 'value' => '3003'),
			array('label' => '浦发银行', 'value' => '3004'),
			array('label' => '农业银行', 'value' => '3005'),
			array('label' => '民生银行', 'value' => '3006'),
			array('label' => '兴业银行', 'value' => '3009'),
			array('label' => '交通银行', 'value' => '3020'),
			array('label' => '光大银行', 'value' => '3022'),
			array('label' => '中国银行', 'value' => '3026'),
			array('label' => '北京银行', 'value' => '3032'),
			array('label' => '平安银行', 'value' => '3035'),
			array('label' => '广发银行', 'value' => '3036'),
			array('label' => '中信银行', 'value' => '3039'),
			array('label' => '招商银行', 'value' => '4001'),
            array('label' => '工商银行', 'value' => '4002'),
			array('label' => '建设银行', 'value' => '4003'),
			array('label' => '浦发银行', 'value' => '4004'),
			array('label' => '农业银行', 'value' => '4005'),
			array('label' => '民生银行', 'value' => '4006'),
			array('label' => '兴业银行', 'value' => '4009'),
			array('label' => '交通银行', 'value' => '4020'),
			array('label' => '光大银行', 'value' => '4022'),
			array('label' => '中国银行', 'value' => '4026'),
			array('label' => '北京银行', 'value' => '4032'),
			array('label' => '平安银行', 'value' => '4035'),
			array('label' => '广发银行', 'value' => '4036'),
			array('label' => '中信银行', 'value' => '4039')
		);
	}

	private function convertAmountToCurrency($amount) {

		return number_format($amount, 2, '.', '');
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}


	# -- signing --
	public function sign($params) {
		$sign_str = $this->createSignStr($params);
        $sign_str .= $this->getSystemInfo('key');
		$signature = MD5($sign_str);
        return $signature;
    }

	private function createSignStr($params) {
        ksort($params);
        reset($params);
		$signStr = "";
		foreach($params as  $key=>$val) {
			if(empty($key) || empty($val) || $key == 'signature') {
				continue;
			}
			$signStr .= $key."=". $val .'&';
		}
        $signStr = strtoupper($signStr);
		return $signStr;
	}


	## callback signature
	private function validateSign($params){
		$callback_sign = $params['signature'] ;
		$params['merchName'] = $this->getSystemInfo("callback_merchname");

		$sign_str = $this->createSignStr($params);
        $sign_str .= $this->getSystemInfo("key");
		$str_gb = iconv('UTF-8','GBK', $sign_str);

		$signature = strtoupper(md5($str_gb));
		return $signature;
	}

	protected function parseResultJson($curlResult) {
		return json_decode($curlResult, true);
	}
}