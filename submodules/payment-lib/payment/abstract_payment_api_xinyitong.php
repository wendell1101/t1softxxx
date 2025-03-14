<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * xinyitong 新亿通
 *
 * * 'XINYITONG_ALIPAY_PAYMENT_API',    ID 5078
 * * 'XINYITONG_ALIPAY_H5_PAYMENT_API', ID 5079
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
abstract class Abstract_payment_api_xinyitong extends Abstract_payment_api {

    const PD_FRPID_ALIPAY	  = 'alipay'; //支付宝
    const PD_FRPID_ALIPAY_H5  = 'alipaywap'; //支付宝H5

	const RETURN_SUCCESS_CODE = 'success';
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

		$params['p0_Cmd'] = 'Buy';
		$params['p1_MerId'] = $this->getSystemInfo("account");
		$params['p2_Order'] = $order->secure_id;
		$params['p3_Amt'] = $this->convertAmountToCurrency($amount); //元
		$params['p4_Cur'] = 'CNY';
		$params['p8_Url'] = $this->getNotifyUrl($orderId);
		$params['p9_SAF'] = '0';
		$params['pr_NeedResponse'] = '1';
				
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['hmac'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================xinyitong generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {

	    $url = $this->getSystemInfo('url');
	    $this->CI->utils->debug_log("=====================xinyitong processPaymentUrlFormPost URL", $url);
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
    	$this->CI->utils->debug_log('=====================xinyitong processPaymentUrlFormQRcode scan url',$url);
        $response = $this->submitPostForm($url, $params, false, $params['p2_Order']);
        $this->CI->utils->debug_log('=====================xinyitong processPaymentUrlFormQRcode received response', $response);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('=====================xinyitong processPaymentUrlFormQRcode response[1] json to array', $decode_data);
        $msg = lang('Invalidte API response');

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
        $this->CI->utils->debug_log('=======================xinyitong callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================xinyitong callbackFromServer server callbackFrom', $params);
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
                $params['r6_Order'], 'Third Party Payment (No Bank Order Number)', # no info available
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

		$requiredFields = array('p1_MerId', 'r0_Cmd','r1_Code','r2_TrxId','r3_Amt','r4_Cur','r5_Pid','r6_Order','r7_Uid','r8_MP','r9_BType','hmac');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================xinyitong missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['r1_Code'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['r1_Code'];
			$this->writePaymentErrorLog("=====================xinyitong Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['r3_Amt'] )
		) {
			$this->writePaymentErrorLog("=====================xinyitong Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['r6_Order'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================xinyitong checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================xinyitong checkCallbackOrder verify signature Error', $fields);
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
            // array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '建设银行', 'value' => '1105'),
            // array('label' => '农业发展银行', 'value' => '203'),
            array('label' => '交通银行', 'value' => '1301'),
            // array('label' => '中信银行', 'value' => 'ECITIC'),
            array('label' => '光大银行', 'value' => '1311'),
            array('label' => '华夏银行', 'value' => '1304'),
            array('label' => '民生银行', 'value' => '1305'),
            array('label' => '广发银行', 'value' => '1460'),
            // array('label' => '平安银行', 'value' => 'PINGAN'),
            array('label' => '招商银行', 'value' => '1308'),
            // array('label' => '兴业银行', 'value' => 'CIB'),
            // array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '北京银行', 'value' => '1313'),
            // array('label' => '恒丰银行', 'value' => '315'),
            // array('label' => '浙商银行', 'value' => '316'),
            // array('label' => '渤海银行', 'value' => 'BHB'),
            array('label' => '上海银行', 'value' => '1310'),
            array('label' => '邮政储蓄银行', 'value' => '1312'),
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

		$params_keys = array('p0_Cmd','p1_MerId','p2_Order','p3_Amt','p4_Cur','p8_Url','p9_SAF','pd_FrpId','pr_NeedResponse');
		$signStr =  $this->createSignStr($params_keys,$params);
		$sign = $this->HmacMd5($signStr, $this->getSystemInfo('key'));
	
        return $sign;
		
	}

    public function verifySignature($data) {
	    $callback_sign = $data['hmac'];
	    $data_keys = array('p1_MerId','r0_Cmd','r1_Code','r2_TrxId','r3_Amt','r4_Cur','r5_Pid','r6_Order','r7_Uid','r8_MP','r9_BType');
        $signStr =  $this->createSignStr($data_keys,$data);
        $sign = $this->HmacMd5($signStr, $this->getSystemInfo('key'));
       
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }

    private function createSignStr($params,$vals) {
       	$signStr='';
		foreach ($params as $value) {

			if(is_null($value)){
				continue;
			}
			$signStr .= $vals[$value];
		}
		return $signStr;
	}

    public function HmacMd5($data, $key)
    {
        // RFC 2104 HMAC implementation for php.
        // Creates an md5 HMAC.
        // Eliminates the need to install mhash to compute a HMAC
        // Hacked by Lance Rushing(NOTE: Hacked means written)

        //需要配置环境支持iconv，否则中文参数不能正常处理
        $this->utils->debug_log('HmacMd5 key : ' . $key . ' data : ' . $data);
        $key = iconv("GB2312", "UTF-8//IGNORE", $key);
        $this->utils->debug_log('HmacMd5 iconv key : ' . $key);
        $data = iconv("GB2312", "UTF-8//IGNORE", $data);
        $this->utils->debug_log('HmacMd5 iconv data : ' . $data);

        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*", md5($key));
        }
        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;

        return md5($k_opad . pack("H*", md5($k_ipad . $data)));
    }
}
