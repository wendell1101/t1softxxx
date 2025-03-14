<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * LEFU 乐富
 * http://lefu8.com/
 *
 * LEFU_PAYMENT_API, ID: 18
 *
 * Required Fields:
 *
 * * URL
 * * Key - partner code
 * * Secret - secret key
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * Sandbox URL: http://qa.lefu8.com/gateway/trade.htm
 * * Extra Info:
 * > {
 * >     "check_white_ip": false,
 * >     "prefix_for_username": "",
 * >     "balance_in_game_log": "",
 * >     "adjust_datetime_minutes": ""
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_lefu extends Abstract_payment_api {

	public function __construct($params = null) {
		parent::__construct($params);
	}

	public function getPlatformCode() {
		return LEFU_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lefu';
	}

	public function getName() {
		return 'LEFU';
	}

	const CALLBACK_FIELD_ORDER_ID = 'outOrderId';
	const CALLBACK_FIELD_RESULT_CODE = 'handlerResult';
	const CALLBACK_FIELD_AMOUNT = 'amount';
	const CALLBACK_FIELD_MERCHANT_CODE = 'partner';
	const CALLBACK_FIELD_SIGNAURE = 'sign';
	const CALLBACK_FIELD_EXTERNAL_ORDER_ID = 'tradeOrderCode';
	const CALLBACK_FIELD_BANK_ORDER_ID = 'tradeOrderCode';

	const CALLBACK_INFO_FIELD_MERCHANT = 'key';

	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const RETURN_FAILED_CODE = 'fail';
	//sold
	const SUCCESS_CODE_LIST = array('0000');

	const REQUEST_API_CODE = 'directPay';
	const REQUEST_VERSION_CODE = '1.0';
	const REQUEST_INPUT_CHARSET = 'UTF-8';
	const REQUEST_SIGN_TYPE = 'MD5';
	const REQUEST_PAYMENT_TYPE = 'ALL';
	const REQUEST_RETRY_FLAG = 'FALSE'; //retryFalg
	const REQUEST_TIMEOUT = '2D';

	const WHITE_IP = array('116.213.177.139');

	//====implements Payment_api_interface start===================================
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {

		$amountNum = $amount;
		// $amount = $this->convertAmountToCurrency($amount);
		$info = $this->getInfoByEnv();
		// $this->CI->utils->debug_log('info', $info);
		if ($this->shouldRedirect($enabledSecondUrl)) {
			//disable second url
			$url = $this->CI->utils->getPaymentUrl($info['second_url'], $this->getPlatformCode(), $amountNum, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		// $bankCode = $bankId;
		// $this->CI->load->model(array('bank_list'));
		// $bankCode = $this->CI->bank_list->getBankShortCodeById($bankId);
		// $this->CI->utils->debug_log('bankId', $bankId, 'bankCode', $bankCode);

		$browserUrl = $this->getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
		// $failedUrl = site_url('/callback/browser/failed/' . $this->getPlatformCode() . '/' . $orderId);
		// $errUrl = site_url('/callback/browser/error/' . $this->getPlatformCode());
		$callbackUrl = $this->getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);

		//yyyyMMddHHmmss
		$tranDateTime = date('YmdHis');
		//can't repeat
		// $isRepeatSubmit = '0';
		//$info['key']=merchantID
		$secret = $info['secret'];
		$now = $this->getTradeDateNow();

		$ord = $this->CI->sale_order->getSaleOrderById($orderId);

		$direct_pay_extra_info = $ord->direct_pay_extra_info;
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bankCode = $extraInfo['banktype_level_second'];
			}
		}

		$secureId = $ord->secure_id;

		$amount = $this->convertAmountToCurrency($amount);
		$ip = $this->getClientIP();

		$params = array(
			'partner' => $info['key'],
			'apiCode' => self::REQUEST_API_CODE,
			'versionCode' => self::REQUEST_VERSION_CODE,
			'inputCharset' => self::REQUEST_INPUT_CHARSET,
			'signType' => self::REQUEST_SIGN_TYPE,
			'buyer' => $playerId,
			'buyerContactType' => 'email',
			'buyerContact' => $playerId . '@buyer.com',
			'outOrderId' => $secureId,
			'amount' => $amount,
			'paymentType' => self::REQUEST_PAYMENT_TYPE,
			'retryFalg' => self::REQUEST_RETRY_FLAG,
			'submitTime' => $tranDateTime,
			'timeout' => self::REQUEST_TIMEOUT,
			'clientIP' => $ip,
			'redirectURL' => $browserUrl,
			'notifyURL' => $callbackUrl,
		);
		if (!empty($bankCode)) {
			$params['interfaceCode'] = $bankCode;
		}
		$params['sign'] = $this->createSign($params, $info);

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

	public function getPlayerInputInfo() {
		// $bankTree = $this->getBankListTree();
		// $bankList = $this->getBankList();
		// if ($this->CI->utils->getConfig('enable_bank_box_for_deposit')) {
		// 	return array(
		// 		array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		// 		array('name' => 'bank_list', 'type' => 'bank_box', 'label_lang' => 'cashier.81',
		// 			'external_system_id' => $this->getPlatformCode(),
		// 			'bank_tree' => $bankTree, 'bank_list' => $bankList),
		// 	);

		// } else {

		// 	return array(
		// 		array('name' => 'bank_list', 'type' => 'bank_list', 'label_lang' => 'cashier.81',
		// 			'external_system_id' => $this->getPlatformCode(),
		// 			'bank_tree' => $bankTree, 'bank_list' => $bankList),
		// 		array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		// 	);
		// }
		return array(
			array('name' => 'banktype', 'type' => 'list', 'label_lang' => 'pay.bank',
				'list' => $this->getBankList(), 'list_tree' => $this->getBankListTree()),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
		// return round($amount * 100);
	}

	protected function getTradeDateNow() {
		$d = new DateTime();
		return $d->format('YmdHis');
	}

	public function createSign($params, $info) {

		$msg = '';
		// $keys = array_keys($params);
		// sort($keys);
		// foreach ($keys as $key) {
		// 	if (!empty($key) && !empty($params[$key])) {
		// 		$msg .= $key . '=' . $params[$key] . '&';
		// 	}
		// }
		ksort($params);
		reset($params);
		foreach ($params as $key => $val) {
			if (!empty($key) && !empty($val) && $key != self::CALLBACK_FIELD_SIGNAURE) {
				$msg .= $key . '=' . $val . '&';
			}
		}
		//remove last &
		$msg = substr($msg, 0, strlen($msg) - 1) . $info['secret'];

		$this->CI->utils->debug_log('createSign', $msg);
		//should be upper
		return strtoupper(md5($msg));
	}

	public function createCallbackSign($params, $info) {
		$msg = '';
		// $keys =
		ksort($params);
		reset($params);
		foreach ($params as $key => $val) {
			if (!empty($key) && !empty($val) && $key != self::CALLBACK_FIELD_SIGNAURE) {
				$msg .= $key . '=' . $val . '&';
			}
		}
		//remove last &
		$msg = substr($msg, 0, strlen($msg) - 1) . $info['secret'];

		$this->CI->utils->debug_log('createCallbackSign', $msg);
		return strtoupper(md5($msg));
	}

	public function callbackFromServer($orderId, $callbackExtraInfo) {
		// $this->CI->load->library('promo_library');
		//must call
		$response_result_id = parent::callbackFromServer($orderId, $callbackExtraInfo);

		// $merchantUrl = site_url('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
		$rlt = array('success' => false, 'next_url' => null, 'message' => 'failed');
		//query order
		$ord = $this->CI->sale_order->getSaleOrderById($orderId);
		if ($ord) {
			$processed = false;
			if ($this->checkCallbackOrder($ord, $callbackExtraInfo, $processed)) {
				$success = true;
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
					$this->CI->sale_order->updateExternalInfo($ord->id, @$callbackExtraInfo['orderId'], @$callbackExtraInfo['SuccTime'], null, null, $response_result_id);
					$this->CI->sale_order->approveSaleOrder($ord->id, 'auto server callback ' . $this->getPlatformCode(), false);
				}

				// $success = $this->CI->sale_order->endTransWithSucc();

				// $rlt['message'] = $this->CI->load->view('payment/ips/success', ['transaction' => $transaction, 'callbackExtraInfo' => $callbackExtraInfo, 'promo' => $promo_transaction], true);
				$rlt['success'] = $success;
				// $rlt['next_url'] = $this->getPlayerBackUrl();
				if ($success) {
					$rlt['message'] = self::RETURN_SUCCESS_CODE;

				} else {
					if ($processed) {
						$rlt['return_error'] = self::RETURN_SUCCESS_CODE;
					} else {
						$rlt['return_error'] = self::RETURN_FAILED_CODE;
					}
				}
				//9999 is failed
				// $rlt['message'] = 'RespCode=0000|JumpURL=' . $merchantUrl;
			}
		}
		return $rlt;
	}

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
					//update sale order
					$this->CI->sale_order->updateExternalInfo($ord->id, @$callbackExtraInfo['orderId'], @$callbackExtraInfo['SuccTime'], null, null, $response_result_id);
					$success = $this->CI->sale_order->browserCallbackSaleOrder($ord->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
				}

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
	 *
	 *
	 *
	 * @return array (success=>boolean, message=>string)
	 */
	private function checkCallbackOrder($ord, $flds, &$processed = false) {
		$info = $this->getInfoByEnv();

		//check white IP
		$ip = $this->CI->utils->getIP();
		$isWhiteIP = in_array($ip, self::WHITE_IP);
		if ($this->getValueFromApiConfig('check_white_ip') && !$isWhiteIP) {
			$msg = $this->CI->utils->debug_log('white ip is wrong', $ip, 'white ip', self::WHITE_IP, $flds);
			$this->writePaymentErrorLog($msg, $flds);
			return false;
		}

		//check respCode first
		// if ($success) {
		$success = false;
		if (isset($flds[self::CALLBACK_FIELD_SIGNAURE])) {

			$signature = @$flds[self::CALLBACK_FIELD_SIGNAURE];

			$callbackSign = $this->createCallbackSign($flds, $info);
			$success = strtolower($callbackSign) == strtolower($signature);
			if (!$success) {
				$msg = $this->CI->utils->debug_log('signaure is wrong', $flds, 'callbackSign', $callbackSign);
				$this->writePaymentErrorLog($msg, $flds);
			}
			// }

			$processed = $success;
		}

		if ($success) {
			$success = in_array(@$flds[self::CALLBACK_FIELD_RESULT_CODE], self::SUCCESS_CODE_LIST);
			if (!$success) {
				$this->writePaymentErrorLog('respCode is not ', self::SUCCESS_CODE_LIST, $flds);
				// } else {
				// 	$success = @$flds[self::CALLBACK_FIELD_STATUS] == self::SUCCESS_STATUS;
			}
		}

		if ($success) {
			//check amount, order id, mercode
			if (isset($flds[self::CALLBACK_FIELD_AMOUNT])) {
				$success = $this->convertAmountToCurrency($ord->amount) ==
				$this->convertAmountToCurrency(floatval($flds[self::CALLBACK_FIELD_AMOUNT]));
			}
			if ($success) {
				$success = $ord->secure_id == $flds[self::CALLBACK_FIELD_ORDER_ID];
				if ($success) {
					$success = $info[self::CALLBACK_INFO_FIELD_MERCHANT] == $flds[self::CALLBACK_FIELD_MERCHANT_CODE];
					if ($success) {
					} else {
						$this->writePaymentErrorLog('merchant code is wrong', $flds);
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

	public function directPay($order) {
		//no direct pay
		return array('success' => false);
	}

	public function getBankList() {
		//create from list tree
		$list = array();
		$bankListInfo = $this->getBankListInfo();
		foreach ($bankListInfo as $bankInfo) {
			$list[$bankInfo['value']] = $bankInfo['label'];
		}
		return $list;
	}

	public function getBankListTree() {
		$tree = array();
		$bankListInfo = $this->getBankListInfo();
		foreach ($bankListInfo as $bankInfo) {
			$subList = array();
			foreach ($bankInfo['sub_list'] as $val => $label) {
				$subList[] = array('value' => $val, 'label' => $label);
			}
			$tree[$bankInfo['value']] = $subList;
		}
		return $tree;
	}

	public function getBankListInfo() {
		// $this->CI->load->library(array('language_function'));
		// $langCode = 'bank';
		// $lang = $this->CI->language_function->getCurrentLanguage();
		// $language = $this->language_function->getLanguage($lang);
		// $this->lang->load($langCode, $language);

		return array(
			array(
				'label' => '个人网银借记卡支付', 'value' => 'B2C_DEBIT',
				'sub_list' => array(
					'B2C_ABC-DEBIT_CARD' => '农业银行',
					'B2C_BOC-DEBIT_CARD' => '中国银行',
					'B2C_CCB-DEBIT_CARD' => '建设银行',
					'B2C_CEB-DEBIT_CARD' => '光大银行',
					'B2C_ICBC-DEBIT_CARD' => '工商银行',
					'B2C_PSBC-DEBIT_CARD' => '邮政储蓄银行',
					'B2C_BCM-DEBIT_CARD' => '交通银行',
					'B2C_BOB-DEBIT_CARD' => '北京银行',
					'B2C_CGB-DEBIT_CARD' => '广发银行',
					'B2C_CMB-DEBIT_CARD' => '招商银行',
					'B2C_CMBC-DEBIT_CARD' => '民生银行',
					'B2C_HXB-DEBIT_CARD' => '华夏银行',
					'B2C_NBCB-DEBIT_CARD' => '宁波银行',
					'B2C_NJCB-DEBIT_CARD' => '南京银行',
					'B2C_PAB-DEBIT_CARD' => '平安银行',
					'B2C_SPDB-DEBIT_CARD' => '浦发银行',
					'B2C_BOS-DEBIT_CARD' => '上海银行',
					'B2C_CDB-DEBIT_CARD' => '成都银行',
					'B2C_CIB-DEBIT_CARD' => '兴业银行',
				),
			),
			array(
				'label' => '个人网银贷记卡支付', 'value' => 'B2C_CREDIT_CARD',
				'sub_list' => array(
					'B2C_ABC-CREDIT_CARD' => '农业银行',
					'B2C_BOC-CREDIT_CARD' => '中国银行',
					'B2C_CCB-CREDIT_CARD' => '建设银行',
					'B2C_CEB-CREDIT_CARD' => '光大银行',
					'B2C_CNCB-CREDIT_CARD' => '中信银行',
					'B2C_ICBC-CREDIT_CARD' => '工商银行',
					'B2C_PSBC-CREDIT_CARD' => '邮政储蓄银行',
					'B2C_BCM-CREDIT_CARD' => '交通银行',
					'B2C_CGB-CREDIT_CARD' => '广发银行',
					'B2C_CIB-CREDIT_CARD' => '兴业银行',
					'B2C_CMB-CREDIT_CARD' => '招商银行',
					'B2C_CMBC-CREDIT_CARD' => '民生银行',
					'B2C_PAB-CREDIT_CARD' => '平安银行',
					'B2C_SPDB-CREDIT_CARD' => '浦发银行',
					'B2C_BOS-CREDIT_CARD' => '上海银行',
					'B2C_HXB-CREDIT_CARD' => '华夏银行',
					'B2C_HCCB-CREDIT_CARD' => '杭州银行',
					'B2C_NBCB-CREDIT_CARD' => '宁波银行',
					'B2C_QDB-CREDIT_CARD' => '青岛银行',
				),
			),
			array(
				'label' => '企业网银支付', 'value' => 'B2B',
				'sub_list' => array(
					'B2B_ABC' => '农业银行',
					'B2B_BOC' => '中国银行',
					'B2B_CCB' => '建设银行',
					'B2B_CEB' => '光大银行',
					'B2B_CNCB' => '中信银行',
					'B2B_ICBC' => '工商银行',
					'B2B_PSBC' => '邮政储蓄银行',
					'B2B_BCM' => '交通银行',
					'B2B_BEA' => '东亚银行',
					'B2B_BOB' => '北京银行',
					'B2B_BOHB' => '河北银行',
					'B2B_CGB' => '广发银行',
					'B2B_CIB' => '兴业银行',
				),
			),
			array(
				'label' => '微信二维码支付', 'value' => 'WALLET_TENCENT_QRCODE',
				'sub_list' => array(
					'WALLET_TENCENT_QRCODE' => '微信二维码',
				),
			),
		);
	}

}

////END OF FILE//////////////////