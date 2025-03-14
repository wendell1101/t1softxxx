<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * tot Bank Reconcilation
 *
 * T1T_BANK_RECONCILATION_PAYMENT_API, ID: 366
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
 * * Live URL: https://gw.baofoo.com/payindex
 * * Sandbox URL: (Same as live)
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class payment_api_t1t_bank_reconcilation extends Abstract_payment_api {

	public $SITE_ID = 123;
	public $DEBUG = true;
	public $DEBUG2 = false;

	public function __construct($params = null) {
		parent::__construct($params);
		/**
		 * Determine SITE_ID from config file
		 * If config item not present, use default value above
		 */
		$this->SITE_ID = $this->CI->config->item('t1t_bank_recon_site_id') ?: $this->SITE_ID;
	}

	public function getPlatformCode() {
		return T1T_BANK_RECONCILATION_PAYMENT_API;
	}

	public function getPrefix() {
		return 't1t_abr';
	}

	public function getName() {
		return 'T1T_ABR';
	}

	public function getPlayerInputInfo() {
	    return [
	        [ 'name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09' ]
	    ];
	}

	// const INTERFACE_VERSION = '4.0';
	// const KEY_TYPE_MD5 = '1';
	const DEFAULT_AMOUNT = 1;
	const DEFAULT_NOTICE_TYPE = 1;

	// const DEFAULT_PRODUCT_NAME='';
	// const SUCCESS_CODE = '1';

	//====implements Payment_api_interface start===================================
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {

		// $amountNum = $amount;

		try {
			$info = $this->getInfoByEnv();
			$this->CI->utils->debug_log('info', $info);

			// Determine player username
			$this->CI->load->library('player_manager');
			// getPlayerUsername return: [ 'username' => (username) ]
			$player_res = $this->CI->player_manager->getPlayerUsername($playerId);

			if (count($player_res) <= 0) {
				throw new Exception("{$this->getName()}: Illegal player ID ($playerId)");
			}

			$player_username = $player_res['username'];
			$this->CI->utils->debug_log('player_username', $player_username);

			// Determine secure ID from order ID
			if (empty($orderId)) {
				throw new Exception("{$this->getName()}: Order ID missing");
			}
			$order = $this->CI->sale_order->getSaleOrderById($orderId);
			$secure_id = $order->secure_id;

			$merchant_url = $this->getCallbackUrl("/callback/browser/success/{$this->getPlatformCode()}/{$order->id}");
			$callback_url = $this->getCallbackUrl("/callback/process/{$this->getPlatformCode()}/{$order->id}");

			// $merchantUrl = $this->getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
			// $failedUrl = site_url('/callback/browser/failed/' . $this->getPlatformCode() . '/' . $orderId);
			// $errUrl = site_url('/callback/browser/error/' . $this->getPlatformCode());
			// $callbackUrl = $this->getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);

			$timestamp = time();

			// Build params
			$params = [
				'site_id'		=> $this->SITE_ID ,
				'player_id'		=> $player_username	,
				'order_id'		=> $order->id		,
				'secure_id'		=> $secure_id		,
				'amount'		=> $amount			,
				'timestamp'		=> $timestamp		,
				'merchant_url'	=> $merchant_url	,
				'callback_url'	=> $callback_url	,
			];

			$params['secure'] = $this->secure_sign_for_payment_url($params, $info);

			$result = [
				'success' => true,
				'type' => self::REDIRECT_TYPE_FORM,
				'url' => $info['url'],
				'params' => $params,
				'post' => false  // Use GET instead
			];

			return $result;
		}
		catch (Exception $ex) {
			$this->utils->error_log($ex->getMessage());
		}

	}

	public function secure_sign_for_payment_url($params, $info) {
		$plaintext = http_build_query($params);
		$securehash = md5($plaintext);

		return $securehash;
	}

	/**
	 * OBSOLETE
	 */
	// public function secure_sign_for_browser_callback($params, $info) {
	// 	$plaintext_array = [
	// 		'bank_name'	=> $params['bank_name'] ,
	// 		'order_id'	=> $params['order_id'] ,
	// 		'site_id'	=> $params['site_id'] ,
	// 		'timestamp'	=> $params['timestamp']
	// 	];
	// 	$plaintext = json_encode($plaintext_array);
	// 	$securehash = md5($plaintext);

	// 	return $securehash;
	// }

	public function secure_sign_for_server_callback($params, $info) {
		$plaintext_array = [
			'account_number'	=> $params['account_number'] ,
			'bank_name'			=> $params['bank_name'] ,
			'bank_trans_id'		=> $params['bank_trans_id'] ,
			'real_amount'		=> $params['real_amount'] ,
			'secure_id'			=> $params['secure_id'] ,
			'site_id'			=> $params['site_id'] ,
			'timestamp'			=> $params['timestamp'] ,
			'trans_date'		=> $params['trans_date']
		];
		$plaintext = json_encode($plaintext_array, JSON_UNESCAPED_UNICODE);
		$securehash = md5($plaintext);
		if ($this->DEBUG)
			{ $this->CI->utils->debug_log("{$this->getName()}:SSFS", 'secure', $securehash, 'plaintext', $plaintext); }

		return $securehash;
	}

	public function callbackFromBrowser($order_id, $callback_extra) {

		try {
			$return_set = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));

			if ($this->DEBUG)
				{ $this->CI->utils->debug_log("{$this->getName()}:CB-B", 'order_id', $order_id); }
			$order = $this->CI->sale_order->getSaleOrderById($order_id);
			if (empty($order)) {
				throw new Exception("Sale order not found by order_id ({$order_id})");
			}

			// must call
			$order_id = $order->id;
			$response_result_id = parent::callbackFromBrowser($order_id, $callback_extra);
			if ($this->DEBUG) {
				$this->CI->utils->debug_log("{$this->getName()}:CB-B", 'order_id', $order_id, 'response_result_id', $response_result_id);
			}

			// Verify order
			if ($callback_extra['site_id'] != $this->SITE_ID) {
				throw new Exception("site_id ({$callback_extra['site_id']}) does not match ({$this->SITE_ID})");
			}

			/**
			 * Standard process was to exec browserCallbackSaleOrder(), setting
			 * sale_orders.status to STATUS_BROWSER_CALLBACK and add cash to player balance
			 * However, we only do this on receiving server callback.
			 * But parent::callbackFromBrowser() will still lock player balance and invoke
			 * this method to cash player balance (like it expects).
			 */
			// $success = $this->CI->sale_order->browserCallbackSaleOrder($order_id, "auto browser callback from {$this->getPlatformCode()}", false);

			$this->CI->utils->debug_log("{$this->getName()}:CB-B", "browser callback received correctly",  'order', $order_id, 'all fields', $callback_extra);
			$success = true;

			$return_set = [
				'success' => $success,
				'next_url' => $this->getPlayerBackUrl(),
				'message' => 'success'
			];
		}
		catch (Exception $ex) {
			$this->CI->utils->debug_log("{$this->getName()}:CB-B", 'exception', $ex->getMessage());
		}
		finally {
			return $return_set;
		}

	}

	public function callbackFromServer($order_id, $callbackExtraInfo) {
		try {
			if ($this->DEBUG) {
				$this->CI->utils->debug_log("{$this->getName()}:CB-S", 'order_id', $order_id);
				$this->CI->utils->debug_log("{$this->getName()}:CB-S", 'bank_name', $callbackExtraInfo['bank_name'], 'bank_name.strlen', strlen($callbackExtraInfo['bank_name']), 'bank_name.mb_strlen', mb_strlen($callbackExtraInfo['bank_name']));
			}
			if ($this->DEBUG2) {
				$this->debug_dump(["{$this->getName()}:CB-S", 'order_id', $order_id]);
				$this->debug_dump(["{$this->getName()}:CB-S", 'bank_name', $callbackExtraInfo['bank_name'], 'bank_name.strlen', strlen($callbackExtraInfo['bank_name']), 'bank_name.mb_strlen', mb_strlen($callbackExtraInfo['bank_name'])]);
			}

			$order = $this->CI->sale_order->getSaleOrderById($order_id);

			if (empty($order)) {
				throw new Exception("Sale order not found by order_id ({$order_id})");
			}

			//must call
			$order_id = $order->id;
			$response_result_id = parent::callbackFromServer($order_id, $callbackExtraInfo);

			if ($this->DEBUG) {
				$this->CI->utils->debug_log("{$this->getName()}:CB-S", 'order_id', $order_id, 'response_result_id', $response_result_id);
			}
			if ($this->DEBUG2) {
				$this->CI->utils->debug_log(["{$this->getName()}:CB-S", 'order_id', $order_id, 'response_result_id', $response_result_id]);
			}

			$rlt = array('success' => false, 'next_url' => null, 'message' => 'failed');

			$chk_error = $this->checkCallbackOrder($order, $callbackExtraInfo);
			if ($chk_error['level'] >= 0x20) {
				$this->CI->sale_order->declineSaleOrder($order_id, $chk_error['mesg'], false);
				throw new Exception("checkCallbackOrder severe error, declining current sale order");
			}
			else if ($chk_error['level'] > 0x0) {
				throw new Exception("checkCallbackOrder error, dropping callback request");
			}

			$success = true;

			$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($order_id);

			// if orderStatus in [ STATUS_SETTLED, STATUS_BROWSER_CALLBACK ] :
			// put log, and ignore
			$this->CI->utils->debug_log("{$this->getName()}:CB-S", "received server callback for order $order_id, status: $orderStatus", 'all fields', $callbackExtraInfo);

			if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {

				if ($order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
					$this->CI->sale_order->setStatusToSettled($order_id);
				}
			} else {
				// update balance once
				// public function updateExternalInfo($id, $externalOrderId, $bankOrderId = null, $statusPaymentGateway = null, $statusBank = null, $response_result_id = null)
				$this->CI->sale_order->updateExternalInfo($order_id, @$callbackExtraInfo['bank_trans_id'], @$callbackExtraInfo['trans_date'], null, null, $response_result_id);

				// $this->CI->sale_order->approveSaleOrder($order_id, "auto server callback {$this->getPlatformCode()}", false);
				$bank_name = urldecode($callbackExtraInfo['bank_name']);
				// $sale_order_notes_ar = [
				// 	"auto server callback" ,
				// 	"platform {$this->getName()} ({$this->getPlatformCode()})" ,
				// 	"bank name '{$bank_name}'" ,
				// 	"account number '{$callbackExtraInfo['account_number']}'"
				// ];
				// $sale_order_notes = implode(";\n", $sale_order_notes_ar);
				$sale_order_notes_ar = [
					"approv_by"		=> "auto server callback" ,
					"pay_api"		=> [ $this->getPlatformCode(), $this->getName() ] ,
					"bank_name"		=> $bank_name ,
					"bank_account"	=> $callbackExtraInfo['account_number']
				];
				$sale_order_notes = json_encode($sale_order_notes_ar, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
				$this->CI->sale_order->approveSaleOrder($order_id, $sale_order_notes, false);
			}

			// $success = $this->CI->sale_order->endTransWithSucc();

			$rlt['success'] = $success;
			if ($success) {
				$rlt['message'] = 'OK';
			}
		}
		catch (Exception $ex) {
			$this->CI->utils->debug_log("{$this->getName()}:CB-S", 'exception', $ex->getMessage());
			if ($this->DEBUG2) {
				$this->debug_dump(["{$this->getName()}:CB-S", 'exception', $ex->getMessage()]);
			}
		}
		finally {
			return $rlt;
		}
	}

	//====implements Payment_api_interface end===================================

	/**
	 * Checks server callback arguments against stored sale order
	 *
 	 * @param	$ord	array 	row from sale_orders
 	 * @param	$flds	array 	server callback POST variables
 	 *
 	 * @return	array 	[ error_level => int , mesg => string ]
 	 *              where error_level =
 	 *                      0x0:	no error
 	 *                     0x10:	general error, just drop this callback
 	 *                     0x20:	severe error, decline this order
	 */
	private function checkCallbackOrder($ord, $flds) {

		$info = $this->getInfoByEnv();
		$error = 0x11;
		$err_mesg = 'checkCallbackOrder exec incomplete';

		try {

			// Check for secure hash
			$secure_expected = $this->secure_sign_for_server_callback($flds, $info);
			if ($secure_expected != $flds['secure']) {
				$this->writePaymentErrorLog('Wrong signature for server callback', $flds);
				$this->CI->utils->debug_log('hash mismatch', 'given hash', $flds['secure'], 'expected', $secure_expected);
				if ($this->DEBUG2) {
					$this->debug_dump(['hash mismatch', 'given hash', $flds['secure'], 'expected', $secure_expected]);
				}
				throw new Exception('Wrong signature for server callback', 0x12);
			}

			// Check site ID
			if ($flds['site_id'] != $this->SITE_ID) {
				throw new Exception("site_id ({$callback_extra['site_id']}) does not match ({$this->SITE_ID})", 0x13);
			}

			// Check amount
			if (floatval($flds['real_amount']) != floatval($ord->amount)) {
				throw new Exception("Amount mismatch (reported {$flds['real_amount']}, expected {$ord->amount}) for sale_order {$ord->id} (secure ID {$ord->secure_id})", 0x21);
			}

			// Everything goes alright
			$error = 0x0;
			$err_mesg = '';
		}
		catch (Exception $ex) {
			$ex_code = $ex->getCode();
			$error = $ex_code - $ex_code % 0x10;
			$err_mesg = $ex->getMessage();

			$this->CI->utils->debug_log("{$this->getName()}:CB-S-CHK", 'exception', $err_mesg);
			if ($this->DEBUG2) {
				$this->debug_dump(["{$this->getName()}:CB-S-CHK", 'exception', $err_mesg]);
			}
		}
		finally {
			$ret = [ 'level' => $error, 'mesg' => $err_mesg ];
			if ($this->DEBUG2)
				{ $this->debug_dump(["{$this->getName()}:CB-S-CHK", 'return', $ret]); }
			return $ret;
		}

	}

	public function directPay($order) {
		//no direct pay
		return array('success' => false);
	}

	protected function debug_dump($ar) {
		$d = "<b>{$ar[0]}</b>";
		if (count($ar) > 1) {
			$aa = array_slice($ar, 1);
			$d .= json_encode($aa);
		}
		echo "<div><tt>$d</tt></div>";
	}

}

////END OF FILE//////////////////