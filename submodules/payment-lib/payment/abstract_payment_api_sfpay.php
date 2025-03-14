<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * SFPAY 新闪付
 *
 * SFPAY_PAYMENT_API, ID: 283
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
 *
 * 新闪付
 * https://gw.sslsf.com/v4.aspx
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_sfpay extends Abstract_payment_api {

	public function __construct($params = null) {
		parent::__construct($params);
	}

	const INTERFACE_VERSION = '4.0';
	const KEY_TYPE_MD5 = '1';
	const DEFAULT_AMOUNT = 1;
	const DEFAULT_NOTICE_TYPE = 1;

	const DEFAULT_PRODUCT_NAME='';
	const SUCCESS_CODE = '1';

	# Implement these to specify pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	public abstract function getBankCode($direct_pay_extra_info);

	//====implements Payment_api_interface start===================================
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {

		$amountNum = $amount;

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$info = $this->getInfoByEnv();
		if ($this->shouldRedirect($enabledSecondUrl)) {
			//disable second url
			$url = $this->CI->utils->getPaymentUrl($info['second_url'], $this->getPlatformCode(), $amountNum, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$this->CI->load->model(array('bank_list'));
		$bankCode = $this->getBankCode($order->direct_pay_extra_info);;
		$this->CI->utils->debug_log('bankId', $bankId, 'bankCode', $bankCode);

		$merchantUrl = $this->getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
		$callbackUrl = $this->getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);

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
			'ProductName' => self::DEFAULT_PRODUCT_NAME,
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

		$this->CI->utils->debug_log("=====================sfpay generatePaymentUrlForm params", $params);
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

		$sign = md5($msg);
		return $sign;
	}

	public function createCallbackSign($params, $info) {

		$msg = 'MemberID=' . $params['MemberID'] . '~|~TerminalID=' . $params['TerminalID']
			. '~|~TransID=' . $params['TransID'] . '~|~Result=' . $params['Result']
			. '~|~ResultDesc=' . $params['ResultDesc'] . '~|~FactMoney=' . $params['FactMoney']
			. '~|~AdditionalInfo=' . $params['AdditionalInfo'] . '~|~SuccTime=' . $params['SuccTime']
			. '~|~Md5Sign=' . $info['secret'];

		return strtolower(md5($msg));
	}

	public function callbackFromServer($orderId, $callbackExtraInfo) {
		$response_result_id = parent::callbackFromServer($orderId, $callbackExtraInfo);

		$rlt = array('success' => false, 'next_url' => null, 'message' => 'failed');
		//query order
		$ord = $this->CI->sale_order->getSaleOrderById($orderId);
		if ($ord) {
			if ($this->checkCallbackOrder($ord, $callbackExtraInfo)) {
				$success = true;

				//save to player balance
				//check order status, if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
				$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
				if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
					$this->CI->utils->debug_log('callbackFromServer, already get callback for order:' . $ord->id, $callbackExtraInfo);
					if ($ord->status == Sale_order::STATUS_BROWSER_CALLBACK) {
						$this->CI->sale_order->setStatusToSettled($orderId);
					}
				} else {
					//update balance once
					$this->CI->sale_order->updateExternalInfo($ord->id, @$callbackExtraInfo['TransID'], @$callbackExtraInfo['SuccTime'], null, null, $response_result_id);
					$this->approveSaleOrder($ord->id, 'auto server callback ' . $this->getPlatformCode(), false);
				}
				$rlt['success'] = $success;
				if ($success) {
					$rlt['message'] = 'OK';
				}
			}
		}
		return $rlt;
	}


	public function callbackFromBrowser($orderId, $callbackExtraInfo) {
		$response_result_id = parent::callbackFromBrowser($orderId, $callbackExtraInfo);
		$rlt = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		//query order
		$ord = $this->CI->sale_order->getSaleOrderById($orderId);
		if ($ord) {
			$success = true;
			$rlt['success'] = $success;
			$rlt['next_url'] = $this->getPlayerBackUrl();
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
						$this->writePaymentErrorLog('============sfpay checkCallback MemberID is wrong', $flds);
					}
				} else {
					$this->writePaymentErrorLog('============sfpay checkCallback order id is wrong', $flds);
				}

			} else {
				$this->writePaymentErrorLog('============sfpay checkCallback amount is wrong', $flds);
			}
		}
		return $success;
	}

	public function directPay($order) {
		//no direct pay
		return array('success' => false);
	}

	public function getBankListInfoFallback() {
		return array(
			array('label' => '招商银行', 'value' => '3001'),
			array('label' => '工商银行', 'value' => '3002'),
			array('label' => '建设银行', 'value' => '3003'),
			array('label' => '浦发银行', 'value' => '3004'),
			array('label' => '农业银行', 'value' => '3005'),
			array('label' => '民生银行', 'value' => '3006'),
			array('label' => '兴业银行', 'value' => '3009'),
			array('label' => '交通银行', 'value' => '3020'),
			array('label' => '光大银行', 'value' => '3022'),
			array('label' => '中国银行', 'value' => '3026'),
			array('label' => '北京银行', 'value' => '3032'),
			array('label' => '平安银行', 'value' => '3035'),
			array('label' => '广发银行', 'value' => '3036'),
			array('label' => '中信银行', 'value' => '3039')
		);
	}
}

////END OF FILE//////////////////