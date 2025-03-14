<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * aipay  艾付
 *
 * * 'AIPAY_PAYMENT_API', ID 5037
 * * 'AIPAY_ALIPAY_PAYMENT_API', ID 5038
 * * 'AIPAY_ALIPAY_H5_PAYMENT_API', ID 5039
 * * 'AIPAY_WEIXIN_PAYMENT_API', ID 5040
 * * 'AIPAY_WEIXIN_H5_PAYMENT_API', ID 5041
 * * 'AIPAY_UNIONPAY_PAYMENT_API', ID 5129
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
abstract class Abstract_payment_api_aipay extends Abstract_payment_api {

	const SCANTYPE_WEIXIN= 'WECHAT'; //微信
    const SCANTYPE_ALIPAY= 'ALIPAY'; //支付宝
    const SCANTYPE_QQSCAN= 'QQSCAN'; //QQ扫码
    const SCANTYPE_JDSCAN= 'JDSCAN'; //京东扫码
    const SCANTYPE_UNIONPAY= 'UNIONPAY'; //银联扫码

    const SCANTYPE_WEIXIN_H5= 'WECHATWAP'; //微信
    const SCANTYPE_ALIPAY_H5= 'ALIPAYWAP'; //支付宝
    const SCANTYPE_QQSCAN_H5= 'QQWAP'; //QQ扫码
    const SCANTYPE_UNIONPAY_H5= 'UNIONPAYWAP'; //银联扫码

    const PAY_MODE_WEB = '01'; // WEB支付模式 網銀用
    const PAY_MODE_QRCODE = '09'; // 扫码支付
    const PAY_MODE_H5 = '12'; // H5支付模式

    const CARD_TYPE = '0'; // 仅允许使用借记卡支付
	const RETURN_SUCCESS_CODE = '';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '00';
	const PAY_RESULT_SUCCESS = 'S';

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

		$params['version'] = 'v1';
		$params['merchant_no'] = $this->getSystemInfo("account");
		$params['order_no'] = $order->secure_id;
		$params['goods_name'] = base64_encode('Deposit');
		$params['order_amount'] = $this->convertAmountToCurrency($amount); //元
		$params['backend_url'] = $this->getNotifyUrl($orderId);
		$params['frontend_url'] = $this->getReturnUrl($orderId);
		$params['reserve'] = 'Deposit';
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['card_type'] = self::CARD_TYPE;

		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================aipay generatePaymentUrlForm", $params);

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
        $response = $this->submitGetForm($url, $params, false, $params['order_no']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================aipay processPaymentUrlFormQRcode response json to array', $decode_data);

        $msg = lang('Invalidate API response');
    	if(!empty($decode_data['code_url']) && ($decode_data['result_code'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $decode_data['code_url'],
            );
        }else {
            if(!empty($decode_data['result_msg'])) {
                $msg = $decode_data['result_msg'];
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
            $this->CI->utils->debug_log('=======================aipay callbackFromServer server callbackFrom', $params);
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
		$requiredFields = array('merchant_no', 'order_no','order_amount','original_amount','upstream_settle','result','pay_time','trace_id','reserve','sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================aipay missing parameter: [$f]", $fields);
				return false;
			}
		}

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================aipay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

		if ($fields['result'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['result'];
			$this->writePaymentErrorLog("=====================aipay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval($fields['order_amount']) ) {
			$this->writePaymentErrorLog("=====================aipay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['order_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================aipay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
            array('label' => '工商银行', 'value' => 'ICBC'),
            array('label' => '农业银行', 'value' => 'ABC'),
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '建设银行', 'value' => 'CCB'),
            array('label' => '交通银行', 'value' => 'BOCOM'),
            array('label' => '中信银行', 'value' => 'CNCB'),
            array('label' => '光大银行', 'value' => 'CEB'),
            array('label' => '华夏银行', 'value' => 'HXB'),
            array('label' => '民生银行', 'value' => 'CMBC'),
            array('label' => '广发银行', 'value' => 'GDB'),
            array('label' => '平安银行', 'value' => 'PAB'),
            array('label' => '招商银行', 'value' => 'CMB'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '北京银行', 'value' => 'BCCB'),
            array('label' => '上海银行', 'value' => 'BOS'),
            array('label' => '邮政储蓄银行', 'value' => 'PSBC'),
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
       	$signStr = $this->createSignStr($params);
        $sign = md5($signStr);
		$this->CI->utils->debug_log('==============================aipay sign: ', $sign, $signStr);
		return $sign;
	}

    public function verifySignature($data) {
	    $data_keys = array('merchant_no','order_no','order_amount','original_amount','upstream_settle','result','pay_time','trace_id','reserve');
        $signStr = '';
		foreach ($data_keys as $value) {
			$signStr .= $value."=".$data[$value]."&";
		}
		$signStr .= 'key='. $this->getSystemInfo('key');
        $sign = strtolower(md5($signStr));

        return $sign == $data['sign'];
    }

    private function createSignStr($params) {
		if($this->getSystemInfo("creatSignStrParams")){
			$params = array(
				'version' => $params['version'],
				'merchant_no' => $params['merchant_no'],
				'order_no' => $params['order_no'],
				'goods_name' => $params['goods_name'],
				'order_amount' => $params['order_amount'],
				'backend_url' => $params['backend_url'],
				'frontend_url' => $params['frontend_url'],
				'reserve' => $params['reserve'],
				'pay_mode' => $params['pay_mode'],
				'bank_code' => $params['bank_code'],
				'card_type' => $params['card_type']
			);
		}
		$signStr = '';
		foreach ($params as $key => $value) {
			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'key='. $this->getSystemInfo('key');
		return $signStr;
	}
}
