<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * BAOFU88 宝付
 * http://baofu88.net/
 *
 * BAOFU88_PAYMENT_API, ID: 12
 *
 * Required Fields:
 *
 * * URL
 * * Key - MerchantID
 * * Secret - secret key
 *
 * Field Values:
 *
 * * URL: http://ag.baofu88.net/GateWay/ReceiveBank.aspx
 *
 * @category Payment
 * @copyright 2013-2022 tot
 *
 */
class Payment_api_baofu88 extends Abstract_payment_api {

	public function __construct($params = null) {
		parent::__construct($params);
	}

	public function getPlatformCode() {
		return BAOFU88_PAYMENT_API;
	}

	public function getPrefix() {
		return 'baofu88';
	}

	public function getName() {
		return 'BAOFU88';
	}

	const CMD = 'Buy';
	const DEFAULT_CURRENCY = 'CNY';
	const NEED_RESPONSE = '1';

	const SUCCESS_CODE = '1';

	const CALLBACK_FIELD_ORDER_ID = 'r6_Order';
	const CALLBACK_FIELD_RESULT_CODE = 'r1_Code';
	const CALLBACK_FIELD_AMOUNT = 'r3_Amt';
	const CALLBACK_FIELD_MERCHANT_CODE = 'p1_MerId';
	const CALLBACK_FIELD_SIGNAURE = 'hmac';
	const CALLBACK_FIELD_EXTERNAL_ORDER_ID = 'r2_TrxId';
	const CALLBACK_FIELD_BANK_ORDER_ID = 'ro_BankOrderId';
	const CALLBACK_FIELD_TYPE = 'r9_BType';

	const CALLBACK_TYPE_BROWSER = '1';
	const CALLBACK_TYPE_SERVER = '2';

	const CALLBACK_INFO_FIELD_MERCHANT = 'key';

	const CALLBACK_SERVER_SUCCESS_RESULT = 'success';

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

		// $merchantUrl = site_url('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
		// $failedUrl = site_url('/callback/browser/failed/' . $this->getPlatformCode() . '/' . $orderId);
		// $errUrl = site_url('/callback/browser/error/' . $this->getPlatformCode());
		$callbackUrl = $this->getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);

		//YYYYMMDDHHMMSS
		// $tranDateTime = date('YmdHis');
		//can't repeat
		// $isRepeatSubmit = '0';
		//$info['key']=merchantID
		// $secret = $info['secret'];
		// $now = $this->getTradeDateNow();
		$orderIdForGateway = $orderId;
		if ($orderId) {
			$ord = $this->CI->sale_order->getSaleOrderById($orderId);
			$orderIdForGateway = $ord->secure_id;
		}
		$amountMoney = $this->convertAmountToCurrency($amount);

		$params = array(
			'p0_Cmd' => self::CMD,
			'p1_MerId' => $info['key'],
			'p2_Order' => $orderIdForGateway,
			'p3_Amt' => $amountMoney,
			'p4_Cur' => self::DEFAULT_CURRENCY,
			'p8_Url' => $callbackUrl,
			'pa_MP' => $playerId,
			'pr_NeedResponse' => self::NEED_RESPONSE,
		);
		if (!empty($bankCode)) {
			$params['pd_FrpId'] = $bankCode;
		}
		$params['hmac'] = $this->createSign($params, $info);

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
		return round($amount, 2);
	}

	public function createSign($params, $info) {

		#进行签名处理，一定按照文档中标明的签名顺序进行
		$sbOld = "";
		#加入业务类型
		$sbOld = $sbOld . $params['p0_Cmd'];
		#加入商户编号
		$sbOld = $sbOld . $params['p1_MerId'];
		#加入商户订单号
		$sbOld = $sbOld . $params['p2_Order'];
		#加入支付金额
		$sbOld = $sbOld . $params['p3_Amt'];
		#加入交易币种
		$sbOld = $sbOld . $params['p4_Cur'];
		#加入商品名称
		// $sbOld = $sbOld . $params['p5_Pid'];
		// #加入商品分类
		// $sbOld = $sbOld . $p6_Pcat;
		// #加入商品描述
		// $sbOld = $sbOld . $p7_Pdesc;
		#加入商户接收支付成功数据的地址
		$sbOld = $sbOld . $params['p8_Url'];
		#加入送货地址标识
		// $sbOld = $sbOld . $p9_SAF;
		#加入商户扩展信息
		$sbOld = $sbOld . $params['pa_MP'];
		#加入支付通道编码
		$sbOld = $sbOld . @$params['pd_FrpId'];
		#加入是否需要应答机制
		$sbOld = $sbOld . $params['pr_NeedResponse'];
		$this->CI->utils->debug_log($params['p2_Order'], $sbOld);
		return $this->HmacMd5($sbOld, $info['secret']);

		// $msg = $params['MemberID'] . '|' . $params['PayID'] . '|' . $params['TradeDate'] . '|' . $params['TransID']
		// 	. '|' . $params['OrderMoney'] . '|' . $params['PageUrl'] . '|' . $params['ReturnUrl']
		// 	. '|' . $params['NoticeType'] . '|' . $info['secret'];

		// $this->CI->utils->debug_log('createSign', $msg);
		// return strtolower(md5($msg));
	}

	function HmacMd5($data, $key) {
		// RFC 2104 HMAC implementation for php.
		// Creates an md5 HMAC.
		// Eliminates the need to install mhash to compute a HMAC
		// Hacked by Lance Rushing(NOTE: Hacked means written)

		//需要配置环境支持iconv，否则中文参数不能正常处理
		// 		$key = iconv("GB2312", "UTF-8", $key);
		// 		$data = iconv("GB2312", "UTF-8", $data);

		$b = 64; // byte length for md5
		if (strlen($key) > $b) {
			$key = pack("H*", md5($key));
		}
		$key = str_pad($key, $b, chr(0x00));
		$ipad = str_pad('', $b, chr(0x36));
		$opad = str_pad('', $b, chr(0x5c));
		$k_ipad = $key ^ $ipad;
		$k_opad = $key ^ $opad;

		return md5($k_opad . pack("H*", md5($k_ipad . $data)));
	}

	public function createCallbackSign($params, $info) {
		#取得加密前的字符串
		$sbOld = "";
		#加入商家ID
		$sbOld = $sbOld . $params['p1_MerId'];
		#加入消息类型
		$sbOld = $sbOld . $params['r0_Cmd'];
		#加入业务返回码
		$sbOld = $sbOld . $params['r1_Code'];
		#加入交易ID
		$sbOld = $sbOld . $params['r2_TrxId'];
		#加入交易金额
		$sbOld = $sbOld . $params['r3_Amt'];
		#加入货币单位
		$sbOld = $sbOld . $params['r4_Cur'];
		#加入产品Id
		$sbOld = $sbOld . $params['r5_Pid'];
		#加入订单ID
		$sbOld = $sbOld . $params['r6_Order'];
		#加入用户ID
		$sbOld = $sbOld . $params['r7_Uid'];
		#加入商家扩展信息
		$sbOld = $sbOld . $params['r8_MP'];
		#加入交易结果返回类型
		$sbOld = $sbOld . $params['r9_BType'];

		$this->CI->utils->debug_log($params['r6_Order'], $sbOld);
		return $this->HmacMd5($sbOld, $info['secret']);

		// $msg = 'MemberID=' . $params['MemberID'] . '~|~TerminalID=' . $params['TerminalID']
		// 	. '~|~TransID=' . $params['TransID'] . '~|~Result=' . $params['Result']
		// 	. '~|~ResultDesc=' . $params['ResultDesc'] . '~|~FactMoney=' . $params['FactMoney']
		// 	. '~|~AdditionalInfo=' . $params['AdditionalInfo'] . '~|~SuccTime=' . $params['SuccTime']
		// 	. '~|~Md5Sign=' . $info['secret'];

		// $this->CI->utils->debug_log('createCallbackSign', $msg);
		// return strtolower(md5($msg));
	}

	public function callbackFromServer($orderId, $callbackExtraInfo) {
		//check type first
		if (@$callbackExtraInfo[self::CALLBACK_FIELD_TYPE] == self::CALLBACK_TYPE_BROWSER) {
			return $this->callbackFromBrowser($orderId, $callbackExtraInfo);
		}

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
					$this->CI->sale_order->updateExternalInfo($ord->id, @$callbackExtraInfo[self::CALLBACK_FIELD_EXTERNAL_ORDER_ID], @$callbackExtraInfo[self::CALLBACK_FIELD_BANK_ORDER_ID], null, null, $response_result_id);
					$this->approveSaleOrder($ord->id, 'auto server callback ' . $this->getPlatformCode(), false);
				}

				// $success = $this->CI->sale_order->endTransWithSucc();

				// $rlt['message'] = $this->CI->load->view('payment/ips/success', ['transaction' => $transaction, 'callbackExtraInfo' => $callbackExtraInfo, 'promo' => $promo_transaction], true);
				$rlt['success'] = $success;
				// $rlt['next_url'] = $this->getPlayerBackUrl();
				if ($success) {
					$rlt['message'] = self::CALLBACK_SERVER_SUCCESS_RESULT;
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
					$this->CI->sale_order->updateExternalInfo($ord->id, @$callbackExtraInfo[self::CALLBACK_FIELD_EXTERNAL_ORDER_ID], @$callbackExtraInfo[self::CALLBACK_FIELD_BANK_ORDER_ID], null, null, $response_result_id);
					$success = $this->CI->sale_order->browserCallbackSaleOrder($ord->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
				}

				// $success = $this->CI->sale_order->endTransWithSucc();

				// $rlt['message'] = $this->CI->load->view('payment/ips/success', ['transaction' => $transaction, 'callbackExtraInfo' => $callbackExtraInfo, 'promo' => $promo_transaction], true);
				$rlt['success'] = $success;
				$rlt['next_url'] = $this->getPlayerBackUrl();
				$rlt['go_success_page'] = true;
				$rlt['message'] = '';
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

			$success = $this->createCallbackSign($flds, $info) == $signature;
			if (!$success) {
				$this->writePaymentErrorLog('signaure is wrong', $flds);
			}
		}
		if ($success) {
			//check amount, order id, mercode
			$success = $this->convertAmountToCurrency($ord->amount) == $this->convertAmountToCurrency(floatval($flds[self::CALLBACK_FIELD_AMOUNT]));
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
}

////END OF FILE//////////////////