<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * HRPAY 华仁
 * http://www.hr-pay.com
 *
 * * HRPAY_PAYMENT_API, ID: 146
 * * HRPAY_ALIPAY_PAYMENT_API, ID: 147
 * * HRPAY_WEIXIN_PAYMENT_API, ID: 148
 * * HRPAY_UNIONPAY_PAYMENT_API, ID: 149
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - MD5 key
 * * ExtraInfo - include pub key and priv key
 *
 * Field Values:
 *
 * * URL: http://api.hr-pay.com/PayInterface.aspx
 * * Extra Info:
 * > {
 * > 	"hrpay_priv_key" : "## path to merchant's private key ##",
 * > 	"hrpay_pub_key" : "## path to API's public key ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_hrpay extends Abstract_payment_api {
	const RETURN_SUCCESS_CODE = '[{result:"ok"}]';
	const API_WITHDRAWAL_RESPONSE_CODE = '0150';
	const MONEYTYPE_CNY = '0';
	const PAGECODE_BANK = '1001';
	const PAGECODE_ALIPAY = '1009';
	const PAGECODE_WEIXIN = '1003'; # Tested that 1011 => 商户没有签约此产品
	const PAGECODE_WITHDRAWAL = '1005';
	const PAGECODE_UNIONPAY = '1013'; # 快捷支付
	const V_RESULT_PAYMENT_SUCCESS = '2000';

	public function __construct($params = null) {
		parent::__construct($params);

		$this->CI->load->model(array('playerbankdetails', 'wallet_model'));
	}

	# Returns the v_pagecode param
	protected abstract function getPageCode();

	# Add in implementation-dependent specific params
	protected abstract function configParams(&$params, $direct_pay_extra_info);

	public function getSecretInfoList() {
		$secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'hrpay_pub_key', 'hrpay_priv_key');
		return $secretsInfo;
	}

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params = array();
		$params['v_pagecode'] = $this->getPageCode();
		$params['v_mid'] = $this->getSystemInfo("account");
		$params['v_oid'] = $this->getOid($order->secure_id);
		$params['v_rcvname'] = $this->getSystemInfo("account");
		$params['v_rcvaddr'] = $this->getSystemInfo("account");
		$params['v_rcvtel'] = $this->getSystemInfo("account"); # 建议统一用商户编号的值代替
		$params['v_goodsname'] = $this->getSystemInfo("goodsname", 'Deposit');
		$params['v_goodsdescription'] = $this->getSystemInfo("goodsdescription", 'Deposit');
		$params['v_rcvpost'] = $this->getSystemInfo("account");
		$params['v_qq'] = $this->getSystemInfo("account");
		$params['v_amount'] = $this->convertAmountToCurrency($amount);
		$params['v_ymd'] = date('Ymd');
		$params['v_orderstatus'] = '1'; # 一般商户该参数无实际意义，建议统一配置为1
		$params['v_ordername'] = $this->getSystemInfo("account");

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['v_moneytype'] = self::MONEYTYPE_CNY;
		$params['v_url'] = $this->getNotifyUrl($orderId);
		$params['v_noticeurl'] = $this->getReturnUrl($orderId);

		$params['v_md5info'] = $this->sign($params);

		$this->CI->utils->debug_log('=====================hrpay generatePaymentUrlForm params', $params);

		$postParams = array();
		$postParams['data'] = $this->encrypt($params);
		$postParams['mid'] = $this->getSystemInfo('account');
		$postParams['state'] = '0';

		$this->CI->utils->debug_log('=====================hrpay generatePaymentUrlForm postParams', $params);

		return $this->processPaymentUrlForm($postParams);
	}

	# Based on demo-php, perform a redirect with params appended to the URL
	# Child implementation can overwrite this
	protected function processPaymentUrlForm($postParams) {
		$url = $this->getSystemInfo('url');

		$data_string = http_build_query($postParams);
		$url = $url.'?'.$data_string;

		$this->utils->debug_log('====================================hrpay_post_url', $url);

		return array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
	}

	//===withdrawal=====================================

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false, 'message' => 'payment failed');
		$success = false;
		$message = 'payment failed';
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("=======================hrpay withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		$fullParams = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
		$url = $this->getSystemInfo('url');
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json') );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "[".$this->CI->utils->encodeJson($fullParams)."]");

        $resultContent = curl_exec($ch);
        curl_close($ch);

		$this->utils->debug_log("===============hrpay curl_exec to resultContent", $resultContent);

		$decryptedString = $this->decrypt($resultContent);

		$this->utils->debug_log("===============hrpay decryptedString", $decryptedString);

		//it's not real json
		//sample: %5b%7b%22v_pagecode%22%3a%221002%22%2c%22v_mid%22%3a%22110034%22%2c%22v_oid%22%3a%2220170412-110034-343519619200%22%2c%22v_orderid%22%3a%221002597811TT2017041211165916907%22%2c%22v_bankno%22%3a%22%22%2c%22v_result%22%3a%222000%22%2c%22v_value%22%3a%2220.00%22%2c%22v_realvalue%22%3a%2220%22%2c%22v_qq%22%3a%22110034%22%2c%22v_telephone%22%3a%22110034%22%2c%22v_goodsname%22%3a%22Top+up%22%2c%22v_goodsdescription%22%3a%22Top+up%22%2c%22v_extmsg%22%3a%22Top+up%22%2c%22v_resultmsg%22%3a%22%e6%88%90%e5%8a%9f%ef%bc%81%22%2c%22v_sign%22%3a%222e68bed02c8aaf2fa79ad8a86aaedeae%22%7d%5d

		$decodedResult = json_decode(urldecode($decryptedString), true);

		if(is_array($decodedResult) && count($decodedResult)==1){
			$this->utils->debug_log("===============hrpay withdraw decoded Result", $decryptedString, $decodedResult);

			if($decodedResult[0]['ResCode'] == self::API_WITHDRAWAL_RESPONSE_CODE) {
				$success = true;
				$message = $decodedResult[0]['ResDesc'];
			}
			else {
				$success = false;
				$message = $decodedResult[0]['ResDesc'];
			}
		}

		$result = array('success' => $success, 'message' => $message);

		return $result;
	}

	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$this->utils->error_log("========================hrpay withdraw getWithdrawParams input - bank: [$bank], accNum: [$accNum], name: [$name], amount: [$amount], transId: [$transId]");
		$order = $this->CI->sale_order->getSaleOrderById($transId);

		# bank info as input
		$params = [];

		# look up bank code
		$params['v_pagecode'] = $this->getPageCode();
		$params['v_mid'] = $this->getSystemInfo('account');
		$params['v_oid'] = $this->getOid($transId);
		$params['v_amount'] = $this->convertAmountToCurrency($amount);

	    $params['v_payeename'] = $name;  //收款人姓名
	    $params['v_payeecard'] = $accNum;   //收款人卡号

		# look up bank code
		$bankInfo = $this->getHrpayBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================hrpay withdraw bank whose bankTypeId=[$bank] is not supported by hrPay");
			return array('success' => false, 'message' => lang('Bank not supported by hrPay'));
		}

		$params['v_bankno'] = $bankInfo[$bank]['code'];

		# look up bank detail from playerbankdetails table, using bank_type ID and accountNumber
		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("==================hrpay withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

		if(!empty($playerBankDetails)){
			$params['v_accountprovince'] = $playerBankDetails['province'];	//开户省  卡号的开户省
			$params['v_accountcity'] = $playerBankDetails['city'];	//开户市 卡号的开户市
			$params['v_bankname'] = $bankInfo[$bank]['name'];	//开户行名称
		}
		else {
			$params['v_accountprovince'] = '无';
			$params['v_accountcity'] = '无';
			$params['v_bankname'] = '无';
		}

		$params['v_accountprovince'] = empty($params['v_accountprovince']) ? "无" : $params['v_accountprovince'];
		$params['v_accountcity'] = empty($params['v_accountcity']) ? "无" : $params['v_accountcity'];
		$params['v_bankname'] = empty($params['v_bankname']) ? "无" : $params['v_bankname'];

	    $params['v_ymd'] = date("Ymd"); //订单产生日期，格式为yyyymmdd，例如：20100101
	    $params['v_url'] = $this->getNotifyUrl($transId); //支付成功返回地址

	    //$this->configParams($params, $order->direct_pay_extra_info);

		$params['v_md5info'] = $this->signWithdrawal($params);

		$this->CI->utils->debug_log('=====================hrpay getWithdrawParams params', $params);

		$postParams = array();
		$postParams['data'] = $this->encrypt($params);
		$postParams['mid'] = $this->getSystemInfo('account');
		$postParams['state'] = '0';

		$this->CI->utils->debug_log('=====================hrpay getWithdrawParams postParams', $postParams);

		return $postParams;
	}

	public function signWithdrawal($params){
		$paramIndex = array('v_pagecode', 'v_mid', 'v_oid', 'v_amount', 'v_payeename',
			'v_payeecard', 'v_accountprovince', 'v_accountcity', 'v_bankname', 'v_bankno', 'v_ymd', 'v_url');
		$signStr = '';
		foreach($paramIndex as $index){
			if(!array_key_exists($index, $params)) {
				continue;
			}
			$signStr .= $index.$params[$index];
		}

		$signStr .= $this->getSystemInfo('key');
		$md5Sign = md5($signStr);

		$this->utils->debug_log("======================hrpay sign withdrawal: [".$signStr."] with MD5, value: ", $md5Sign);
		return $md5Sign;
	}

	# 该参数格式为：订单生成日期-商户编号-商户流水号。例如：20100101-888-12345
	private function getOid($orderId) {
		$dateStr = date('Ymd');
		$merchantId = $this->getSystemInfo('account');
		$orderId = ltrim($orderId, 'D'); # No letter allowed
		return "$dateStr-$merchantId-$orderId";
	}

	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	public function callbackFromBrowser($orderId, $params) {
		// $this->utils->error_log("Callback from browser is not supported by HRPay");
		// $this->returnUnimplemented();

        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return array('success' => TRUE,'response_result_id' => $response_result_id);
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$this->utils->debug_log('callbackFrom' . ucfirst($source) . ': [' . $orderId .'], params:', $params);

		# Get raw post data, as HRPay sends it without a variable name
		$postdata = file_get_contents("php://input");
		$decryptedString = $this->decrypt($postdata);
		//save to file
		$filename=$this->CI->utils->saveToResponseFile($this->getPlatformCode(), $postdata);

		//it's not real json
		//sample: %5b%7b%22v_pagecode%22%3a%221002%22%2c%22v_mid%22%3a%22110034%22%2c%22v_oid%22%3a%2220170412-110034-343519619200%22%2c%22v_orderid%22%3a%221002597811TT2017041211165916907%22%2c%22v_bankno%22%3a%22%22%2c%22v_result%22%3a%222000%22%2c%22v_value%22%3a%2220.00%22%2c%22v_realvalue%22%3a%2220%22%2c%22v_qq%22%3a%22110034%22%2c%22v_telephone%22%3a%22110034%22%2c%22v_goodsname%22%3a%22Top+up%22%2c%22v_goodsdescription%22%3a%22Top+up%22%2c%22v_extmsg%22%3a%22Top+up%22%2c%22v_resultmsg%22%3a%22%e6%88%90%e5%8a%9f%ef%bc%81%22%2c%22v_sign%22%3a%222e68bed02c8aaf2fa79ad8a86aaedeae%22%7d%5d
		// $params = json_decode($decryptedString, true);
		$params = json_decode(rawurldecode($decryptedString), true);
		if(is_array($params) && count($params)==1){
			$params=$params[0];
			$this->utils->debug_log("extract params", $params);
		}

		$this->utils->debug_log('Callback params decrypted', $params,'size of postdata:'.$filename, count($postdata),'decrypted', $decryptedString);

		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			return $result;
		}

		$success = true; # we have checked that callback is valid

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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['v_orderid'], null, null, null, $response_result_id);
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
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : '';
		}

		return $result;
	}

	# Ref: Documentation section 6.3
	private function checkCallbackOrder($order, $fields, &$processed = false) {
		# does all required fields exist?
		$requiredFields = array(
			'v_pagecode', 'v_mid', 'v_oid', 'v_realvalue',
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
				return false;
			}
		}

		$processed = true; # processed is set to true once the signature verification pass

		# is payment successful?
		if (strcasecmp($fields['v_result'], self::V_RESULT_PAYMENT_SUCCESS) != 0) {
			$this->writePaymentErrorLog('Payment was not successful', $fields);
			return false;
		}

		# does amount match?
		if ( !$this->CI->utils->compareResultCurrency($order->amount, '=', floatval($fields['v_realvalue']))) {
			$this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		# does merchNo match?
		if ($fields['v_mid'] != $this->getSystemInfo('account')) {
			$this->writePaymentErrorLog("Merchant codes do not match, expected [" . $this->getSystemInfo('account') . "]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- private helper functions --
	private function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	private function getNotifyUrl($transId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $transId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function getBankListInfoFallback() {
		$bankList = array(
			array('label' => '招商银行', 'value' => '308584000013'),
			array('label' => '中国工商银行', 'value' => '102100099996'),
			array('label' => '中国建设银行', 'value' => '105100000017'),
			array('label' => '中国农业银行', 'value' => '103100000026'),
			array('label' => '中国银行', 'value' => '104100000004'),
			array('label' => '浦发银行', 'value' => '310290000013'),
			array('label' => '交通银行', 'value' => '301290000007'),
			array('label' => '广东发展银行', 'value' => '306581000003'),
			array('label' => '中信银行', 'value' => '302100011000'),
			array('label' => '中国光大银行', 'value' => '303100000006'),
			array('label' => '兴业银行', 'value' => '309391000011'),
			array('label' => '平安银行', 'value' => '313584099990'),
			array('label' => '华夏银行', 'value' => '304100040000'),
			array('label' => '中国邮政储蓄', 'value' => '403100000004')
		);

		return $bankList;
	}

	public function getHrpayBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                if(isset($bankInfoItem['name'])){
                    $bankInfo[$system_bank_type_id]['name'] = $bankInfoItem['name'];
                }
                if(isset($bankInfoItem['code'])){
                    $bankInfo[$system_bank_type_id]['code'] = $bankInfoItem['code'];
                }
            }
            $this->utils->debug_log("=========================hrpay bank info from extra_info: ", $bankInfo);
        } else {
			$bankInfo = array(
				'1' => array('name' => '中国工商银行', 'code' => '102100099996'),
				'2' => array('name' => '招商银行', 'code' => '308584000013'),
				'3' => array('name' => '中国建设银行', 'code' => '105100000017'),
				'4' => array('name' => '中国农业银行', 'code' => '103100000026'),
				'5' => array('name' => '交通银行', 'code' => '301290000007'),
				'6' => array('name' => '中国银行', 'code' => '104100000004'),
				'8' => array('name' => '广东发展银行', 'code' => '306581000003'),
				'10' => array('name' => '中信银行', 'code' => '302100011000'),
				'12' => array('name' => '中国邮政储蓄', 'code' => '403100000004'),
				'13' => array('name' => '兴业银行', 'code' => '309391000011'),
				'15' => array('name' => '平安银行', 'code' => '313584099990'),
				'20' => array('name' => '中国光大银行', 'code' => '303100000006'),
				'998' => array('name' => '浦发银行', 'code' => '310290000013'),
				'999' => array('name' => '华夏银行', 'code' => '304100040000')
			);
			$this->utils->debug_log("=======================hrpay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	## Create MD5 signature for post params
	private function sign($params) {
		$paramIndex = array('v_pagecode', 'v_mid', 'v_oid', 'v_rcvname', 'v_rcvaddr',
			'v_goodsname', 'v_goodsdescription', 'v_rcvtel', 'v_rcvpost', 'v_qq', 'v_amount', 'v_ymd',
			'v_orderstatus', 'v_ordername', 'v_bankno', 'v_app', 'v_moneytype', 'v_url', 'v_noticeurl');
		$signStr = '';
		foreach($paramIndex as $index){
			if(!array_key_exists($index, $params)) {
				continue;
			}
			$signStr .= $index.$params[$index];
		}

		$signStr .= $this->getSystemInfo('key');
		$md5Sign = md5($signStr);
		return $md5Sign;
	}

	# Validates MD5 signature of callback params
	private function validateSign($params) {
		$paramIndex = array('v_pagecode', 'v_mid', 'v_oid', 'v_orderid', 'v_btype', 'v_bankno', 'v_result', 'v_value', 'v_realvalue', 'v_qq', 'v_telephone', 'v_goodsname', 'v_goodsdescription', 'v_extmsg', 'v_resultmsg');
		$signStr = '';
		foreach($paramIndex as $index){
			if(!array_key_exists($index, $params)) {
				continue;
			}
			$signStr .= $index.$params[$index];
		}

		$signStr .= $this->getSystemInfo('key');
		$md5Sign = md5($signStr);

		return strcasecmp($md5Sign, $params['v_sign']) === 0;
	}

	# Returns public key given by gateway
	private function getPubKey() {
		return $this->getSystemInfo('hrpay_pub_key');
	}

	# Returns the private key generated by merchant
	private function getPrivKey() {
		return $this->getSystemInfo('hrpay_priv_key');
	}

	private function encrypt($params) {
		return $this->rsaEncrypt('['.json_encode($params).']');
	}

	# Functions below are copied from demo-php
	/**
	 * RSA加密
	 * @param $plaintext
	 * @return bool|string
	 */
	protected function rsaEncrypt($plaintext){
		//The second arguments' value must be <= 117 if your key length is 1024 bit
		$split_data = str_split($plaintext, 117);

		$encrypt_array = array();
		foreach ($split_data as $part) {
			$result = openssl_public_encrypt($part, $encrypt_string, $this->getPubKey());
			if(!$result){
				return false;
			}

			$encrypt_array[]= base64_encode($encrypt_string);
		}

		return join('@',$encrypt_array);
	}

	/**
	 * RSA解密
	 * @param $ciphertext
	 * @return bool|string
	 */
	protected function decrypt($ciphertext) {
		$split_array = explode('@', $ciphertext); //At here using @ split ciphertext as array

		$decrypt_string = '';
		foreach ($split_array as $part) {
			$result = openssl_private_decrypt(base64_decode($part), $decrypt_data, $this->getPrivKey());
			if (!$result) {
				return false;
			}

			$decrypt_string .= $decrypt_data;
		}

		return $decrypt_string;
	}
}
