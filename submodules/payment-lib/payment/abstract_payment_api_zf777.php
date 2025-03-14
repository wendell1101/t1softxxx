<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ZF777
 *
 * * 'ZF777_PAYMENT_API', ID 5944
 * * 'ZF777_MOMO_PAYMENT_API', ID 5945
 * * 'ZF777_ZALO_PAYMENT_API', ID 5946
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
abstract class Abstract_payment_api_zf777 extends Abstract_payment_api {

    const PAYWAY_MOMO	   = 'momo';
    const PAYWAY_ZALO	   = 'zalo';
	const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '1';

    public function __construct($params = null) {
		parent::__construct($params);
		$this->_custom_curl_header = array('Content-Type:application/json');
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
		$params['userid'] = $this->getSystemInfo("account");
		$params['orderid'] = $order->secure_id;
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['amount'] = (int)$this->convertAmountToCurrency($amount); //元
		$params['notifyurl'] = $this->getNotifyUrl($orderId);
		$params['returnurl'] = $this->getReturnUrl($orderId);
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================zf777 generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
    	$url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, true, $params['orderid']);
        $decode_data = json_decode($response, true);
        $this->CI->utils->debug_log('=====================zf777 processPaymentUrlFormQRcode response json to array', $decode_data);
		$msg = lang('Invalidate API response');

		if((isset($decode_data['success']) && $decode_data['success'] == self::REQUEST_SUCCESS  && isset($decode_data['pageurl']) && !empty($decode_data['pageurl']))) {
			return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decode_data['pageurl'],
            );
        }else {
            if(!empty($decode_data['message']) && isset($decode_data['message'])) {
                $msg = $decode_data['message'];
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
            $this->CI->utils->debug_log('=======================zf777 callbackFromServer server callbackFrom', $params);
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

		$requiredFields = array('ispay', 'sign','userid','amount','orderid','payamount');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================zf777 missing parameter: [$f]", $fields);
				return false;
			}
		}
		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['payamount'] ) ) {
			$this->writePaymentErrorLog("=====================zf777 Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['orderid'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================zf777 checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }
        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================zf777 checkCallbackOrder verify signature Error', $fields);
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
            array('label' => 'vietcombank', 'value' => 'vietcombank'),
            array('label' => 'vietinbankipay', 'value' => 'vietinbankipay'),
            array('label' => 'vtpay', 'value' => 'ViettelPay'),
            array('label' => 'tpbank', 'value' => 'TPBank'),
            array('label' => 'acbbank', 'value' => 'ACBBank')
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
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 2, '.', '');
    }

	# -- private helper functions --
	public function sign($params) {
		$signStr = $this->getSystemInfo('key').$params["orderid"].$params["amount"];
        $sign = md5($signStr);
		return $sign;
	}

    public function verifySignature($data) {
        $signStr = $this->getSystemInfo('key').$data["orderid"].$data["payamount"];
        $sign = md5($signStr);
        if($sign == $data['sign']){
        	return true;
        }else{
        	return false;
        }
    }
}
