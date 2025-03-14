<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * wepay 微富
 *
 * * 'WEPAY_ALIPAY_PAYMENT_API', ID 5119
 * * 'WEPAY_WEIXIN_PAYMENT_API', ID 5120
 * * 'WEPAY_ALIPAY_H5_PAYMENT_API', ID 5391
 * * 'WEPAY_WEIXIN_H5_PAYMENT_API', ID 5392
 * * 
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
abstract class Abstract_payment_api_wepay extends Abstract_payment_api {

    const PAY_CHANNEL_ALIPAY = 'AliPay'; //支付宝
	const PAY_CHANNEL_WEIXIN = 'WeChat'; //微信

	const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = 'true';
	const PAY_RESULT_SUCCESS = 'true';
	const REQUEST_ERROR = 'false';
    const RETURN_ERROR_CODE = 'fail';

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

		$params['BuCode'] = $this->getSystemInfo("account");
		$params['OrderId'] = $order->secure_id;
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['OrderAccount'] = $username;
		$params['Amount'] = $this->convertAmountToCurrency($amount); //元
		$params['NotifyURL'] = $this->getNotifyUrl($orderId);
		$params['Sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================wepay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {

	    $url = $this->getSystemInfo('url');
	    $this->CI->utils->debug_log("=====================wepay processPaymentUrlFormPost URL", $url);
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
    	$this->CI->utils->debug_log('=====================wepay processPaymentUrlFormQRcode scan url',$url);
        $response = $this->submitPostForm($url, $params, true, $params['OrderId']);
        $this->CI->utils->debug_log('=====================wepay processPaymentUrlFormQRcode received response', $response);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('=====================wepay processPaymentUrlFormQRcode response[1] json to array', $decode_data);
        $msg = lang('Invalidte API response');

		if(!empty($decode_data['data']['redirectURL']) && ($decode_data['status'] == self::REQUEST_SUCCESS)) {
			echo $decode_data['data']['redirectURL'];
        }else {
            if(!empty($decode_data['err_msg'])) {
                $msg = $decode_data['err_msg'];
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
		if(empty($params) || is_null($params)){
			$raw_post_data = file_get_contents('php://input', 'r');
        	$params = json_decode($raw_post_data, true);
		}
        $this->CI->utils->debug_log('=======================wepay callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================wepay callbackFromServer server callbackFrom', $params);
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
                $params['TransId'], 'Third Party Payment (No Bank Order Number)', # no info available
                null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$resultContent=[
                    'status'=> self::REQUEST_SUCCESS,
                    'err_msg'=> self::RETURN_SUCCESS_CODE,
                ];

            $result['message'] = json_encode($resultContent);
		} else {
			$resultContent=[
                    'status'=> self::REQUEST_ERROR,
                    'err_msg'=> self::RETURN_ERROR_CODE,
                ];

            $result['message'] = json_encode($resultContent);
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

		$requiredFields = array('BuCode','TransId','Amount','Status','Sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================wepay missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['Status'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['Status'];
			$this->writePaymentErrorLog("=====================wepay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['Amount'] )
		) {
			$this->writePaymentErrorLog("=====================wepay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}
		$orderid = substr($fields['TransId'], -13);
        if ($orderid != $order->secure_id) {
            $this->writePaymentErrorLog("=====================wepay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================wepay checkCallbackOrder verify signature Error', $fields);
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
            array('label' => '工商银行', 'value' => 'ICBC'),
            array('label' => '中国农业银行', 'value' => 'ABC'),
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '建设银行', 'value' => 'CCB'),
            // array('label' => '农业发展银行', 'value' => '203'),
            array('label' => '交通银行', 'value' => 'BOCM'),
            array('label' => '中信银行', 'value' => 'CITIC'),
            array('label' => '光大银行', 'value' => 'CEB'),
            array('label' => '华夏银行', 'value' => 'HXB'),
            array('label' => '中国民生银行', 'value' => 'CMBC'),
            array('label' => '广发银行', 'value' => 'CGB'),
            array('label' => '平安银行', 'value' => 'PINGANBANK'),
            array('label' => '招商银行', 'value' => 'CMB'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            // array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '北京银行', 'value' => 'BCCB'),
            array('label' => '南京银行', 'value' => 'NJCB'),
            // array('label' => '恒丰银行', 'value' => '315'),
            array('label' => '浙商银行', 'value' => 'CZ'),
            array('label' => '杭州银行', 'value' => 'HZBANK'),
            array('label' => '上海农村商业银行', 'value' => 'SRCB'),
            array('label' => '河北银行', 'value' => 'BOHB'),
            array('label' => '泰隆银行', 'value' => 'ZJTLCB'),
            array('label' => '成都银行', 'value' => 'BOCDBANK'),
            array('label' => '渤海银行', 'value' => 'CBHB'),
            array('label' => '东亚银行', 'value' => 'HKBEA'),
            array('label' => '宁波银行', 'value' => 'NBCB'),
            array('label' => '上海银行', 'value' => 'SHB'),
            array('label' => '上海浦东发展银行', 'value' => 'SPDB'),
            array('label' => '中国邮政', 'value' => 'PSBC'),
            array('label' => '北京农村商业银行', 'value' => 'BJRCB'),
            // array('label' => '徽商银行', 'value' => '440'),
            // array('label' => '广州市商业银行', 'value' => 'GRCBANK')
		);
	}

	# -- Private functions --
	/**
	 * detail: After payment is complete, the gateway will invoke this URL asynchronously
	 *
	 * @param int $orderId
	 * @return void
	 */
	public function getNotifyUrl($orderId) {
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
        $sign=md5($signStr);
		return $sign;
	}

    public function verifySignature($data) {
	    $callback_sign = $data['Sign'];
        $signStr =  $this->createSignStr($data);
        $sign=md5($signStr);
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }

    private function createSignStr($params) {
       	$signStr='';
		foreach ($params as $key => $value) {
			if(is_null($value) || empty($value) || $key == 'Sign' || $key == 'NotifyURL'){
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'Key='. $this->getSystemInfo('key');
		return $signStr;
	}
}
