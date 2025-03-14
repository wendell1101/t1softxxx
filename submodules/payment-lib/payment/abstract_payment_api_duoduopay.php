<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * Payment API implementation template
 */
abstract class Abstract_payment_api_duoduopay extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'success|9999';
	const RETURN_FAILED_CODE = 'FAILED';
	private $info;
	public function __construct($params = null) {
		parent::__construct($params);
		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->info = $this->getInfoByEnv();
	}

	protected abstract function configParams(&$params, $direct_pay_extra_info);



	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        // For second url redirection
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		/*
		MerId        String(10)  商户号
		OrdId        String(64)  商户网站唯一订单号
		OrdAmt       Number      订单金额，格式: 1.00
		PayType      String(4)   支付类型，默认DT
		CurCode      String(3)   支付币种，默认CNY
		BankCode     String(7)   银行代码，参考附录银行代码
		ProductInfo  String(100) 物品信息，可以随机填写
		Remark       String(255) 备注信息，可以随机填写
		ReturnURL    String(255) 前端页面返回地址
		NotifyURL    String(255) 后台异步通知
		SignType     String(5)   签名方式，默认MD5
		SignInfo     String(255) 签名数据
		*/

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$direct_pay_extra_info = $order->direct_pay_extra_info;
        $sysinfo = $this->getAllSystemInfo();
		$jsonEI =  $sysinfo['extra_info'];
		$jsonAry = json_decode($jsonEI,true);
		$merchantId = $jsonAry['merchantId'];
		$params['MerId'] = $merchantId;
		$params['OrdId'] = $order->secure_id;
		$params['OrdAmt'] =$this->convertAmountToCurrency($amount);
		$params['PayType'] = 'DT';
		$params['CurCode'] =  'CNY';
		$params['ProductInfo'] ='pay.deposit';
		$params['Remark'] ='pay.deposit';
		$params['ReturnURL'] =$this->getReturnUrl($orderId);;
		$params['NotifyURL'] = $this->getNotifyUrl($orderId);;
		$params['SignType'] ='MD5';

		$this->configParams($params, $order->direct_pay_extra_info);

		# sign param
		$params['SignInfo'] = $this->sign($params);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->info['url'],
			'params' => $params,
			'post' => true,
		);
	}
	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	## Reference: sample code, callback.php
	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}
	## This will be called when user redirects back to our page from payment API
	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}
	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;
		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			return $result;
		}
		# Update order payment status and balance
		$this->CI->sale_order->startTrans();
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
                ((isset($params['OrdNo'])) ? $params['OrdNo'] : ''), '', # only platform order id exist. Reference: documentation section 2.4.2
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}
		$success = $this->CI->sale_order->endTransWithSucc();
		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
		}
		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}
		return $result;
	}

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array(
			'MerId', 'OrdId', 'OrdAmt', 'OrdNo', 'ResultCode',
			'Remark', 'SignInfo',
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}
		# is signature authentic?
		if (!$this->verify($fields, $fields['SignInfo'])) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}
		$processed = true; # processed is set to true once the signature verification pass
		# check parameter values: orderStatus, tradeAmt, orderNo, merchNo
		# is payment successful?
		if ($fields['ResultCode'] !== 'success002') {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}
		# does amount match?
		if (
			$this->convertAmountToCurrency($order->amount) !==
			$this->convertAmountToCurrency(floatval($fields['OrdAmt']))
		) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}
		# does merchNo match?
		$sysinfo = $this->getAllSystemInfo();
		$jsonEI =  $sysinfo['extra_info'];
		$jsonAry = json_decode($jsonEI,true);
		$merchantId = $jsonAry['merchantId'];
		if ($fields['MerId'] !==$merchantId) {
			$this->writePaymentErrorLog("Merchant codes do not match, expected [" . $merchantId . "]", $fields);
			return false;
		}
		# does order_no match?
		if ($fields['OrdId'] !== $order->secure_id) {
			$this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}
		# everything checked ok
		return true;
	}
	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}
	## Format the amount value for the API
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}
	# -- private helper functions --
	/**
	 * @name	生成签名
	 * @param	sourceData
	 * @return	签名数据
	 * @sample  md5(MerId=xxx&OrdId=xxx&OrdAmt=xxx&PayType=DT&CurCode=CNY&BankCode=xxx&ProductInfo= xxx&Remark=xxx&ReturnURL=xxx&NotifyURL=xxx&SignType=MD5&MerKey=xxx)
	 */
	public function sign($data) {

		$info= $this->getAllSystemInfo();
		$key = $info['live_key'];
        $str="";
		$str.='MerId='.$data['MerId']."&";
		$str.='OrdId='.$data['OrdId']."&";
		$str.='OrdAmt='.$data['OrdAmt']."&";
		$str.='PayType='.$data['PayType']."&";
		$str.='CurCode='.$data['CurCode']."&";
		$str.='BankCode='.$data['BankCode']."&";
		$str.='ProductInfo='.$data['ProductInfo']."&";
		$str.='Remark='.$data['Remark']."&";
		$str.='ReturnURL='.$data['ReturnURL']."&";
		$str.='NotifyURL='.$data['NotifyURL']."&";
		$str.='SignType='.$data['SignType']."&";
		$str.='MerKey='.$key;

        // do some calculation to get signature
		$signature =  md5($str);

		return $signature;
	}

	public function callBackSign($data) {

		$info= $this->getAllSystemInfo();
		$key = $info['live_key'];

        $str="";
		$str.='MerId='.$data['MerId']."&";
		$str.='OrdId='.$data['OrdId']."&";
		$str.='OrdAmt='.$data['OrdAmt']."&";
		$str.='OrdNo='.$data['OrdNo']."&";
		$str.='ResultCode='.$data['ResultCode']."&";
		$str.='Remark='.$data['Remark']."&";
		$str.='SignType='.$data['SignType'];
		$SignInfo=md5( md5($str).$key);
		return $SignInfo;
	}
	/*
		 * @name	验证签名
		 * @param	data 原数据
		 * @param	signature 签名数据
		 * @return
	*/
	private function verify($data, $signature) {
		$mySign = $this->callBackSign($data);
		if (strcasecmp($mySign, $signature) === 0) {
			return true;
		} else {
			return false;
		}
	}
}