<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * GTPAY
 *
 * * 'GTPAY_ALIPAY_PAYMENT_API', ID 5376
 * * 'GTPAY_ALIPAY_H5_PAYMENT_API', ID 5377
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
abstract class Abstract_payment_api_gtpay extends Abstract_payment_api {


    const PAYTYPE_ALIPAY = 2;
    const PAYTYPE_ALIPAY_H5 = 12;


    const RETURN_SUCCESS_CODE = '0';
    const CALLBACK_SUCCESS_CODE = '1';
	const RETURN_SUCCESS = 'SUCCESS';
    const RETURN_FAILED = 'FAIL';

	public function __construct($params = null) {
		parent::__construct($params);
		$this->_custom_curl_header = ["Content-Type: application/json"];
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
        $params['mcnNum'] = $this->getSystemInfo("account");
		$params['orderId'] = $order->secure_id;
        $params['backUrl'] = $this->getNotifyUrl($orderId);
		$this->configParams($params, $order->direct_pay_extra_info); //$params['payType']
		$params['amount'] = $this->convertAmountToCurrency($amount); //分
		$params['sign'] = $this->sign($params);
		$params['ip'] = $this->getClientIP();
        $params['returnUrl'] = $this->getReturnUrl($orderId);

		$this->CI->utils->debug_log("=====================gtpay generatePaymentUrlForm", $params);
		return $this->processPaymentUrlForm($params);
	}


	# Display QRCode get from curl
	protected function processPaymentUrlFormQRCode($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['orderId']);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================gtpay processPaymentUrlFormPost response json to array', $response);

        $msg = lang('Invalidate API response');
    	if(!empty($response['qrCode']) && ($response['status'] == self::RETURN_SUCCESS_CODE)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['qrCode'],
            );
        }else {
            if(($response['status'] != self::RETURN_SUCCESS_CODE) && !empty($response['message'])) {
                $msg = $response['message'];
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
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================gtpay callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['order_id'], null, null, null, $response_result_id);
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
		$requiredFields = array('orderId', 'payStatus','amount');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================gtpay missing parameter: [$f]", $fields);
				return false;
			}
		}
        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=======================gtpay checkCallbackOrder verify signature Error', $fields);
        }

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['payStatus'] != self::CALLBACK_SUCCESS_CODE) {
			$payStatus = $fields['payStatus'];
			$this->writePaymentErrorLog("=====================gtpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
			$this->writePaymentErrorLog("=====================gtpay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['orderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================gtpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
		return number_format($amount * 100, 0, '.', '');
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
        $sign = strtoupper(md5($signStr));
		return $sign;
	}

    private function createSignStr($params) {
		$signStr = '';
		foreach ($params as $key => $value) {

			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'secreyKey='. $this->getSystemInfo('key');
		return $signStr;
    }

    public function validateSign($params) {
		$keys = array(
            'orderId'   => $params['orderId'],
            'payTime'   => $params['payTime'],
            'payStatus' => $params['payStatus']
        );
		$signStr = '';
		foreach ($keys as $key => $value) {
            if($key = 'sign'){
                continue;
            }
			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'secreyKey='. $this->getSystemInfo('key');
        $sign = strtoupper(md5($signStr));
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

}
