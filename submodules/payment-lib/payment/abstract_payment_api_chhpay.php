<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * 畅汇 CHHPAY
 * https://t24o.cn/
 *
 * * CHHPAY_PAYMENT_API, ID: 585
 * * CHHPAY_WEIXIN_PAYMENT_API, ID: 586
 * * CHHPAY_QQPAY_PAYMENT_API, ID: 587
 * * CHHPAY_QUICKPAY_H5_PAYMENT_API, ID: 588
 * * CHHPAY_JDPAY_PAYMENT_API, ID: 589
 * * CHHPAY_UNIONQRPAY_PAYMENT_API, ID: 590
 * * CHHPAY_WITHDRAWAL_PAYMENT_API, ID: 600
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://changcon.chhpay.com/controller.action
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

abstract class Abstract_payment_api_chhpay extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = 'success';
	const TRADE_STATUS_SUCCESS = '1';
	const QRCODE_RESULT_CODE_SUCCESS = 0;

	const PAYTYPE_BANK = 'OnlinePay';
	const PAYTYPE_WEIXIN = 'WEIXIN';
	const PAYTYPE_WEIXIN_WAP = 'WEIXINWAP';
	const PAYTYPE_QUICKPAY_H5 = 'Nocard_H5';
	const PAYTYPE_QQPAY = 'QQ';
	const PAYTYPE_QQPAY_WAP = 'QQWAP';
	const PAYTYPE_JDPAY_WAP = 'JDPAY';
	const PAYTYPE_UNIONQRPAY = 'UnionPay';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these to specify pay type
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
		$params['p0_Cmd'] = 'Buy';
		$params['p1_MerId'] = $this->getSystemInfo("account");
		$params['p2_Order'] = $order->secure_id;
		$params['p3_Cur'] = 'CNY';
		$params['p4_Amt'] = $this->convertAmountToCurrency($amount);
		$params['p5_Pid'] = 'payment';
		$params['p8_Url'] = $this->getNotifyUrl($orderId);
		$params['pi_Url'] = $this->getReturnUrl($orderId);
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['hmac'] = $this->sign($params);

		$this->CI->utils->debug_log('=========================chhpay generatePaymentUrlForm', $params);
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
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['p2_Order']);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================chhpay response json to array', $decode_data);

        $err_msg = "Unknown error";
        if(!isset($decode_data)){
           	preg_match("/(<span class=\"red\">)(.*)(<\/span>)/i", $response, $err_msg);
            if(empty($err_msg)){
                preg_match("/(<h3>)(.*)(<\/h3>)/i", $response, $err_msg);
            }
        }

        if(!empty($decode_data['r3_PayInfo']) && isset($decode_data['r3_PayInfo'])) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'base64_url' => $decode_data['r3_PayInfo'],
            );
        } else{
            if(!empty($decode_data['r7_Desc'])) {
                $err_msg = $decode_data['r7_Desc'];
            }
            else{
                $err_msg = "錯誤碼: ".$decode_data['r1_Code'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $err_msg,//lang('Invalidte API response')
            );
        }
    }


	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	## Reference: sample code, callback.php
	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$this->utils->debug_log('callbackFrom' . ucfirst($source) . ': [' . $orderId .'], params:', $params);

		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		if (!$order) {
			$this->utils->error_log("Order ID [$orderId] not found.");
			return $result;
		}
		if($source == 'server' ){
			$callbackValid = false;
			$paymentSuccessful = $this->checkCallbackOrder($order, $params, $callbackValid); # $callbackValid is also assigned

			# Do not proceed to update order status if payment failed, but still print success msg as callback response
			if(!$paymentSuccessful) {
				return $result;
			}
		}
		# We can respond with ack to callback now
		$success = true;
		$result['message'] = self::RETURN_SUCCESS_CODE;

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
				$params['r2_TrxId'], $params['ro_BankOrderId'],
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$success = $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		# This $success marks whether the order status update is successful
		$result['success'] = $success;

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	# returns true if callback is valid and payment is successful
	# sets the $callbackValid parameter if callback is valid
	private function checkCallbackOrder($order, $fields, &$callbackValid) {
		# does all required fields exist?
		$requiredFields = array(
			'p1_MerId', 'r0_Cmd', 'r1_Code', 'r2_TrxId', 'r3_Amt', 'r4_Cur', 'r5_Pid', 'r6_Order', 'r8_MP', 'r9_BType', 'ro_BankOrderId', 'rp_PayDate'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=========================chhpay checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		if($this->ignore_callback_sign){
			$chhpay_pub_key = $this->getSystemInfo('key');

			$this->CI->utils->debug_log('ignore callback sign', $fields, $order, $chhpay_pub_key, $this->validateSign($fields));

		}else{

			# is signature authentic?
			if (!$this->validateSign($fields)) {
				$this->writePaymentErrorLog('=========================chhpay checkCallbackOrder validateSign Error', $fields);
				return false;
			}
		}

		$callbackValid = true; # callbackValid is set to true once the signature verification pass

		if ($fields['r1_Code'] != self::TRADE_STATUS_SUCCESS) {
			$this->writePaymentErrorLog('=========================chhpay checkCallbackOrder payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['r3_Amt']) {
			$this->writePaymentErrorLog("=========================chhpay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['r6_Order'] != $order->secure_id) {
			$this->writePaymentErrorLog("=========================chhpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- private helper functions --
	protected function getBankListInfoFallback() {
		return array(
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '工商银行', 'value' => 'ICBC'),
			array('label' => '建设银行', 'value' => 'CCB'),
			array('label' => '中信银行', 'value' => 'ECITIC'),
			array('label' => '民生银行', 'value' => 'CMBC'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '农业银行', 'value' => 'ABC'),
			array('label' => '交通银行', 'value' => 'BOCO'),
			array('label' => '北京银行', 'value' => 'BOB'),
			array('label' => '平安银行', 'value' => 'PAB'),
			array('label' => '招商银行', 'value' => 'CMBCHINA'),
			array('label' => '光大银行', 'value' => 'CEB'),
			array('label' => '深圳发展银行', 'value' => 'GDB'),
			array('label' => '广发银行', 'value' => 'CGB'),
			array('label' => '浦发银行', 'value' => 'SPDB'),
			array('label' => '华夏银行', 'value' => 'HXB'),
            array('label' => '邮政储蓄银行', 'value' => 'POST'),
            array('label' => '上海银行', 'value' => 'SHB'),
		);
	}

	public function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	# -- signing --
	public function sign($params) {
		$signStr = $this->createSignStr($params);
		$KEY = $this->getSystemInfo('key');
		$sign = $this->hamacmd5($signStr,$KEY);
		return $sign;
	}

	public function hamacmd5($data,$key){
		// RFC 2104 HMAC implementation for php.
		// Creates an md5 HMAC.
		// Eliminates the need to install mhash to compute a HMAC
		// Hacked by Lance Rushing(NOTE: Hacked means written)

		//需要配置环境支持iconv，否则中文参数不能正常处理
		$key = iconv("GB2312","UTF-8",$key);
		$data = iconv("GB2312","UTF-8",$data);

		$b = 64; // byte length for md5
		if (strlen($key) > $b) {
		$key = pack("H*",md5($key));
		}
		$key = str_pad($key, $b, chr(0x00));
		$ipad = str_pad('', $b, chr(0x36));
		$opad = str_pad('', $b, chr(0x5c));
		$k_ipad = $key ^ $ipad ;
		$k_opad = $key ^ $opad;

		return md5($k_opad . pack("H*",md5($k_ipad . $data)));
	}

	public function validateSign($params) {
		$signStr = $this->createSignStr($params);
		$KEY = $this->getSystemInfo('key');
		$shaSign = $this->hamacmd5($signStr,$KEY);

		return strcasecmp($shaSign, $params['hmac']) === 0;
	}

	public function createSignStr($params) {
		ksort($params);
		$signStr = '';
		foreach($params as $key => $value) {
			if(empty($value) || $key == 'hmac' ) {
				continue;
			}
			$signStr .= $value;
		}
		return $signStr;
	}
}