<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * LeMei 乐美
 *
 * * 'LEMEI_ALIPAY_PAYMENT_API', ID 5064
 * * 'LEMEI_ALIPAY_H5_PAYMENT_API', ID 5065
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
abstract class Abstract_payment_api_lemei extends Abstract_payment_api {

	const CHANNELID_ONLINEBANK = '5000'; //网关支付
    const CHANNELID_ALIPAY	  = '1100'; //支付宝
    const CHANNELID_ALIPAY_H5  = '1000'; //支付宝H5
    const CHANNELID_WEIXIN	  = '2100'; //微信
    const CHANNELID_WEIXIN_H5  = '2000'; //微信H5

    const RESULTTYPE = '0';
	const RETURN_SUCCESS_CODE = 'SUCCESS';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '0';
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

		$params['UserID'] = $this->getSystemInfo("account");
		$params['OrderID'] = $order->secure_id;
		$params['FaceValue'] = $this->convertAmountToCurrency($amount); //单位为RMB-元
		$params['TimeStamp'] = date('Y-m-d h:i:s');
		$params['Version'] = 'V2.0';
		$params['IP'] = $this->getClientIp();
		$params['ResultType'] = self::RESULTTYPE;
		$params['NotifyUrl'] = $this->getNotifyUrl($orderId);
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================lemei generatePaymentUrlForm", $params);

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
        $response = $this->submitPostForm($url, $params, false, $params['OrderID']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('=====================lemei processPaymentUrlFormQRcode response json to array', $decode_data);

        $msg = lang('Invalidate API response');
		if(!empty($decode_data['PayUrl']) && ($decode_data['Resultcode'] == self::REQUEST_SUCCESS)) {
			return array(
	                'success' => true,
	                'type' => self::REDIRECT_TYPE_QRCODE,
	                'url' => $decode_data['PayUrl'],
            	);
        }else {
            if(!empty($decode_data['Resultdesc'])) {
                $msg = $decode_data['Resultdesc'];
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
        $this->CI->utils->debug_log('=======================lemei callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================lemei callbackFromServer server callbackFrom', $params);
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
                $params['OrderID'], 'Third Party Payment (No Bank Order Number)', # no info available
                null, null, $response_result_id);
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

		$requiredFields = array('UserID', 'OrderID','OrderIDP','FaceValue','PayTime','PayState','sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================lemei missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['PayState'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['PayState'];
			$this->writePaymentErrorLog("=====================lemei Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['FaceValue'] )
		) {
			$this->writePaymentErrorLog("=====================lemei Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['OrderID'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================lemei checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================lemei checkCallbackOrder verify signature Error', $fields);
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
        $sign=md5($signStr);
		return $sign;
	}

    public function verifySignature($data) {
	    $callback_sign = $data['sign'];
        $signStr =  $this->createSignStr($data,false);
        $sign=md5($signStr);
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }

    private function createSignStr($params,$keys=true) {
    	ksort($params);
       	$signStr='';
		foreach ($params as $key => $value) {

			if(is_null($value) || $key == 'sign'){
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		if($keys){
			$signStr .= 'key='. $this->getSystemInfo('key');
		}else{
			$signStr .= 'key='. $this->getSystemInfo('Backstage_key');
		}
		return $signStr;
	}
}
