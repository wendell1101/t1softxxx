<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * Anxinpay 安心支付
 *
 * * 'ANXINPAY_QUICKPAY_PAYMENT_API', ID 5433
 * * 'ANXINPAY_QUICKPAY_H5_PAYMENT_API', ID: 5436
 *
 * Required Fields:
 *
 * * URL - http://pay.aixinyu.cn/createOrder
 * * Account - Merchant ID
 * * Key - Secret key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_anxinpay extends Abstract_payment_api {

    const PAYTYPE_QUICKPAY = "bank_quick";

	const RETURN_SUCCESS_CODE = '1';
	const RETURN_SUCCESS = 'success';
    const RETURN_FAILED_CODE = 'FAIL';



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

		$params['Version'] = '1.3';
		$params['Memid'] = $this->getSystemInfo("account");
		$params['outTradeNo'] = $order->secure_id.'0';
		$params['Amount'] = $this->convertAmountToCurrency($amount); //元
        $params['tradeTime'] = date("YmdHis");
		$this->configParams($params, $order->direct_pay_extra_info); //$params['tradeType'] $params['bankCode']
		$params['NotifyUrl'] = $this->getNotifyUrl($orderId);
		$params['ReturnUrl'] = $this->getReturnUrl($orderId);
		$params['Body'] = 'Deposit';
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================anxinpay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
    protected function processPaymentUrlFormPost($params) {

    	$url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, false, substr($params['outTradeNo'], 0, -1));
        $decode_data = json_decode($response, true);
        $this->CI->utils->debug_log('=====================anxinpay processPaymentUrlFormQRcode response json to array', $decode_data);
        $msg = lang('Invalidate API response');

		if(!empty($decode_data['status']) && ($decode_data['status'] == self::RETURN_SUCCESS_CODE)) {
			return array(
	                'success' => true,
	                'type' => self::REDIRECT_TYPE_URL,
	                'url' => $decode_data['url'],
            	);
        }else {
            if( $decode_data['status'] != self::RETURN_SUCCESS_CODE && !empty($decode_data['msg'])) {
                $msg = $decode_data['msg'];
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

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================anxinpay callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id,
            substr($params['orderid'],0,-1), 'Third Party Payment (No Bank Order Number)', # no info available
                null, null, $response_result_id);
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
		$requiredFields = array('Memid','orderid','outTradeNo','Amount','status','tradeType','Body','sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================anxinpay missing parameter: [$f]", $fields);
				return false;
			}
		}

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=======================anxinpay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass


		if ($fields['status']!= self::RETURN_SUCCESS_CODE) {
			$payStatus = $fields['status'];
			$this->writePaymentErrorLog("=====================anxinpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['Amount'] )) {
			$this->writePaymentErrorLog("=====================anxinpay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if (substr($fields['outTradeNo'], 0, -1) != $order->secure_id) {
            $this->writePaymentErrorLog("========================anxinpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
    }


	public function getBankListInfoFallback() {
		return array(
            "1" => array('label' => '工商银行', 'value' => 'ICBC'),
            "2" => array('label' => '招商银行', 'value' => 'CMB'),
            "3" => array('label' => '建设银行', 'value' => 'CCB'),
            "4" => array('label' => '农业银行', 'value' => 'ABC'),
            "5" => array('label' => '交通银行', 'value' => 'BOCOM'),
            "6" => array('label' => '中国银行', 'value' => 'BOC'),
            "8" => array('label' => '广发银行', 'value' => 'GDB'),
            "11" => array('label' => '民生银行', 'value' => 'CMBC'),
            "12" => array('label' => '邮储银行', 'value' => 'PSBC'),
            "13" => array('label' => '兴业银行', 'value' => 'CIB'),
            "15" => array('label' => '平安银行', 'value' => 'PAB'),
            "20" => array('label' => '光大银行', 'value' => 'CEB'),
            "32" => array('label' => '浦发银行', 'value' => 'SPDB')
		);
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
		return number_format($amount, 2, '.', '');
	}

    # -- signatures --
     private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtolower(md5($signStr));
        return $sign;
    }

    private function createSignStr($params) {
        $params = array(
            'Amount' => $params['Amount'],
            'bankCode' => $params['bankCode'],
            'Memid' => $params['Memid'],
            'NotifyUrl' => $params['NotifyUrl'],
            'outTradeNo' => $params['outTradeNo'],
            'ReturnUrl' => $params['ReturnUrl'],
            'tradeTime' => $params['tradeTime'],
            'tradeType' => $params['tradeType'],
            'Version' => $params['Version']
        );
		$signStr = '';
        foreach($params as $key => $value) {
            $signStr.=$key."=".$value."&";
        }
        $signStr = $signStr.$this->getSystemInfo('key');
		return $signStr;
    }

    private function validateSign($params) {
        $keys = array(
            'Amount' => $params['Amount'],
            'Memid' => $params['Memid'],
			'orderid' => $params['orderid'],
			'outTradeNo' => $params['outTradeNo'],
            'status' => $params['status'],
            'tradeType' => $params['tradeType']
        );
        $signStr = '';
        foreach($keys as $key => $value) {
            $signStr.=$key."=".$value."&";
        }
        $signStr = $signStr.$this->getSystemInfo('key');
		$sign = strtolower(md5($signStr));
		if($params['sign'] == $sign){
			return true;
		}
		else{
			return false;
		}
	}
}
