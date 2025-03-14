<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**

 * TXWPAY  同兴旺
 *
 * * 'TXWPAY_ALIPAY_PAYMENT_API', ID 5282
 * * 'TXWPAY_ALIPAY_H5_PAYMENT_API', ID 5283
 * * 'TXWPAY_WEIXIN_PAYMENT_API', ID 5284
 * * 'TXWPAY_WEIXIN_H5_PAYMENT_API', ID 5285
 * * 'TXWPAY_QUICKPAY_PAYMENT_API', ID 5286
 * * 'TXWPAY_WITHDRAWAL_PAYMENT_API', ID 5341
 *
 * Required Fields:
 * * Account
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_txwpay extends Abstract_payment_api {


    const PAYTYPE_PCSCAN = '1'; // PC扫码支付(微信)
    const PAYTYPE_H5WAP = '2'; // H5支付模式(支付寶)


	const RETURN_SUCCESS_CODE = '0';
	const PAYRESULT_SUCCESS_CODE = '20';
	const RETURN_SUCCESS = 'ok';

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
		$params['app_id'] = $this->getSystemInfo("account");
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['order_id'] = $order->secure_id;
		$params['order_amt'] = $this->convertAmountToCurrency($amount); //元
		$params['notify_url'] = urlencode($this->getNotifyUrl($orderId));
		$params['return_url'] = urlencode($this->getReturnUrl($orderId));
		$params['goods_name'] = 'Deposit';
		$params['time_stamp'] = $this->getTimestamp();
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================txwpay generatePaymentUrlForm", $params);
		$this->CI->utils->debug_log("=====================txwpay notify_url", $this->getNotifyUrl($orderId));

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['order_id']);

        $this->CI->utils->debug_log('=====================txwpay processPaymentUrlFormPost url',$this->getSystemInfo('url'));
        $this->CI->utils->debug_log('========================================txwpay processPaymentUrlFormPost received response', $response);

        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================txwpay processPaymentUrlFormPost response[1] json to array', $response);
        $msg = lang('Invalidte API response');

    	if(!empty($response['pay_url']) && ($response['status_code'] == self::RETURN_SUCCESS_CODE)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['pay_url'],
            );
        }else {
            if(!empty($response['status_msg'])) {
                $msg = $response['status_msg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $msg
            );
        }
    }


    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
        $response = $this->submitGetForm($this->getSystemInfo('url'), $params, false, $params['order_id']);
        
        $this->CI->utils->debug_log('=====================txwpay processPaymentUrlFormQRcode url',$this->getSystemInfo('url'));
        $this->CI->utils->debug_log('========================================txwpay processPaymentUrlFormQRcode received response', $response);

        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================txwpay processPaymentUrlFormQRcode response[1] json to array', $response);
        $msg = lang('Invalidte API response');

    	if(!empty($response['pay_url']) && ($response['status_code'] == self::RETURN_SUCCESS_CODE)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $response['pay_url'],
            );
        }else {
            if(!empty($response['status_msg'])) {
                $msg = $response['status_msg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
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
		if(empty($params) || is_null($params)){
			$raw_post_data = file_get_contents('php://input', 'r');
        	$params = json_decode($raw_post_data, true);
		}
        $this->CI->utils->debug_log('=======================txwpay callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================txwpay callbackFromServer server callbackFrom', $params);
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
                $params['order_id'], 'Third Party Payment (No Bank Order Number)', # no info available
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
			$result['message'] = $processed ? self::RETURN_SUCCESS : 'failed';
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

		$requiredFields = array('app_id', 'order_id','pay_seq','pay_amt','pay_result','time_stamp','sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================txwpay missing parameter: [$f]", $fields);
				return false;
			}
		}
        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=======================txwpay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['pay_result'] != self::PAYRESULT_SUCCESS_CODE) {
			$payStatus = $fields['pay_result'];
			$this->writePaymentErrorLog("=====================txwpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($fields['pay_amt'] != $this->convertAmountToCurrency($order->amount)) {
			$this->writePaymentErrorLog("=====================txwpay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['order_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================txwpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
		return number_format($amount, 2, '.', '');
	}

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
       	$signStr =  $this->createSignStr($params);
        $sign = strtolower(trim(md5($signStr)));
		return $sign;
	}

    public function validateSign($params) {
	    $key = array('app_id'=>$params['app_id'],'order_id'=>$params['order_id'],'pay_seq'=>$params['pay_seq'],'pay_amt'=>$params['pay_amt'],'pay_result'=>$params['pay_result'],);
        $signStr = '';
		foreach ($key as $key => $value) {

			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'key='. md5($this->getSystemInfo('key'));
        $sign = strtolower(trim(md5($signStr)));
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    private function createSignStr($params) {
        if(!isset($params['pay_type'])){
            $params = array('app_id'=>$params['app_id'],'bank_code'=>$params['bank_code'],'order_id'=>$params['order_id'],'order_amt'=>$params['order_amt'],'notify_url'=>urldecode($params['notify_url']),'return_url'=>urldecode($params['return_url']),'time_stamp'=>$params['time_stamp']);
        }
        else{
            $params = array('app_id'=>$params['app_id'],'pay_type'=>$params['pay_type'],'order_id'=>$params['order_id'],'order_amt'=>$params['order_amt'],'notify_url'=>urldecode($params['notify_url']),'return_url'=>urldecode($params['return_url']),'time_stamp'=>$params['time_stamp']);
        }
		$signStr = '';
		foreach ($params as $key => $value) {

			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'key='. md5($this->getSystemInfo('key'));
		return $signStr;
    }
    

    

	# -- time_stamp --
	public function getTimestamp() {
        date_default_timezone_set('Asia/Shanghai');
		return date('YmdHis');
	}
}
