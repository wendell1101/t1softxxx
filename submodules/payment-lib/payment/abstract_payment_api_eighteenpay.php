<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * 18pay EIGHTEENPAY
 * *
 * * 'EIGHTEENPAY_PAYMENT_API', ID 5862
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://18-pays.com/api/trans/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

abstract class Abstract_payment_api_eighteenpay extends Abstract_payment_api {

    const PAY_TYPE_ALIPAY_H5 = '1';
    const PAY_TYPE_ALIPAY    = '2';
    const PAY_TYPE_WEIXIN_H5 = '3';
    const PAY_TYPE_WEIXIN    = '4';
	const RESPONSE_SUCCESS   = '0';
	const CALLBACK_SUCCESS   = '1';
	const RETURN_SUCCESS     = 'SUCCESS';
	const RETURN_FAILED		 = 'FAIL';
	const CHANNEL_CODE       = '1025';

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
		$params['merchant_sn']  = $this->getSystemInfo("account");
		$params['down_sn']      = $order->secure_id;
		$params['amount']       = $this->convertAmountToCurrency($amount);
		$this->configParams($params, $order->direct_pay_extra_info);
        $params['notify_url']   = $this->getNotifyUrl($orderId);
        $params['channel_code'] = $this->getSystemInfo("channel_code",self::CHANNEL_CODE);
		$params['sign']         = $this->sign($params);

		$this->CI->utils->debug_log("=====================eighteenpay generatePaymentUrlForm", $params);
		
		return $this->processPaymentUrlForm($params);
	}

	protected function processPaymentUrlFormRedirect($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['down_sn']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================eighteenpay processPaymentUrlFormRedirect response', $response);

        if(isset($response['code']) && $response['code'] == self::RESPONSE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['pay_url'],
            );
        }
        else if(isset($response['msg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'code: ['.$response['code'].'] msg: '.$response['msg']
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidte API response')
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

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================eighteenpay callbackFromServer json_decode params", $params);
        }

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================eighteenpay callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['down_sn'], null, null, null, $response_result_id);
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
			$result['message'] = $processed ? self::RETURN_SUCCESS : self::RETURN_FAILED;
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
		$requiredFields = array('code', 'trans_sn','down_sn','sign','status','amount');
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("======================eighteenpay check callback Missing parameter: [$f]", $fields);
				return false;
			}
		}

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================eighteenpay checkCallbackOrder Signature Error', $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['status'] != self::CALLBACK_SUCCESS) {
			$this->writePaymentErrorLog('=====================eighteenpay checkCallbackOrder status was not successful', $fields);
			return false;
		}

		if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
			$this->writePaymentErrorLog("=====================eighteenpay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['down_sn'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================eighteenpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- notifyURL --
	public function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

    # -- returnURL --
	public function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

    # -- amount --
	protected function convertAmountToCurrency($amount) {
		return number_format($amount*100, 2, '.', '');
	}

	# -- bankInfo --
	protected function getBankListInfoFallback() {
		return array(
			array('label' => '工商银行', 'value' => '102'),
			array('label' => '农业银行', 'value' => '103'),
			array('label' => '中国银行', 'value' => '104'),
			array('label' => '中国建设银行', 'value' => '105'),
			array('label' => '交通银行', 'value' => '301'),
			array('label' => '中信银行', 'value' => '302'),
			array('label' => '中国光大银行', 'value' => '303'),
			array('label' => '华夏银行', 'value' => '304'),
			array('label' => '民生银行', 'value' => '305'),
			array('label' => '广发银行', 'value' => '306'),
			array('label' => '平安银行', 'value' => '307'),
			array('label' => '招商银行', 'value' => '308'),
			array('label' => '兴业银行', 'value' => '309'),
			array('label' => '浦发银行', 'value' => '310'),
			array('label' => '北京银行', 'value' => '31310000'),
			array('label' => '南京银行', 'value' => '31310001'),
			array('label' => '宁波银行', 'value' => '31310002'),
			array('label' => '杭州银行', 'value' => '31310009'),
			array('label' => '成都银行', 'value' => '31365100'),
			array('label' => '富滇银行', 'value' => '31365306'),
			array('label' => '常熟农商银行', 'value' => '31430202'),
			array('label' => '中国邮政储蓄银行', 'value' => '403'),
		);
	}

	# -- MD5sign --
	public function sign($params) {
       	$signStr = $this->createSignStr($params);
        $sign = strtolower(md5($signStr));
		return $sign;
	}

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign' || empty($value) || $key == 'code' || $key == 'msg') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $sign = $this->sign($params);
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }
}
