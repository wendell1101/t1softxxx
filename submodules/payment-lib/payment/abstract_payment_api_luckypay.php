<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * LUCKYPAY
 *
 * * 'LUCKYPAY_ALIPAY_PAYMENT_API', ID 5677
 * * 'LUCKYPAY_WEIXIN_PAYMENT_API', ID 5678
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
abstract class Abstract_payment_api_luckypay extends Abstract_payment_api {

    const PAYWAY_ALIPAY	   = '1'; //支付宝
    const PAYWAY_WEIXIN	   = '2'; //微信
	const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '1';

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
		$this->CI->load->model('player');

		$params['MerchantCode'] = $this->getSystemInfo("account");
		$params['OrderMoney'] = $this->convertAmountToCurrency($amount); //元
		$params['NotifyUrl'] = $this->getNotifyUrl($orderId);
		$params['CallbackUrl'] = $this->getReturnUrl($orderId);
		$params['CustomerIP'] = $this->getClientIP();
		$params['MerchantOrderID'] = $order->secure_id;
		$params['OrderDate'] = date('Y-m-d H:i:s'); # e.g. 2015-01-01 12:45:52;

		$this->configParams($params, $order->direct_pay_extra_info);
		$params['MerchantSign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================luckypay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}


    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
    	$url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, false, $params['MerchantOrderID']);
        $decode_data = json_decode($response, true);
        $this->CI->utils->debug_log('=====================luckypay processPaymentUrlFormQRcode response json to array', $decode_data);
		$msg = lang('Invalidate API response');

		if(($decode_data['result'] == self::REQUEST_SUCCESS  && !empty($decode_data['data']['pay_url']))) {
			return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decode_data['data']['pay_url'],
            );
        }else {
            if(!empty($decode_data['msg'])) {
                $msg = $decode_data['msg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

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
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================luckypay callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['mch_order_id'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['message'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields, &$processed = false) {

		$requiredFields = array('mch_order_id', 'mch_code','order_type','order_amount','callback_url', 'notify_url', 'sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================luckypay missing parameter: [$f]", $fields);
				return false;
			}
		}
		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['order_amount'] ) ) {
			$this->writePaymentErrorLog("=====================luckypay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['mch_order_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================luckypay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }
        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================luckypay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	public function getBankListInfoFallback() {
		return array(
            array('label' => '工商银行', 'value' => '1102'),
            array('label' => '农业银行', 'value' => '1103'),
            array('label' => '建设银行', 'value' => '1105'),
            array('label' => '交通银行', 'value' => '1301'),
            array('label' => '光大银行', 'value' => '1311'),
            array('label' => '华夏银行', 'value' => '1304'),
            array('label' => '民生银行', 'value' => '1305'),
            array('label' => '广发银行', 'value' => '1460'),
            array('label' => '招商银行', 'value' => '1308'),
            array('label' => '北京银行', 'value' => '1313'),
            array('label' => '上海银行', 'value' => '1310'),
            array('label' => '邮政储蓄银行', 'value' => '1312'),
		);
	}

	# -- Private functions --
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- private helper functions --
	public function sign($params) {
		$data = [
			'mch_code' => $params["MerchantCode"],
			'order_amount'=> $params["OrderMoney"],
			'notify_url' => $params["NotifyUrl"],
			'callback_url' => $params["CallbackUrl"],
			'user_ip' => $params["CustomerIP"],
			'mch_order_id' => $params["MerchantOrderID"],
			'order_time' => $params["OrderDate"],
			'order_type' => $params["OrderType"]
		];
		$signStr =  $this->createSignStr($data);
        $sign = md5($signStr);
		return $sign;
	}

    public function verifySignature($data) {
        $signStr =  $this->createSignStr($data);
        $sign = md5($signStr);
        return $sign == $data['sign'];
    }

    private function createSignStr($data) {
		$date = date("Ymd");
		ksort($data);
        $signStr = '';
        foreach($data as $key => $value) {
            if($value == null || $key == 'sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= $this->getSystemInfo('key'). '&' .$date;

		return $signStr;
	}
}
