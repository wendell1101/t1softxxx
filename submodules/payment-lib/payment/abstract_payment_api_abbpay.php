<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ABB
 *
 * * 'ABBPAY_ALIPAY_PAYMENT_API', ID 5082
 * * 'ABBPAY_WEIXIN_PAYMENT_API', ID 5114
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
abstract class Abstract_payment_api_abbpay extends Abstract_payment_api {

    const PAYWAY_ALIPAY	   = '0'; //支付宝
    const PAYWAY_WEIXIN	   = '1'; //微信

	const RETURN_SUCCESS_CODE = 'ok';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '10000';
	const PAY_RESULT_SUCCESS = '1';

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
		$player = $this->CI->player->getPlayerById($playerId);
		$username = $player['username'];

		$params['uid'] = $this->getSystemInfo("account");
		$params['price'] = $this->convertAmountToCurrency($amount); //元
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['return_url'] = $this->getReturnUrl($orderId);
		$params['orderid'] = $order->secure_id;
		$params['orderuid'] = $username;
		$params['goodsname'] = 'Deposit';

		$this->configParams($params, $order->direct_pay_extra_info);
		$params['key'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================abbpay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {

	    $url = $this->getSystemInfo('url');
	    $this->CI->utils->debug_log("=====================abbpay processPaymentUrlFormPost URL", $url);
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
        $response = $this->submitPostForm($url, $params, false, $params['orderid']);
        $decode_data = json_decode($response, true);
        $this->CI->utils->debug_log('=====================abbpay processPaymentUrlFormQRcode response json to array', $decode_data);

        $msg = lang('Invalidate API response');
		if(!empty($decode_data['data']['scanurl']) && ($decode_data['resultCode'] == self::REQUEST_SUCCESS)) {
			return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decode_data['data']['scanurl'],
            );
        }else {
            if(!empty($decode_data['resultMsg'])) {
                $msg = $decode_data['resultMsg'];
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
            $this->CI->utils->debug_log('=======================abbpay callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderid'], null, null, null, $response_result_id);
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

		$requiredFields = array('pay_id', 'orderid','price','realprice','orderuid','key');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================abbpay missing parameter: [$f]", $fields);
				return false;
			}
		}

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================abbpay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['price'] ) ) {
			$this->writePaymentErrorLog("=====================abbpay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['orderid'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================abbpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
		$params_keys = array('goodsname', 'notify_url', 'orderid', 'orderuid', 'payway', 'price', 'return_url', 'token', 'uid');
		$signStr =  $this->createSignStr($params_keys, $params);
        $sign = md5($signStr);
		return $sign;
	}

    public function verifySignature($data) {
	    $data_keys = array('orderid', 'orderuid', 'pay_id', 'price', 'realprice', 'token');
        $signStr =  $this->createSignStr($data_keys, $data);
        $sign = md5($signStr);
        return $sign == $data['key'];
    }

    private function createSignStr($params_keys, $params) {
        $params['token'] = $this->getSystemInfo('key');
       	$signStr = '';
		foreach ($params_keys as $value) {
			if(is_null($value)){
				continue;
			}
			$signStr .= $params[$value];
		}
		return $signStr;
	}
}
