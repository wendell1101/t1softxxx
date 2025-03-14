<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * psgagent 海貝
 *
 *
 * * PSGAGENT_PAYMENT_API, ID: 5298
 * * PSGAGENT_ALIPAY_PAYMENT_API, ID: 5299
 * * PSGAGENT_ALIPAY_H5_PAYMENT_API, ID: 5300
 * * PSGAGENT_WEIXIN_PAYMENT_API, ID: 5301
 * * PSGAGENT_WEIXIN_H5_PAYMENT_API, ID: 5302
 * * PSGAGENT_UNIONPAY_PAYMENT_API, ID: 5303
 * * PSGAGENT_UNIONPAY_H5_PAYMENT_API, ID: 5304
 * * PSGAGENT_QUICKPAY_PAYMENT_API, ID: 5305
 *
 * Required Fields:
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 * Field Values:
 * * URL:
 *   網銀 http://47.96.92.118:7001/psgAgent/h5WkPay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_psgagent extends Abstract_payment_api {
	const BIZTYPE_ONLINEBANK  = '100011'; #网关支付
    const BIZTYPE_ALIPAY	  = '100007'; #支付宝
    const BIZTYPE_WEIXIN      = '100006'; #微信
	const BIZTYPE_UNIONPAY    = '100005'; #銀聯扫码
    const BIZTYPE_QUICKPAY    = '100003'; #网关快捷

    const BIZTYPE_WEIXIN_H5   = '100013'; #微信h5
    const BIZTYPE_ALIPAY_H5   = '100014'; #支付宝H5
	const BIZTYPE_UNIONPAY_H5 = '100017'; #銀聯h5

    const RESULT_CODE_SUCCESS = 'F5';
	const RETURN_SUCCESS_CODE = 'FS';
    const RETURN_FAILED_CODE  = 'FAIL';
	const PAY_RESULT_SUCCESS  = '00';

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

        $params['branchId']  = $this->getSystemInfo("branchId");
        $params['merCode']   = $this->getSystemInfo("account");
        $params['settType']  = 'T0';
        $params['orderId']   = $order->secure_id;
        $params['transDate'] = date("Ymd");
        $params['transTime'] = date("His");
        $params['transAmt']  = $this->convertAmountToCurrency($amount); #分
        $params['returnUrl'] = $this->getNotifyUrl($orderId);

		$this->configParams($params, $order->direct_pay_extra_info);
		$params['signature'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================psgagent generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

    protected function processPaymentUrlFormPost($params) {
        $req_data_json = json_encode($params,JSON_UNESCAPED_UNICODE);
		$data_str = $this->encrypt($req_data_json,$this->getSystemInfo('conf_3deskey'),$this->getSystemInfo('iv'));
		$data = urlencode(urlencode($data_str));

		$result = $this->postFormdata($data);
		$result = json_decode($result,true);
		$post_data = array('data' => $result);
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $this->getSystemInfo('url'),
            'params' => $post_data,
            'post' => true,
        );

    }

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['orderId']);
        $result = json_decode($response,true);
        $this->CI->utils->debug_log('=====================psgagent processPaymentUrlFormPost decoded result', $result);

        if(isset($result['qrCodeURL']) && $result['respCode'] == self::RESULT_CODE_SUCCESS) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $result['qrCodeURL'],
            );
        }
        else if($result['respMsg']) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'Msg: '.$result['respMsg'].', RespCode: '.$result['respCode'],
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
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
            $this->CI->utils->debug_log('=======================psgagent callbackFromServer server callbackFrom', $params);
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

		# Update order payment status and balance
		$success=true;
		$decrypyt_result = $this->decrypt($params['data'], $this->getSystemInfo('conf_3deskey'), $this->getSystemInfo('iv'));
		$params = json_decode($decrypyt_result,true);


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
                $params['orderId'], 'Third Party Payment (No Bank Order Number)', # no info available
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

		$requiredFields = array('data');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================psgagent missing parameter: [$f]", $fields);
				return false;
			}
		}

		$decrypyt_result = $this->decrypt($fields['data'], $this->getSystemInfo('conf_3deskey'), $this->getSystemInfo('iv'));
		$params = json_decode($decrypyt_result,true);
        $this->CI->utils->debug_log('=========================psgagent checkCallbackOrder json_decode decrypt params', $params);

		if ($params['transCode'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $params['transCode'];
			$this->writePaymentErrorLog("=====================psgagent checkCallbackOrder Payment was not successful, payStatus is [$payStatus]", $params);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $params['transAmt']) {
			$this->writePaymentErrorLog("=====================psgagent checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $params);
			return false;
		}

        if ($params['orderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================psgagent checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $params);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($params)) {
            $this->writePaymentErrorLog('=====================psgagent checkCallbackOrder verify signature Error', $params);
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
            array('label' => '工商银行', 'value' => '1027'),
            array('label' => '农业银行', 'value' => '1002'),
            array('label' => '中国银行', 'value' => '1003'),
            array('label' => '建设银行', 'value' => '1004'),
            array('label' => '交通银行', 'value' => '1005'),
            array('label' => '中信银行', 'value' => '1007'),
            array('label' => '光大银行', 'value' => '1008'),
            array('label' => '华夏银行', 'value' => '1009'),
            array('label' => '民生银行', 'value' => '1010'),
            array('label' => '广发银行', 'value' => '1017'),
            array('label' => '平安银行', 'value' => '1011'),
            array('label' => '招商银行', 'value' => '1012'),
            array('label' => '兴业银行', 'value' => '1013'),
            array('label' => '北京银行', 'value' => '1016'),
            array('label' => '上海银行', 'value' => '1025'),
            array('label' => '邮政储蓄银行', 'value' => '1006'),
            array('label' => '北京农村商业银行', 'value' => '1103'),
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
		return number_format($amount*100, 0, '.', '');
	}

    public function encrypt($input,$key,$iv,$base64=true){
		$size = 8;
		$input = $this->pkcs5_pad($input,$size);
		$encryption_descriptor = @mcrypt_module_open(MCRYPT_3DES,'','cbc','');
		@mcrypt_generic_init($encryption_descriptor, substr($key,0,24), $iv);//这里截取KEY前24位，可直接$key
		$data = @mcrypt_generic($encryption_descriptor,$input);
		@mcrypt_generic_deinit($encryption_descriptor);
		@mcrypt_module_close($encryption_descriptor);
		return base64_encode($data);
	}

	private function pkcs5_pad($text,$blocksize){
		$pad = $blocksize-(strlen($text)%$blocksize);
		return $text.str_repeat(chr($pad),$pad);
	}

	private function decrypt($crypt,$key,$iv,$base64 = true) {
		$crypt = base64_decode($crypt);
		$encryption_descriptor = @mcrypt_module_open(MCRYPT_3DES, '', 'cbc', '');
		@mcrypt_generic_init($encryption_descriptor, substr($key,0,24), $iv);
		$decrypted_data = @mdecrypt_generic($encryption_descriptor, $crypt);
		@mcrypt_generic_deinit($encryption_descriptor);
		@mcrypt_module_close($encryption_descriptor);
		$decrypted_data = $this->pkcs5_unpad($decrypted_data);
		return rtrim($decrypted_data);
	}
	private function pkcs5_unpad($text){
		$pad = ord($text[strlen($text)-1]);
		if ($pad > strlen($text)) return false;
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
		return false;
		return substr($text, 0, -1 * $pad);
	}

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
		$signStr = $this->createSignStr($params);
        $sign = md5($signStr);
		return $sign;
	}

    public function verifySignature($data) {
	    $callback_sign = $data['signature'];
        $signStr = $this->createSignStr($data);
        $sign = md5($signStr);
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }

    private function createSignStr($params) {
    	ksort($params);
       	$signStr='';
		foreach ($params as $key => $value) {
			if(is_null($value) || $key == 'signature' || $key == ''){
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		$signStr .= $this->getSystemInfo('key');
		return $signStr;
	}
}
