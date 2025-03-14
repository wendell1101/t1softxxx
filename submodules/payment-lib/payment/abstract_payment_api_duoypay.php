<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * DUOYPAY 铎亿
 *
 *
 * * DUOYPAY_UNIONPAY_PAYMENT_API, ID: 635
 * * DUOYPAY_QQPAY_H5_PAYMENT_API, ID: 636
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

abstract class Abstract_payment_api_duoypay extends Abstract_payment_api {

	const PAYTYPE_QQPAY = '1009';
	const PAYTYPE_QQPAY_WAP = '1008';
	const PAYTYPE_WEIXIN = '1004';
	const PAYTYPE_WEIXIN_WAP = '1007';
	const PAYTYPE_ALIPAY = '1003';
	const PAYTYPE_ALIPAY_WAP = '1006';


	const REQUEST_SUCCESS_CODE = '0';
	const CALLBACK_SUCCESS_CODE = '0';
	const RETURN_SUCCESS_CODE = 'opstate=0 ';

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
        $params['parter'] =  $this->getSystemInfo("account");
		$params['value'] = $this->convertAmountToCurrency($amount);
		$params['orderid'] = $order->secure_id;
        $params['callbackurl'] = $this->getNotifyUrl($orderId);
		$params['hrefbackurl']=$this->getReturnUrl($orderId);

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log('=========================duoypay generatePaymentUrlForm', $params);

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
			'post' => false,
		);
	}



	# Display QRCode get from curl
	protected function processPaymentUrlFormQRCode($params) {
		# CURL post the data to Dinpay
		$postString = http_build_query($params);
		$curlConn = curl_init($this->getSystemInfo('url'));
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
			# parses return XML result into array, validate it, and get QRCode URL
			## Parse xml array
			$xmlResult = $this->parseResultXML($curlResult);

			## Flatten the parsed xml array
			$result = $this->flattenResult($xmlResult);

			## Validate result data
			$curlSuccess = $this->validateResult($result);

			if ($curlSuccess) {
				## All good, return with qrcode link
				$qrCodeUrl = urldecode($result['qrcode']);

				if(!$qrCodeUrl) {
					$curlSuccess = false;
				}
			}

			if(array_key_exists('result_desc', $result)) {
				$errorMsg = $result['result_desc'];
			} elseif (array_key_exists('resp_desc', $result)) {
				$errorMsg = $result['resp_desc'];
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
			$this->utils->error_log("============================duoypay payment failed, resp_code = [".$param['respCode']."], resp_msg = [".$param['message']."], Params: ", $param);
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
			$this->utils->error_log("============================duoypay Order ID [$orderId] not found.");
			return $result;
		}

		if($source == 'server' ){
				$callbackValid = false;
				$paymentSuccessful = $this->checkCallbackOrder($order, $params, $callbackValid); # $callbackValid is also assigned

				# Do not proceed to update order status if payment failed, but still print success msg as callback response
				if(!$paymentSuccessful) {
					return $result;
				}
		}

		# Do not proceed to update order status if payment failed, but still print success msg as callback response
		if(!$paymentSuccessful) {
			$result['status'] = self::RETURN_SUCCESS_CODE;
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
				$params['sysorderid'],
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
    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'orderid', 'opstate', 'ovalue', 'sysorderid', 'systime', 'attach','msg','sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=========================duoypay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }
        $callbackSign = $this->validateSign($fields);

		# is signature authentic?
		if ($fields['sign'] != $callbackSign) {
			$this->writePaymentErrorLog("=====================duoypay check callback sign error, signature is [$callbackSign], match? ", $fields);
			return false;
		}

        if ( $order->amount !=$fields['ovalue'])
	    {
			$this->writePaymentErrorLog("=====================duoypay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        # does order_no match?
        if ($fields['orderid'] !== $order->secure_id) {
            $this->writePaymentErrorLog("=========================duoypay checkCallbackOrder Order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        if ($fields['opstate'] !== self::CALLBACK_SUCCESS_CODE) {
            switch($fields['opstate']){
                case '-1':
                    $this->writePaymentErrorLog("=========================duoypay checkCallbackOrder Order status [請求參數無效], expected [".$fields['status']."]", $fields);
                    return false;
                    break;
                case '-2':
                    $this->writePaymentErrorLog("=========================duoypay checkCallbackOrder Order status [簽名錯誤], expected [".$fields['status']."]", $fields);
                    return false;
                    break;
                default:
                    $this->writePaymentErrorLog("=========================duoypay checkCallbackOrder Order status [未知錯誤], expected [".$fields['status']."]", $fields);
                    return false;
                    break;
            }

            $this->writePaymentErrorLog("=========================duoypay checkCallbackOrder Order IDs do not match, expected [$order->secure_id]", $fields);
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
			array('label' => '中国工商银行', 'value' => 'ICBC'),
			array('label' => '中国农业银行', 'value' => 'ABC'),
			array('label' => '中国银行', 'value' => 'BOCSH'),
			array('label' => '建设银行', 'value' => 'CCB'),
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '浦发银行', 'value' => 'SPDB'),
			array('label' => '广发银行', 'value' => 'GDB'),
			array('label' => '交通银行', 'value' => 'BOCOM'),
			array('label' => '邮政储蓄银行', 'value' => 'PSBC'),
			array('label' => '中信银行', 'value' => 'CNCB'),
			array('label' => '民生银行', 'value' => 'CMBC'),
			array('label' => '光大银行', 'value' => 'CEB'),
			array('label' => '华夏银行', 'value' => 'HXB'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '上海银行', 'value' => 'BOS'),
            array('label' => '上海农商', 'value' => 'SRCB'),
			array('label' => '平安银行', 'value' => 'PAB'),
			array('label' => '北京银行', 'value' => 'BCCB')
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
		$params_key = array(
            'parter', 'type', 'value', 'orderid', 'callbackurl'
        );

		$sign_str = $this->createSignStr($params,$params_key);
        $sign_str .= $this->getSystemInfo('key');
		$signature = MD5($sign_str);
        return $signature;
    }

	private function createSignStr($params,$params_key) {
		$sign_str = "";
		foreach ($params_key as $f) {
			if (array_key_exists($f, $params)) {
				$sign_str .=  $f."=". $params[$f] .'&';
			}
		}
        $sign_str = substr($sign_str, 0, strlen($sign_str) -1 );
		return $sign_str;
	}

	## callback signature
	private function validateSign($params){

		$callback_sign = $params['sign'] ;
		$params_key = array(
            'orderid', 'opstate', 'ovalue'
        );

		$sign_str=$this->createSignStr($params,$params_key);

        $sign_str .= $this->getSystemInfo('key');

		$signature = MD5($sign_str);

		if($callback_sign != $signature){
			return false;
		}

		return true;
	}
}