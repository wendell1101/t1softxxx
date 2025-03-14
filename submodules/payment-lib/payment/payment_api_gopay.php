<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 *
 * GOPAY
 * https://www.gopay.com/
 *
 * GOPAY_PAYMENT_API, ID: 5
 *
 * General behaviors include :
 *
 * * General payment form
 * * Recieving callbacks
 * * Checking callbacks order
 * * Creating sign and callback signs
 *
 * Required Fields:
 *
 * * URL
 * * Key - merchantID
 * * Secret - VerificationCode
 * * Account - virCardNoIn
 * * Extra Info
 *
 * Field Values:
 *
 * * Sandbox URL: https://mertest.gopay.com.cn/PGServer/Trans/WebClientAction.do
 * * Live URL: https://gateway.gopay.com.cn/Trans/WebClientAction.do
 * * Extra Info
 * > {
 * > 	"bank_list": {
 * > 		"CCB" : "_json: { \"1\": \"China Construction Bank\", \"2\": \"中国建设银行\" }",
 * > 		"CMB" : "_json: { \"1\": \"China Zheshang Bank\", \"2\": \"招商银行\" }",
 * > 		"ICBC" : "_json: { \"1\": \"Industrial and Commercial Bank(ICBC)\", \"2\": \"中国工商银行\" }",
 * > 		"BOC" : "_json: { \"1\": \"Bank of China(BOC)\", \"2\": \"中国银行\" }",
 * > 		"ABC" : "_json: { \"1\": \"Agricultural Bank of China\", \"2\": \"中国农业银行\" }",
 * > 		"BOCOM" : "_json: { \"1\": \"Bank of Communications\", \"2\": \"交通银行\" }",
 * > 		"CMBC" : "_json: { \"1\": \"Minsheng Bank\", \"2\": \"中国民生银行\" }",
 * > 		"HXBC" : "_json: { \"1\": \"Huaxia Bank\", \"2\": \"华夏银行\" }",
 * > 		"CIB" : "_json: { \"1\": \"Industrial Bank\", \"2\": \"兴业银行\" }",
 * > 		"SPDB" : "_json: { \"1\": \"SPK Bank\", \"2\": \"上海浦东发展银行\" }",
 * > 		"GDB" : "_json: { \"1\": \"Guangdong Development Bank (GDB)\", \"2\": \"广东发展银行\" }",
 * > 		"CITIC" : "_json: { \"1\": \"CITIC Bank\", \"2\": \"中信银行\" }",
 * > 		"CEB" : "_json: { \"1\": \"China Everbright Bank\", \"2\": \"光大银行\" }",
 * > 		"PSBC" : "_json: { \"1\": \"Postal Savings Bank of China\", \"2\": \"中国邮政储蓄银行\" }",
 * > 		"BOS" : "_json: { \"1\": \"Bank of Shanghai\", \"2\": \"上海银行\" }",
 * > 		"PAB" : "_json: { \"1\": \"Ping An Bank\", \"2\": \"平安银行\" }",
 * > 		"NJCB" : "_json: { \"1\": \"Bank of Nanjing\", \"2\": \"南京银行\" }",
 * > 		"BOBJ" : "_json: { \"1\": \"Bank Of Beijing\", \"2\": \"北京银行\" }"
 * > 	}
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gopay extends Abstract_payment_api {

	const VERSION = '2.1';
	const CHARSET = '2';
	const DEFAULT_LANGUAGE = '1';
	//1=md5
	const SIGN_TYPE = '1';
	const TRAN_CODE = '8888';
	const DEFAULT_CURRENCY = '156';
	//person
	const DEFAULT_USER_TYPE = '1';

	const SUCCESS_CODE = '0000';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	/**
	 * detail: get the platform code from the constant
	 *
	 * @return string
	 */
	public function getPlatformCode() {
		return GOPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'gopay';
	}

	public function getName() {
		return 'GOPAY';
	}

	//====implements Payment_api_interface start===================================

	/**
	 * detail: generate the payment form
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

		$amountNum = $amount;
		// $amount = $this->convertAmountToCurrency($amount);
		$info = $this->getInfoByEnv();
		$this->CI->utils->debug_log('info', $info);
		if ($this->shouldRedirect($enabledSecondUrl)) {
			//disable second url
			$url = $this->CI->utils->getPaymentUrl($info['second_url'], $this->getPlatformCode(), $amountNum, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$this->CI->load->model(array('bank_list'));
		$bankCode = $this->CI->bank_list->getBankShortCodeById($bankId);
		$this->CI->utils->debug_log('bankId', $bankId, 'bankCode', $bankCode);

		$merchantUrl = $this->getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
		// $failedUrl = site_url('/callback/browser/failed/' . $this->getPlatformCode() . '/' . $orderId);
		// $errUrl = site_url('/callback/browser/error/' . $this->getPlatformCode());
		$callbackUrl = $this->getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);

		$merOrderNum = $orderId;
		if ($orderId) {
			$ord = $this->CI->sale_order->getSaleOrderById($orderId);
			$merOrderNum = $ord->secure_id;
		}

		//YYYYMMDDHHMMSS
		$tranDateTime = date('YmdHis');
		//can't repeat
		$isRepeatSubmit = '0';
		//$info['key']=merchantID
		$params = array(
			'version' => self::VERSION, 'charset' => self::CHARSET, 'language' => self::DEFAULT_LANGUAGE,
			'signType' => self::SIGN_TYPE, 'tranCode' => self::TRAN_CODE, 'merchantID' => $info['key'],
			'merOrderNum' => $merOrderNum, 'tranAmt' => $amountNum, 'currencyType' => self::DEFAULT_CURRENCY,
			'frontMerUrl' => $merchantUrl, 'backgroundMerUrl' => $callbackUrl, 'tranDateTime' => $tranDateTime,
			'virCardNoIn' => $info['account'], 'tranIP' => $this->getClientIP(), 'isRepeatSubmit' => $isRepeatSubmit,
		);

		if (!empty($bankCode)) {
			$params['bankCode'] = $bankCode;
			$params['userType'] = self::DEFAULT_USER_TYPE;
		}

		$params['gopayServerTime'] = $this->getGopayServerTime();
		$params['signValue'] = $this->createSign($params, $info);

		// $orge = 'billno'.$Billno.'currencytype'.$Currency_Type.'amount'.$Amount.'date'.$Date.'orderencodetype'.$OrderEncodeType.$Mer_key ;
		// string SignMD5 = System.Web.Security.FormsAuthentication.HashPasswordForStoringInConfigFile(
		// "billno" + Billno + "currencytype" + Currency_Type + "amount" + Amount + "date" + BillDate + "orderencodetype" + OrderEncodeType + Mer_key, "MD5").ToLower();
		// $org = 'billno' . $orderId . 'currencytype' . $curr . 'amount' . $amount . 'date' . $orderDate . 'orderencodetype' . $orderEncodeType . $info['secret'];
		// log_message('debug', 'org:' . $org);
		// $signMD5 = $this->getSignMD5($org);
		// $params['SignMD5'] = $signMD5;
		$result = array('success' => true, 'type' => self::REDIRECT_TYPE_FORM, 'url' => $info['url'], 'params' => $params, 'post' => true);
		return $result;
	}

	/**
	 * detail:
	 *
	 * @param array $params
	 * @param array $info
	 * @return string
	 */
	public function createSign($params, $info) {
		$msg = "version=[" . $params['version'] . "]tranCode=[" . $params['tranCode'] . "]merchantID=[" . $params['merchantID'] .
			"]merOrderNum=[" . $params['merOrderNum'] . "]tranAmt=[" . $params['tranAmt'] . "]feeAmt=[]tranDateTime=[" . $params['tranDateTime'] .
			"]frontMerUrl=[" . $params['frontMerUrl'] . "]backgroundMerUrl=[" . $params['backgroundMerUrl'] .
			"]orderId=[]gopayOutOrderId=[]tranIP=[" . $params['tranIP'] . "]respCode=[]gopayServerTime=[" . $params['gopayServerTime'] .
			"]VerficationCode=[" . $info['secret'] . "]";
		$this->CI->utils->debug_log('createSign', $msg);
		return strtolower(md5($msg));
	}

	/**
	 * detail:
	 *
	 * @param array $params
	 * @param array $info
	 * @return string
	 */
	public function createCallbackSign($params, $info) {
		$msg = "version=[" . $params['version'] . "]tranCode=[" . $params['tranCode'] . "]merchantID=[" . $params['merchantID'] .
			"]merOrderNum=[" . $params['merOrderNum'] . "]tranAmt=[" . $params['tranAmt'] . "]feeAmt=[" . $params['feeAmt'] .
			"]tranDateTime=[" . $params['tranDateTime'] . "]frontMerUrl=[" . $params['frontMerUrl'] . "]backgroundMerUrl=[" . $params['backgroundMerUrl'] .
			"]orderId=[" . $params['orderId'] . "]gopayOutOrderId=[" . $params['gopayOutOrderId'] . "]tranIP=[" . $params['tranIP'] .
			"]respCode=[" . $params['respCode'] . "]gopayServerTime=[]VerficationCode=[" . $info['secret'] . "]";

		$this->CI->utils->debug_log('createCallbackSign', $msg);
		return strtolower(md5($msg));
	}

	/**
	 * detail: get the server time from the provider
	 *
	 * @return array
	 */
	public function getGopayServerTime() {
		$url = 'https://www.gopay.com.cn/PGServer/time';
		return $this->CI->utils->getUrlContent($url);
	}

	/**
	 * detail: callback from server
	 *
	 * @param int $orderId order id
	 * @param array $callbackExtraInfo
	 * @return array
	 */
	public function callbackFromServer($orderId, $callbackExtraInfo) {
		// $this->CI->load->library('promo_library');
		//must call
		$response_result_id = parent::callbackFromServer($orderId, $callbackExtraInfo);

		$merchantUrl = $this->getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
		$rlt = array('success' => false, 'next_url' => null, 'message' => 'RespCode=9999|JumpURL=' . $merchantUrl);
		//query order
		$ord = $this->CI->sale_order->getSaleOrderById($orderId);
		if ($ord) {
			if ($this->checkCallbackOrder($ord, $callbackExtraInfo)) {
				$success = true;
				$extra_info=null;

				// $this->CI->sale_order->startTrans();
				//save to player balance
				//check order status, if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
				$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
				if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
					$this->CI->utils->debug_log('callbackFromServer, already get callback for order:' . $ord->id, $callbackExtraInfo);
					if ($ord->status == Sale_order::STATUS_BROWSER_CALLBACK) {
						$this->CI->sale_order->setStatusToSettled($orderId);
					}
				} else {
					// $this->CI->sale_order->setStatusToSettled($orderId);
					//update balance once
					$this->CI->sale_order->updateExternalInfo($ord->id, @$callbackExtraInfo['orderId'], @$callbackExtraInfo['gopayOutOrderId'], null, null, $response_result_id);
					$success = $this->approveSaleOrder($ord->id, 'auto server callback ' . $this->getPlatformCode(), false, $extra_info);

					// $ord->external_order_id = $callbackExtraInfo['ipsbillno'];
					// $ord->response_result_id = $response_result_id;
					// $transaction = $this->saveToPlayerBalance($ord);

					// # APPLY PROMO IF PLAYER PROMO ID IS PASSED
					// $promo_transaction = isset($ord->player_promo_id) ? $this->CI->promo_library->approvePromo($ord->player_promo_id) : null;

					// //update sale order
					// $this->CI->sale_order->updateExternalInfo($ord->id, $callbackExtraInfo['ipsbillno'], $callbackExtraInfo['bankbillno'], null, null, $response_result_id);
				}
				// $rlt['message'] = $this->CI->load->view('payment/ips/success', ['transaction' => $transaction, 'callbackExtraInfo' => $callbackExtraInfo, 'promo' => $promo_transaction], true);
				// $success = $this->CI->sale_order->endTransWithSucc();

				$this->processStandaloneTrans($extra_info);

				$rlt['success'] = $success;
				$rlt['next_url'] = $this->getPlayerBackUrl();
				if ($success) {
					//9999 is failed
					$rlt['message'] = 'RespCode=0000|JumpURL=' . $merchantUrl;
				}
			}
		}
		return $rlt;
	}

	/**
	 * detail: call from the browser
	 *
	 * @param int $orderId order id
	 * @param array $callbackExtraInfo
	 * @return array
	 */
	public function callbackFromBrowser($orderId, $callbackExtraInfo) {
		// $this->CI->load->library('promo_library');
		//must call
		$response_result_id = parent::callbackFromBrowser($orderId, $callbackExtraInfo);

		$rlt = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		//query order
		$ord = $this->CI->sale_order->getSaleOrderById($orderId);
		if ($ord) {

			if ($this->checkCallbackOrder($ord, $callbackExtraInfo)) {

				$success = true;

				// $this->CI->sale_order->startTrans();

				$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
				//save to player balance
				//check order status, if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
				if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
					$this->CI->utils->debug_log('callbackFromBrowser, already get callback for order:' . $ord->id, $callbackExtraInfo);
				} else {
					// $this->CI->sale_order->setStatusToBrowserCallback($orderId);
					// //update balance once
					// $ord->external_order_id = @$callbackExtraInfo['orderId'];
					// $ord->response_result_id = $response_result_id;
					// $this->saveToPlayerBalance($ord);

					# APPLY PROMO IF PLAYER PROMO ID IS PASSED
					// $promo_transaction = isset($ord->player_promo_id) ? $this->CI->promo_library->approvePromo($ord->player_promo_id) : null;

					//update sale order
					$this->CI->sale_order->updateExternalInfo($ord->id, @$callbackExtraInfo['orderId'], @$callbackExtraInfo['gopayOutOrderId'], null, null, $response_result_id);
					$success = $this->CI->sale_order->browserCallbackSaleOrder($ord->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
				}

				// $this->CI->sale_order->endTrans();

				// $success = $this->CI->sale_order->endTransWithSucc();

				// $rlt['message'] = $this->CI->load->view('payment/ips/success', ['transaction' => $transaction, 'callbackExtraInfo' => $callbackExtraInfo, 'promo' => $promo_transaction], true);
				$rlt['success'] = $success;
				$rlt['next_url'] = $this->getPlayerBackUrl();
			}
		}
		return $rlt;
	}

	//====implements Payment_api_interface end===================================

	/**
	 * detail: checking the callback orders
	 *
	 * @param string $ord
	 * @param array $flds
	 * @return array
	 */
	private function checkCallbackOrder($ord, $flds) {
		//check respCode first
		$success = @$flds['respCode'] == self::SUCCESS_CODE;

		if (!$success) {
			$this->writePaymentErrorLog('respCode is not ' . self::SUCCESS_CODE, $flds);
		}

		$info = $this->getInfoByEnv();

		if ($success) {
			//check signature
			// $signStr = 'billno' . $flds['billno'] . 'currencytype' . $flds['Currency_type'] . 'amount' . $flds['amount'] . 'date' . $flds['date'] . 'succ' . $flds['succ'] . 'ipsbillno' . $flds['ipsbillno'] . 'retencodetype' . $flds['retencodetype'] . $info['secret'];
			// $signature = $flds['signature'];
			// log_message('debug', 'signStr:' . $signStr . ' md5:' . $signature . ' new md5:' . $this->getSignMD5($signStr));
			$signature = $flds['signValue'];

			$success = $this->createCallbackSign($flds, $info) == $signature;
			if (!$success) {
				$this->writePaymentErrorLog('signaure is wrong', $flds);
			}
		}
		if ($success) {
			//check amount, order id, mercode
			$success = $ord->amount == $this->CI->utils->roundCurrencyForShow(floatval($flds['tranAmt']));
			if ($success) {
				$success = $ord->secure_id == $flds['merOrderNum'];
				if ($success) {
					$success = $info['key'] == $flds['merchantID'];
					if ($success) {
					} else {
						$this->writePaymentErrorLog('merchantID is wrong', $flds);
					}
				} else {
					$this->writePaymentErrorLog('order id is wrong', $flds);
				}

			} else {
				$this->writePaymentErrorLog('amount is wrong', $flds);
			}
		}
		return $success;
	}

	/**
	 * detail: result for direct payment
	 *
	 * @param string $order
	 * @return array
	 */
	public function directPay($order) {
		//no direct pay
		return array('success' => false);
	}

	/* Extra info bank list
	"bank_list": {
		"CCB" : "_json: { \"1\": \"China Construction Bank\", \"2\": \"中国建设银行\" }",
		"CMB" : "_json: { \"1\": \"China Zheshang Bank\", \"2\": \"招商银行\" }",
		"ICBC" : "_json: { \"1\": \"Industrial and Commercial Bank(ICBC)\", \"2\": \"中国工商银行\" }",
		"BOC" : "_json: { \"1\": \"Bank of China(BOC)\", \"2\": \"中国银行\" }",
		"ABC" : "_json: { \"1\": \"Agricultural Bank of China\", \"2\": \"中国农业银行\" }",
		"BOCOM" : "_json: { \"1\": \"Bank of Communications\", \"2\": \"交通银行\" }",
		"CMBC" : "_json: { \"1\": \"Minsheng Bank\", \"2\": \"中国民生银行\" }",
		"HXBC" : "_json: { \"1\": \"Huaxia Bank\", \"2\": \"华夏银行\" }",
		"CIB" : "_json: { \"1\": \"Industrial Bank\", \"2\": \"兴业银行\" }",
		"SPDB" : "_json: { \"1\": \"SPK Bank\", \"2\": \"上海浦东发展银行\" }",
		"GDB" : "_json: { \"1\": \"Guangdong Development Bank (GDB)\", \"2\": \"广东发展银行\" }",
		"CITIC" : "_json: { \"1\": \"CITIC Bank\", \"2\": \"中信银行\" }",
		"CEB" : "_json: { \"1\": \"China Everbright Bank\", \"2\": \"光大银行\" }",
		"PSBC" : "_json: { \"1\": \"Postal Savings Bank of China\", \"2\": \"中国邮政储蓄银行\" }",
		"BOS" : "_json: { \"1\": \"Bank of Shanghai\", \"2\": \"上海银行\" }",
		"PAB" : "_json: { \"1\": \"Ping An Bank\", \"2\": \"平安银行\" }",
		"NJCB" : "_json: { \"1\": \"Bank of Nanjing\", \"2\": \"南京银行\" }",
		"BOBJ" : "_json: { \"1\": \"Bank Of Beijing\", \"2\": \"北京银行\" }"
	}

removed
		"TCCB" : "_json: { \"1\": \"Tianjing Bank\", \"2\": \"天津银行\" }",

	*/
}

////END OF FILE//////////////////
