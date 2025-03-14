<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * YANGPAY 洋洋支付
 * Ref. to Abstract_payment_api_gheepay.php
 *
 * YANGPAY_UNIONPAY_PAYMENT_API, ID: 5447
 * YANGPAY_ALIPAY_PAYMENT_API, ID: 5448
 * YANGPAY_ALIPAY_H5_PAYMENT_API, ID: 5484
 * YANGPAY_WEIXIN_QR_PAYMENT_API, ID: 5485
 * YANGPAY_WEIXIN_PAYMENT_API, ID: 5486
 *
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_yangpay extends Abstract_payment_api {


    // Patch for OGP-12959 [Payment] 喜洋洋支付 deposit 支付宝扫码/WAP 微信扫码/WAP 银联扫码

	const RETURN_SUCCESS_CODE = 'OK';
	const RETURN_FAIL_CODE = 'NG';
	const P_ERRORCODE_SUCCESS = '00';

	public function __construct($params = null) {

        parent::__construct($params);
		$this->selfExtraInfoDefault();
    }

    // The function RECOMMENDED define in Payment_api_gheepay_XXXX class.
    // public function getPlatformCode() {
	// 	return TEMPLATE_ALIPAY_PAYMENT_API;
    // }

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	# -- override common API functions --
	## Constructs an URL so that the caller can redirect / invoke it to make payment through this API
	## See controllers/redirect.php for detail.
	##
	## Retuns a hash containing these fields:
	## array(
	##	'success' => true,
	##	'type' => self::REDIRECT_TYPE_FORM,  ## constants defined in abstract_payment_api.php
	##	'url' => $info['url'],
	##	'params' => $params,
	##	'post' => true
	## );
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderid);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_FORM, 'url' => $url );
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params = array();
		$params['pay_memberid']    = $this->getSystemInfo("account");
		$params['pay_orderid']     = $order->secure_id;
		$params['pay_ordeid']     = $order->secure_id;
		$params['pay_applydate']   = $orderDateTime->format('Y-m-d H:i:s');
		// $params['pay_bankcode']    = $this->getBankCode();
		$params['pay_notifyurl']   = $this->getNotifyUrl($orderId);
		$params['pay_callbackurl'] = $this->getReturnUrl($orderId);
		$params['pay_amount']      = $this->convertAmountToCurrency($amount);
		$this->configParams($params, $order->direct_pay_extra_info);
		$sign = $this->sign($params);
		$params['pay_md5sign']     = $sign;

		ksort($params);
        reset($params);

		$params['pay_md5sign'] = $sign;
		$params['pay_productname'] = $this->getSystemInfo("pay_productname");
		$this->CI->utils->debug_log("=====================". self::getPayStrFromClassName( get_class($this) ). " generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}
	/**
	 * 由 processPaymentUrlForm() 呼叫的
	 */
	protected function processPaymentUrlFormPost($params) {
		// $queryString = http_build_query($params);
		// $postUrl = $this->getSystemInfo('url').'?'.$queryString;
		if($this->getSystemInfo("url")){
			$postUrl = $this->getSystemInfo('url');
		}


		$this->CI->utils->debug_log("=====================". self::getPayStrFromClassName( get_class($this) ). " postUrl", $postUrl);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM, // self::REDIRECT_TYPE_URL,
			'url' => $postUrl,
			'params' => $params,
			'post' => true
		);
	}

	# Display QRCode get from curl
	// protected function processPaymentUrlFormQRCode($params) {
	// 	$this->CI->utils->debug_log('=====================". self::getPayStrFromClassName( get_class($this) ). " scan url', $this->getSystemInfo('url'));
	// 	$response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['orderid']);
	// 	$response = json_decode($response, true);

	// 	$this->CI->utils->debug_log('=====================". self::getPayStrFromClassName( get_class($this) ). " response', $response);

	// 	$msg = lang('Invalidte API response');

	// 	if($response['errcode'] == '0') {
	// 		return array(
	// 			'success' => true,
	// 			'type' => self::REDIRECT_TYPE_QRCODE,
	// 			'url' => $response['scanurl']
	// 		);
	// 	}
	// 	else {
	// 		if($response['errmsg']) {
	// 			$msg = $response['errmsg'];
	// 		}

	// 		return array(
	// 			'success' => false,
	// 			'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
	// 			'message' => $msg
	// 		);
	// 	}
	// }

	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	## Reference: sample code, callback.php
	public function callbackFromServer($orderid, $params) {
		$response_result_id = parent::callbackFromServer($orderid, $params);
		return $this->callbackFrom('server', $orderid, $params, $response_result_id);
	}

	## This will be called when user redirects back to our page from payment API
	public function callbackFromBrowser($orderid, $params) {
		$response_result_id = parent::callbackFromBrowser($orderid, $params);
		return $this->callbackFrom('browser', $orderid, $params, $response_result_id);
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderid, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderid);
		$processed = false;

		$raw_post_data = file_get_contents('php://input', 'r');
		$this->CI->utils->debug_log("=====================". self::getPayStrFromClassName( get_class($this) ). " raw_post_data", $raw_post_data);
		$this->CI->utils->debug_log("=====================". self::getPayStrFromClassName( get_class($this) ). " params", $params);
		// "memberid":"10142",
		// "orderid":"D767462743891",
		// "transaction_id":"20190711101514501009",
		// "amount":"100.0000",
		// "datetime":"20190711101617",
		// "returncode":"00",
		// "sign":"249182ADA129157A17878844BB5C8B72",
		// "attach":""

		$checkCallbackOrder =  $this->checkCallbackOrder($order, $params, $processed);

$this->CI->utils->debug_log('$processed:',$processed);
$this->CI->utils->debug_log('$order:',$order);
$this->CI->utils->debug_log('$checkCallbackOrder:',$checkCallbackOrder);

		if (!$order || !$checkCallbackOrder) {
			$result['message'] = lang('error.payment.failed'). '('. __LINE__. ')';
			return $result;
		}

		# Update order payment status and balance
		$success = true;

		# Update player balance based on order status
		# if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
		$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderid);
		if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
			$this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
			if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
				$this->CI->sale_order->setStatusToSettled($orderid);
			}
		} else {
			# update player balance
			$this->CI->sale_order->updateExternalInfo($order->id,
				'', '', # no info available
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
$this->CI->utils->debug_log('$success:');
$this->CI->utils->debug_log($success);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($processed) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['message'] = self::RETURN_FAIL_CODE ;
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}
$this->CI->utils->debug_log('callbackFrom.$result:');
$this->CI->utils->debug_log($result);
		return $result;
	} // EOF callbackFrom

	/**
	 * Validates whether the callback from API contains valid info and matches with the order
	 * Reference: code sample, callback.php
	 * @param object $order the return of sale_order->getSaleOrderById().
	 * @param array $fields YANGPAY NOT support the fields. Get data from php://input .
	 */
	private function checkCallbackOrder($order, $fields, &$processed = false) {




		// $requiredFields = array(
		// 	 'orderid','opstate','ovalue'
		// );
		//
		// foreach ($requiredFields as $f) {
		// 	if (!array_key_exists($f, $fields)) {
		// 		$this->writePaymentErrorLog("=====================". self::getPayStrFromClassName( get_class($this) ). " checkCallbackOrder missing parameter: [$f]", $fields);
		// 		return false;
		// 	}
		// }

		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('====================='. self::getPayStrFromClassName( get_class($this) ). ' checkCallbackOrder signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['returncode'] != self::P_ERRORCODE_SUCCESS) {
			$this->writePaymentErrorLog('====================='. self::getPayStrFromClassName( get_class($this) ). ' checkCallbackOrder payment was not successful', $fields);
			return false;
		}

		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['amount']))
		) {
			$this->writePaymentErrorLog("=====================". self::getPayStrFromClassName( get_class($this) ). ' checkCallbackOrder payment amounts do not match, expected [$order->amount]', $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- Private functions --
	# After payment is complete, the gateway will invoke this URL asynchronously
	private function getNotifyUrl($orderid) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderid);
	}

	## After payment is complete, the gateway will send redirect back to this URL
	private function getReturnUrl($orderid) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderid);
	}

	## Format the amount value for the API
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- signatures --
	//private function getCustormId($playerId, $parter) {
	//	return $playerId.'_'.md5($parter.'|'.$this->getSystemInfo('key').'|'.$playerId);
	//}

    private function assemble($arr){
        $str = '';
        foreach($arr as $key=>$val){
            $str .= $key."=".$val."&";
        }
        $str .= "key=" .$this->getSystemInfo("key");
        // $str .= $this->getSystemInfo("key");
        return $str ;
    }

	private function sign($params) {
		$class_name =get_class($this);

        $data= array(
			'pay_memberid'  	=> $params['pay_memberid'] ,
            'pay_bankcode'  	=> $params['pay_bankcode'] ,
            'pay_amount'  		=> $params['pay_amount'] ,
            'pay_orderid'  		=> $params['pay_orderid'] ,
            'pay_applydate' 	=> $params['pay_applydate'] ,
            'pay_callbackurl'  	=> $params['pay_callbackurl'],
            'pay_notifyurl' 	=> $params['pay_notifyurl']
		);
		ksort($data);
        reset($data);

    	$str = $this->assemble($data);
		//加密
        $sign = strtoupper(md5($str));
        // $sign = strtolower(md5($str));
	
		return $sign;
	}

	/**
	 * 驗證第三方支付商回調資料無篡改。
	 * @param string $params 第三方支付商回調的資料，會依照支付商不同而有所不同。
	 * 此案為 params array。
	 * @return boolean 若正確則 true，否則 false。
	 */
	private function validateSign($params) {
		// memberid
		// 商户编号
		// 是
		//
		// orderid
		// 订单号
		// 是
		//
		// amount
		// 订单金额
		// 是
		//
		// transaction_id
		// 交易流水号
		// 是
		//
		// datetime
		// 交易时间
		// 是
		//
		// returncode
		// 交易状态
		// 是
		$keys = array('orderid', 'memberid', 'amount', 'transaction_id', 'datetime', 'returncode');

		// "memberid":"10142",
		// "orderid":"D767462743891",
		// "transaction_id":"20190711101514501009",
		// "amount":"100.0000",
		// "datetime":"20190711101617",
		// "returncode":"00",
		//
		// "sign":"249182ADA129157A17878844BB5C8B72",
		// "attach":""


		$params4Sign = array();
		foreach($keys as $key) {
			if (array_key_exists($key, $params)) {
				$params4Sign[$key] = $params[$key];
				// $signStr .= $key .'='.$params[$key].'&';
			}
		}
		$this->utils->debug_log('$params4Sign:',$params4Sign);
		ksort($params4Sign);
        reset($params4Sign);

    	$str = $this->assemble($params4Sign);
		//加密
        $sign = strtoupper(md5($str));
	
		return strcasecmp($sign, $params['sign']) === 0;
		}

	protected function getBankListInfoFallback() {
		return array(
			array('label' => '中信银行', 'value' => '962'),
			array('label' => '中国银行', 'value' => '963'),
			array('label' => '中国农业银行', 'value' => '964'),
			array('label' => '中国建设银行', 'value' => '965'),
			array('label' => '中国工商银行', 'value' => '967'),
			array('label' => '浙商银行', 'value' => '968'),
			array('label' => '浙江稠州商业银行', 'value' => '969'),
			array('label' => '招商银行', 'value' => '970'),
			array('label' => '邮政储蓄', 'value' => '971'),
			array('label' => '兴业银行', 'value' => '972'),
			array('label' => '顺德农村信用合作社', 'value' => '973'),
			array('label' => '深圳发展银行', 'value' => '974'),
			array('label' => '上海银行', 'value' => '975'),
			array('label' => '上海农村商业银行', 'value' => '976'),
			array('label' => '浦东发展银行', 'value' => '977'),
			array('label' => '平安银行', 'value' => '978'),
			array('label' => '南京银行', 'value' => '979'),
			array('label' => '民生银行', 'value' => '980'),
			array('label' => '交通银行', 'value' => '981'),
			array('label' => '华夏银行', 'value' => '982'),
			array('label' => '杭州银行', 'value' => '983'),
			array('label' => '广州市农村信用社|广州市商业银行', 'value' => '984'),
			array('label' => '广东发展银行', 'value' => '985'),
			array('label' => '光大银行', 'value' => '986'),
			array('label' => '东亚银行', 'value' => '987'),
			array('label' => '渤海银行', 'value' => '988'),
            array('label' => '北京银行', 'value' => '989'),
            array('label' => '北京农村商业银行', 'value' => '990')
		);
    }

	/*

	██╗  ██╗███████╗██╗     ██████╗ ███████╗██████╗     ██╗   ██╗████████╗██╗██╗     ███████╗
	██║  ██║██╔════╝██║     ██╔══██╗██╔════╝██╔══██╗    ██║   ██║╚══██╔══╝██║██║     ██╔════╝
	███████║█████╗  ██║     ██████╔╝█████╗  ██████╔╝    ██║   ██║   ██║   ██║██║     ███████╗
	██╔══██║██╔══╝  ██║     ██╔═══╝ ██╔══╝  ██╔══██╗    ██║   ██║   ██║   ██║██║     ╚════██║
	██║  ██║███████╗███████╗██║     ███████╗██║  ██║    ╚██████╔╝   ██║   ██║███████╗███████║
	╚═╝  ╚═╝╚══════╝╚══════╝╚═╝     ╚══════╝╚═╝  ╚═╝     ╚═════╝    ╚═╝   ╚═╝╚══════╝╚══════╝

	Helper Utils
	*/

	protected function setupPayAndBillingStr($class_name){
		$this->payStr = self::getPayStrFromClassName( $class_name );
		$this->payUpperStr = strtoupper( $this->payStr );
		$this->billingStr = self::getBillingStrFromClassName( $class_name );
		$this->billingUpperStr = strtoupper( $this->billingStr );
	}

	/**
	 * 此物件自帶的 Extra Info 用來設定成預設值
	 *
	 */
	protected function selfExtraInfoDefault(){

		$ExtraInfoDefault = array();
		$ExtraInfoDefault['callback_host'] = '';
		$ExtraInfoDefault['pay_productname'] = 'Deposit';
		/**
		 * pay_bankcode_list
		 * deposit 用，渠道代碼
		 */
        $ExtraInfoDefault['pay_bankcode_list'] = array();
		$ExtraInfoDefault['pay_bankcode_list']['ALIPAY_QR'] = '903'; //支付宝扫码 #1
		$ExtraInfoDefault['pay_bankcode_list']['ALIPAY_H5'] = '904'; //支付宝WAP #2
		$ExtraInfoDefault['pay_bankcode_list']['WEIXIN_QR'] = '902'; //微信扫码 #3
		$ExtraInfoDefault['pay_bankcode_list']['WEIXIN_H5'] = '928'; //微信WAP #4
		$ExtraInfoDefault['pay_bankcode_list']['UNIONPAY'] = '926'; //银联扫码 #5
		$ExtraInfoDefault['pay_bankcode_list']['QUICKPAY'] = '911'; //银联快捷 #6
		$ExtraInfoDefault['pay_bankcode_list']['ALIPAY'] = '930'; //原生支付宝 #7
		$ExtraInfoDefault['pay_bankcode_list']['WEIXIN'] = '929'; //原声微信 #8
		if( empty($this->pay_bankcode_list) ){
			$this->pay_bankcode_list = $ExtraInfoDefault['pay_bankcode_list'];
		}
		$this->pay_bankcode_list = $this->getSystemInfo("pay_bankcode_list", $ExtraInfoDefault['pay_bankcode_list']);

		/**
		 * action_uri_list
		 * deposit 用，渠道網關
		 */
		$ExtraInfoDefault['action_uri_list'] = array();
		$ExtraInfoDefault['action_uri_list']['ALIPAY_QR'] = 'http://wx.sachiko.cn/Pay_Index.html#NotYetSupport'; //支付宝扫码 903
		$ExtraInfoDefault['action_uri_list']['ALIPAY_H5'] = 'http://zfbh5.sachiko.cn/Pay_Index.html'; //支付宝WAP 904
		$ExtraInfoDefault['action_uri_list']['WEIXIN_QR'] = 'http://wx.sachiko.cn/Pay_Index.html'; //微信扫码
		$ExtraInfoDefault['action_uri_list']['WEIXIN_H5'] = 'http://wx.sachiko.cn/Pay_Index.html#NotYetSupport'; //微信WAP 928
		$ExtraInfoDefault['action_uri_list']['UNIONPAY'] = 'http://yl.sachiko.cn/Pay_Index.html'; //银联扫码
		$ExtraInfoDefault['action_uri_list']['QUICKPAY'] = 'http://wx.sachiko.cn/Pay_Index.html#NotYetSupport'; //银联快捷 911
		$ExtraInfoDefault['action_uri_list']['ALIPAY'] = 'http://yszfb.sachiko.cn/Pay_Index.html'; //原生支付宝
		$ExtraInfoDefault['action_uri_list']['WEIXIN'] = 'http://yswx.sachiko.cn/Pay_Index.html'; //原声微信
		$this->action_uri_list = $this->getSystemInfo("action_uri_list", $ExtraInfoDefault['action_uri_list']);
		if( empty($this->action_uri_list) ){
			$this->action_uri_list = $ExtraInfoDefault['action_uri_list'];
		}


		// /**
		//  * action_uri_list
		//  * withdrawal 用，銀行代碼
		//  */
		// $ExtraInfoDefault['withdrawal_bank_list'] = array();
		//
		// $bankInfo = array();
		// $bankInfo['bankTypeId'] = '1';
		// $bankInfo['name'] = '工商银行';
		// $bankInfo['bankId'] = '33'; // for yftPay
		// $bankInfo['bankStr'] = 'ICBC';
		// $ExtraInfoDefault['withdrawal_bank_list'][] = $bankInfo;
		//
		// $bankInfo = array();
		// $bankInfo['bankTypeId'] = '2';
		// $bankInfo['name'] = '招商银行';
		// $bankInfo['bankId'] = '8'; // for yftPay
		// $bankInfo['bankStr'] = 'CMB';
		// $ExtraInfoDefault['withdrawal_bank_list'][] = $bankInfo;
		//
		// $bankInfo = array();
		// $bankInfo['bankTypeId'] = '3';
		// $bankInfo['name'] = '建设银行';
		// $bankInfo['bankId'] = '42'; // for yftPay
		// $bankInfo['bankStr'] = 'CCB';
		// $ExtraInfoDefault['withdrawal_bank_list'][] = $bankInfo;
		//
		// $bankInfo = array();
		// $bankInfo['bankTypeId'] = '4';
		// $bankInfo['name'] = '农业银行';
		// $bankInfo['bankId'] = '47'; // for yftPay
		// $bankInfo['bankStr'] = 'ABC';
		// $ExtraInfoDefault['withdrawal_bank_list'][] = $bankInfo;
		//
		// $bankInfo = array();
		// $bankInfo['bankTypeId'] = '5';
		// $bankInfo['name'] = '交通银行';
		// $bankInfo['bankId'] = '43'; // for yftPay
		// $bankInfo['bankStr'] = 'BCOM'; // COMM
		// $ExtraInfoDefault['withdrawal_bank_list'][] = $bankInfo;

		return $ExtraInfoDefault;
	}

	/**
	 * Get extra_info json string
	 * @return string json srting for <extra_info> of xml.
	 */
	public function getExtraInfoStr(){

		$selfExtraInfoDefault = $this->selfExtraInfoDefault();
		$extra_infoStr = json_encode($selfExtraInfoDefault, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
		return $extra_infoStr;
	} // EOF getExtraInfoStr


    /**
	 * 取得金流商，銀行/渠道代號字串
	 * 依照正規表示式取特定位置的金流商字串。
	 * 參考 https://regex101.com/r/dQ2aaJ/1/
	 *
	 * 支援「Payment_api_yangpay_weixin_qr」輸入，
	 *
	 *
	 * @todo move to helper.
	 * @param string $className 物件的名稱
	 * @return string 金流商字串
	 */
	static function getPayStrFromClassName($classNameStr = ''){
		$re = '/Abstract_payment_api_(?P<pay_name>.+)/'; // Ref. to https://regex101.com/r/dQ2aaJ/1/
		// $classNameStr = '// Abstract_payment_api_yangpay
		// // Payment_api_yangpay_weixin
		// // Payment_api_yangpay_weixin_qr';

		if( is_string($classNameStr ) ){
			preg_match($re, $classNameStr, $matches);
		}else{
			// disable for static
			// $this->CI->utils->debug_log("=====================getPayStrFromClassName.func_get_args", func_get_args());
			$matches = array();
		}


		// display the Warning while not found.
		// Severity: Warning  --> preg_match() expects parameter 2 to be string, object given /home/vagrant/Code/og/submodules/payment-lib/payment/abstract_payment_api_yangpay.php 478

		// Print the entire match result
		$return = '';

		if( ! empty($matches) && $matches['pay_name'] ){
			$return = $matches['pay_name'];
		}else{
			// bsfcn =  getBillingStrFromClassName
			$matches4bsfcn = self::getBillingStrFromClassName($classNameStr, true);
			// disable for static
			// $this->CI->utils->debug_log("=====================getBillingStrFromClassName.matches4bsfcn", $matches4bsfcn);
			if( ! empty($matches4bsfcn) && $matches4bsfcn['pay_name'] ){
				$return = $matches4bsfcn['pay_name'];
			}
		}
		// disable for static
		// $this->CI->utils->debug_log("=====================getPayStrFromClassName.return", $return);
		return $return;
	} // EOF getPayStrFromClassName

	/**
	 * 取得金流商代號字串
	 * 依照正規表示式取特定位置的渠道字串。
	 * 參考： https://regex101.com/r/dQ2aaJ/2/
	 *
	 * @todo move to helper.
	 * @param string $className 物件的名稱
	 * @return string|array  銀行/渠道代號字串
	 */
	static function getBillingStrFromClassName($classNameStr = '',$getMatches = false){
		$re = '/Payment_api_(?P<pay_name>[^_]+)_(?P<billing_name>.*)/';

		if( is_string($classNameStr ) ){
			preg_match($re, $classNameStr, $matches);
		}else{
			$matches = array();
			// disable for static
			// $this->CI->utils->debug_log("=====================getBillingStrFromClassName.func_get_args", func_get_args());
		}

		if($getMatches){
			$return = $matches;
		}else{
			// Print the entire match result
			$return = $matches['billing_name'];
		}

		// disable for static
		// $this->CI->utils->debug_log("=====================getBillingStrFromClassName.return", $return);
		return $return;
	} // EOF getBillingStrFromClassName

	/**
	 * Add CData into Node of xml
	 *
	 * Scaffolding Utils
	 *
	 * @param string $cdata_text The content of Node.
	 * @param string $nodeName The node name, ex: "my_name" eq. <my_name>.
	 * @param SimpleXMLElement &$xml A SimpleXMLElement object
	 * @return void The param, $xml will add a CData of Node.
	 */
	static function addCData2xmlNode($cdata_text, $nodeName, &$xml) {
		$xml->$nodeName = NULL; // VERY IMPORTANT! We need a node where to append
		$node = dom_import_simplexml($xml->$nodeName);
		$no   = $node->ownerDocument;
		$node->appendChild($no->createCDATASection($cdata_text));
	}

}
