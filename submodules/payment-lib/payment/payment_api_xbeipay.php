<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * XBEI 新贝支付
 * Website: http://www.xbeipay.com/
 *
 * XBEI_PAYMENT_API, ID: 28
 *
 * Required Fields:
 *
 * * URL
 * * Key - partner code
 * * Secret - secret key
 *
 *
 * Field Values:
 *
 * * URL: http://gateway.xbeipay.com/Gateway/XbeiPay
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xbeipay extends Abstract_payment_api {

	public function __construct($params = null) {
		parent::__construct($params);
	}

	public function getPlatformCode() {
		return XBEI_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xbei';
	}

	public function getName() {
		return 'XBEI';
	}

	const CALLBACK_FIELD_ORDER_ID = 'OrderId';
	const CALLBACK_FIELD_RESULT_CODE = 'State';
	const CALLBACK_FIELD_AMOUNT = 'Amount';
	const CALLBACK_FIELD_MERCHANT_CODE = 'MerchantCode';
	const CALLBACK_FIELD_SIGNAURE = 'SignValue';
	const CALLBACK_FIELD_EXTERNAL_ORDER_ID = 'SerialNo';
	const CALLBACK_FIELD_BANK_ORDER_ID = 'SerialNo';

	const CALLBACK_INFO_FIELD_MERCHANT = 'key';

	const RETURN_SUCCESS_CODE = 'ok';
	const RETURN_FAILED_CODE = 'FAILED';
	//sold
	const SUCCESS_CODE_LIST = array('8888');

	const REQUEST_VERSION_CODE = 'V1.0';
	// const REQUEST_INPUT_CHARSET = 'UTF-8';
	// const REQUEST_SIGN_TYPE = 'MD5';
	// const REQUEST_PAYMENT_TYPE = 'ALL';
	// const REQUEST_RETRY_FLAG = 'FALSE'; //retryFalg
	// const REQUEST_TIMEOUT = '2D';

	// const WHITE_IP = array('116.213.177.139');

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
		// $secret = $info['secret'];
		$now = $this->getTradeDateNow();

		$ord = $this->CI->sale_order->getSaleOrderById($orderId);

		$direct_pay_extra_info = $ord->direct_pay_extra_info;
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		$bankCode = $this->getBankCode($direct_pay_extra_info);

		$secureId = $ord->secure_id;

		$amount = $this->convertAmountToCurrency($amount);
		$ip = $this->getClientIP();

		$params = array(
			'Version' => self::REQUEST_VERSION_CODE,
			'MerchantCode' => $info['key'],
			'OrderId' => $secureId,
			'Amount' => $amount,
			'AsyNotifyUrl' => $callbackUrl,
			'SynNotifyUrl' => $callbackUrl,
			'OrderDate' => $this->getTradeDateNow(),
			'TradeIp' => $ip,
			'PayCode' => $bankCode,
		);
		// if (!empty($bankCode)) {
		// 	$params['interfaceCode'] = $bankCode;
		// }
		$params['SignValue'] = $this->createSign($params, $info, self::FROM_REQUEST);

		$result = array('success' => true, 'type' => self::REDIRECT_TYPE_FORM, 'url' => $info['url'], 'params' => $params, 'post' => true);

		$this->utils->debug_log("URL Form: ", $result);

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

	public function createRequestSign($params, $info) {

		$original = "Version=[" . $params['Version'] .
			"]MerchantCode=[" . $params['MerchantCode'] .
			"]OrderId=[" . $params['OrderId'] .
			"]Amount=[" . $params['Amount'] .
			"]AsyNotifyUrl=[" . $params['AsyNotifyUrl'] .
			"]SynNotifyUrl=[" . $params['SynNotifyUrl'] .
			"]OrderDate=[" . $params['OrderDate'] .
			"]TradeIp=[" . $params['TradeIp'] .
			"]PayCode=[" . $params['PayCode'] .
			"]TokenKey=[" . $info['secret'] . "]";

		// $msg = '';
		// $keys = array_keys($params);
		// sort($keys);
		// foreach ($keys as $key) {
		// 	if (!empty($key) && !empty($params[$key])) {
		// 		$msg .= $key . '=' . $params[$key] . '&';
		// 	}
		// }
		// ksort($params);
		// reset($params);
		// foreach ($params as $key => $val) {
		// 	if (!empty($key) && !empty($val) && $key != self::CALLBACK_FIELD_SIGNAURE) {
		// 		$msg .= $key . '=' . $val . '&';
		// 	}
		// }
		// //remove last &
		// $msg = substr($msg, 0, strlen($msg) - 1) . $info['secret'];

		$this->CI->utils->debug_log('original', $original);
		//should be upper
		return strtoupper(md5($original));
	}

	public function createBrowserCallbackSign($params, $info) {
		$original = "Version=[" . $params['Version'] .
			"]MerchantCode=[" . $params['MerchantCode'] .
			"]OrderId=[" . $params['OrderId'] .
			"]OrderDate=[" . $params['OrderDate'] .
			"]TradeIp=[" . $params['TradeIp'] .
			"]PayCode=[" . $params['PayCode'] .
			"]State=[" . $params['State'] .
			"]TokenKey=[" . $info['secret'] . "]";

		$this->CI->utils->debug_log('original', $original);
		return strtoupper(md5($original));
	}

	public function createServerCallbackSign($params, $info) {
		$original = "Version=[" . $params['Version'] .
			"]MerchantCode=[" . $params['MerchantCode'] .
			"]OrderId=[" . $params['OrderId'] .
			"]OrderDate=[" . $params['OrderDate'] .
			"]TradeIp=[" . $params['TradeIp'] .
			"]SerialNo=[" . $params['SerialNo'] .
			"]Amount=[" . $params['Amount'] .
			"]PayCode=[" . $params['PayCode'] .
			"]State=[" . $params['State'] .
			"]FinishTime=[" . $params['FinishTime'] .
			"]TokenKey=[" . $info['secret'] . "]";

		$this->CI->utils->debug_log('original', $original);
		return strtoupper(md5($original));
	}

	public function createSign($params, $info, $from) {
		$this->CI->utils->debug_log('from', $from);
		if ($from == self::FROM_SERVER) {
			return $this->createServerCallbackSign($params, $info);
		} else if ($from == self::FROM_BROWSER) {
			return $this->createBrowserCallbackSign($params, $info);
		} else if ($from == self::FROM_REQUEST) {
			return $this->createRequestSign($params, $info);
		}

		return null;
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
			if ($this->checkCallbackOrder($ord, $callbackExtraInfo, $processed, self::FROM_SERVER)) {
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
			$processed = false;
			if ($this->checkCallbackOrder($ord, $callbackExtraInfo, $processed, self::FROM_BROWSER)) {

				$success = true;

				// $this->CI->sale_order->startTrans();

				$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
				//save to player balance
				//check order status, if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
				if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
					$this->CI->utils->debug_log('callbackFromBrowser, already get callback for order:' . $ord->id, $callbackExtraInfo);
				} else {
					//update sale order
					$this->CI->sale_order->updateExternalInfo($ord->id, @$callbackExtraInfo[self::CALLBACK_FIELD_EXTERNAL_ORDER_ID],
						@$callbackExtraInfo[self::CALLBACK_FIELD_BANK_ORDER_ID], null, null, $response_result_id);
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

	protected function getBankCode($direct_pay_extra_info) {
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				return $extraInfo['banktype'];
			}
		}
	}

	/**
	 *
	 *
	 *
	 * @return array (success=>boolean, message=>string)
	 */
	private function checkCallbackOrder($ord, $flds, &$processed = false, $from = self::FROM_SERVER) {
		$info = $this->getInfoByEnv();

		//check white IP
		// $ip = $this->CI->utils->getIP();
		// $isWhiteIP = in_array($ip, self::WHITE_IP);
		// if ($this->getValueFromApiConfig('check_white_ip') && !$isWhiteIP) {
		// 	$msg = $this->CI->utils->debug_log('white ip is wrong', $ip, 'white ip', self::WHITE_IP, $flds);
		// 	$this->writePaymentErrorLog($msg, $flds);
		// 	return false;
		// }

		//check sign
		// if ($success) {
		$success = false;
		if (isset($flds[self::CALLBACK_FIELD_SIGNAURE])) {

			$signature = @$flds[self::CALLBACK_FIELD_SIGNAURE];
			$callbackSign = $this->createSign($flds, $info, $from);
			$success = strtolower($callbackSign) == strtolower($signature);
			if (!$success) {
				$msg = $this->CI->utils->debug_log('signaure is wrong', $flds, 'callbackSign', $callbackSign);
				$this->writePaymentErrorLog($msg, $flds);
			}
			// }

			$processed = $success;
		}

		//check respCode
		if ($success) {
			$success = in_array(@$flds[self::CALLBACK_FIELD_RESULT_CODE], self::SUCCESS_CODE_LIST);
			if (!$success) {
				$this->writePaymentErrorLog(self::CALLBACK_FIELD_RESULT_CODE . ' is not ', self::SUCCESS_CODE_LIST, $flds);
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
			if (!empty($bankInfo['sub_list'])) {
				foreach ($bankInfo['sub_list'] as $val => $label) {
					$subList[] = array('value' => $val, 'label' => $label);
				}
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
				'label' => '中国工商银行', 'value' => '100012',
			),
			array(
				'label' => '中国农业银行', 'value' => '100013',
			),
			array(
				'label' => '中国建设银行', 'value' => '100014',
			),
			array(
				'label' => '交通银行', 'value' => '100015',
			),
			array(
				'label' => '招商银行', 'value' => '100016',
			),
			array(
				'label' => '中国银行', 'value' => '100017',
			),
			array(
				'label' => '中国民生银行', 'value' => '100018',
			),
			array(
				'label' => '兴业银行', 'value' => '100020',
			),
			array(
				'label' => '中信银行', 'value' => '100023',
			),
			array(
				'label' => '光大银行', 'value' => '100024',
			),
			array(
				'label' => '中国邮政储蓄银行', 'value' => '100025',
			),
			array(
				'label' => '平安银行', 'value' => '100030',
			),
		);
	}

}

////END OF FILE//////////////////