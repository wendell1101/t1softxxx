<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * GFDHQN 艾比德
 *
 * * GFDHQN_UNIONPAY_PAYMENT_API, ID: 5354
 * * GFDHQN_QUICKPAY_H5_PAYMENT_API, ID: 5355
 *
 * Required Fields:
 *
 * * URL
 * * Account - ## Merchant ID ##
 * * Key - ## API Key ##
 *
 * Field Values:
 *
 * * URL:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

abstract class Abstract_payment_api_gfdhqn extends Abstract_payment_api {

	const PAYTYPE_UNIONPAY = 'bankquick';
	const PAYTYPE_QUICKPAY_H5 = 'bankquick';

	const RETURN_SUCCESS = 'OK';
	const RECALL_SUCCESS_CODE = '1';

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

		$params['userid'] = $this->getSystemInfo("account");
		$params['orderid'] = $order->secure_id;
		$params['total_fee'] = $this->convertAmountToCent($amount); //分
		$params['body'] ='deposit';
		$params['notifyUrl'] = $this->getNotifyUrl($orderId);
        $params['returnUrl'] = $this->getReturnUrl($orderId);
		$this->configParams($params, $order->direct_pay_extra_info); //$params['paytype']
		$params['clientIp'] = $this->getClientIp();
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log('=========================gfdhqn generatePaymentUrlForm', $params);
		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
    	$url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, false, $params['orderid']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('==============================gfdhqn processPaymentUrlFormQRcode response json to array', $decode_data);

        $msg = lang('Invalidate API response');
    	if($decode_data['r'] == self::RECALL_SUCCESS_CODE) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decode_data['data']['url'],
            );
        }else {
            if(!empty($decode_data['errMsg'])) {
                $msg = $decode_data['r'].": ".$decode_data['errMsg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
	}

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
    	$url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, false, $params['orderid']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('=========================gfdhqn processPaymentUrlFormQRcode response json to array', $decode_data);

        $msg = lang('Invalidate API response');
    	if($decode_data['r'] == self::RECALL_SUCCESS_CODE) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $decode_data['data']['url'],
            );
        }else {
            if(!empty($decode_data['errMsg'])) {
                $msg = $decode_data['r'].": ".$decode_data['errMsg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
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
            $this->CI->utils->debug_log('=======================gfdhqn callbackFromServer server callbackFrom', $params);
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
			$result['message'] = $processed ? self::RETURN_SUCCESS : 'failed';
		}

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
		$requiredFields = array('customerid', 'status', 'sdorderno', 'total_fee');
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=========================gfdhqn checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if ($fields['sign']!=$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=========================gfdhqn checkCallbackOrder validateSign Error', $fields);
			return false;
		}

		$callbackValid = true; # callbackValid is set to true once the signature verification pass

		if ($fields['status'] != self::RECALL_SUCCESS_CODE) {
			$payStatus = $fields['status'];
			$this->writePaymentErrorLog("=====================gfdhqn Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['total_fee']) { //元
			$this->writePaymentErrorLog("=========================gfdhqn checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['sdorderno'] != $order->secure_id) {
			$this->writePaymentErrorLog("=========================gfdhqn checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- bankInfo --
	protected function getBankListInfoFallback() {
		return array(
			array('label' => '工商银行', 'value' => 'ICBC'),
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '建设银行', 'value' => 'CCB'),
			array('label' => '农业银行', 'value' => 'ABCD'),
			array('label' => '交通银行', 'value' => 'BOCOMD'),
			array('label' => '广东发展银行', 'value' => 'GDBD'),
			array('label' => '民生银行', 'value' => 'CMBCD'),
			array('label' => '中国邮政储蓄银行', 'value' => 'PSBCD'),
			array('label' => '光大银行', 'value' => 'CEBD'),
		);
	}

	public function convertAmountToCent($amount) {
		return number_format($amount *100 , 2, '.', '');
	}

	public function convertAmountToCurrency($amount) {
		return number_format($amount , 2, '.', '');
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
		$sign = strtolower(md5($signStr));
		return $sign;
	}

	private function validateSign($params) {
		ksort($params);
		$signStr = '';
		foreach($params as $key => $value) {
			if( ($key == 'sign') || (empty($value)) ) {
				continue;
			}
			$signStr .= "$key=$value&";
        }
		$signStr .= $this->getSystemInfo('key');
		$sign = strtolower(md5($signStr));
		if($params['sign'] == $sign){
			return true;
		}
		else{
			return false;
		}
	}

	public function createSignStr($params) {
		$params = array('userid'=>$params['userid'],'orderid'=>$params['orderid'],'total_fee'=>$params['total_fee'],'body'=>$params['body'],'notifyUrl'=>urlencode($params['notifyUrl']),'returnUrl'=>urlencode($params['returnUrl']),'paytype'=>$params['paytype'],'clientIp'=>$params['clientIp']);
		ksort($params);
		$signStr = "";
		foreach ($params as $key=>$value) {
			$signStr .= "$key=$value&";
		}
		$signStr .= "key=".$this->getSystemInfo('key');
		return $signStr;
	}
}