<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * HZMHKJ 嘉联
 *
 * * HZMHKJ_PAYMENT_API, ID: 616
 * * HZMHKJ_WEIXIN_PAYMENT_API, ID: 617
 * * HZMHKJ_ALIPAY_PAYMENT_API, ID: 618
 * * HZMHKJ_QQPAY_PAYMENT_API, ID: 619
 * * HZMHKJ_UNIONPAY_PAYMENT_API, ID: 620
 * * HZMHKJ_QUICKPAY_PAYMENT_API, ID: 621
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

abstract class Abstract_payment_api_hzmhkj extends Abstract_payment_api {

	const PAYTYPE_BANK   = 'bank';
	const PAYTYPE_ALIPAY  = 'alipay';
	const PAYTYPE_QQPAY_QR = 'qqrcode';
    const PAYTYPE_WEIXIN_QR = 'weixin';
	const PAYTYPE_UNIONPAY  = 'ylsm';
	const PAYTYPE_ALIPAY_WAP  = 'alipaywap';
	const PAYTYPE_QQPAY_WAP = 'qqwallet';
    const PAYTYPE_WEIXIN_WAP = 'wxh5';
	const PAYTYPE_QUICKPAY  = 'kuaijie';

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
		$params['version'] = "1.0";
		$params['customerid'] =  $this->getSystemInfo("account");
		$params['sdorderno'] = $order->secure_id;
		$params['total_fee'] = $this->convertAmountToCurrency($amount);
		$params['paytype'] = '';
		$params['bankcode'] = '';
		$params['notifyurl'] = $this->getNotifyUrl($orderId);
        $params['returnurl']=$this->getReturnUrl($orderId);
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log('=========================hzmhkj generatePaymentUrlForm', $params);
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['sdpayno'], null, null, $response_result_id);
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
            'status', 'customerid', 'sdpayno', 'sdorderno', 'total_fee', 'paytype', 'remark' , 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=========================hzmhkj checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }
        $callbackSign = $this->validateSign($fields);

		# is signature authentic?
		if ($fields['sign'] != $callbackSign) {
			$this->writePaymentErrorLog("=====================hzmhkj check callback sign error, signature is [$callbackSign], match? ", $fields);
			return false;
		}

		switch ($fields['paytype'])
		{
			case self::PAYTYPE_ALIPAY :
				if(abs($this->convertAmountToCurrency($order->amount) - $this->convertAmountToCurrency(floatval($fields['total_fee']))) >= 0.1 ) {
					$this->writePaymentErrorLog("=========================hzmhkj checkCallbackOrder Payment ALIPAY amounts do not match, expected [".$this->ReturnAmountToCurrency($order->amount)."]", $fields);
					return false;
				}
			break;
			case self::PAYTYPE_ALIPAY_WAP :
				if(abs($this->convertAmountToCurrency($order->amount) - $this->convertAmountToCurrency(floatval($fields['total_fee']))) >= 0.1 ) {
					$this->writePaymentErrorLog("=========================hzmhkj checkCallbackOrder Payment ALIPAY amounts do not match, expected [".$this->ReturnAmountToCurrency($order->amount)."]", $fields);
					return false;
				}
			break;
			default:
				if (
					$this->convertAmountToCurrency($order->amount) !==
					$this->convertAmountToCurrency(floatval($fields['total_fee']))
				) {
					$this->writePaymentErrorLog("=========================hzmhkj checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields);
					return false;
				}
		}


        # does order_no match?
        if ($fields['sdorderno'] !== $order->secure_id) {
            $this->writePaymentErrorLog("=========================hzmhkj checkCallbackOrder Order IDs do not match, expected [$order->secure_id]", $fields);
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
			'version', 'customerid', 'total_fee', 'sdorderno', 'notifyurl','returnurl'
		);

		$sign_str = $this->createSignStr($params,$params_key);
        $sign_str .= $this->getSystemInfo('key');
		$signature = MD5($sign_str);
        return $signature;
    }

	private function createSignStr($params,$params_key) {
		$sign_str='';
		foreach ($params_key as $key) {
			if(array_key_exists($key, $params)) {
				$sign_str .=  $key."=". $params[$key] . '&';
			}
		}

		return $sign_str;
	}

	## callback signature
	private function validateSign($params){
		$callback_sign = $params['sign'] ;
		$params_key = array(
			'customerid', 'status', 'sdpayno', 'sdorderno', 'total_fee','paytype'
		);

		$sign_str = $this->createSignStr($params,$params_key);
        $sign_str .= $this->getSystemInfo('key');
		$signature = MD5($sign_str);

		if($callback_sign != $signature){
			return false;
		}

		return true;
	}
}