<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * Qianwang 千网
 * http://www.10001000.com/
 *
 * QIANWANG_PAYMENT_API, ID: 76
 *
 * Required Fields:
 *
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: http://apika.10001000.com/chargebank.aspx
 * * Extra Info:
 * > {
 * >  	"qianwang_partner" : "##partner code##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_qianwang extends Abstract_payment_api {
	const PAYMENT_STATUS_SUCCESS = '0';
	const RETURN_SUCCESS_CODE = 'opstate=0';
	const RETURN_FAILED_CODE='opstate=-2';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# -- implementation of abstract functions --
	public function getPlatformCode() {
		return QIANWANG_PAYMENT_API;
	}

	public function getPrefix() {
		return 'qianwang';
	}

	# -- override common API functions --
	## Constructs an URL so that the caller can redirect / invoke it to make payment through this API
	## See controllers/redirect.php for detail.
	##
	## Retuns a hash containing these fields:
	## array(
	##	'success' => true,
	##	'type' => self::REDIRECT_TYPE_FORM,
	##	'url' => $info['url'],
	##	'params' => $params,
	##	'post' => true
	## );
	##
	## Reference: documentation section 3.2.2
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params['parter'] = $this->getSystemInfo("qianwang_parter");

		$direct_pay_extra_info = $order->direct_pay_extra_info;
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['type'] = @$extraInfo['banktype'];
			}
		}
		if(!array_key_exists('type', $params)) {
			$params['type'] = '';
		}

		if(empty($params['type'])){
			//try bank id
			$bankId=$this->getBankId($order);
			$params['type'] = $bankId;
		}

		$params['value'] = $this->convertAmountToCurrency($amount);
		$params['orderid'] = strtolower($order->secure_id);
		$params['callbackurl'] = $this->getNotifyUrl($orderId);
		$params['hrefbackurl'] = $this->getReturnUrl($orderId);
		$params['payerIp'] = $this->getClientIp();

		$params['sign'] = $this->sign($params);

		$this->utils->debug_log('params', $params);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true,
		);
	}

	public function getBankId($order){
		//default bank id
		return '';
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

	## $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		if ($source == 'browser') {
			$result['success'] =true;
			$result['message'] ='';
			if($this->CI->utils->getPlayerCenterTemplate()=='iframe'){
				$result['next_url'] = $this->getPlayerBackUrl();
			}else{
				$result['next_url'] = '/player_center/iframe_viewCashier';
			}
			$result['go_success_page'] = true;
			return $result;
		}

		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
			return $result;
		}

		# Update order payment status and balance
		$success=true;
		// $this->CI->sale_order->startTrans();

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
				$params['ekaorderid'], $params['ekatime'], # bank order id not exist, record trade time instead
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->CI->sale_order->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}
		// $success = $this->CI->sale_order->endTransWithSucc();

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
		}

		// if ($source == 'browser') {
		// 	$result['next_url'] = $this->getPlayerBackUrl();
		// 	$result['go_success_page'] = true;
		// }

		return $result;
	}

	## Validates whether the callback from API contains valid info and matches with the order
	## Reference: documentation, section 3.3; sample code, payReturn.php
	public function checkCallbackOrder($order, $fields, &$processed = false) {
		# does all required fields exist?
		$requiredFields = array(
			'orderid', 'opstate', 'ovalue', 'sign',
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->verify($fields, $fields['sign'])) {
			$this->writePaymentErrorLog('Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		# check parameter values: orderStatus, tradeAmt, orderNo, merchNo
		# is payment successful?
		if ($fields['opstate'] != self::PAYMENT_STATUS_SUCCESS) {
			$this->writePaymentErrorLog('Payment was not successful, msg:', $fields['msg']);
			return false;
		}

		# does amount match?
		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['ovalue']))
		) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# does order_no match?
		if (strtolower($fields['orderid']) != strtolower($order->secure_id)) {
			$this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	public function getPlayerInputInfo() {
		return array(
			array('name' => 'banktype', 'type' => 'list', 'label_lang' => 'pay.bank',
				'list' => $this->getBankList(), 'list_tree' => $this->getBankListTree()),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	# Reference: documentation Appendix 1
	public function getBankListInfo() {
		return array(
            array('label' => '中信银行', 'value' => 962),
			array('label' => '中国银行', 'value' => 963),
			array('label' => '中国农业银行', 'value' => 964),
			array('label' => '中国建设银行', 'value' => 965),
			array('label' => '中国工商银行（仅限工行手机签约客户）', 'value' => 966),
			array('label' => '中国工商银行（全国范围）', 'value' => 967),
			array('label' => '浙商银行', 'value' => 968),
			array('label' => '浙江稠州商业银行', 'value' => 969),
			array('label' => '招商银行', 'value' => 970),
			array('label' => '邮政储蓄', 'value' => 971),
			array('label' => '兴业银行', 'value' => 972),
			array('label' => '顺德农村信用合作社', 'value' => 973),
			array('label' => '深圳发展银行', 'value' => 974),
			array('label' => '上海银行', 'value' => 975),
			array('label' => '上海农村商业银行', 'value' => 976),
			array('label' => '浦东发展银行', 'value' => 977),
			array('label' => '平安银行', 'value' => 978),
			array('label' => '南京银行', 'value' => 979),
			array('label' => '民生银行', 'value' => 980),
			array('label' => '交通银行', 'value' => 981),
			array('label' => '华夏银行', 'value' => 982),
			array('label' => '杭州银行', 'value' => 983),
			array('label' => '广州市农村信用社 | 广州市商业银行', 'value' => 984),
			array('label' => '广东发展银行', 'value' => 985),
			array('label' => '光大银行', 'value' => 986),
			array('label' => '东亚银行', 'value' => 987),
			array('label' => '渤海银行', 'value' => 988),
			array('label' => '北京银行', 'value' => 989),
			array('label' => '北京农村商业银行', 'value' => 990),
			array('label' => '支付宝', 'value' => 992),
			array('label' => '财付通', 'value' => 993),
			array('label' => '快钱', 'value' => 994),
		);
	}

	# -- Private functions --
	## After payment is complete, the gateway will invoke this URL asynchronously
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## After payment is complete, the gateway will send redirect back to this URL
	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## Format the amount value for the API
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- private helper functions --
	public function getClientIp() {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif (!empty($_SERVER['REMOTE_ADDR'])) {
			$ip = $_SERVER['REMOTE_ADDR'];
		} else {
			$ip = '127.0.0.1';
		}

		# If there are multiple IPs, take the first one
		$multipleIps = explode(",", $ip);
		return trim($multipleIps[0]);
	}

	# Reference: Documentation section 3.1.4
	public function sign($data) {
		$params = array('parter', 'type', 'value', 'orderid', 'callbackurl');
		$signParams = array();

		foreach ($params as $p) {
			$signParams[$p] = $data[$p];
		}

		// $original=http_build_query($signParams).$this->getSystemInfo('key');
		$original='parter='.$data['parter'].'&type='.$data['type'].'&value='.$data['value'].'&orderid='.$data['orderid'].'&callbackurl='.$data['callbackurl'].$this->getSystemInfo('key');
		$sign=strtolower(md5($original));
		$this->CI->utils->debug_log('original', $original, $sign);
		return $sign;
		// return md5(http_build_query($signParams));
	}

	public function server_sign($data) {
		// $params = array('orderid', 'opstate', 'ovalue');
		// $signParams = array();

		// foreach ($params as $p) {
		// 	$signParams[$p] = $data[$p];
		// }

		// $this->CI->utils->debug_log($this->getAllSystemInfo(), $this->getSystemInfo('key'));

		$this->CI->utils->debug_log('get key', $this->getSystemInfo('key'));
		$original='orderid='.trim(strtolower($data['orderid'])).'&opstate='.trim(strtolower($data['opstate'])).'&ovalue='.trim(strtolower($data['ovalue'])).$this->getSystemInfo('key');
		$this->CI->utils->debug_log('original', $original);
		return md5($original);
	}

	public function verify($data, $signature) {
		$mySign = $this->server_sign($data);

		$this->utils->debug_log('compare sign, our',$mySign, 'callback sign', $signature);

		if (strcasecmp($mySign, $signature) === 0) {
			return true;
		} else {
			return false;
		}
	}

}