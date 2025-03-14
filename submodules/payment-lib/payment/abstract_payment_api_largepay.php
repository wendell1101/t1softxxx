<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * LARGEPAY
 *
 * * 'LARGEPAY_PAYMENT_API', ID 5532
 * * 'LARGEPAY_UNIONPAY_PAYMENT_API', ID 5533
 * * 'LARGEPAY_UNIONPAY_H5_PAYMENT_API', ID 5534
 * * 'LARGEPAY_WITHDRAWAL_PAYMENT_API', ID 5540
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Secret key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_largepay extends Abstract_payment_api {

    const BANKCODE_UNIONPAY = 'QUICKPAY';
    const BANKCODE_UNIONPAY_WAP = 'UNIONPAYWAP';

    const PAYMODE_WEB = '01'; //电脑浏览器
    const PAYMODE_WEBH5 = '07'; //手机浏览器，电脑浏览器也适用
    const PAYMODE_H5 = '12';

    const CARD_TYPE = '0'; //仅允许使用借记卡支付

    const RETURN_SUCCESS_CODE = 'S';
    const RETURN_SUCCESS = 'SUCCESS';
    const RETURN_FAILED = 'ERROR';


    public function __construct($params = null) {
        parent::__construct($params);
    }


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
		$params['version'] = "v1";
        $params['merchant_no'] = $this->getSystemInfo("account");
        $params['order_no'] = $order->secure_id;
		$params['goods_name'] = base64_encode("充值");
        $params['order_amount'] = $this->convertAmountToCurrency($amount); //元
        $params['backend_url'] = $this->getNotifyUrl($orderId);
		$params['frontend_url'] = $this->getReturnUrl($orderId);
		$params['reserve'] = '';
        $this->configParams($params, $order->direct_pay_extra_info);  //$params['pay_mode'] $params['bank_code'] $params['card_type'] $params['bank_card_no']
		if($params['bank_code'] == self::BANKCODE_UNIONPAY){
			$params['merchant_user_id'] = $playerId;
		}
        $params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================largepay generatePaymentUrlForm", $params);

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
			'post' => true,
		);
	}


    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
    	$url = $this->getSystemInfo('url');
		$response = $this->submitPostForm($url, json_encode($params), false, $params['outTradeNo']);

		$decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================dada processPaymentUrlFormQRcode response[1] json to array', $decode_data);
		$msg = lang('Invalidte API response');


		if(isset($decode_data['url'])) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decode_data['url'],
            );
        }else {
            if(!empty($decode_data['msg'])){
                $msg = $decode_data['s'].":".$decode_data['msg'];
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
            $this->CI->utils->debug_log('=======================largepay callbackFromServer server callbackFrom', $params);
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
			'', '', # no info available
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
			$result['return_error'] = $processed ? self::RETURN_SUCCESS : self::RETURN_FAILED;
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

		$requiredFields = array('order_no','order_amount','sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================largepay missing parameter: [$f]", $fields);
				return false;
			}
		}
		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================largepay checkCallbackOrder Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['result'] != self::RETURN_SUCCESS_CODE) {
			$payStatus = $fields['result'];
			$this->writePaymentErrorLog("=====================largepay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($fields['order_amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================largepay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================largepay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
            "10" => array('label' => '中信银行', 'value' => 'CNCB'),
            "11" => array('label' => '民生银行', 'value' => 'CMBC'),
            "12" => array('label' => '邮政储蓄银行', 'value' => 'PSBC'),
            "13" => array('label' => '兴业银行', 'value' => 'CIB'),
            "14" => array('label' => '华夏银行', 'value' => 'HXB'),
            "15" => array('label' => '平安银行', 'value' => 'PAB'),
            "20" => array('label' => '光大银行', 'value' => 'CEB'),
            "24" => array('label' => '上海银行', 'value' => 'BOS'),
            "32" => array('label' => '浦发银行', 'value' => 'SPDB'),
            "119" => array('label' => '北京银行', 'value' => 'BCCB')
		);
	}


	# -- amount --
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- notifyURL --
	public function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

    # -- returnURL --
	public function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}


   # -- signatures --
    private function sign($params) {
		$signStr = $this->createSignStr($params);
		$sign = md5($signStr);
		return $sign;
	}

    private function createSignStr($params) {
		$signStr = '';
        foreach($params as $key => $value) {
			if( ($key == 'sign') || $key == ('bank_card_no')) {
				continue;
			}

			$signStr.=$key."=".$value."&";
        }
		$signStr .= "key=".$this->getSystemInfo('key');
		return $signStr;
    }

	# -- 驗簽 --
    public function validateSign($params) {
        $keys = array(
            'merchant_no' => $params['merchant_no'],
            'order_no' => $params['order_no'],
            'order_amount' => $params['order_amount'],
            'original_amount' => $params['original_amount'],
            'upstream_settle' => $params['upstream_settle'],
            'result' => $params['result'],
            'pay_time' => $params['pay_time'],
            'trace_id' => $params['trace_id'],
            'reserve' => $params['reserve']
        );
		$signStr = '';
        foreach($keys as $key => $value) {
			$signStr.=$key."=".$value."&";
        }
		$signStr .= "key=".$this->getSystemInfo('key');
		$sign = md5($signStr);

		if($params['sign'] == $sign){
			return true;
		}
		else{
			return false;
		}
	}
}


