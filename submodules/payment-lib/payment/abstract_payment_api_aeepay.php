<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * aeepay
 *
 * * AEEPAY_PAYMENT_API, ID: 6299
 *   AEEPAY_BANKTRANSFER_PAYMENT_API, ID: 6301
 * * AEEPAY_WITHDRAWAL_PAYMENT_API, ID: 6300
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
abstract class Abstract_payment_api_aeepay extends Abstract_payment_api {

	const REQUEST_TYPE_WEB = 0;
	const REQUEST_TYPE_WAP = 1;
	const RETURN_SUCCESS_CODE = "success";
	const REQUEST_SUCCESS_CODE = "10000";
	const RETURN_FAIL_CODE = 'fail';
	const P_ERRORCODE_PAYMENT_SUCCESS = 1;
	const  QR_CODE= 'THBQR';
	const  TRANSFER_PAYMENT= 'THBTP';
	const RESPONSE_WITHDRAWAL_SUCCESS_CODE = 3;

	public function __construct($params = null) {
		parent::__construct($params);
	}
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

		$params['merchantno'] = $this->getSystemInfo("account");
		$params['morderno'] = $order->secure_id;
		$params['productname'] = $order->secure_id;
		$params['money'] = $this->convertAmountToCurrency($amount);	
		$this->configParams($params, $order->direct_pay_extra_info); //$params['payType']
		$params['sendtime'] = date("YmdHis");
		$params['notifyurl'] = $this->getNotifyUrl($orderId);
		$params['buyerip'] = $this->utils->getIP();
		$params['userinfo'] = $playerId;
		$params['sign'] = $this->sign($params);

		$this->utils->error_log("==============aeepay params !!!!!!!!!",$params);


		return $this->processPaymentUrlForm($params);
	}

	protected function processPaymentUrlFormRedirect($params) {
		$response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['morderno']);
        $response = json_decode($response, true);

        if($response['resultCode'] == self::REQUEST_SUCCESS_CODE) {
			return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['data']['payurl']
        	);
        }
        else if(isset($response['resultMsg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => '['.$response['resultCode'].']: '.$response['resultMsg']
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


	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	## Reference: sample code, callback.php
	public function callbackFromServer($orderId, $params) {

		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	## This will be called when user redirects back to our page from payment API
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
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $params = json_decode($raw_post_data, true);
            }
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

		# Update order payment status and balance
		$success = true;

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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['morderno'], '', null, null, $response_result_id);
            
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($processed) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = self::RETURN_FAIL_CODE;
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	## Validates whether the callback from API contains valid info and matches with the order
	## Reference: code sample, callback.php
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array(
			'merchantno','orderno','morderno', 'paycode', 'tjmoney', 'money', 'status', 'sign'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}
		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['status'] != self::P_ERRORCODE_PAYMENT_SUCCESS) {
			$this->writePaymentErrorLog('=====================aeepay payment was not successful', $fields);
			return false;
		}

        $checkFieldMoney = $this->getSystemInfo('check_field_money', 'tjmoney'); // default to tjmoney

        if(!isset($fields[$checkFieldMoney])){
            $this->writePaymentErrorLog(
                '=====================aeepay payment extra info [check_field_money] setting errors', 
                'extra info setting: '.$this->getSystemInfo('check_field_money').
                ', fields: '.$fields
            );
            return false;
        }

        if ($fields[$checkFieldMoney] != $this->convertAmountToCurrency($order->amount)) {

            if($this->getSystemInfo('allow_callback_amount_diff') && $checkFieldMoney == 'tjmoney'){
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['tjmoney'], $notes);
            }
            else{
                $this->writePaymentErrorLog("=====================aeepay payment amount not match, expected [$order->amount]", $fields);
                return false;
            }
        }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# Hide banklist by default, as this API does not support bank selection during form submit
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	# -- Private functions --
	# After payment is complete, the gateway will invoke this URL asynchronously
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## After payment is complete, the gateway will send redirect back to this URL
	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## Format the amount value for the API
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

    private function createSignStr($params) {
		// OriginalString=merchantno+"|"+morderno+"|"+ paycode +"|"+notifyurl+"|"+money+"|"+sendtime+"|"+buyerip+"|"+md5key；

		$keys = array('merchantno', 'morderno','paycode','notifyurl','money','sendtime','buyerip');
		$signStr = "";
		foreach($keys as $key) {
			$signStr .= $params[$key].'|';
		}
        $signStr .= $this->getSystemInfo('key');
		return $signStr;
    }


	protected function sign($params) {
		$signStr=$this->createSignStr($params);
		$sign = md5($signStr);
		return $sign;
	}

	private function validateSign($params) {
		// merchantno+“|”+orderno+“|”+morderno+ “|”+paycode+“|”+tjmoney+“|”+money+“|”+status + “|”+md5key；
		$keys = array('merchantno', 'orderno', 'morderno', 'paycode', 'tjmoney', 'money', 'status');
		$signStr = "";
		$result=false;
		foreach($keys as $key) {
			$signStr .= $params[$key].'|';
		}
		$signStr .= $this->getSystemInfo('key');
		$sign = md5($signStr);
		if($sign === $params['sign']){
			$result=true;
		}
		return $result;

	}
}
