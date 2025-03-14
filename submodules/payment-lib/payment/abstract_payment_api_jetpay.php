<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * JetPay 捷智付
 *
 * * JETPAY_PAYMENT_API, ID: 5029
 * * JETPAY_UNIONPAY_PAYMENT_API, ID: 5080
 * * JETPAY_QUICKPAY_PAYMENT_API, ID: 5081
 * * JETPAY_ALIPAY_PAYMENT_API, ID: 5193
 * * JETPAY_ALIPAY_H5_PAYMENT_API, ID: 5194
 * * JETPAY_QQPAY_PAYMENT_API, ID: 5195
 * * JETPAY_QQPAY_H5_PAYMENT_API, ID: 5196
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://39.98.88.140:8082/pp_server/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_jetpay extends Abstract_payment_api {

    const SCANTYPE_ONLINE_BANK = '17'; #个人网银支付
    const SCANTYPE_WEIXIN      = '12'; #微信支付
    const SCANTYPE_ALIPAY      = '14'; #支付宝支付
    const SCANTYPE_QQPAY       = '15'; #QQ钱包支付
    const SCANTYPE_UNIONPAY    = '20'; #网银扫码
    const SCANTYPE_QUICKPAY    = '21'; #网银H5支付(网银快捷支付)
    const TRANTYPE_QUICKPAY    = 'h5pay';

	const RETURN_SUCCESS_CODE = 'SUCCESS';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '00';
	const PAY_RESULT_SUCCESS = '00';

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
        $params['tranName']    = 'payment';
        $params['version']     = '1.00';
        $params['merCode']     = $this->getSystemInfo("account");
        $params['orderNo']     = $order->secure_id;
        $params['orderTime']   = date('Ymdhis');
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['reservedField2'] = $playerId;
        $params['amount']      = $this->convertAmountToCurrency($amount);
        $params['currency']    = $this->getSystemInfo("currency", 'CNY');
        $params['productName'] = 'Deposit';
        $params['notifyURL']   = $this->getNotifyUrl($orderId);
        $params['sign']        = $this->sign($params);
		$this->CI->utils->debug_log("=====================jetpay generatePaymentUrlForm", $params);

        $submit = array();
        $submit['tranType'] = $params['tranName'];
        $submit['param']    = $this->array2xml($params);
		return $this->processPaymentUrlForm($submit);
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
        $this->CI->utils->debug_log('=======================jetpay callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
        	$raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log('=======================jetpay callbackFromServer server callbackFrom', $params);
            $params = $this->xmlToarray($raw_post_data);
			$this->CI->utils->debug_log("=====================jetpay callbackFrom xmlToarray params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderNo'], null, null, null, $response_result_id);
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
        $requiredFields = array(
            'orderNo', 'ordAmt', 'paymentState', 'sign'
        );

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================jetpay missing parameter: [$f]", $fields);
				return false;
			}
		}

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================jetpay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

		if ($fields['paymentState'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['paymentState'];
			$this->writePaymentErrorLog("=====================jetpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($fields['ordAmt'] != $this->convertAmountToCurrency($order->amount)) {
			$this->writePaymentErrorLog("=====================jetpay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['orderNo'] != $order->secure_id) {
        $this->writePaymentErrorLog("========================jetpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
            array('label' => '中国工商银行', 'value' => 'ICBC'),
            array('label' => '中国农业银行', 'value' => 'ABC'),
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '中国建设银行', 'value' => 'CCB'),
            array('label' => '交通银行', 'value' => 'BOCOM'),
            array('label' => '中信银行', 'value' => 'ECITIC'),
            array('label' => '中国光大银行', 'value' => 'CEBB'),
            array('label' => '华夏银行', 'value' => 'CGB'),
            array('label' => '中国民生银行', 'value' => 'CMBC'),
            array('label' => '广发银行', 'value' => 'CGB'),
            array('label' => '平安银行', 'value' => 'PINGAN'),
            array('label' => '招商银行', 'value' => 'CMB'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '北京银行', 'value' => 'BJBANK'),
            array('label' => '渤海银行', 'value' => 'BHB'),
            array('label' => '上海银行', 'value' => 'SHBANK'),
            array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
            array('label' => '广州市商业银行', 'value' => 'GRCBANK')
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
		ksort($params);
       	$signStr = '';
		foreach ($params as $key => $value) {
			if(is_null($value) || empty($value) || $key == 'sign'){
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'key='.$this->getSystemInfo('key');
        $sign = md5($signStr);
		return strtoupper($sign);
	}

    public function verifySignature($params) {
		$sign = $this->sign($params);
		return strcasecmp($params['sign'], $sign) === 0;
    }

     #For XML
	public function array2xml($values){
		if (!is_array($values) || count($values) <= 0) {
		    return false;
		}
		$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?><payment>";
		foreach ($values as $key => $val) {
				$xml .= "<" . $key . ">" . $val . "</" . $key . ">";
		}
		$xml .= "</payment>";
		$this->CI->utils->debug_log(' =========================jetpay array2xml', $xml);
		return $xml;
	}

	//xml 转 数组
	public function xmlToarray($xml){
		if(!$xml) return;
        //将XML转为array
        $array = json_decode(json_encode(simplexml_load_string($xml)), true);
		return $array;
	}
}
