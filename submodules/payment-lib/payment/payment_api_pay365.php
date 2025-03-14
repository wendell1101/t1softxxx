<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 *
 * https://payment.quietnookz.net/MerchantTransfer
 *
 */
class Payment_api_pay365 extends Abstract_payment_api {

	public function __construct($params = null) {
		parent::__construct($params);
	}

	const CALLBACK_FIELD_ORDER_ID = 'RefID';
	const CALLBACK_FIELD_RESULT_CODE = 'Status';
	const CALLBACK_FIELD_AMOUNT = 'Amount';
	const CALLBACK_FIELD_MERCHANT_CODE = 'MerchantCode';
	const CALLBACK_FIELD_SIGNAURE = 'EncryptedSign';
	const CALLBACK_FIELD_EXTERNAL_ORDER_ID = 'RefID';
	const CALLBACK_FIELD_BANK_ORDER_ID = 'RefID';
	// const CALLBACK_FIELD_TYPE = 'r9_BType';

	const CALLBACK_TYPE_BROWSER = '1';
	const CALLBACK_TYPE_SERVER = '2';

	const CALLBACK_INFO_FIELD_MERCHANT = 'key';

	const SUCCESS_CODE = '000';

	const DEFAULT_CURRENCY = 'CNY';

	const DEFAULT_LANG = 'zh-cn';

	public function getPlatformCode() {
		return PAY365_PAYMENT_API;
	}

	public function getPrefix() {
		return 'pay365';
	}

	public function getName() {
		return 'PAY365';
	}

	//====implements Payment_api_interface start===================================
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {

		$amount = $this->convertLocalAmountToPaymentAmount($amount);
		$amountNum = $amount;
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
		$tranDateTime = date('Y-m-d h:i:sA');
		$lang = self::DEFAULT_LANG;
		//$info['key']=merchantID
		$params = array(
			'MerchantCode' => $info['key'], 'Currency' => self::DEFAULT_CURRENCY, 'CustomerID' => $playerId,
			'RefID' => $merOrderNum, 'Amount' => $amountNum, 'TransactionDate' => $tranDateTime,
			'ReturnFrontURL' => $merchantUrl, 'ReturnBackURL' => $callbackUrl, 'ClientIP' => $this->getClientIP(),
			'Language' => $lang,
		);

		if (!empty($bankCode)) {
			$params['BankCode'] = $bankCode;
		}

		$params['EncryptedSign'] = $this->createSign($params, $info);

		$result = array('success' => true, 'type' => self::REDIRECT_TYPE_FORM, 'url' => $info['url'], 'params' => $params, 'post' => true);
		return $result;
	}

	public function createSign($params, $info) {
		$tranDateTime = new DateTime($params['TransactionDate']);

		$msg = $params['MerchantCode'] . $params['RefID'] . $params['CustomerID'] . $params['Amount'] . $params['Currency'] .
		$tranDateTime->format('YmdHis') . $info['secret'] . $params['ClientIP'];

		$this->CI->utils->debug_log('createSign', $msg);
		return strtolower(md5($msg));
	}

	public function createCallbackSign($params, $info) {

		$msg = $params['MerchantCode'] . $params['RefID'] . $params['CustomerID'] . $params['Amount'] . $params['Currency'] .
			$params['Status'] . $info['secret'];

		$this->CI->utils->debug_log('createCallbackSign', $msg);
		return strtolower(md5($msg));
	}

	public function callbackFromServer($orderId, $callbackExtraInfo) {
		// $this->CI->load->library('promo_library');
		//must call
		$response_result_id = parent::callbackFromServer($orderId, $callbackExtraInfo);

		$this->utils->debug_log('orderId', $orderId, 'callbackExtraInfo', $callbackExtraInfo, 'response_result_id', $response_result_id);

		$merchantUrl = site_url('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
		$rlt = array('success' => false, 'next_url' => null);
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
				$rlt['next_url'] = $this->getPlayerBackUrl();
				// if ($success) {
				//9999 is failed
				// $rlt['message'] = 'RespCode=0000|JumpURL=' . $merchantUrl;
				// }
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
					// $this->CI->sale_order->setStatusToBrowserCallback($orderId);
					// //update balance once
					// $ord->external_order_id = @$callbackExtraInfo['orderId'];
					// $ord->response_result_id = $response_result_id;
					// $this->saveToPlayerBalance($ord);

					# APPLY PROMO IF PLAYER PROMO ID IS PASSED
					// $promo_transaction = isset($ord->player_promo_id) ? $this->CI->promo_library->approvePromo($ord->player_promo_id) : null;

					//update sale order
					$this->CI->sale_order->updateExternalInfo($ord->id, @$callbackExtraInfo[self::CALLBACK_FIELD_EXTERNAL_ORDER_ID],
						@$callbackExtraInfo[self::CALLBACK_FIELD_BANK_ORDER_ID], null, null, $response_result_id);
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
	 *
	 *
	 *
	 * @return array (success=>boolean, message=>string)
	 */
	private function checkCallbackOrder($ord, $flds) {
		//check respCode first
		$success = @$flds[self::CALLBACK_FIELD_RESULT_CODE] == self::SUCCESS_CODE;

		if (!$success) {
			$this->writePaymentErrorLog('respCode is not ' . self::SUCCESS_CODE, $flds);
		}

		$info = $this->getInfoByEnv();

		if ($success) {
			//check signature
			// $signStr = 'billno' . $flds['billno'] . 'currencytype' . $flds['Currency_type'] . 'amount' . $flds['amount'] . 'date' . $flds['date'] . 'succ' . $flds['succ'] . 'ipsbillno' . $flds['ipsbillno'] . 'retencodetype' . $flds['retencodetype'] . $info['secret'];
			// $signature = $flds['signature'];
			// log_message('debug', 'signStr:' . $signStr . ' md5:' . $signature . ' new md5:' . $this->getSignMD5($signStr));
			$signature = $flds[self::CALLBACK_FIELD_SIGNAURE];

			$success = strtolower($this->createCallbackSign($flds, $info)) == strtolower($signature);
			if (!$success) {
				$this->writePaymentErrorLog('signaure is wrong', $flds);
			}
		}
		if ($success) {
			//check amount, order id, mercode
			$success = $this->convertLocalAmountToPaymentAmount($ord->amount) == $this->convertLocalAmountToPaymentAmount(floatval($flds[self::CALLBACK_FIELD_AMOUNT]));
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

	protected function convertLocalAmountToPaymentAmount($amount) {
		//format number
		return number_format($amount, 2, '.', '');
	}

}

////END OF FILE//////////////////