<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * cocozf
 *
 * * 'COCOZF_ALIPAY_PAYMENT_API', ID 5197
 * * 'COCOZF_ALIPAY_H5_PAYMENT_API', ID 5198
 * * 'COCOZF_QUICKPAY_PAYMENT_API', ID 5199
 * * 'COCOZF_UNIONPAY_PAYMENT_API', ID 5242
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
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_cocozf extends Abstract_payment_api {

	//扫码類型
	const SCANTYPE_WEIXIN= 1002; //微信扫码
    const SCANTYPE_UNIONPAY= 9002; //银联扫码


    //支付类型
    const PAYTYPE_ALIPAY= 2002; //支付宝
    const PAYTYPE_ALIPAY_H5  = 2004; //支付宝h5
	const PAYTYPE_QUICK_PAY = 3001; //快捷
	const PAYTYPE_GATEWAY = 4001; //网关



	const RETURN_SUCCESS_CODE = '0000';
	const RETURN_SUCCESS = 'success';

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
		$params['merchantId'] = $this->getSystemInfo("account");
		$params['merReqNo'] = $order->secure_id;
		$params['amt'] = $this->convertAmountToCurrency($amount);
		$params['notifyUrl'] = $this->getNotifyUrl($orderId);
		$params['returnUrl'] = $this->getReturnUrl($orderId);
		$params['pordInfo'] = 'Deposit';
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================cocozf generatePaymentUrlForm", $params);

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
    	$url = $this->getSystemInfo('url');
		$response = $this->submitPostForm($url, $params, true, $params['merReqNo']);
		$decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================cocozf processPaymentUrlFormQRcode response json to array', $decode_data);

		$msg = lang('Invalidate API response');
    	if(!empty($decode_data['respCode']) && ($decode_data['respCode'] == self::RETURN_SUCCESS_CODE)) {
			if( $params['tranType'] == self::PAYTYPE_ALIPAY_H5 || $params['tranType'] == self::PAYTYPE_QUICK_PAY){
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_URL,
					'url' => $decode_data['payUrl'],
				);
			}
			else{
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_QRCODE,
					'url' => $decode_data['payUrl'],
				);
			}
        }else {
            if(!empty($decode_data['respDesc'])) {
                $msg = $decode_data['respCode'].": ".$decode_data['respDesc'];
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
            $this->CI->utils->debug_log('=======================cocozf callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['order_no'], null, null, null, $response_result_id);
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
			$result['message'] = $processed ? self::RETURN_SUCCESS : self::RETURN_FAILED_CODE;
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

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array('merReqNo', 'respCode','respDesc','amt','tranTime','tranType','serverRspNo');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================cocozf missing parameter: [$f]", $fields);
				return false;
			}
		}
		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================cocozf checkCallbackOrder Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['respCode'] != self::RETURN_SUCCESS_CODE) {
			$payStatus = $fields['respCode'];
			$this->writePaymentErrorLog("=====================cocozf Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['amt'] )
		) {
			$this->writePaymentErrorLog("=====================cocozf Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['merReqNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================cocozf checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- Private functions --
	/**
	 * detail: After payment is complete, the gateway will invoke this URL asynchronously
	 *
	 * @param int $orderId
	 * @return void
	 */
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: After payment is complete, the gateway will send redirect back to this URL
	 *
	 * @param int $orderId
	 * @return void
	 */
	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: Format the amount value for the API
	 *
	 * @param float $amount
	 * @return float
	 */
	protected function convertAmountToCurrency($amount) {
        return number_format($amount * 100, 0, '.', ''); //1元=100分
	}

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
        ksort($params);

        //生成待签名字符串
        $srcData = "";
        foreach ($params as $key => $val) {
            if($val === null || $val === "sign" ){
                //值为空的跳过，不参与加密
                continue;
            }
            $srcData .= "$key=$val" . "&";
        }
        $srcData = substr($srcData, 0, strlen($srcData) - 1);

		$srcData .= $this->getSystemInfo('key');
        $sign = md5($srcData);
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
		$signStr = rtrim($signStr, '&');
		$sign = md5($signStr.$this->getSystemInfo('key'));
		if($params['sign'] == $sign){
			return true;
		}
		else{
			return false;
		}
	}
}


