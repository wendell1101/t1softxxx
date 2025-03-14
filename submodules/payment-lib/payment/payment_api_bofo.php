<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * Baofoo 宝付
 * http://www.baofoo.com/
 *
 * BOFO_PAYMENT_API, ID: 9
 *
 * Required Fields:
 *
 * * URL
 * * Account - MemberID
 * * Key - TerminalID
 * * Secret - SecretKey
 *
 * Field Values:
 *
 * * Sandbox URL: https://tgw.baofoo.com/payindex
 * * Live URL: https://gw.baofoo.com/payindex
 *
 * 闪付
 * http://gw.sfvipgate.com/v4.aspx
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bofo extends Abstract_payment_api {

	public function __construct($params = null) {
		parent::__construct($params);
	}

	public function getPlatformCode() {
		return BOFO_PAYMENT_API;
	}

	public function getPrefix() {
		return 'bofo';
	}

	public function getName() {
		return 'BOFO';
	}

	const INTERFACE_VERSION = '4.0';
	const KEY_TYPE_MD5 = '1';
	const DEFAULT_AMOUNT = 1;
	const DEFAULT_NOTICE_TYPE = 1;

	// const DEFAULT_PRODUCT_NAME='';
	const SUCCESS_CODE = '1';

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

		$this->CI->load->model(array('bank_list'));
		$bankCode = $this->CI->bank_list->getBankShortCodeById($bankId);
		$this->CI->utils->debug_log('bankId', $bankId, 'bankCode', $bankCode);

		$merchantUrl = $this->getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
		// $failedUrl = site_url('/callback/browser/failed/' . $this->getPlatformCode() . '/' . $orderId);
		// $errUrl = site_url('/callback/browser/error/' . $this->getPlatformCode());
		$callbackUrl = $this->getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);

		//YYYYMMDDHHMMSS
		// $tranDateTime = date('YmdHis');
		//can't repeat
		// $isRepeatSubmit = '0';
		//$info['key']=merchantID
		$secret = $info['secret'];
		$now = $this->getTradeDateNow();
		$TransID = $orderId;
		if ($orderId) {
			$ord = $this->CI->sale_order->getSaleOrderById($orderId);
			$TransID = $ord->secure_id;
		}
		$OrderMoney = $this->convertAmountToCurrency($amount);

		$params = array(
			'MemberID' => $info['account'],
			'TerminalID' => $info['key'],
			'InterfaceVersion' => self::INTERFACE_VERSION,
			'KeyType' => self::KEY_TYPE_MD5,
			'PayID' => $bankCode,
			// 'ProductName' => self::DEFAULT_PRODUCT_NAME,
			'TradeDate' => $now,
			'TransID' => $TransID,
			'OrderMoney' => $OrderMoney,
			'Amount' => self::DEFAULT_AMOUNT,
			'Username' => $playerId,
			'PageUrl' => $merchantUrl,
			'ReturnUrl' => $callbackUrl,
			'NoticeType' => self::DEFAULT_NOTICE_TYPE,
		);
		$params['Signature'] = $this->createSign($params, $info);

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

	protected function convertAmountToCurrency($amount) {
		return round($amount * 100);
	}

	protected function getTradeDateNow() {
		$d = new DateTime();
		return $d->format('YmdHis');
	}

	public function createSign($params, $info) {

		$msg = $params['MemberID'] . '|' . $params['PayID'] . '|' . $params['TradeDate'] . '|' . $params['TransID']
			. '|' . $params['OrderMoney'] . '|' . $params['PageUrl'] . '|' . $params['ReturnUrl']
			. '|' . $params['NoticeType'] . '|' . $info['secret'];

		$this->CI->utils->debug_log('createSign', $msg);
		return strtolower(md5($msg));
	}

	public function createCallbackSign($params, $info) {

		$msg = 'MemberID=' . $params['MemberID'] . '~|~TerminalID=' . $params['TerminalID']
			. '~|~TransID=' . $params['TransID'] . '~|~Result=' . $params['Result']
			. '~|~ResultDesc=' . $params['ResultDesc'] . '~|~FactMoney=' . $params['FactMoney']
			. '~|~AdditionalInfo=' . $params['AdditionalInfo'] . '~|~SuccTime=' . $params['SuccTime']
			. '~|~Md5Sign=' . $info['secret'];

		$this->CI->utils->debug_log('createCallbackSign', $msg);
		return strtolower(md5($msg));
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
			if ($this->checkCallbackOrder($ord, $callbackExtraInfo)) {
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
					$this->CI->sale_order->updateExternalInfo($ord->id, @$callbackExtraInfo['TransID'], @$callbackExtraInfo['SuccTime'], null, null, $response_result_id);
					$this->approveSaleOrder($ord->id, 'auto server callback ' . $this->getPlatformCode(), false);
				}

				// $success = $this->CI->sale_order->endTransWithSucc();

				// $rlt['message'] = $this->CI->load->view('payment/ips/success', ['transaction' => $transaction, 'callbackExtraInfo' => $callbackExtraInfo, 'promo' => $promo_transaction], true);
				$rlt['success'] = $success;
				// $rlt['next_url'] = $this->getPlayerBackUrl();
				if ($success) {
					$rlt['message'] = 'OK';
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

			// if ($this->checkCallbackOrder($ord, $callbackExtraInfo)) {

				$success = true;

				// $this->CI->sale_order->startTrans();

				// $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
				// //save to player balance
				// //check order status, if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
				// if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
				// 	$this->CI->utils->debug_log('callbackFromBrowser, already get callback for order:' . $ord->id, $callbackExtraInfo);
				// } else {
				// 	//update sale order
				// 	$this->CI->sale_order->updateExternalInfo($ord->id, @$callbackExtraInfo['TransID'], @$callbackExtraInfo['SuccTime'], null, null, $response_result_id);
				// 	$success = $this->CI->sale_order->browserCallbackSaleOrder($ord->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
				// }

				// $success = $this->CI->sale_order->endTransWithSucc();

				// $rlt['message'] = $this->CI->load->view('payment/ips/success', ['transaction' => $transaction, 'callbackExtraInfo' => $callbackExtraInfo, 'promo' => $promo_transaction], true);
				$rlt['success'] = $success;
				$rlt['next_url'] = $this->getPlayerBackUrl();
			// }
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
	private function checkCallbackOrder($ord, $flds) {
		//check respCode first
		$success = @$flds['Result'] == self::SUCCESS_CODE;

		if (!$success) {
			$this->writePaymentErrorLog('respCode is not ' . self::SUCCESS_CODE, $flds);
		}

		$info = $this->getInfoByEnv();

		if ($success) {
			//check signature
			// $signStr = 'billno' . $flds['billno'] . 'currencytype' . $flds['Currency_type'] . 'amount' . $flds['amount'] . 'date' . $flds['date'] . 'succ' . $flds['succ'] . 'ipsbillno' . $flds['ipsbillno'] . 'retencodetype' . $flds['retencodetype'] . $info['secret'];
			// $signature = $flds['signature'];
			// log_message('debug', 'signStr:' . $signStr . ' md5:' . $signature . ' new md5:' . $this->getSignMD5($signStr));
			$signature = $flds['Md5Sign'];

			$success = $this->createCallbackSign($flds, $info) == $signature;
			if (!$success) {
				$this->writePaymentErrorLog('signaure is wrong', $flds);
			}
		}
		if ($success) {
			//check amount, order id, mercode
			$success = $this->convertAmountToCurrency($ord->amount) == intval($flds['FactMoney']);
			if ($success) {
				$success = $ord->secure_id == $flds['TransID'];
				if ($success) {
					$success = $info['account'] == $flds['MemberID'];
					if ($success) {
					} else {
						$this->writePaymentErrorLog('MemberID is wrong', $flds);
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
}

////END OF FILE//////////////////