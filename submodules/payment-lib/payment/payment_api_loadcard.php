<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * LOADCARD (36kgj)
 * http://www.36kgj.com/mservice/login.action
 *
 * LOADCARD_PAYMENT_API, ID: 17
 *
 * Required Fields:
 *
 * * URL
 * * Key - merchantNo
 * * Secret - signKey
 *
 *
 * Field Values:
 *
 * * URL: http://api.36kgj.com/trx-service/card.action
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_loadcard extends Abstract_payment_api {

	public function __construct($params = null) {
		parent::__construct($params);
	}

	const CALLBACK_FIELD_ORDER_ID = 'b1_orderNumber';
	const CALLBACK_FIELD_RESULT_CODE = 'b4_returnCode';
	const CALLBACK_FIELD_STATUS = 'b3_status';
	const CALLBACK_FIELD_AMOUNT = 'b2_successAmount';
	const CALLBACK_FIELD_MERCHANT_CODE = 'merchantNo';
	const CALLBACK_FIELD_SIGNAURE = 'sign';
	const CALLBACK_FIELD_EXTERNAL_ORDER_ID = 'b8_serialNumber';
	const CALLBACK_FIELD_BANK_ORDER_ID = 'b8_serialNumber';
	// const CALLBACK_FIELD_TYPE = 'r9_BType';

	// const CALLBACK_TYPE_BROWSER = '1';
	// const CALLBACK_TYPE_SERVER = '2';

	const CALLBACK_INFO_FIELD_MERCHANT = 'key';
	//accepted
	const SUCCESS_ACCEPTED_CODE = '800000';
	//sold
	const SUCCESS_CODE_LIST = array('80808888', '808888');
	const SUCCESS_STATUS = 'SUCCESS';

	// const DEFAULT_CURRENCY = 'CNY';

	// const DEFAULT_LANG = 'zh-cn';

	public function getPlatformCode() {
		return LOADCARD_PAYMENT_API;
	}

	public function getPrefix() {
		return 'loadcard';
	}

	public function getName() {
		return 'LOADCARD';
	}

	//====implements Payment_api_interface start===================================
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {

		return array('success' => true, 'type' => Abstract_payment_api::REDIRECT_TYPE_DIRECT_PAY);
	}

	public function createSign($params, $info) {

		$msg = '#' . $params['trxType'] . '#' . $params['r1_orderNumber'] . '#' . $params['r2_amount'] .
			'#' . $params['r3_cardNo'] . '#' . $params['r4_cardPwd'] . '#' . $params['r5_cardType'] .
			'#' . $params['r6_callbackUrl'] . '#' . $params['r7_orderIp'] . '#' . $params['r8_desc'] .
			'#' . $params['r9_encrypt'] . '#' . $params['timestamp'] . '#' . $params['merchantNo'] .
			'#' . $info['secret'];

		$this->CI->utils->debug_log('createSign', $msg);
		return strtolower(md5($msg));
	}

	public function createCallbackSign($params, $info) {

		$msg = '#' . $params['b1_orderNumber'] . '#' . $params['b2_successAmount'] .
			'#' . $params['b3_status'] . '#' . $params['b4_returnCode'] . '#' . $params['b5_returnMsg'] .
			'#' . $params['b6_completeTime'] . '#' . $params['b7_desc'] .
			'#' . $params['b8_serialNumber'] . '#' . $params['merchantNo'] . '#' . $params['timestamp'] .
			'#' . $info['secret'];

		$this->CI->utils->debug_log('createCallbackSign', $msg);
		return strtolower(md5($msg));

	}

	public function processOrder($ord) {
		$success = true;
		$extraInfo = $ord->direct_pay_extra_info;
		if (!empty($extraInfo)) {
			$this->CI->load->model(array('sale_order'));
			//get cardtype
			$info = json_decode($extraInfo, true);
			if (isset($info['cardtype'])) {
				$cardInfo = $this->getCardInfoById($info['cardtype']);
				if (!empty($cardInfo)) {
					$feeRate = $cardInfo['fee_rate_percent'];
					$amount = $this->CI->utils->roundCurrencyForShow($ord->amount * (1 - $feeRate / 100));
					$notes = $ord->notes . " remove fee rate percent:" . $feeRate . ",old amount is " . $ord->amount;
					$success = $this->CI->sale_order->fixOrderAmount($ord->id, $amount, $notes);
				}
			}
		}
		return $success;
	}

	public function callbackFromServer($orderId, $callbackExtraInfo) {
		// $this->CI->load->library('promo_library');
		//must call
		$response_result_id = parent::callbackFromServer($orderId, $callbackExtraInfo);

		$this->utils->debug_log('orderId', $orderId, 'callbackExtraInfo', $callbackExtraInfo, 'response_result_id', $response_result_id);

		$merchantUrl = $this->getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
		$rlt = array('success' => false, 'next_url' => null, 'return_error' => 'SUCCESS');
		$this->CI->load->model(array('sale_order'));
		//query order
		$ord = $this->CI->sale_order->getSaleOrderById($orderId);
		if ($ord) {
			$processed = false;
			if ($this->checkCallbackOrder($ord, $callbackExtraInfo, $processed)) {

				$success = true;
				// $this->CI->sale_order->startTrans();
				//save to player balance
				//check order status, if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
				$orderStatus = $ord->status; // $this->CI->sale_order->getSaleOrderStatusById($orderId);
				if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
					$this->CI->utils->debug_log('callbackFromServer, already get callback for order:' . $ord->id, $callbackExtraInfo);
					if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK) {
						$this->CI->sale_order->setStatusToSettled($orderId);
					}
					$success = true;
				} else {

					$success = $this->processOrder($ord);

					// $this->CI->sale_order->setStatusToSettled($orderId);
					//update balance once
					$this->CI->sale_order->updateExternalInfo($ord->id, @$callbackExtraInfo[self::CALLBACK_FIELD_EXTERNAL_ORDER_ID],
						@$callbackExtraInfo[self::CALLBACK_FIELD_BANK_ORDER_ID], null, null, $response_result_id);
					$success = $this->CI->sale_order->approveSaleOrder($ord->id, 'auto server callback ' . $this->getPlatformCode(), false);

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
				$rlt['success'] = $success;
				$rlt['message'] = 'SUCCESS';
				// $rlt['next_url'] = $this->getPlayerBackUrl();
				// if ($success) {
				//9999 is failed
				// $rlt['message'] = 'RespCode=0000|JumpURL=' . $merchantUrl;
				// }
			} else {
				if ($processed) {
					$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
					if ($orderStatus == Sale_order::STATUS_PROCESSING) {
						//set failed
						$this->CI->sale_order->setStatusToDeclined($orderId);
						$this->writePaymentErrorLog('callbackFromServer, setStatusToDeclined', $orderId, $callbackExtraInfo);
					}
				}
			}
		}
		return $rlt;
	}

	public function callbackFromBrowser($orderId, $callbackExtraInfo) {
		// $this->CI->load->library('promo_library');
		//must call
		// $response_result_id = parent::callbackFromBrowser($orderId, $callbackExtraInfo);

		$rlt = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		// //query order
		// $ord = $this->CI->sale_order->getSaleOrderById($orderId);
		// if ($ord) {

		// 	if ($this->checkCallbackOrder($ord, $callbackExtraInfo)) {

		// 		$success = true;

		// 		$this->CI->sale_order->startTrans();

		// 		$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
		// 		//save to player balance
		// 		//check order status, if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
		// 		if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
		// 			$this->writePaymentErrorLog('callbackFromBrowser, already get callback for order:' . $ord->id, $callbackExtraInfo);
		// 		} else {
		// 			// $this->CI->sale_order->setStatusToBrowserCallback($orderId);
		// 			// //update balance once
		// 			// $ord->external_order_id = @$callbackExtraInfo['orderId'];
		// 			// $ord->response_result_id = $response_result_id;
		// 			// $this->saveToPlayerBalance($ord);

		// 			# APPLY PROMO IF PLAYER PROMO ID IS PASSED
		// 			// $promo_transaction = isset($ord->player_promo_id) ? $this->CI->promo_library->approvePromo($ord->player_promo_id) : null;

		// 			//update sale order
		// 			$this->CI->sale_order->updateExternalInfo($ord->id, @$callbackExtraInfo[self::CALLBACK_FIELD_EXTERNAL_ORDER_ID],
		// 				@$callbackExtraInfo[self::CALLBACK_FIELD_BANK_ORDER_ID], null, null, $response_result_id);
		// 			$success = $this->CI->sale_order->browserCallbackSaleOrder($ord->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
		// 		}

		// 		// $this->CI->sale_order->endTrans();

		// 		$success = $this->CI->sale_order->endTransWithSucc();

		// 		// $rlt['message'] = $this->CI->load->view('payment/ips/success', ['transaction' => $transaction, 'callbackExtraInfo' => $callbackExtraInfo, 'promo' => $promo_transaction], true);
		// 		$rlt['success'] = $success;
		// 		$rlt['next_url'] = $this->getPlayerBackUrl();
		// 	}
		// }
		return $rlt;
	}

	//====implements Payment_api_interface end===================================

	/**
	 *
	 *
	 *
	 * @return array (success=>boolean, message=>string)
	 */
	public function checkCallbackOrder($ord, $flds, &$processed = false) {
		$info = $this->getInfoByEnv();

		//check respCode first
		// if ($success) {
		$signature = @$flds[self::CALLBACK_FIELD_SIGNAURE];

		$success = strtolower($this->createCallbackSign($flds, $info)) == strtolower($signature);
		if (!$success) {
			$this->writePaymentErrorLog('signaure is wrong', $flds);
		}
		// }

		$processed = $success;

		$success = in_array(@$flds[self::CALLBACK_FIELD_RESULT_CODE], self::SUCCESS_CODE_LIST);

		if (!$success) {
			$this->writePaymentErrorLog('respCode is not ', self::SUCCESS_CODE_LIST, $flds);
		} else {
			$success = @$flds[self::CALLBACK_FIELD_STATUS] == self::SUCCESS_STATUS;
		}

		if ($success) {
			//check amount, order id, mercode
			if (isset($flds[self::CALLBACK_FIELD_AMOUNT])) {
				$success = $this->convertLocalAmountToPaymentAmount($ord->amount) ==
				$this->convertLocalAmountToPaymentAmount(floatval($flds[self::CALLBACK_FIELD_AMOUNT]));
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

	public function isResponseError($response) {
		$error = parent::isResponseError($response);
		if (!$error) {
			//check json format
			$error = json_decode($response->body) === false;
		}
		return $error;
	}

	public function directPay($order) {
		$this->CI->load->model(array('payment_account'));
		$success = false;
		$next_url = null;
		$message = lang('error.payment.failed');
		// $this->CI->utils->debug_log('order', $order);
		if (!empty($order)) {
			$direct_pay_extra_info = $order->direct_pay_extra_info;
			if (!empty($direct_pay_extra_info)) {
				$extraInfo = json_decode($direct_pay_extra_info, true);
				if (!empty($extraInfo)) {
					$orderId = $order->id;
					$secure_id = $order->secure_id;
					$playerId = $order->player_id;
					$cardType = $extraInfo['cardtype'];
					$cardAmount = $extraInfo['cardtype_level_second'];
					$cardNumber = $extraInfo['cardnumber'];
					$cardPassword = $extraInfo['cardpassword'];

					$info = $this->getInfoByEnv();
					//right card type?
					if (intval($cardAmount) == $order->amount) {

						$params = array(
							'trxType' => 'REQ_SaleCard',
							'r1_orderNumber' => $secure_id,
							'r2_amount' => $order->amount,
							'r3_cardNo' => $cardNumber,
							'r4_cardPwd' => $cardPassword,
							'r5_cardType' => $cardType,
							'r6_callbackUrl' => $this->getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId),
							'r7_orderIp' => $this->CI->utils->getIP(),
							'r8_desc' => $playerId,
							'r9_encrypt' => 'FALSE',
							'timestamp' => $this->getCurrentTimeStamp(),
							'merchantNo' => $info['key'],
						);

						$params['sign'] = $this->createSign($params, $info);

						//call http
						$response = $this->httpCall($orderId, $info['url'], $params);
						if ($response) {
							$response_result_id = $this->logSuccessHttpCall($orderId, $response, $params);
							$this->CI->utils->debug_log('order id', $orderId, 'response_result_id', $response_result_id);
							if (!$this->isResponseError($response)) {
								$rlt = json_decode($response->body, true);
								if ($rlt !== false && isset($rlt['r0_code']) &&
									$rlt['r0_code'] == self::SUCCESS_ACCEPTED_CODE) {
									$success = true;
									$next_url = '/iframe_module/deposit_result/' . $order->id . '/' . Payment_account::FLAG_AUTO_ONLINE_PAYMENT;
									$message = '';
								} else {
									if ($response->body == '[ SIGN IS ERROR ]') {
										$err = 'sign is error';
									} else {
										$err = '';
									}
									$this->CI->utils->debug_log('pay failed', $err, $rlt);
								}

							} else {
								$this->logFailedHttpCall($orderId, $params, 'json format error', $response);
							}
						}
					}
				}
			}
		}
		return array('success' => $success, 'next_url' => $next_url, 'message' => $message);

		// $success = false;
		// $orderId = $order->id;
		// $playerId = $order->player_id;
		// $cardNo = $extra['card_no'];

		// $amount = $this->convertLocalAmountToPaymentAmount($order->amount);
		// // $amountNum = $amount;
		// $info = $this->getInfoByEnv();
		// $this->CI->utils->debug_log('info', $info);
		// if ($this->shouldRedirect($enabledSecondUrl)) {
		// 	//disable second url
		// 	$url = $this->CI->utils->getPaymentUrl($info['second_url'], $this->getPlatformCode(), $amountNum, $playerId, $playerPromoId, false, $bankId, $orderId);
		// 	$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
		// 	return $result;
		// }

		// $this->CI->load->model(array('bank_list'));
		// $bankCode = $this->CI->bank_list->getBankShortCodeById($bankId);
		// $this->CI->utils->debug_log('bankId', $bankId, 'bankCode', $bankCode);

		// $merchantUrl = site_url('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
		// // $failedUrl = site_url('/callback/browser/failed/' . $this->getPlatformCode() . '/' . $orderId);
		// // $errUrl = site_url('/callback/browser/error/' . $this->getPlatformCode());
		// $callbackUrl = site_url('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);

		// $merOrderNum = $orderId;
		// if ($orderId) {
		// 	$ord = $this->CI->sale_order->getSaleOrderById($orderId);
		// 	$merOrderNum = $ord->secure_id;
		// }

		// //YYYYMMDDHHMMSS
		// $tranDateTime = date('Y-m-d h:i:sA');
		// $lang = self::DEFAULT_LANG;
		// //$info['key']=merchantID
		// $params = array(
		// 	'trxType' => 'REQ_SaleCard', 'merchantNo' => $info['key'],
		// 	'r1_orderNumber' => $merOrderNum, 'r2_amount' => $amountNum,
		// 	'r3_cardNo' => $cardNo,
		// 	'TransactionDate' => $tranDateTime,
		// 	'ReturnFrontURL' => $merchantUrl, 'ReturnBackURL' => $callbackUrl, 'ClientIP' => $this->getClientIP(),
		// 	'Language' => $lang,
		// );

		// if (!empty($bankCode)) {
		// 	$params['BankCode'] = $bankCode;
		// }

		// $params['EncryptedSign'] = $this->createSign($params, $info);

		// //no direct pay
		// return array('success' => $success);
	}

	protected function convertLocalAmountToPaymentAmount($amount) {
		// return intval($amount);
		//format number
		return number_format($amount, 2, '.', '');
	}

	public function getPlayerInputInfo() {
		return array(
			array('name' => 'cardtype', 'type' => 'list', 'label_lang' => 'pay.loadcard.type',
				'list' => $this->getCardList(), 'list_tree' => $this->getCardListTree()),
			array('name' => 'cardnumber', 'type' => 'text', 'size' => 50, 'label_lang' => 'pay.loadcard.number'),
			array('name' => 'cardpassword', 'type' => 'text', 'size' => 50, 'label_lang' => 'pay.loadcard.password'),
			array('name' => 'carddesc', 'type' => 'html', 'value' => $this->getCardDesc()),
		);
	}

	public function getAmount($fields) {
		return isset($fields['cardtype_level_second']) ? $fields['cardtype_level_second'] : null;
	}

	public function getCardDesc() {
		$cardListInfo = $this->getCardListInfo();
		$html = '<table class="table table-bordered table-hover table-striped"><tr><td>种类</td><td>费率</td></tr>';

		foreach ($cardListInfo as $cardInfo) {
			if (isset($cardInfo['fee_rate_percent'])) {
				$html .= '<tr><td>' . $cardInfo['label'] . '</td><td>' . $cardInfo['fee_rate_percent'] . '%</td></tr>';
			}
		}
		$html .= '</table>';

		return $html;
	}

	public function getCardList() {
		//create from list tree
		$list = array();
		$cardListInfo = $this->getCardListInfo();
		foreach ($cardListInfo as $cardInfo) {
			$list[$cardInfo['value']] = $cardInfo['label'];
		}
		return $list;
	}

	public function getCardListTree() {
		$min_loadcard_amount = $this->CI->utils->getConfig('min_loadcard_amount');
		$tree = array();
		$cardListInfo = $this->getCardListInfo();
		foreach ($cardListInfo as $cardInfo) {
			$subList = array();
			foreach ($cardInfo['sub_list'] as $sub) {
				if ($sub >= $min_loadcard_amount) {
					$subList[] = array('value' => $sub, 'label' => $sub);
				}
			}
			$tree[$cardInfo['value']] = $subList;
		}
		return $tree;
	}

	public function getCardInfoById($val) {
		$cardListInfo = $this->getCardListInfo();
		foreach ($cardListInfo as $cardInfo) {
			if ($cardInfo['value'] == $val) {
				return $cardInfo;
			}
		}

		return null;
	}

	public function getCardListInfo() {
		// $this->CI->load->library(array('language_function'));
		// $langCode = 'bank';
		// $lang = $this->CI->language_function->getCurrentLanguage();
		// $language = $this->language_function->getLanguage($lang);
		// $this->lang->load($langCode, $language);

		return array(
			array(
				'label' => '移动充值卡', 'value' => 'MOBILE', 'fee_rate_percent' => 5,
				'sub_list' => array(
					10, 20, 30, 50, 100, 200, 300, 500,
				),
			),
			array(
				'label' => '联通充值卡', 'value' => 'UNICOM', 'fee_rate_percent' => 5,
				'sub_list' => array(
					10, 20, 30, 50, 100, 200, 300, 500,
				),
			),
			array(
				'label' => '电信充值卡', 'value' => 'TELECOM', 'fee_rate_percent' => 5,
				'sub_list' => array(
					10, 20, 30, 50, 100, 300,
				),
			),
			array(
				'label' => '盛大游戏卡', 'value' => 'SNDA', 'fee_rate_percent' => 15,
				'sub_list' => array(
					1, 2, 3, 5, 9, 10, 15, 25, 30, 35, 45, 50, 100, 300, 350, 1000,
				),
			),
			array(
				'label' => '骏网卡', 'value' => 'JCARD', 'fee_rate_percent' => 15,
				'sub_list' => array(
					5, 6, 10, 15, 20, 30, 50, 100, 120, 200, 300, 500, 1000,
				),
			),
			array(
				'label' => '盛付通卡', 'value' => 'SFT', 'fee_rate_percent' => 15,
				'sub_list' => array(
					1, 2, 3, 5, 9, 10, 15, 25, 30, 35, 45, 50, 100, 300, 350, 1000,
				),
			),
			array(
				'label' => '网易卡', 'value' => 'WYK', 'fee_rate_percent' => 15,
				'sub_list' => array(
					5, 10, 15, 20, 30, 50,
				),
			),
			array(
				'label' => '完美卡', 'value' => 'WMK', 'fee_rate_percent' => 15,
				'sub_list' => array(
					15, 30, 50, 100,
				),
			),
			array(
				'label' => 'QQ 充值卡', 'value' => 'QQCARD', 'fee_rate_percent' => 15,
				'sub_list' => array(
					5, 10, 15, 30, 60, 100,
				),
			),
			array(
				'label' => '搜狐卡', 'value' => 'SOUHU', 'fee_rate_percent' => 15,
				'sub_list' => array(
					5, 10, 15, 30, 60, 100,
				),
			),
			array(
				'label' => '久游卡', 'value' => 'JYCARD', 'fee_rate_percent' => 20,
				'sub_list' => array(
					5, 10, 20, 25, 30, 50, 100,
				),
			),
			array(
				'label' => '天宏卡', 'value' => 'THCARD', 'fee_rate_percent' => 15,
				'sub_list' => array(
					5, 10, 15, 30, 50, 100,
				),
			),
			array(
				'label' => '天下卡', 'value' => 'TIANXI', 'fee_rate_percent' => 15,
				'sub_list' => array(
					10, 20, 30, 40, 50, 60, 70, 80, 90, 100,
				),
			),
			array(
				'label' => '光宇卡', 'value' => 'GYCARD', 'fee_rate_percent' => 15,
				'sub_list' => array(
					10, 20, 30, 50, 100,
				),
			),
			array(
				'label' => '纵游卡', 'value' => 'ZONGYOU', 'fee_rate_percent' => 15,
				'sub_list' => array(
					10, 15, 30, 50, 100,
				),
			),
			array(
				'label' => '征途卡', 'value' => 'ZTCARD', 'fee_rate_percent' => 15,
				'sub_list' => array(
					10, 15, 20, 25, 30, 50, 60, 100, 300, 468, 500, 1000,
				),
			),
			array(
				'label' => '京东 E 卡', 'value' => 'JDECARD',
				'sub_list' => array(
					5, 10, 30, 50, 100, 200, 300, 500, 800, 1000,
				),
			),
			array(
				'label' => '中石化加油卡', 'value' => 'ZSHJY',
				'sub_list' => array(
					50, 100, 500, 1000,
				),
			),
		);
	}

}

////END OF FILE//////////////////