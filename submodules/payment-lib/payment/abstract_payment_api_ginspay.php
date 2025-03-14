<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * Ginspay  隱聯支付
 *
 * * 'GINSPAY_UNIONPAY_PAYMENT_API', ID 945
 * * 'GINSPAY_QUICKPAY_PAYMENT_API', ID 946
 * * 'GINSPAY_PAYMENT_API', ID 947
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
abstract class Abstract_payment_api_ginspay extends Abstract_payment_api {

    const SCANTYPE_MUP= 'MUP'; // Union QR Code Payment 手机支付
    const SCANTYPE_DUP= 'DUP'; // Union Fast Pay 银联快捷
    const TRANATTR_MODE = '8'; //支付渠道，参数取值 支付宝支付

	const RETURN_SUCCESS_CODE = 'OK';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '0';
	const PAY_RESULT_SUCCESS = 'S1';

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

		$params['service_version'] = '1.0';
		$params['mcode'] = $this->getSystemInfo("account");
		$params['orderid'] = $order->secure_id;
		$params['member'] = 'Deposit';
		$params['currency'] = $this->getSystemInfo("currency");
		$params['amount'] = $this->convertAmountToCurrency($amount); //分
		$params['backend_url'] = $this->getNotifyUrl($orderId);
		$params['redirect_url'] = $this->getReturnUrl($orderId);
		$params['trans_time'] = date('Y-m-dhis');;
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================ginspay generatePaymentUrlForm", $params);
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
        $response = $this->submitPostForm($url, $params, false, $params['orderid']);
        $response = $this->parseResultXML($response);
        $this->CI->utils->debug_log('========================================ginspay processPaymentUrlFormQRcode xmltoarray response', $response);
        $msg = lang('Invalidate API response');

        if(!empty($decode_data['Data']['Url']) && ($decode_data['Code'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $decode_data['Data']['Url'],
            );
        }else {
            if(!empty($decode_data['Status'])) {
                $msg = $decode_data['Status'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }


    public function parseResultXML($resultXml) {
		$result = NULL;
		$obj = simplexml_load_string($resultXml);
		$result = $this->CI->utils->xmlToArray($obj);

		return $result;
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

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================ginspay callbackFromServer server callbackFrom', $params);
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

	/**
	 * detail: Validates whether the callback from API contains valid info and matches with the order
	 *
	 * @return boolean
	 */

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array('service_version','sign','billno','orderid','currency','amount','status');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================ginspay missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['status'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['status'];
			$this->writePaymentErrorLog("=====================ginspay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['amount'] )
		) {
			$this->writePaymentErrorLog("=====================ginspay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['orderid'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================ginspay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================ginspay checkCallbackOrder verify signature Error', $fields);
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
            array('label' => '中国工商银行', 'value' => 'ICBC'),
            array('label' => '中国农业银行', 'value' => 'ABC'),
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '中国建设银行', 'value' => 'CCB'),
            array('label' => '交通银行', 'value' => 'BCOM'),
            array('label' => '中信银行', 'value' => 'CITIC'),
            array('label' => '中国光大银行', 'value' => 'CEB'),
            array('label' => '中国华夏银行', 'value' => 'HXB'),
            array('label' => '中国民生银行', 'value' => 'CMBC'),
            array('label' => '广东发展银行', 'value' => 'GDB'),
            array('label' => '中国平安银行', 'value' => 'SZCB'),
            array('label' => '招商银行', 'value' => 'CMB'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '上海浦东发展银行', 'value' => 'SPDB'),
            array('label' => '北京银行', 'value' => 'BOBJ'),
            array('label' => '渤海银行', 'value' => 'CBHB'),
            array('label' => '上海银行', 'value' => 'SHRCC'),
            array('label' => '中国邮政储蓄银行 ', 'value' => 'CPSRB'),
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
		return number_format($amount*100, 0, '.', '');
	}

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
		$params_keys = array('service_version','mcode','orderid','member','currency','amount','backend_url','redirect_url','bank_code','trans_time');
        $signStr = '';

		foreach($params_keys as $key => $value) {
			$signStr .= $value.'='.$params[$value].'&';
		}
		$signStr .= 'key='.$this->getSystemInfo('key');
        $sign = strtoupper(sha1($signStr));
		return $sign;
	}

    public function verifySignature($data) {
	    $callback_sign = $data['sign'];
        $data_keys = array('service_version','billno','orderid','currency','amount','status');
        $signStr = '';
        foreach ($data_keys as $key => $value) {
			$signStr .= $value.'='.$data[$value].'&';
		}
		$signStr .= 'key='. $this->getSystemInfo('key');
        $sign = strtoupper(sha1($signStr));
        return $sign == $callback_sign;
    }
}
