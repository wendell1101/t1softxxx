<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * YFTPAY YFT支付
 * Ref. to abstract_payment_api_yangpay.php
 *
 * YFTPAY_QUICKPAY_PAYMENT_API, ID: XXX
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: go.ypaygo.com/pay/bank_deposit.do
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_yftpay extends Abstract_payment_api {


    // Patch for OGP-13021
	// 	對於 銀行編碼 and apis.php：
	// 1. 云闪付 YFTPAY_UNIONPAY_PAYMENT_API
	// 2. 快捷支付 YFTPAY_QUICKPAY_PAYMENT_API

	const RETURN_SUCCESS_CODE = 'success'; // SDK: 接收成功后,请在返回的response的body写入一个小写的 success
	const RETURN_FAIL_CODE = 'fail';
	const P_ERRORCODE_SUCCESS = '200';

	const ACTION_URI_LIST = 'http://go.ypaygo.com/pay/order.do';

	public function __construct($params = null) {

        parent::__construct($params);


        $ExtraInfoDefault = array();
        $ExtraInfoDefault['pay_bankcode_list'] = array();
        $ExtraInfoDefault['pay_bankcode_list']['QUICKPAY'] = '8'; // 快捷支付
		$ExtraInfoDefault['pay_bankcode_list']['UNIONPAY'] = '9'; //云闪付

		$this->pay_bankcode_list = $this->getSystemInfo("pay_bankcode_list", $ExtraInfoDefault['pay_bankcode_list']);
		$ExtraInfoDefault['action_uri_list'] = array();
		$ExtraInfoDefault['action_uri_list']['UNIONPAY'] = self::ACTION_URI_LIST;
		$ExtraInfoDefault['action_uri_list']['QUICKPAY'] = self::ACTION_URI_LIST;
		$this->action_uri_list = $this->getSystemInfo("action_uri_list", $ExtraInfoDefault['action_uri_list']);
        // $allSystemInfo = $this->getAllSystemInfo();
        // $this->CI->utils->debug_log("=====================". self::getPayStrFromClassName( get_class($this) ). " allSystemInfo", $allSystemInfo);
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

		// $params = array();
		// $params['pay_memberid'] = $this->getSystemInfo("account");
		// $params['pay_orderid'] = $order->secure_id;
		// $params['pay_amount'] = $this->convertAmountToCurrency($amount);
        // $params['pay_applydate'] = date("Y-m-d H:i:s");
        // /// $params['pay_bankcode'] reference by configParams() of Payment_api_gheepay_XXX.php .
        // // $params['pay_bankcode'] = self::PAY_BANKCODE_WEIXIN ;
        // $params["pay_version"] = 'vb1.0';
		// $params['pay_notifyurl'] = $this->getNotifyUrl($orderId);
		// // $params['pay_callbackurl']=$this->getReturnUrl($orderId);
		// $this->configParams($params, $order->direct_pay_extra_info);

		$params = array();
		$params['access_code']    = $this->getSystemInfo("account");
		$params['order_number']     = $order->secure_id; // pay_orderid
		// $params['pay_bankcode']    = $this->getBankCode();
		// return_url :(必填)支付成功后,商户接收回调的地址
		$params['return_url']   = $this->getNotifyUrl($orderId);
		// direct_type:跳转方式 ,1 或者2 （必填），1为直接在跳转处理，2为将完整的请求的URL 提供给接入商.
		// $params['direct_type'] = 1; // QR Code 無法呈現
		$params['direct_type'] = 2; // 需要解析 resp. json :
		/*
		{
  "Status": 200,
  "Msg": "请求完成",
  "Data": {
    "imgUrl": "http://api.game-pay.tw/pay/sfpay/gopayurl?param=expay20190626093741614&type=1"
  }
}
		 */
		// source_url :(必填)网站地址，用于用户支付完成后，跳转到这个地址
		$params['source_url'] = $this->getReturnUrl($orderId);
		$params['amount']      = $this->convertAmountToCurrency($amount);
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['params']     = $this->sign($params);


		ksort($params);
        reset($params);

		$sign = $this->sign($params);
		// $params['pay_md5sign'] = $sign;
$allSystemInfo = $this->getAllSystemInfo();
$this->CI->utils->debug_log("=====================". self::getPayStrFromClassName( get_class($this) ). " allSystemInfo", $allSystemInfo);

		// cloned form abstract_payment_api_eboo.php, but Not work. $this->getSystemInfo("pay_productname") is empty.
        // if($this->getSystemInfo("pay_productname")){
        //     $params['pay_productname']  = $this->getSystemInfo("pay_productname");
        // }else{
		// 	$params['pay_productname']  = 'deposit';
		// }
		// $params['pay_productname'] ='deposit';
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
		/// TEST
		// $postUrl = 'http://go.ypaygo.com/access/info.do';
		// $postUrl = 'http://go.ypaygo.com/pay/order.do';

		$respJson = array();
		// direct_type:跳转方式 ,1 或者2 （必填），1为直接在跳转处理，2为将完整的请求的URL 提供给接入商.
		if($params['direct_type'] == '2'){
/*
	{
		"Status": 200,
		"Msg": "请求完成",
		"Data": {
			"imgUrl": "http://api.game-pay.tw/pay/sfpay/gopayurl?param=expay20190626094053688&type=1"
		}
	}
*/

			$respContent = $this->submitPostForm($postUrl, $params, false, $params['order_number']);

			$respJson = json_decode($respContent, true);
		}else{
			$respJson['Status'] = '';
		}

		$this->CI->utils->debug_log("=====================". self::getPayStrFromClassName( get_class($this) ). " postUrl", $postUrl);
		$this->CI->utils->debug_log('processPaymentUrlFormPost.respJson:',$respJson);

		// 呈現 QR Code 頁面。
		$return = array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $postUrl,
			'params' => $params,
			'post' => true
		);

		if($respJson['Status'] == '200' ){ // 直接轉入所提供的網址
			$return = array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_URL,
				'url' => $respJson['Data']['imgUrl'],
				'params' => $params,
				'post' => true
			);
		}

		return $return;
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
		// $this->CI->utils->debug_log("=====================". self::getPayStrFromClassName( get_class($this) ). " raw_post_data", $raw_post_data);
		// $this->CI->utils->debug_log("=====================". self::getPayStrFromClassName( get_class($this) ). " params", $params);

		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
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
			$this->CI->sale_order->updateExternalInfo( $order->id // #1
				, '' // #2
				, '' # no info available // #3
				, null // #4
				, null // #5
				, $response_result_id // #6
			);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($processed) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = self::RETURN_FAIL_CODE;
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	} // EOF callbackFrom

	## Validates whether the callback from API contains valid info and matches with the order
	## Reference: code sample, callback.php
	/**
	 * Validates whether the callback from API contains valid info and matches with the order
	 * Reference: code sample, callback.php
	 * @param object $order the return of sale_order->getSaleOrderById().
	 * @param array $fields YFTPAY NOT support the fields. Get data from php://input .
	 */
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		// $this->writePaymentErrorLog("checkCallbackOrder.fields:",$fields);
		$raw_post_data = file_get_contents('php://input', 'r');

		# is signature authentic?
		if (!$this->validateSign($raw_post_data)) {
			$this->writePaymentErrorLog('====================='. self::getPayStrFromClassName( get_class($this) ). ' checkCallbackOrder signature Error, input: ', $raw_post_data);
			return false;
		}

		// convert to Data
		$raw_post = trim($raw_post_data);
		$fields = json_decode($raw_post, true);

		$processed = true; # processed is set to true once the signature verification pass
		// Check result status ref. by SDK from 3-rd payment provider.
		if ($fields['Status'] != self::P_ERRORCODE_SUCCESS) {
			$this->writePaymentErrorLog('====================='. self::getPayStrFromClassName( get_class($this) ). ' checkCallbackOrder payment was not successful, input: ', $raw_post_data);
			return false;
		}

		// check same amount
		$orderAmount = $this->convertAmountToCurrency($order->amount);
		$orderAmountFromData = $this->convertAmountToCurrency(floatval($fields['Data']['amount']));
		if ( $orderAmount != $orderAmountFromData ) {
			$this->writePaymentErrorLog("=====================". self::getPayStrFromClassName( get_class($this) ). " checkCallbackOrder payment amounts do not match, expected order->amount:[$order->amount], [$orderAmount] != [$orderAmountFromData], input: ", $fields);
			return false;
		}

		// check same payment id.
		if ( $fields['Data']['web_ordernumber'] != $order->secure_id ) {
            $this->writePaymentErrorLog("========================". self::getPayStrFromClassName( get_class($this) ). " checkCallbackOrder order IDs do not match, expected [$order->secure_id], input: ", $fields);
            return false;
		}

		# everything checked ok
		return true;
	} // EOF checkCallbackOrder

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
        // $str .= "&key=" .$this->getSystemInfo("key");
        // $str .= $this->getSystemInfo("key");
        return $str ;
    }

	private function sign($params) {

        $data= array(
            'pay_bank_id'  	=> $params['pay_bank_id'] ,
			'amount'  		=> $params['amount'] ,
			'direct_type'  		=> $params['direct_type'] ,
            'order_number'  		=> $params['order_number'] ,
            'return_url'  	=> $params['return_url'],
            'source_url' 	=> $params['source_url']
		);
		ksort($data);
        reset($data);

    	$str = $this->assemble($data);
		//加密
        $sign = $this->aesEncode($this->getSystemInfo("key"), $str);
        // $sign = strtolower(md5($str));
	
		return $sign;
	}// EOF sign

	/**
	 * 驗證第三方支付商回調資料無篡改。
	 * @param string $params 第三方支付商回調的資料，會依照支付商不同而有所不同。
	 * 此案為  json 字串，透過 $params[Data][sign] 來印證資料無篡改。
	 * @return boolean 若正確則 true，否則 false。
	 */
	private function validateSign($params) {

		/// $params,
		// {"Status":200,"Msg":"请求完成","Data":{"web_ordernumber":"D881897484957","pay_bank_id":"8","ordernumber":"15625758887408281420","amount":"121.0000","return_params":"","sign":"GZGNJfMGoPk/umoO4g65/ipCW7HQtmmDQWdSc54SEDIUjJmTYXL05inqhlNA7wueBKdCS1TJKXgYDMEZyPM56T5jgmaVqhHkQvz85GVXGJYitBwp4catopuQxLXO+t9QQt39ssq5fQlmwE4bDuhXSRW8pNaJ3UCMLswLhKOqyQbWQtD67dXspicAHqDrgKxCZOmFDO830zgTNfiIpq+MzO8mF9Eq2SbDcKJt4HrB46I="}}
		$raw_post = trim($params);
		$raw_post_json = json_decode($raw_post, true);
		$sign = $raw_post_json['Data']['sign']; // get sign from Data and clone it.

		unset($raw_post_json['Data']['sign']); // remove sign of Data.
		// gen signStr by Data
		$dataStr = json_encode($raw_post_json, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
		$signStr = $this->aesEncode($this->getSystemInfo("key"), $dataStr);

		return strcasecmp($sign, $signStr) === 0;
	}// EOF validateSign

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

	// ===============
	// HELPER UTILS
	// ===============

	/**
	 * Get extra_info json string
	 * @return string json srting for <extra_info> of xml.
	 */
	public function getExtraInfoStr(){

		$extra_info = array();
		$extra_info['callback_host'] = '';
		$extra_info['call_socks5_proxy'] = 'socks5://10.140.0.3:1000'; // Patch for "您的IP:35.194.156.49不在访问白名单中"
		$extra_info['pay_bankcode_list'] = $this->pay_bankcode_list; // 所有渠道
		// $extra_info['pay_bankcode_list']['ALIPAY_QR']= '903';
		$extra_info['action_uri_list'] = $this->action_uri_list; // 所有渠道
		// $extra_info['action_uri_list']['ALIPAY_QR']= 'http://wx.vkoov.com/Pay_Index.html';
		// $extra_info['pay_productname'] = 'Deposit';
		$extra_infoStr = json_encode($extra_info, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
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
	public function getPayStrFromClassName($classNameStr = ''){
		$re = '/Abstract_payment_api_(?P<pay_name>.+)/'; // Ref. to https://regex101.com/r/dQ2aaJ/1/
		// $classNameStr = '// Abstract_payment_api_yangpay
		// // Payment_api_yangpay_weixin
		// // Payment_api_yangpay_weixin_qr';


		if( is_string($classNameStr ) ){
			preg_match($re, $classNameStr, $matches);
		}else{
			$this->CI->utils->debug_log("=====================getPayStrFromClassName.func_get_args", func_get_args());
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
			$matches4bsfcn = $this->getBillingStrFromClassName($classNameStr, true);

			$this->CI->utils->debug_log("=====================getBillingStrFromClassName.matches4bsfcn", $matches4bsfcn);
			if( ! empty($matches4bsfcn) && $matches4bsfcn['pay_name'] ){
				$return = $matches4bsfcn['pay_name'];
			}
		}
		$this->CI->utils->debug_log("=====================getPayStrFromClassName.return", $return);
		return $return;
	}

	/**
	 * 取得金流商代號字串
	 * 依照正規表示式取特定位置的渠道字串。
	 * 參考： https://regex101.com/r/dQ2aaJ/2/
	 *
	 * @todo move to helper.
	 * @param string $className 物件的名稱
	 * @return string|array  銀行/渠道代號字串
	 */
	public function getBillingStrFromClassName($classNameStr = '',$getMatches = false){
		$re = '/Payment_api_(?P<pay_name>[^_]+)_(?P<billing_name>.*)/';


		if( is_string($classNameStr ) ){
			preg_match($re, $classNameStr, $matches);
		}else{
			$matches = array();
			$this->CI->utils->debug_log("=====================getBillingStrFromClassName.func_get_args", func_get_args());
		}

		// display the Warning while not found.
		// Severity: Warning  --> preg_match() expects parameter 2 to be string, object given /home/vagrant/Code/og/submodules/payment-lib/payment/abstract_payment_api_yangpay.php 511

		if($getMatches){
			$return = $matches;
		}else{
			// Print the entire match result
			$return = $matches['billing_name'];
		}

		$this->CI->utils->debug_log("=====================getBillingStrFromClassName.return", $return);
		return $return;
	}

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
	public function addCData2xmlNode($cdata_text, $nodeName, &$xml) {
		$xml->$nodeName = NULL; // VERY IMPORTANT! We need a node where to append
		$node = dom_import_simplexml($xml->$nodeName);
		$no   = $node->ownerDocument;
		$node->appendChild($no->createCDATASection($cdata_text));
	}


	/**
	 * ============
	 * AES Utils
	 * ============
	 */

	/**
     * AES加密
     * @param string $k  商户密钥
     * @param string $msg  加密参数
     * @return string
     */
    public function aesEncode($k,$msg){
        $key = $k;             //AES加密的密码(16位) 配给商户的密钥一般是16位;如果不对请联系技术
        $iv =  $k;           //AES 偏移量,这里的偏移量和密码相同
        $message = $msg;       //需要加密的参数
        $blocksize = @mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);//采用128位 CBC加密
        $len = strlen($message); //取得字符串长度
        $pad = $blocksize - ($len % $blocksize); //取得补码的长度
		$message .= str_repeat(chr($pad), $pad); //用ASCII码为补码长度的字符， 补足最后一段
        $xcrypt = @mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $message, MCRYPT_MODE_CBC, $iv);
        $xcrypt = base64_encode($xcrypt);//base64输出
        return $xcrypt;
    }
    /**
     * AES解密
     * @param string $k  商户密钥
     * @param string $msg  加密参数
     * @return string
     */
    public function aesDecode($k,$msg) {
        $key = $k;             //AES加密的密码(16位) 配给商户的密钥一般是16位;如果不对请联系技术
        $iv =  $k;           //AES 偏移量,这里的偏移量和密码相同
        $msg = base64_decode($msg);
        $sReturn = $this->stripPkcs5Padding(@mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $msg, MCRYPT_MODE_CBC, $iv));
        return $sReturn;
    }
    private function stripPkcs5Padding($string) {
        $slast = ord(substr($string, -1));
        $slastc = chr($slast);
        if (preg_match("/$slastc{" . $slast . "}/", $string)) {
            $string = substr($string, 0, strlen($string) - $slast);
            return $string;
        } else {
            return false;
        }
    }
}


class AES extends BaseController
{
    /**
     * AES加密
     * @param string $k  商户密钥
     * @param string $msg  加密参数
     * @return string
     */
    public function aesEncode($k,$msg){
        $key = $k;             //AES加密的密码(16位) 配给商户的密钥一般是16位;如果不对请联系技术
        $iv =  $k;           //AES 偏移量,这里的偏移量和密码相同
        $message = $msg;       //需要加密的参数
        $blocksize = @mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);//采用128位 CBC加密
        $len = strlen($message); //取得字符串长度
        $pad = $blocksize - ($len % $blocksize); //取得补码的长度
        $message .= str_repeat(chr($pad), $pad); //用ASCII码为补码长度的字符， 补足最后一段
        $xcrypt = @mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $message, MCRYPT_MODE_CBC, $iv);
        $xcrypt = base64_encode($xcrypt);//base64输出
        return $xcrypt;
    }




    /**
     * AES解密
     * @param string $k  商户密钥
     * @param string $msg  加密参数
     * @return string
     */

    public function aesDecode($k,$msg) {
        $key = $k;             //AES加密的密码(16位) 配给商户的密钥一般是16位;如果不对请联系技术
        $iv =  $k;           //AES 偏移量,这里的偏移量和密码相同
        $msg = base64_decode($msg);
        $sReturn = $this->stripPkcs5Padding(@mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $msg, MCRYPT_MODE_CBC, $iv));
        return $sReturn;
    }



    private function stripPkcs5Padding($string) {
        $slast = ord(substr($string, -1));
        $slastc = chr($slast);
        if (preg_match("/$slastc{" . $slast . "}/", $string)) {
            $string = substr($string, 0, strlen($string) - $slast);
            return $string;
        } else {
            return false;
        }
    }
}