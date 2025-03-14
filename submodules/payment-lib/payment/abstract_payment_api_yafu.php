<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 *
 * yafu 雅付
 *
 * YAFU_PAYMENT_API, ID: 94
 * YAFU_WECHAT_PAYMENT_API, ID: 95
 * YAFU_ALIPAY_PAYMENT_API, ID: 96
 * YAFU_QQPAY_PAYMENT_API, ID: 276
 *
 * Required Fields:
 *
 * * URL
 * * Key: The user key assigned by Yafu
 * *
 *
 * Field Values:
 * * URL
 * 	- bank: http://pay.yafupay.com/bank_pay.do
 * 	- wechat: http://pay.yafupay.com/weixin_pay.do
 *  - alipay: http://pay.yafupay.com/alipay_pay.do
 *
 * 参考文档： 上海雅付网络技术文档.doc
 * 参考文档纠错：
 * 1. payType需参与签名，位于transAmt之后，callbackUrl之前
 * 2. 微信支付的类型为payType=02， 地址需要改weixin_pay.do
 * 3. 支付宝的类型为payType=03,提交地址改为alipay_pay.do
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_yafu extends Abstract_payment_api {

	const RETURN_SUCCESS_CODE = 'success';
	const RETURN_FAILED_CODE = 'error';
	const PAY_TYPE_BANK = '0101';
	const PAY_TYPE_WEIXIN = '0201';
	const PAY_TYPE_WEIXIN_H5 = '0901';
	const PAY_TYPE_ALIPAY = '0301';
	const PAY_TYPE_ALIPAY_H5 = '0303';
	const PAY_TYPE_QQPAY = '0501';
	const PAY_TYPE_QQPAY_H5 = '0503';
	const PAY_TYPE_JDPAY = '0801';
	const PAY_TYPE_JDPAY_H5 = '0803';
	const PAY_TYPE_UNIONPAY = '0701';
	const RESP_CODE_SUCCESS = '1';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	abstract public function getPayType();

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
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		# order-related params
		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		# Reference: Documentation section 3.1
		# constant parameters
		$params['version'] = '3.0';
		$params['consumerNo'] = $this->getSystemInfo('account');
		$params['merOrderNo'] = $order->secure_id;
		$params['transAmt'] = $this->convertAmountToCurrency($amount);
		$params['backUrl'] = $this->getNotifyUrl($orderId);
		$params['frontUrl'] = $this->getReturnUrl($orderId);

		$direct_pay_extra_info = $order->direct_pay_extra_info;
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['bankCode'] = array_key_exists('banktype', $extraInfo) ? $extraInfo['banktype'] : (array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : null);
			}
		}

		if(!$params['bankCode']) {
			unset($params['bankCode']);
		}

		$params['payType'] = $this->getPayType();

		# misc fields
		$params['goodsName'] = 'Topup';
		$params['merRemark'] = 'Topup';

		# sign param
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================yafu generatePaymentUrlForm params", $params);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
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
		$success = true;

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
				$params['orderId'], $params['OutOrderId'],
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
			$result['return_error'] = $this->getReturnErrorMsg($params['orderStatus']);
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	## Validates whether the callback from API contains valid info and matches with the order
	## Reference: code sample, callback.php
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array(
			'version', 'consumerNo', 'merOrderNo', 'orderNo', 'transAmt', 'orderStatus', 'payType', 'sign'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("========================yafu checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->verifySignature($fields)) {
			$this->writePaymentErrorLog('=======================yafu checkCallbackOrder verify signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['orderStatus'] != self::RESP_CODE_SUCCESS) {
			$this->writePaymentErrorLog('========================yafu checkCallbackOrder payment was not successful, orderStatus not 1', $fields);
			return false;
		}

		if (
			$this->convertAmountToCurrency($order->amount) !=
			$this->convertAmountToCurrency(floatval($fields['transAmt']))
		) {
			$this->writePaymentErrorLog("========================yafu checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['consumerNo'] != $this->getSystemInfo('account')) {
			$this->writePaymentErrorLog("========================yafu checkCallbackOrder merchant codes do not match, expected [" . $this->getSystemInfo('yompay_MER_NO') . "]", $fields);
			return false;
		}

		if ($fields['merOrderNo'] != $order->secure_id) {
			$this->writePaymentErrorLog("========================yafu checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# Config in extra_info will overwrite this one
	public function getBankListInfoFallback() {
		return array(
			array('label' => '中国建设银行', 'value' => 'CCB'),
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '中国工商银行', 'value' => 'ICBC'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '中国农业银行', 'value' => 'ABC'),
			array('label' => '交通银行', 'value' => 'BOCOM'),
			array('label' => '中国民生银行', 'value' => 'CMBC'),
			array('label' => '华夏银行', 'value' => 'HXBC'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '上海浦东发展银行', 'value' => 'SPDB'),
			array('label' => '广东发展银行', 'value' => 'GDB'),
			array('label' => '中信银行', 'value' => 'CITIC'),
			array('label' => '光大银行', 'value' => 'CEB'),
			array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
			array('label' => '深圳发展银行', 'value' => 'SDB'),
			array('label' => '北京银行', 'value' => 'BOBJ'),
			array('label' => '天津银行', 'value' => 'TCCB'),
			array('label' => '上海银行', 'value' => 'BOS'),
			array('label' => '平安银行', 'value' => 'PAB'),
			array('label' => '宁波银行', 'value' => 'NBCB'),
			array('label' => '南京银行', 'value' => 'NJCB'),
			array('label' => '江苏银行', 'value' => 'JSB'),
			array('label' => '农业银行', 'value' => 'ABC'),
			array('label' => '邮政银行', 'value' => 'PSBC'),
			array('label' => '平安银行', 'value' => 'PAB'),
			array('label' => '东亚银行', 'value' => 'BEA'),
			array('label' => '成都银行', 'value' => 'BOCD'),
			array('label' => '渤海银行', 'value' => 'CBHB'),
		);
	}

	# -- Private functions --
	## Reference: Documentation section 7
	private function getReturnErrorMsg($returnCode) {
		$msgs = array(
			'ok' => '交易成功',
			'GT01' => '系统内部错误',
			'GT03' => '通讯失败',
			'GT11' => '虚拟账户不存在',
			'GT12' => '用户没有注册雅付账户',
			'GT14' => '该笔记录不存在',
			'GT16' => '缺少字段',
			'MR01' => '验证签名失败',
			'MR02' => '报文某字段格式错误',
			'MR03' => '报文某字段超出允许范围',
			'MR04' => '报文某必填字段为空',
			'MR05' => '交易类型不存在',
			'MR06' => '证书验证失败或商户校验信息验证失败',
			'MN01' => '超过今日消费上限',
			'MN02' => '超过单笔消费上限',
			'MN03' => '超过今日提现上限',
			'MN04' => '超过单笔提现上限',
			'MN07' => '余额不足',
			'MN11' => '商户上送的单笔订单交易金额超限',
			'SM01' => '商户不存在',
			'SM02' => '商户状态非正常',
			'SM03' => '商户不允许支付',
			'SM04' => '商户不具有该权限',
			'SC10' => '支付密码错误',
			'SC11' => '卡状态非正常',
			'SC13' => '雅付号不正确',
			'ST07' => '支付类交易代码匹配失败',
			'ST10' => '交易重复，订单号已存在',
			'ST12' => '不允许（商户||企业）给个人转账',
			'ST13' => '不允许自己给自己支付或转帐',
			'SU05' => '不存在该用户',
			'SU10' => '用户状态非正常',
			'SM07' => '商铺不能做为交易平台提供服务',
			'AN01' => '商户域名校验失败',
			'9999' => '订单处理中',
		);

		return array_key_exists($returnCode, $msgs) ? $msgs[$returnCode] : '订单失败';
	}

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
	# Sign the data for submitting to payment API
	# Reference: Documentation, last section
	public function sign($data) {
		$bankCode = '';
		if($data['bankCode']) {
			$bankCode = $data['bankCode'];
		}
		else {
			unset($bankCode);
		}
		$version = $data['version'];
		$payType = $data['payType'];
		$consumerNo = $data['consumerNo'];
		$merOrderNo = $data['merOrderNo'];
		$transAmt = $data['transAmt'];
		$frontUrl = $data['frontUrl'];
		$backUrl = $data['backUrl'];
		$goodsName = $data['goodsName'];
		$key = $this->getSystemInfo('key');
		$merRemark = $data['merRemark'];

		$signStr = "backUrl=".$backUrl. ( isset($bankCode) ? ("&bankCode=".$bankCode."&") : "&")."consumerNo=".$consumerNo."&frontUrl=".$frontUrl."&goodsName=".$goodsName."&merOrderNo=".$merOrderNo."&merRemark=".$merRemark."&payType=".$payType."&transAmt=".$transAmt."&version=".$version."&key=".$key;
		//$signStr = "backUrl=http://127.0.0.1:8080/yfpay/third/test/payCallBack.json&bankCode=CMB&buyIp=127.0.0.1&buyPhome=13511112222&consumerNo=12004&frontUrl=http://www.baidu.com&goodsName=test&merOrderNo=1494778744867&merRemark=test&payType=0202&shopName=test&transAmt=1.5&version=3.0&key=FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF";
		$md5Str=strtoupper(md5($signStr, false));

		return $md5Str;
	}

	public function callbackSign($data) {
		$version = $data['version'];
		$consumerNo = $data['consumerNo'];
		$merOrderNo = $data['merOrderNo'];
		$transAmt = $data['transAmt'];
		$orderNo = $data['orderNo'];
		$orderStatus = $data['orderStatus'];
		$payType = $data['payType'];
		$key = $this->getSystemInfo('key');

		# payType does not exist in callback data. skip it when we sign callback data.
		if(array_key_exists('payType', $data)){
			$payType = $data['payType'];
		}

		$signStr = "consumerNo=".$consumerNo."&merOrderNo=".$merOrderNo."&orderNo=".$orderNo."&orderStatus=".$orderStatus."&payType=".$payType."&transAmt=".$transAmt."&version=".$version."&key=".$key;
		$md5Str=strtoupper(md5($signStr, false));

		return $md5Str;
	}

	public function verifySignature($data) {
		$mySign = $this->callbackSign($data);
		if (strcasecmp($mySign, $data['sign']) === 0) {
			return true;
		} else {
			return false;
		}
	}

}
