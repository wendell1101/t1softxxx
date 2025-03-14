<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * DFYUNPAY 雲飛支付
 *
 * * DFYUNPAY_PAYMENT_API, ID: 500
 * * DFYUNPAY_BANK_WAP_PAYMENT_API, ID: 501
 * * DFYUNPAY_WEIXIN_PAYMENT_API, ID: 502
 * * DFYUNPAY_ALIPAY_PAYMENT_API, ID: 503
 * * DFYUNPAY_JDPAY_PAYMENT_API, ID: 504
 *
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
 * @copyright 2017-2022 tot
 */

abstract class Abstract_payment_api_dfyunpay extends Abstract_payment_api {
	//回傳結果
	const RETURNCODE = '00';
	//通道
	const TONGDAO_BANK = 'Gopay';
	const TONGDAO_BANK_WAP = 'Gopaywap';
	const TONGDAO_WEIXIN = 'WxSm';
	const TONGDAO_ALIPAY = 'DFYzfb';
	const TONGDAO_ALIPAY_WAP = 'ZfbWap';
	const TONGDAO_JDPAY = 'JdPay';
	//銀行編碼
	const PAYTYPE_ALIPAY = 'ALIPAY';
	const PAYTYPE_WEIXIN = 'WXZF';
	const PAYTYPE_ALIPAY_WAP = 'ALIPAY';
    const PAYTYPE_JDPAY = 'JDZF';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	/**
	 * detail: Constructs an URL so that the caller can redirect / invoke it to make payment through this API, See controllers/redirect.php for detail.
	 *
	 * @param int $orderId order id
	 * @param int $playerId player id
	 * @param float $amount amount
	 * @param string $orderDateTime
	 * @param int $playerPromoId
	 * @param string $enabledSecondUrl
	 * @param int $bankId
	 * @return array
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params['pay_memberid'] = $this->getSystemInfo("account");
		$params['pay_orderid'] = $order->secure_id;
		$params['pay_amount'] = $this->convertAmountToCurrency($amount);
		$params['pay_applydate'] = date("Y-m-d H:i:s");
		$params['pay_bankcode'] = '' ;
		$params["tongdao"] = '';
		$params['pay_notifyurl'] = $this->getNotifyUrl($orderId);
		$params['pay_callbackurl']=$this->getReturnUrl($orderId);

		$this->configParams($params, $order->direct_pay_extra_info);

		ksort($params);
        reset($params);

		$sign = $this->sign($params);
		$params['pay_md5sign'] = $sign;

		$this->CI->utils->debug_log("=====================dfyunpay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
		$url = $this->getSystemInfo('url');
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $url,
			'params' => $params,
			'post' => true
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
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;
		if($source == 'server'){
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
			$result['message'] = 'OK';
		} else {
			$result['return_error'] = $processed ? 'OK' : 'fail';
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
			'memberid' ,
			'orderid',
			'amount',
			'datetime',
			'returncode',
			'reserved1',
			'reserved2',
			'reserved3',
			'sign'
		);

		ksort($ReturnArray);
        reset($ReturnArray);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================dfyunpay missing parameter: [$f]", $fields);
				return false;
			}
		}

		$callbackSign = $this->createParamStr($fields);

		# is signature authentic?
		if ($fields['sign'] != $callbackSign) {
			$this->writePaymentErrorLog("=====================dfyunpay check callback sign error, signature is [$callbackSign], match? ", $fields);
			return false;
		}

		if ($fields['returncode'] != self::RETURNCODE) {
			$payStatus = $fields['returncode'];
			$this->writePaymentErrorLog("=====================dfyunpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['amount'] ) ) {
			$this->writePaymentErrorLog("=====================dfyunpay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
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

	public function getBankListInfoFallback() {
		return array(
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '工商银行', 'value' => 'ICBC'),
			array('label' => '建设银行', 'value' => 'CCB'),
			array('label' => '浦发银行', 'value' => 'SPDB'),
			array('label' => '农业银行', 'value' => 'ABC'),
			array('label' => '民生银行', 'value' => 'CMBC'),
			array('label' => '深圳发展银行', 'value' => 'SDB'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '交通银行', 'value' => 'BOCOM'),
			array('label' => '光大银行', 'value' => 'CEB'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '平安银行', 'value' => 'PAB'),
			array('label' => '广发银行', 'value' => 'GDB'),
			array('label' => '中信银行', 'value' => 'CITIC'),
			array('label' => '宁波银行', 'value' => 'NBCB'),
			array('label' => '华夏银行', 'value' => 'HXBC'),
			array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
			array('label' => '北京银行', 'value' => 'BOBJ'),
			array('label' => '上海银行', 'value' => 'BOS'),
			array('label' => '南京银行', 'value' => 'NJCB'),
			array('label' => '双乾网银', 'value' => 'ShuangQian')
		);
	}

	private function sign($params){
		$data= array(
			'pay_memberid'  	=> $params['pay_memberid'] ,
			'pay_orderid'  		=> $params['pay_orderid'] ,
			'pay_amount'  		=> $params['pay_amount'] ,
			'pay_applydate' 	=> $params['pay_applydate'] ,
			'pay_bankcode'  	=> $params['pay_bankcode'] ,
			'pay_notifyurl' 	=> $params['pay_notifyurl'] ,
			'pay_callbackurl'  	=> $params['pay_callbackurl']
		);
		ksort($data);
        reset($data);

    	$str = $this->assemble($data);
		//加密
		$sign = strtoupper(md5($str));
		return $sign;
    }

	private function assemble($arr){
        $str = '';
        foreach($arr as $key=>$val){
            $str .= $key."=>".$val."&";
        }
		$str .= "key=" .$this->getSystemInfo("syspwd");
        return $str ;
    }

	public function createParamStr($params) {
		$data= array(
			'memberid' 	=> $params['memberid'],
			'orderid' 	=> $params['orderid'],
			'amount' 	=> $params['amount'],
			'datetime' 	=> $params['datetime'],
			'returncode'=> $params['returncode']
		);
	  	//数组进行组装
		ksort($data);
        reset($data);
      	//加密
		$str = '';
		$str  = $this->assemble($data); //进行组装
		$finalStr =  strtoupper(md5($str));
		return $finalStr;
	}

}
