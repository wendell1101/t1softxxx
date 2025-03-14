<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

/**
 * Class Callback
 *
 * General behaviors include
 * * get wallet account record using transaction code
 * * get a certain order records
 * * validate tokens
 * * game authentication
 *
 * @category Payment Controller
 * @version 1.8.10
 * @copyright 2013-2022 tot
 * 
 * @property Sale_orders_status_history $sale_orders_status_history
 * @property Transactions $transactions
 * @property Common_token $common_token
 * @property Affiliatemodel $affiliatemodel
 */
class Callback extends BaseController {

	function __construct() {
		parent::__construct();
		$this->load->model('external_system');
		$this->load->library('session');
		//CHECK BY SESSION
		$this->setLangByPlayer();
		// $this->lang->load('main');
	}

	/**
	 * Payment service provider callback handlling
	 *
	 * URI: callback/process/{$systemId}/{$orderId}/[$flds[?reSendBySecureId=D146418550384]]
	 *
	 * Steps,
	 * loadExternalSystemLib()
	 * Check getInputGetAndPost()
	 * Check orderId
	 * Check validateWhiteIp()
	 * Check depositOrder() and getWalletAccountByTransactionCode()
	 * Check isOrderExpired()
	 * Check isApiDisabled()
	 * Check isDisabledPaymentAccountByOrderId()
	 * Check isDefaultCollectionAccount()
	 * Check getPlayerIdByOrderId()
	 * Check getSaleOrderStatusById()
	 * Do callbackFromServer().
	 *
	 * Case "success",
	 * 	Check go_success_page then goPaymentResultPage().
	 * 		for server callback then returnText(message|return_status|json_result)
	 *
	 * Case "no success",
	 * 	Do createSaleOrderStatusHistory()
	 * 		or returnText( return_error | return_status | return_error_msg)
	 *
	 * @param int system_id link to external_system
	 * @param int data_id link to sale_orders.id, response_results.related_id1(while request_api=deposit)
	 * @param array $flds
	 * @param string $currency (Deprecated) not used
	 */
	public function process($systemId=null, $orderId=null, $flds=null , $currency = null) {
		$this->load->model(['sale_orders_status_history', 'external_system', 'sale_order', 'operatorglobalsettings']);
		$this->load->library('playerapi_lib');

		$orderId = rtrim(urldecode($orderId), '"'); #trim '%22' when click from skype

		$orderMsg = 'system id: ' . $systemId . ', order id: ' . $orderId;
		$this->utils->debug_log('=====callback process===== ' . $orderMsg);

		$flds = (empty($flds) || $flds == 'null')? $this->getInputGetAndPost() : $flds;
		$this->utils->debug_log('=====callback process.flds===== ' , $flds);

		#just for log record
		$raw_post = file_get_contents("php://input");
		$this->utils->debug_log('=====callback process.raw_post===== ' , $raw_post);

		//load config
		list($loaded, $managerName) = $this->utils->loadExternalSystemLib($systemId);

		if ($loaded) {
			$api = $this->$managerName;

			if (empty($orderId)) {
				/** @var array $third_party_api_verification_from_fix_process */
				$third_party_api_verification_from_fix_process = $this->config->item('third_party_api_verification_from_fix_process');
				if (in_array($systemId, $third_party_api_verification_from_fix_process) && $api->notReturnBadRequest()) {
					$result = $api->callbackFromServer($orderId, $flds);
				}else if (!empty($flds)) {
					$result = $api->callbackException($flds);
				} else {
					$msg = "Wrong callback, can not get orderId nor any params from callback";
					$this->utils->error_log($msg.' '.$orderMsg);
					$api->callbackFailed($orderId, $flds, 'server', $msg);
					return $this->returnBadRequest();
				}
			} else {
				#if callback no deposit or withdrawal orderid, decline it
				$depositOrder = $this->CI->sale_order->getSaleOrderById($orderId);
				$withdrawalOrder = $this->CI->wallet_model->getWalletAccountByTransactionCode($orderId);
				if(is_null($depositOrder) && is_null($withdrawalOrder)){
					$msg = 'Wrong callback, lost deposit or withdrawal orderid';
					$this->utils->error_log($msg.' '.$orderMsg);
					$api->callbackNeedToBeNotify($orderId, $flds, 'server', $msg);

					if($api->notReturnBadRequest()) {
						$result = array('result' => false, 'error_code' => 4, 'error_message' => $msg);
						return $this->returnJsonResult($result);
					}
					else {
						return $this->returnBadRequest();
					}
				}

				#if resend, skip ip check; if not validate ip, decline it
				if(!empty($flds['reSendBySecureId'])){
					$isResendCallback = $this->CI->response_result->checkResendCallbackExists($systemId ,$orderId ,$flds['reSendBySecureId']);
					if(!$isResendCallback){
						$msg = 'Wrong resend callback, resend callback does not exist';
						$this->utils->debug_log($msg.' '.$orderMsg);
						$api->callbackNeedToBeNotify($orderId, $flds, 'server', $msg);

						if($api->notReturnBadRequest()) {
							$result = array('result' => false, 'error_code' => 6, 'error_message' => $msg);
							return $this->returnJsonResult($result);
						}
						else {
							return $this->returnBadRequest();
						}
					}
				}else{
					$ip = $api->getClientIP();
					if(!$api->validateWhiteIp($ip)){
						$msg = 'Wrong callback, callback ip : ['.$ip.'] not in white_ip_list';
						$this->utils->debug_log($msg.' '.$orderMsg, implode(',', $api->white_ip_list));
						$api->callbackNeedToBeNotify($orderId, $flds, 'server', $msg);
						if($api->notReturnBadRequest()) {
							$result = array('result' => false, 'error_code' => 7, 'error_message' => $msg);
							return $this->returnJsonResult($result);
						}
						else {
							return $this->returnBadRequest();
						}
					}
				}

				#if callback time over valid hour, decline it
				$isOrderExpired = $api->isOrderExpired($orderId);
				if($isOrderExpired){
					if( empty($flds['reSendBySecureId']) ){ // resend is exception
						$validHour = $api->getValidHour();
						$msg = 'Wrong callback, callback time over valid hour. Valid hour: '.$validHour.' hours';
						$this->utils->error_log($msg.' '.$orderMsg);
						$api->callbackFailed($orderId, $flds, 'server', $msg);
						if($api->notReturnBadRequest()) {
							$result = array('result' => false, 'error_code' => 8, 'error_message' => $msg);
							return $this->returnJsonResult($result);
						}
						else {
							return $this->returnBadRequest();
						}
					}
				}

				#if payment api is disabled, decline it
				$isApiDisabled = $this->external_system->isApiDisabled($systemId);
				if($isApiDisabled){
					$msg = 'Wrong callback, api is disabled.';
					$this->utils->error_log($msg.' '.$orderMsg);
					$api->callbackFailed($orderId, $flds, 'server', $msg);
					if($api->notReturnBadRequest()) {
						$result = array('result' => false, 'error_code' => 9, 'error_message' => $msg);
						return $this->returnJsonResult($result);
					}
					else {
						return $this->returnBadRequest();
					}
				}

				#only check payment_account when deposit
				if(substr($orderId, 0, 1) != 'W'){
					#if payment account is disabled, decline it
					$isPaymentAccountDisabled = $this->sale_order->isDisabledPaymentAccountByOrderId($orderId);
					if($isPaymentAccountDisabled){
						$msg = 'Wrong callback, payment account is disabled.';
						$this->utils->error_log($msg.' '.$orderMsg);
						$api->callbackFailed($orderId, $flds, 'server', $msg);
						if($api->notReturnBadRequest()) {
							$result = array('result' => false, 'error_code' => 10, 'error_message' => $msg);
							return $this->returnJsonResult($result);
						}
						else {
							return $this->returnBadRequest();
						}
					}

					#if is not default collection account, decline it
					$saleOrder = $this->sale_order->getSaleOrderById($orderId);
					$isDefaultCollectionAccount = $this->operatorglobalsettings->isDefaultCollectionAccount($saleOrder->payment_account_id);
					$checkDefaultCollectionAccount = $this->config->item('check_the_default_collection_account_when_callback');
					if($checkDefaultCollectionAccount && !$isDefaultCollectionAccount){
						$msg = 'Wrong callback, payment account is not default collection account.';
						$this->utils->error_log($msg.' '.$orderMsg);
						$api->callbackFailed($orderId, $flds, 'server', $msg);
						if($api->notReturnBadRequest()) {
							$result = array('result' => false, 'error_code' => 11, 'error_message' => $msg);
							return $this->returnJsonResult($result);
						}
						else {
							return $this->returnBadRequest();
						}
					}
				}

				$result = array('success' => false, 'return_error' => 'FAILED');

				$playerId = $this->getPlayerIdByOrderId($orderId);
				if ($playerId) {
					$controller = $this;
					$success = $this->lockAndTransForPlayerBalance($playerId, function ()
						use ($controller, $orderId, $flds, $managerName, &$result) {

                        $sale_order_status = $this->sale_order->getSaleOrderStatusById($orderId);
                        if($sale_order_status != Sale_order::STATUS_SETTLED && $sale_order_status != Sale_order::STATUS_DECLINED){
                            $this->sale_orders_status_history->createSaleOrderStatusHistory($orderId,Sale_order::DEPOSIT_STATUS_GET_CALLBACK);
                        }
                        if(! empty($flds['reSendBySecureId'])){
							unset($flds['reSendBySecureId']);
                        }
						$result = $controller->$managerName->callbackFromServer($orderId, $flds);

						$success= true;
						return $success;
					});
					if(substr($orderId, 0, 1) != 'W' && !$this->config->item('enable_async_approve_sale_order')){
						$sale_order_status = $this->sale_order->getSaleOrderStatusById($orderId);
						$this->utils->debug_log('===triggerDepositEvent and processSaleOrderAddonFunction=== , status_settled:', $sale_order_status);
						if($sale_order_status == Sale_order::STATUS_SETTLED){
							if(!$this->config->item('disable_3rd_party_payment_trigger_event')) {
								$this->utils->debug_log('===triggerDepositEvent and processSaleOrderAddonFunction is success, status_settled:', $sale_order_status);
								$this->triggerDepositEvent($playerId, $orderId, null, $systemId, null, null);
							}

							$this->$managerName->processSaleOrderAddonFunction($orderId, $success);
						}
					}
				} else {
					$this->utils->error_log('lost order and player id', $orderId);
				}
			}

			$this->utils->debug_log('result', $result);
			if ($result['success']) {
				if (isset($result['go_success_page']) && @$result['go_success_page']) {
					//browser callback
					$this->goPaymentResultPage($systemId, $orderId, $result);

				} else {
					//only for server callback
					if (isset($result['message'])) {
						$this->returnText($result['message']);
					}
					else if(isset($result['return_status'])){
						$this->returnText($result['return_status']);
						return;
					}
                    if (isset($result['json_result'])) {
                        $this->returnJsonResult($result['json_result']);
                    }
				}

				return;
			} else {
                $this->sale_orders_status_history->createSaleOrderStatusHistory($orderId,Sale_order::DEPOSIT_STATUS_CHECK_CALLBACK_ORDER_FAILED);

				if (isset($result['return_error']) && !empty($result['return_error'])) {
					$this->returnText($result['return_error']);
					return;
				}else if(isset($result['return_status'])){
					$this->returnText($result['return_status']);
					return;
				}else if(isset($result['return_error_msg'])){
					return $this->returnText($result['return_error_msg']);
				}else if(isset($result['return_error_json'])){
					return $this->returnJsonResult($result['return_error_json']);
				}
			}
		} else {
			$msg = "Wrong callback, can not load ExternalSystemLib";
			$this->utils->error_log($msg.' '.$orderMsg);
			#use DUMMY to access api
			list($loaded, $managerName) = $this->utils->loadExternalSystemLib(DUMMY_PAYMENT_API);
			$api = $this->$managerName;
			$api->callbackNeedToBeNotify($orderId, $flds, 'server', $msg);
			return $this->returnBadRequest();
		}
		$this->returnBadRequest();
	}

	/**
	 * @param int $systemId
	 * @return Boolean
	 */
	public function fixed_process($systemId){
		list($loaded, $managerName) = $this->utils->loadExternalSystemLib($systemId);

		$params = $this->getInputGetAndPost();
		if(empty($params)){
			$params = $_REQUEST;
		}

        $this->utils->debug_log('System', $systemId);
		if(!$loaded){
			$this->utils->error_log("Unknown system [$systemId]");
			return;
		}

		if (method_exists($this->$managerName, 'getInputGetAndPost')) {
			$params = $this->$managerName->getInputGetAndPost();
		}
		$this->utils->debug_log('Params', $params);

		$orderId = $this->$managerName->getOrderIdFromParameters($params);

		//not used
		//$currency = $this->$managerName->getTargetCurrencyFromFixProcess($params);
		
		return $this->process($systemId, $orderId, $params);
	}

	/**
	 * @param int $systemId
	 * @param int $orderId
	 * @return void
	 */
	public function show_success($systemId, $orderId) {
		$this->setLangByPlayer();
		$message = $this->session->flashdata('message');
		if (empty($message)) {
			$message = lang('payment.success');
		}
		$nextUrl = $this->session->flashdata('next_url');
		if (empty($nextUrl)) {
			$nextUrl = $this->utils->getPlayerHomeUrl();
		}

		//load sale order
		$saleOrder = $this->sale_order->getSaleOrderById($orderId);
		list($saleOrder, $transaction, $promoTrans) = $this->getSaleOrderAndTransaction($orderId);

		if($this->config->item('enabled_www_and_m_domain_on_sucess_page')){
			if(!empty($saleOrder->direct_pay_extra_info)){
				$extra_info = json_decode($saleOrder->direct_pay_extra_info, true);
				if(isset($extra_info['source_reference_url']) && !empty($extra_info['source_reference_url'])){
					$nextUrl = $extra_info['source_reference_url'];
				}else{
					if($this->utils->is_mobile()){
						$nextUrl = $this->utils->getPlayerHomeUrl('m');
					}else{
						$nextUrl = $this->utils->getPlayerHomeUrl('www');
					}
				}
			}
		}

		$this->load->model('external_system');
        $this->utils->debug_log("====================show_success platform_code:", $systemId);
        $systemInfo = $this->external_system->getSystemById($systemId);
        $extraInfoJson = (!isset($systemInfo->live_mode) || $systemInfo->live_mode) ? $systemInfo->extra_info : $systemInfo->sandbox_extra_info;
        $extraInfo = json_decode($extraInfoJson, true) ?: array();
        $this->utils->debug_log("====================show_success extraInfo:", $extraInfo);
        $hide_back_btn = false;
        //force_desktop_iframe
        if (!empty($extraInfo['hide_callback_success_back_btn'])) {
			$hide_back_btn = true;
        }

		$data = array('message' => $message, 'next_url' => $nextUrl,
			'system_id' => $systemId, 'order_id' => $orderId,
			'sale_order' => $saleOrder, 'transaction' => $transaction,
			'promo_trans' => $promoTrans, 'hide_back_btn' => $hide_back_btn);
		$this->load->view('player/callback_success', $data);
	}

	/**
	 * @param int $systemId
	 * @param int $orderId
	 * @return void
	 */
	public function show_pending($systemId, $orderId) {
		$nextUrl = $this->session->flashdata('next_url');
		if (empty($nextUrl)) {
			//go home or go game
			$nextUrl = $this->utils->getPlayerHomeUrl();
		}
		//load sale order
		$saleOrder = $this->sale_order->getSaleOrderById($orderId);

		$data = array('home_url' => '/iframe_module/iframe_viewCashier', 'next_url' => $nextUrl, 'sale_order' => $saleOrder,
					'system_id' => $systemId, 'order_id' => $orderId);

		$this->load->view('player/request_pending', $data);
	}

	/**
	 * @param int $orderId
	 * @return array
	 */
	private function getSaleOrderAndTransaction($orderId) {
		$this->load->model(array('sale_order', 'transactions'));
		$saleOrder = $this->sale_order->getSaleOrderById($orderId);
		$transaction = null;
		$promoTrans = null;
		if ($saleOrder) {
			if (isset($saleOrder->transaction_id) && $saleOrder->transaction_id) {
				$transaction = $this->transactions->getTransaction($saleOrder->transaction_id);
			}
			if (isset($saleOrder->player_promo_id) && $saleOrder->player_promo_id) {
				$promoTrans = $this->transactions->getTransactionByPlayerPromoId($saleOrder->id, $saleOrder->player_promo_id);
			}

		}
		return array($saleOrder, $transaction, $promoTrans);
	}

	/**
	 * @param int $systemId
	 * @param int $orderId
	 * @return load template
	 */
	public function show_error($systemId, $orderId) {
		// $this->setLangByPlayer();
		$message = $this->session->flashdata('message');
		if (empty($message)) {
			$message = lang('error.payment.failed');
		}
		$nextUrl = $this->session->flashdata('next_url');
		if (empty($nextUrl)) {
			//retry payment
			$this->load->model(array('sale_order'));
			$ord = $this->sale_order->getSaleOrderById($orderId);
			$nextUrl = $this->getPaymentUrl($systemId, $ord->amount, $ord->player_id);
		}
		$data = array('message' => $message, 'next_url' => $nextUrl, 'home_url' => '/player_center2',
			'system_id' => $systemId, 'order_id' => $orderId);
		$this->load->view('player/callback_error', $data);
	}

	/**
	 * @param int $systemId
	 * @return void
	 */
	public function fixed_browser($systemId) {
		$resultCallback = array('success' => false, 'message' => lang('error.gateway.down'), 'next_url' => null, 'order_id' => null);

		list($loaded, $managerName) = $this->utils->loadExternalSystemLib($systemId);

		$this->utils->debug_log('systemId', $systemId, 'managerName', $managerName);
		if ($loaded) {
			$flds = $this->getInputGetAndPost();
			$orderId = $this->$managerName->getOrderIdFromParameters($flds);

			$playerId = $this->getPlayerIdByOrderId($orderId);
			if ($playerId) {
				$controller=$this;
				$success = $this->lockAndTransForPlayerBalance($playerId, function ()
					use ($controller, $orderId, $flds, $managerName, &$resultCallback) {
					$resultCallback = $controller->$managerName->callbackFromBrowser($orderId, $flds);
					$controller->utils->debug_log('resultCallback', $resultCallback);
					$success=false;
					return $success;
				});
			} else {
				$this->utils->debug_log('lost order and player id', $orderId);
			}

		}

		//go result page
		$this->goPaymentResultPage($systemId, $orderId, $resultCallback);
	}

	/**
	 * note: browser -> success/failed -> goPlayerCallbackPage -> show_success/show_error(redirect) -> callback_success/callback_error(view)
	 *
	 * @param string $result
	 * @param int $systemId
	 * @param int $orderId
	 */
	public function browser($result, $systemId, $orderId) {
		$flds = $this->getInputGetAndPost();
		$this->utils->debug_log('callback browser', $flds);
		if ($result == 'success') {
			$this->success($systemId, $orderId, $flds);
		} else if ($result == 'failed') {
			$this->failed($systemId, $orderId, $flds);
		}
	}

	public function fixed_validation($systemId) {
		list($loaded, $managerName) = $this->utils->loadExternalSystemLib($systemId);
		$params = $this->getInputGetAndPost();
        $this->utils->debug_log('fixed_validation: System', $systemId, 'params', $params);

		if($loaded) {
			$returnText = $this->$managerName->getOrderValidation($params);

			if(is_array($returnText)){
				return $this->returnJsonResult($returnText);
			} else {
				return $this->returnText($returnText);
			}
		}
	}

	/**
	 *
	 *
	 * @param int $systemId
	 * @param string $action
	 */
	public function general_routing($systemId, $action) {
        $this->utils->debug_log("general_routing: System [$systemId], Action [$action]");
		list($loaded, $managerName) = $this->utils->loadExternalSystemLib($systemId);

		if($loaded) {
			$type = $this->$managerName->getRoutingType($action);
		}

		switch ($type) {
			case 'validation':
				return $this->fixed_validation($systemId);
				break;

			case 'process':
				$this->fixed_process($systemId);
				break;

			default:
				return $this->returnBadRequest();
				break;
		}
	}

	/**
	 * @param int $systemId
	 * @param int $orderId
	 * @param array $flds
	 *
	 * @return void
	 */
	private function success($systemId, $orderId, $flds) {
		$resultCallback = array('success' => false, 'message' => lang('error.gateway.down'), 'next_url' => null, 'order_id' => $orderId);

		list($loaded, $managerName) = $this->utils->loadExternalSystemLib($systemId);

		$this->utils->debug_log('systemId', $systemId, 'managerName', $managerName, 'orderId', $orderId);
		if ($loaded) {
			$playerId = $this->getPlayerIdByOrderId($orderId);
			if ($playerId) {
				$controller = $this;
				$success = $this->lockAndTransForPlayerBalance($playerId, function ()
					use ($controller, $orderId, $flds, $managerName, &$resultCallback) {
						$resultCallback = $controller->$managerName->callbackFromBrowser($orderId, $flds);
						return false;
				});
			} else {
				$this->utils->debug_log('lost order and player id', $orderId);
			}
		}

		//go result page
		$this->goPaymentResultPage($systemId, $orderId, $resultCallback);
	}

	/**
	 * @param int $systemId
	 * @param int $orderId
	 * @param array $flds
	 *
	 * @return void
	 */
	private function failed($systemId, $orderId, $flds) {
		$resultCallback = array('success' => false, 'message' => lang('error.payment.failed'), 'next_url' => null, 'order_id' => $orderId);

		$this->load->model(array('sale_order'));
		$oldStatus = $this->sale_order->getStatus($orderId);
		if ($oldStatus == Sale_order::STATUS_PROCESSING || $oldStatus == Sale_order::STATUS_CHECKING) {
			//set failed
			$this->sale_order->setStatusToFailed($orderId);
		} else {
			$this->utils->debug_log('cannot set status to failed', $orderId);
		}
		//save error message
		$this->utils->debug_log('systemId:', $systemId, ' ,orderId:', $orderId, ' ,flds:', $flds);
		$this->goPaymentResultPage($systemId, $orderId, $resultCallback);
	}

	const API_METHOD_VALIDATE_MEMBER = 'ValidateMember';
	const API_METHOD_VALIDATE_PASSWORD = 'validate_password';

	/**
	 * detail: callback auth
	 *
	 * @param  string $gamePlatformId game platform id
	 * @param  string $method         callback method
	 * @param  string $platform       web/pc/mobile/android/ios
	 * @return mixin  by api
	 */
	public function game($gamePlatformId, $method = null, $platform = 'web') {
		$this->utils->debug_log('------------------ init callback --------------------');
		$rawRequest = $this->rawRequest();

        $load_api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
        if( !$load_api->checkGameIpWhitelistByGameProvider($this->input->ip_address()) ){
            $response = array(
                "status" => "error",
                "code" => 'W_01',
                "message" => 'IP restricted',
		    );
            return $this->returnJsonResult($response);
        };

        switch ($gamePlatformId) {
			case NEX4D_GAME_API:
	        	if ($method == 'requestBoUrl' || $method ==  'getDrawListInfo') {
					$gameplay_api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$response = $gameplay_api->callback($method);
					return $this->returnJsonResult($response);
				} else {
					return show_error('Bad Parameters [Platform Id]', 400);
				}
        	break;

        	case ONE88_API:
	        	if ($method == self::API_METHOD_VALIDATE_MEMBER) {
					if ($this->isPostMethod()) {
						$result = file_get_contents('php://input');
						/** @var array|string $encryptedXML */
						$encryptedXML = $result;
						$this->utils->debug_log('(Callback) ENCRYPTED-XML-INPUT:', count($encryptedXML), ' RAW-RESULT: ', $encryptedXML);

						$key = $this->config->item('one88_merchant_key');
						$decryptedXML = $this->CI->utils->aes128_cbc_decrypt($key, base64_decode($encryptedXML), $key);
						$this->utils->debug_log('(Callback) ENCRYPTED-XML:', count($encryptedXML), ' (Callback) DECRYPTED-XML:', $decryptedXML);

						$one88_api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
						return $this->returnText($one88_api->callback(strtolower($method), $decryptedXML));
					}
				} else {
					return show_error('Bad Parameters [Platform Id]', 400);
				}
        	break;

        	case LB_API:
        		if ($method == self::API_METHOD_VALIDATE_MEMBER || $method == self::API_METHOD_VALIDATE_PASSWORD) {
					$this->utils->debug_log('(LB - Callback) ValidateMember or validate_password');
					if ($this->isPostMethod()) {
						$result = file_get_contents('php://input');
						$this->utils->debug_log('(LB - Callback) RAW-RESULT: ', $result);

						$lb_api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
						return $this->returnText($lb_api->callback(strtolower($method), $result));
					}
				} else {
					return show_error('Bad Parameters [Platform Id]', 400);
				}
        	break;

        	case GAMEPLAY_API:
        		if (strtolower($method) == strtolower(self::API_METHOD_VALIDATE_MEMBER)) {
					$this->utils->debug_log('(GAMEPLAY - Callback) ValidateMember');
					$gameplay_api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					if ($platform == 'web') {
						$result = $this->getInputGetAndPost();
						$this->utils->debug_log('(CALLBACK_POST_GET_GAMEPLAY_WEB)  - Callback RAW-RESULT: ', $result);
						return $this->returnXml($gameplay_api->callback(strtolower($method), $result['ticket']));
					}else{
	                    # MOBILE PC
						//$result = $this->getInputGetAndPost();
						$result = file_get_contents('php://input');
						$this->utils->debug_log('(CALLBACK_POST_GET_GAMEPLAY_MOBILE_PC)  - Callback RAW-RESULT: ', $result);
						return $this->returnText($gameplay_api->callback(strtolower($method), $result, $platform));
					}
				} else {
					return show_error('Bad Parameters [Platform Id]', 400);
				}
        	break;

        	case OPUS_API:
        	case OPUS_SPORTSBOOK_API:
        	case OPUS_KENO_API:
        		if (strtolower($method) == strtolower(self::API_METHOD_VALIDATE_MEMBER)) {
					$this->utils->debug_log('(OPUS - Callback) ValidateMember');
					if ($this->isPostMethod()) {
						$result = ($platform == 'web') ? file_get_contents('php://input') : $this->input->post();
					$this->utils->debug_log('(POST) OPUS - Callback RAW-RESULT: ', $result);
						$opus_api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
						return $this->returnXml($opus_api->callback(strtolower($method), $result, $platform));
					} elseif ($this->isGetMethod()) {
						$this->utils->debug_log('(GET) OPUS - Callback Result: ' . $_GET["ticket"]);
						if ($_GET["ticket"]) {
							$opus_api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
							return $this->returnXml($opus_api->callback(strtolower($method), $_GET["ticket"]));
						}
					}
				} else {
					return show_error('Bad Parameters [Platform Id]', 400);
				}
        	break;

        	case BS_API:
        		if (in_array($method, array('authenticate', 'transfer', 'endGameSession', 'transferStatus', 'getAccountInfo', 'bonusRelease'))) {
					$get = $this->input->get() ?: array();
					$post = $this->input->post() ?: array();
					$params = array_merge($get, $post);
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$data = $api->callback($method, $params);

					$xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><EXTSYSTEM></EXTSYSTEM>");
					$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);

					return $this->returnXml($xmlData);
				} else {
					return show_error('Bad Parameters [method]', 400);
				}
        	break;

        	case ENTWINE_API:
        		$this->utils->debug_log('(ENTWINE - Callback) LOGIN METHOD: ', $method);
				if (in_array($method, array('dlogin', 'wlogin', 'deposit', 'withdraw',
					'player_balance', 'auto_cashier', 'cashier_cs',
					'cashier_ct', 'cashier_en', 'cashier_th', 'cashier_ind',
					'reg_cs', 'reg_ct', 'reg_en', 'reg_th', 'reg_ind'))) {

					if ($this->isPostMethod()) {
						$result = file_get_contents('php://input');
						$this->utils->debug_log('(POST) ENTWINE - Callback RAW-RESULT: ', $result);
						$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
						$xmlData = $api->callback($method, $result);
						$this->CI->utils->debug_log("XML RETURN: " . $this->returnXml($xmlData));
						return $this->returnXml($xmlData);
					}
				}
        	break;

        	case EBET_API:
        	case EBET2_API:
        	case EBET_THB_API:
        	case EBET_USD_API:
        		if ($this->external_system->isGameApiActive($gamePlatformId)) {
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$request = file_get_contents('php://input');
					$this->utils->debug_log('(POST) EBET - Callback RAW-RESULT: ' . $request);
					$params = array_merge(json_decode($request, true) ?: array(), ($this->input->post() ?: array()));
					$data = $api->callback($params);
				} else {
					$data = array('status' => 505);
				}
				return $this->returnJsonResult($data);
			break;
			case QT_API:
				if (!empty($method)){
					if ($this->external_system->isGameApiActive($gamePlatformId)) {
						$gameplay_api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
						$result = file_get_contents('php://input');
						$this->utils->debug_log('(POST) QT - Callback RAW-RESULT: ' . $result);
						$data = $gameplay_api->callback(strtolower($method), $result, $platform);
					}
					if ($data['status'] == 401) { // invalid INVALID_LOGIN
						http_response_code(401);
						return $this->returnJsonResult($data['header']);
					} else if ($data['status'] == 400) { // INVALID_TOKEN
						http_response_code(400);
						return $this->returnJsonResult($data['header']);
					} else { // SUCCESS
						http_response_code(200);
						return $this->returnJsonResult($data['header']);
					}
				} else {
					return show_error('Bad Parameters [method]', 400);
				}
			break;
        	case AG_API:
        	case AGIN_API:
        		if ($this->external_system->isGameApiActive($gamePlatformId)) {
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$request = file_get_contents('php://input');
					$this->utils->debug_log('(POST) AG - Callback RAW-RESULT: ' . $request);
					$data = $api->callback($request, $platform, $method);
					$this->utils->debug_log('AG callback return', $data);
					return $this->returnXml($data);
				} else {
					return show_error('Bad Parameters [method]', 400);
				}
        	break;

			case ONEWORKS_API:
				$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
				if(!empty($method) && $method == "login"){
					if($this->isGetMethod()){
						$data['_csrf_hidden_field'] = $this->_initCSRFAndReturnHiddenField();
						$lang = null;
						if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
							$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
						    $acceptLang = ['zh', 'id', 'vi' , 'ko', 'th'];
						    $lang = in_array($lang, $acceptLang) ? $lang : 'en';
						    switch ($lang) {
						    	case 'zh':
						    		$lang = language_function::INT_LANG_CHINESE;
						    		break;
						    	case 'id':
						    		$lang = language_function::INT_LANG_INDONESIAN;
						    		break;
						    	case 'vi':
						    		$lang = language_function::INT_LANG_VIETNAMESE;
						    		break;
						    	case 'ko':
						    		$lang = language_function::INT_LANG_KOREAN;
						    		break;
						    	case 'th':
						    		$lang = language_function::INT_LANG_THAI;
						    		break;
						    	case 'en':
						    		$lang = language_function::INT_LANG_ENGLISH;
						    		break;

						    	default:
						    		$lang = null;
						    		break;
						    }
						}

					    $data['lang'] = $lang;
					    $this->utils->debug_log('ONEWORKS Callback login: ', $lang);
						return $this->load->view('iframe/player/goto_login_oneworks',$data);
					}
				}

				if(!empty($method) && $method == "transfer"){
					if($this->isGetMethod()){
						$token = $this->input->get('token');
						$this->utils->debug_log('ONEWORKS - CASHIER TOKEN: ', $token);
						if (empty($token)) {
							$this->utils->debug_log('ONEWORKS - CASHIER EMPTY TOKEN: ');
							redirect('/');
						}
						$redirect_url = "/iframe/auth/login_with_token/{$token}?next=/player_center/mobile_transfer_view/null/".ONEWORKS_API;
						$this->utils->debug_log('ONEWORKS - REDIRECT: ', $redirect_url);
						redirect($redirect_url);
					}
				}

        		$resultArr = array();
				if ($platform == 'web') {
					$result = file_get_contents('php://input');
					$this->utils->debug_log('(POST) ONEWORKS - Web Callback RAW-RESULT : ', $result);
					parse_str($result, $resultArr);
					$this->utils->debug_log('(POST) ONEWORKS - Web Callback ARRAY-RESULT : ', $resultArr);
				} else {
					$get = $this->input->get() ?: array();
					$post = $this->input->post() ?: array();
					$resultArr = array_merge($get, $post);
					$this->utils->debug_log('(POST) ONEWORKS - Mobile Callback ARRAY-RESULT : ', $resultArr);
				}
				$xmlData = $api->callback($resultArr);
				$this->CI->utils->debug_log("XML RETURN: " , $xmlData);
				return $this->returnXml($xmlData);
        	break;

        	case SPORTSBOOK_API:
				$this->utils->debug_log('(SPORTSBOOK - Callback) RESULT: ', $method);

				//if logout , just redirect back
				if($method=='logout'){
					//go back
					$this->utils->debug_log('(SPORTSBOOK - Callback) logout go back');
					$home_www=$this->utils->getSystemUrl('www');
					return redirect($home_www);
				}

				$get = $this->input->get() ?: array();
				$post = $this->input->post() ?: array();
				$params = array_merge($get, $post);
				$this->utils->debug_log('(GETPOST) SPORTSBOOK - Callback RAW-RESULT: ', $params);
				$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
				$jsonData = $api->callback($method, $params);
				$this->CI->utils->debug_log("JSON RETURN: " , $jsonData);
				return $this->returnJsonResult($jsonData);
			break;

			case SA_GAMING_API:
				$this->utils->debug_log('(SA GAMING - Callback) RESULT: ', $method);
				if (strtolower($method)=="checkkey"){
					if($this->isGetMethod()) {
						if (isset($_GET["checkkey"])) {
							$this->utils->debug_log('(GET) SA GAMING - Callback Result: ' . $_GET["checkkey"]);
							if ($_GET["checkkey"]) {
								$gameplay_api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
								$ret = $gameplay_api->callback(strtolower($method), $_GET["checkkey"]);
								$this->utils->debug_log('(GET) SA GAMING RETURN: ' . $ret);
								echo $ret;
								return;
							}
						}
					}
				} else if($method == "queryBetSetting") {
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$request = file_get_contents('php://input');
					$response = $api->queryBetSetting($method,$request);
					return $this->returnJsonResult($response);
				} else if($method == "app") {
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$request = file_get_contents('php://input');
					$this->utils->debug_log('(GET) SA Gaming - Callback Result: ' . $request);
					$response = $api->callback($method,$request);
					$this->utils->debug_log('SA Gaming - Callback Response Result: ' . $response);

					return $this->returnXml($response);
				}
			break;

			case SPADE_GAMING_API:
				if ($this->external_system->isGameApiActive($gamePlatformId)) {
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$request = file_get_contents('php://input');
					$this->utils->debug_log('(POST) Spade - Callback RAW-RESULT: ' . $request);
					$olparams = array_merge(json_decode($request,true) ?: array(), ($this->input->post() ?: array()));
					$params = json_decode($request,true);
					$data = $api->callback($params,$platform);
				} else {
					$data = array('status' => 505);
				}
				return $this->returnJsonResult($data);
			break;

			case PNG_API:
				if ($this->external_system->isGameApiActive($gamePlatformId)) {
					if ($this->isPostMethod()) {
						if($method == "syncLDF"){
							$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
							$request = file_get_contents('php://input');
							$this->utils->debug_log('(POST) PNG - Callback RAW-RESULT: ' . $request);
							$params = json_decode($request,true);
							$data = $api->syncLDF($params,$platform);
						}else {
							return show_error('Bad Parameters [method]', 400);
						}
					}
				} else {
					$data = array('status' => 505);
				}
				return $this->returnJsonResult($data);
			break;

			case EBET_BBTECH_API:
				if ($this->external_system->isGameApiActive($gamePlatformId)) {
					if ($this->isPostMethod()) {
						if($method == "login"){
							$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
							$request = file_get_contents('php://input');
							$this->utils->debug_log('(POST) EBET_BBTECH_API - Callback RAW-RESULT: ' . $request);
							$params = json_decode($request,true);
							$data = $api->callback($params,$platform);
						}else {
							return show_error('Bad Parameters [method]', 400);
						}
					}
				} else {
					$data = array('status' => 505);
				}
				return $this->returnJsonResult($data);
			break;

			case EBET_SPADE_GAMING_API:
				if ($this->external_system->isGameApiActive($gamePlatformId)) {
	                if ($this->isPostMethod()) {
	                    if($method == "authenticate_player"){
	                        $api        = $this->utils->loadExternalSystemLibObject($gamePlatformId);
	                        $request    = file_get_contents('php://input');
	                        $params     = json_decode($request,true);
	                        $data       = $api->callback($params,$platform);

	                        $this->utils->debug_log('(POST) EBET_BBTECH_API - Callback RAW-RESULT: ' . $request);

	                    }else {
	                        return show_error('Bad Parameters [method]', 400);
	                    }
	                }
	            } else {
	                $data = array('status' => 505);
	            }
	            return $this->returnJsonResult($data);
			break;

			case EBET_OPUS_API:
				if ($this->external_system->isGameApiActive($gamePlatformId)) {
	                if ($this->isPostMethod()) {
	                    if ($method == "authenticate_player"){
	                        $api        = $this->utils->loadExternalSystemLibObject($gamePlatformId);
	                        $request    = file_get_contents('php://input');
	                        $params     = json_decode($request,true);
	                        $data       = $api->callback($params, $platform);

	                        print_r($data);
	                        die();
	                        $this->utils->debug_log('(POST) EBET_OPUS_API - Callback RAW-RESULT: ' . $request);

	                    }else {
	                        return show_error('Bad Parameters [method]', 400);
	                    }
	                }
	            } else {
	                $data = array('status' => 505);
	            }
	            return $this->returnJsonResult($data);
			break;

			case SBTECH_API:
				if ($this->external_system->isGameApiActive($gamePlatformId)) {
					if($method == "status" || $method == 'refresh_session') {
						$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
						$token = isset($_GET['token']) ? $_GET['token'] : '';

						if(isset($_GET['operatorToken'])){
							$token = $_GET['operatorToken'];
						}

						$data = $api->callback($token, $method);
					} else {
						return show_error('Bad Parameters [method]', 400);
					}
				} else {
					$data = array('status' => 505);
				}
				echo "jsoncb"."(".json_encode($data).");";
			break;

			case GMT_GAME_API:
				$request_json = file_get_contents('php://input');
				$request_json = json_decode($request_json, true);
				$token = isset($request_json['launchToken']) ? $request_json['launchToken'] : null;
				if ($this->external_system->isGameApiActive($gamePlatformId) && !empty($token)) {
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$data = $api->callback($token, $method);
					if($data){
						return $this->returnJsonResult($data);
					}else{
						return show_error('Bad Parameters [method]', 400);
					}
				} else {
					return show_error('Bad Parameters [method]', 400);
				}

			break;

			case OM_LOTTO_GAME_API:
				$request = $_GET;
				$this->CI->utils->debug_log('OMLOTTO (callback)', 'request', $request);
				if ($this->external_system->isGameApiActive($gamePlatformId) && !empty($request)) {
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$data = $api->callback($request, $method);
					$this->CI->utils->debug_log('OMLOTTO (callback)', 'response', $data);
					if($data){
						return $this->returnJsonResult($data);
					}else{
						return show_error('Bad Parameters [method]', 400);
					}
				} else {
					return show_error('Bad Parameters [method]', 400);
				}

			break;

			case HP_2D3D_GAME_API:
				$request = $_GET;
				$this->CI->utils->debug_log('HP_2D3D (callback)', 'request', $request);
				if ($this->external_system->isGameApiActive($gamePlatformId) && !empty($request)) {
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$data = $api->callback($request, $method);
					$this->CI->utils->debug_log('HP_2D3D (callback)', 'response', $data);
					if($data){
						return $this->returnJsonResult($data);
					}else{
						return show_error('Bad Parameters [method]', 400);
					}
				} else {
					return show_error('Bad Parameters [method]', 400);
				}
			break;

			case SBTECH_BTI_API:
				if ($this->external_system->isGameApiActive($gamePlatformId)) {
					if($method == "status" || $method == 'refresh_session') {
						$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
						$token = isset($_GET['token']) ? $_GET['token'] : '';
						$data = $api->callback($token, $method);
					} else {
						return show_error('Bad Parameters [method]', 400);
					}
				} else {
					$data = array('status' => 505);
				}
				echo "jsoncb"."(".json_encode($data).");";
			break;

			case PGSOFT_API:
			case PGSOFT3_API:
				if($this->external_system->isGameApiActive($gamePlatformId)) {
					$post_params = $this->getInputGetAndPost();
					$this->utils->debug_log('(POST) PGSOFT - Callback : ', $post_params);
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$data = $api->callback($post_params, $method);
				}else{
					$data = array(
						'data' => null,
						'error'=> [
							'code' => '1200',
							'message' => 'Invalid request.'
						]
					);
				}
				if(!is_array($data)){
					$data = json_decode($data);
				}
				return $this->returnJsonResult($data);
			break;
            case HKB_GAME_API:
				if ($this->external_system->isGameApiActive($gamePlatformId))
                {
                    $api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
                    $method_params = $this->getInputGetAndPost();
                    $params = $api->callback($method_params, $method);
				} else {
					$params = [
                        "code" => 503,
                        "msg" => "Invalid Data Input (method)"
                    ];
				}

				return $this->returnJsonResult($params);
			break;
			case GL_API :
				if($this->external_system->isGameApiActive($gamePlatformId)) {
					$post_params = $this->getInputGetAndPost();
					$this->utils->debug_log(__METHOD__, 'GL_API callback', [ 'method' => $method, 'post_params' => $post_params ]);
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$ret = $api->callback($post_params, $method);
				}
				else {
					$ret = [ 'status' => 505 ];
				}

				return $this->returnJsonResult($ret);
			break;

			case BETSOFT_API:
				if (in_array($method, ['authenticate', 'transfer', 'endGameSession', 'transferStatus', 'getAccountInfo', 'bonusRelease', 'betResult'] )) {
					$get = $this->input->get() ?: array();
					$post = $this->input->post() ?: array();
					$params = array_merge($get, $post);

					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$data = $api->callback($method, $params);

					$xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><EXTSYSTEM></EXTSYSTEM>");
					$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);

					return $this->returnXml($xmlData);
				} else {
					return show_error('Bad Parameters [method]', 400);
				}
				break;

			case HG_API:
					$this->utils->debug_log('(HOGAMING - Callback) ValidateMember');
					$hoGamingApi = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$result = file_get_contents('php://input');
					$this->utils->debug_log('(CALLBACK_POST_GET_HOGAMING)  - Callback RAW-RESULT: ', $result);
					return $this->returnXml($hoGamingApi->callback(strtolower($method), $result));
				break;

			case GGPOKER_GAME_API:
				if ($this->external_system->isGameApiActive($gamePlatformId)) {
					$authorization_key = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : null;
					$request = file_get_contents('php://input');
					$this->utils->debug_log('(POST) GGPOKER_GAME_API - Callback RAW-RESULT: ' . $request);
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$params = json_decode($request,true);
					$loginData = array();
					if(!empty($method) && $method == "login"){
						$brandId = filter_input(INPUT_GET,"brandId",FILTER_SANITIZE_STRING);
						$redirectUri = filter_input(INPUT_GET,"redirectUri",FILTER_SANITIZE_STRING);
						$loginData = array(
							"brandId" => $brandId,
							"redirectUri" => $redirectUri
						);
					}
					$data = $api->callback($params,$authorization_key,$loginData);
					$this->utils->debug_log('(POST) GGPOKER_GAME_API - Callback RAW-RESPONSE: ' . json_encode($data));
					$this->CI->load->model("response_result");
        			$flag = $data ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        			$apiName = isset($params['requestType']) ? $params['requestType']: null;
        			$field = array(
        				'decoded_result_text'=>json_encode($data),
        			);
        			$this->CI->response_result->saveResponseResultForFile(GGPOKER_GAME_API, $flag, $apiName, json_encode($params), null, $field);
					if(isset($data['login']) && $data['success'] && $data['login']){
						return $this->load->view('iframe/player/goto_login_ggpokerew',$data);
					}
					return $this->returnJsonResult($data);
				} else {
					http_response_code(400);
					$data = array(
						"code" 		=> "INTERNAL_ERROR",
						"message" 	=> "Game not active."
					);
				}
				return $this->returnJsonResult($data);
			break;

			case PLAYSTAR_API:
				if ($this->external_system->isGameApiActive($gamePlatformId)) {
					$request = $this->input->get() ?: array();
					$this->utils->debug_log('(GET) PLAYSTAR_GAME_API - Callback RAW-RESULT: ' . json_encode($request));
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$data = $api->callback($request);
				}else {
					http_response_code(400);
					$data = [
						"code" 		=> "INTERNAL_ERROR",
						"message" 	=> "Game not active."
					];
				}
				return $this->returnJsonResult($data);
			break;

			case N2LIVE_API:
				if ($this->external_system->isGameApiActive($gamePlatformId)) {
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);

					if($method == "login_cashier"){
						 $ticket = filter_input(INPUT_GET, 'ticket', FILTER_SANITIZE_URL);
						 $username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_URL);
						 $request = array(
						 	"username" => $username,
						 	"ticket" => $ticket
						 );
						 $result = $api->callback($request, $method);
						 return redirect($result['forward_url']);
					} else {
						$request = file_get_contents('php://input');
						$this->utils->debug_log('(POST) N2LIVE_API - Callback RAW-RESULT: ' . json_encode($request));
						$data = $api->callback($request, $method);
					}

				}else {
					http_response_code(400);
					$sxe  = new SimpleXMLElement("<message/>");
					$status = $sxe->addChild('status','fail');
					$result = $sxe->addChild('result','Game not active.');
					$data =  $sxe->asXML();
				}
				return $this->returnXml($data);
			break;

			case ISIN4D_API:
			case ISIN4D_IDR_B1_API:
			case ISIN4D_CNY_B1_API:
			case ISIN4D_THB_B1_API:
			case ISIN4D_USD_B1_API:
			case ISIN4D_VND_B1_API:
			case ISIN4D_MYR_B1_API:
				if ($this->external_system->isGameApiActive($gamePlatformId)) {
					$postJson = file_get_contents("php://input");
					$this->utils->debug_log("ISIN4D Callback ====> ",$postJson);

					$post = !empty($postJson) ? json_decode($postJson,true) : array();
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$response = $api->callback($method, $post);
					return $this->returnJsonResult($response);
				}
			break;

			case QQKENO_QQLOTTERY_API:
			case QQKENO_QQLOTTERY_IDR_B1_API:
			case QQKENO_QQLOTTERY_CNY_B1_API:
			case QQKENO_QQLOTTERY_THB_B1_API:
			case QQKENO_QQLOTTERY_USD_B1_API:
			case QQKENO_QQLOTTERY_VND_B1_API:
			case QQKENO_QQLOTTERY_MYR_B1_API:
				if ($this->external_system->isGameApiActive($gamePlatformId)) {
					$postJson = file_get_contents("php://input");
					$this->utils->debug_log("QQKENO_QQLOTTERY Callback ====> ",$postJson);

					$post = !empty($postJson) ? json_decode($postJson,true) : array();
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$response = $api->callback($method, $post);
					return $this->returnJsonResult($response);
				}
			break;

			case TFGAMING_ESPORTS_API:
				if ($this->external_system->isGameApiActive($gamePlatformId)) {
					$postJson = file_get_contents("php://input");
					$this->utils->debug_log("TFGAMING_ESPORTS_API Callback -------> ", $postJson);

					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$post = !empty($postJson) ? json_decode($postJson,true) : array();
					$result = $api->callback($method, $post);
					if($result == "Invalid Token") {
						http_response_code(400);
					}
					return $this->returnJsonResult($result);
				} else {
					http_response_code(400);
					$sxe  = new SimpleXMLElement("<message/>");
					$status = $sxe->addChild('status','fail');
					$result = $sxe->addChild('result','Game not active.');
					$data =  $sxe->asXML();
				}
				return $this->returnXml($data);
			break;

			case ASIASTAR_API:
				if ($this->external_system->isGameApiActive($gamePlatformId)) {
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);

					$method = filter_input(INPUT_GET, 'method', FILTER_SANITIZE_URL);
					$username = filter_input(INPUT_GET, 'userName', FILTER_SANITIZE_URL);
					$timestamp = filter_input(INPUT_GET, 'timestamp', FILTER_SANITIZE_URL);
					$key = filter_input(INPUT_GET, 'key', FILTER_SANITIZE_URL);

					$request = array(
						"method" => $method,
						"username" => $username,
						"timestamp" => $timestamp,
						"key" => $key
					);

					$result = $api->callback($request, $method);
					return redirect($result['forward_url']);
				}
			break;

			case RGS_API:
				if($this->external_system->isGameApiActive($gamePlatformId)) {
					$params = $this->getInputGetAndPost();
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$data = $api->callback($method, $params);
					$this->utils->debug_log('RGS - Callback : ', $params, $method, $data);
				}

				if(!is_array($data)){
					$data = json_decode($data);
				}
				return $this->returnJsonResult($data);
			break;
            case AMG_API:
                if($this->external_system->isGameApiActive($gamePlatformId)) {
                    $api = $this->utils->loadExternalSystemLibObject($gamePlatformId);

                    if(empty($api->callback_credentials)) {
                        http_response_code(400);
                        return $this->returnJsonResult(['message' => 'Callback not properly setup.']);
                    }

                    $auth_header = $this->input->get_request_header('X-Tain-Auth');

                    if(!$auth_header) {
                        http_response_code(403);
                        return $this->returnJsonResult(['message' => 'Credentials not valid.']);
                    }

                    $auth_header = json_decode($auth_header);
                    if($api->callback_credentials['username'] != $auth_header->login || $api->callback_credentials['password'] != $auth_header->password ) {
                        http_response_code(403);
                        return $this->returnJsonResult(['message' => 'Credentials not valid.']);
                    }

                    $params = $this->getInputGetAndPost();
                    if(!$params) {
                        $params = json_decode(file_get_contents("php://input"), true);
                    }

                    $data = $api->callback($method, $params);
                    $this->utils->debug_log('AMG - Callback : ', $params, $method, $data);

                    if($data != null && !is_array($data)){
                        $data = json_decode($data);
                    }
                    return $this->returnJsonResult($data);
                }
                break;
			case IMESB_API:
				if ($this->external_system->isGameApiActive($gamePlatformId)) {
					$request = $this->input->get() ?: array();
					$this->utils->debug_log('(GET) IMESB_API - Callback RAW-RESULT: ' . json_encode($request));
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$data = $api->callback($request);
				}else {
					http_response_code(400);
					$data = [
						"code" 		=> "INTERNAL_ERROR",
						"message" 	=> "Game not active."
					];
				}
				$apiResponse = $this->returnJsonResult($data);
				$this->utils->debug_log('(GET) IMESB_API - Callback RAW-RESULT: ' . $apiResponse);
				return $apiResponse;
			break;

			case EA_GAME_API:
			case EA_GAME_API_THB1_API:

				$request = $this->loadXmlString();
				$id = is_object($request) ? $request->element->attributes()->{'id'} : null;
				$action = is_object($request) ? $request->attributes()->{'action'} : null;
				$flag = 2;

				$this->utils->debug_log('initial request of EA_GAMING - Callback RAW-RESULT: ',json_encode($request),'RAW request: ',$rawRequest);

				if ($this->external_system->isGameApiActive($gamePlatformId)) {
					$headerCode = 200;
					$flag =1;
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);

					if($method == "login_cashier"){
						 $ticket = filter_input(INPUT_GET, 'ticket', FILTER_SANITIZE_URL);
						 $username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_URL);
						 $request = array(
						 	"username" => $username,
						 	"ticket" => $ticket
						 );
						 $result = $api->callback($request, $method);
						 return redirect($result['forward_url']);
					} else {
						$this->utils->debug_log('(POST) EA_GAMING - Callback RAW-RESULT: ',json_encode($request));
						$data = $api->callback($request, $method);
					}

				}else {
					/** Static because just an error response */
					$headerCode = 400;
					$data = [
						'request' => [
							'action_attr' => $action,
							'element' => [
								'id_attr' => null,
								'properties' => [
									[
										'name_attr' => 'status',
										201
									],
									[
										'name_attr' => 'errdesc',
										'ERR_INVALID_REQ'
									]
								]
							]
						]
					];
				}

				if($id){
					$data['request']['element']['id_attr'] = $id;
				}

				$this->utils->debug_log(' EA_GAMING - RESPONSE >>>>>>>> ',$data,'method',$method,'request',json_encode($request));

				$requestParams = json_encode($request);
				$response = is_array($data) ? json_encode($data) : $data;

				$this->load->model(['response_result']);
				$this->response_result->saveResponseResult(
					$gamePlatformId,
					$flag,
					$method,
					$requestParams,
					null,
					$headerCode,
					null,
					null,
					['content'=>$response]
				);

				return $this->outputXmlResponse($data,$headerCode);
			break;
			case SPORTSBOOK_FLASH_TECH_GAME_API:
			case SPORTSBOOK_FLASH_TECH_GAME_IDR1_API:
			case SPORTSBOOK_FLASH_TECH_GAME_CNY1_API:
			case SPORTSBOOK_FLASH_TECH_GAME_THB1_API:
			case SPORTSBOOK_FLASH_TECH_GAME_MYR1_API:
			case SPORTSBOOK_FLASH_TECH_GAME_VND1_API:
			case SPORTSBOOK_FLASH_TECH_GAME_USD1_API:
				$headerCode = 400;
				$message = 'Failed';
				$flag =2;
				$response = [
						'authenticate' => [
							'member_id' => [
								[
									null
								]
							],
							'status_code' => [
								[
									2 # anything, other than 0
								]
							],
							'message' => [
								[
									$message
								]
							]
						]
				];

				$this->utils->debug_log('initial request of SPORTSBOOK_FLASH_TECH with rawRequest: ',$rawRequest);

				if($this->isGetMethod()){
					if ($this->external_system->isGameApiActive($gamePlatformId)){
						$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_URL);
						$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);

						if($api && $token){
							$headerCode = 200;
							$flag =1;
							$response = $api->callback($token,$method);
						}else{
							$message = 'token is empty or game API is not Active';
							if(isset($response['authenticate']['message'])){
								$response['authenticate']['message'] = $message;
							}
						}

					}else{
						$message = 'Game API is not Active';
						if(isset($response['authenticate']['message'])){
							$response['authenticate']['message'] = $message;
						}
					}
				}

				$this->utils->debug_log(__METHOD__.' callback response details',$response,'token',$token,'method',$method);

				$requestParams = json_encode(['token'=>$token,'method'=>$method]);
				$data = is_array($response) ? json_encode($response) : $response;

				$this->load->model(['response_result']);
				$this->response_result->saveResponseResult(
					$gamePlatformId,
					$flag,
					$method,
					$requestParams,
					null,
					$headerCode,
					null,
					null,
					['content'=>$data]
				);

				return $this->outputXmlResponse($response,$headerCode,null,null,[],false,false);
			break;

            case EVOPLAY_GAME_API:
                $params = $this->getInputGetAndPost();
                if(!$params) {
                    $params = json_decode(file_get_contents("php://input"), true);
                }
                $this->utils->debug_log('EVOPLAY CALLBACK -----> ', $params);

                if($this->external_system->isGameApiActive($gamePlatformId)) {
                    $api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
                    $api->callback($params);
					http_response_code(200);
                } else {
					http_response_code(400);
                }
                return;
            break;

            case TPG_API:
                if($this->external_system->isGameApiActive($gamePlatformId)) {
                    $api = $this->utils->loadExternalSystemLibObject($gamePlatformId);

                    if(!empty($method) && $method == "login"){
                        if($this->isGetMethod()){
                            $params = $this->getInputGetAndPost();
                            $lang = $api->getPLayerLoginLanguage($params['lang']);
                            $data = [
                                'token' => urlencode($params['token']),
                                'lang' => $lang,
                            ];
                            return $this->load->view('iframe/player/goto_login_tpg.php', $data);
                        }
                    }

                    $params = $this->getInputGetAndPost();
                    if(!$params) {
                        $params = json_decode(file_get_contents("php://input"), true);
                    }

                    $data = $api->callback($method, $params);
                    $this->utils->debug_log('TPG - Callback : ', $params, $method, $data);

                    // if($data != null && !is_array($data)){
                    //     $data = json_decode($data);
                    // }
                    // return $this->returnJsonResult($data);
                }
            break;


			case HA_GAME_API:
				if ($this->external_system->isGameApiActive($gamePlatformId)) {
					if ($this->isPostMethod()) {
							$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
							$request = file_get_contents('php://input');
							$this->utils->debug_log('(POST) HA GAME API - Callback RAW-RESULT: ' . $request);
							$params = json_decode($request,true);
							$data = $api->callback($params);
							if ($data == 1 || $data == 0) {
								http_response_code(200);
							} else {
								http_response_code(500);
							}
					}
				} else {
					http_response_code(500);
				}
				return;
			break;

        case NEXTSPIN_GAME_API:
            $request = $this->getInputGetAndPost();
            if ($this->external_system->isGameApiActive($gamePlatformId))
            {
                $api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
                if(!$request)
                {
                    $request = json_decode(file_get_contents("php://input"), true);
                }
            }
            else
            {
                $data = array('status' => 505);
            }
            $this->utils->debug_log('NextSpin DATA', $request);
            $data = $api->callback($request,$platform);
            $jsonData = $this->returnJsonResult($data);
            // echo '<br>' . $jsonData;
            return $jsonData;


        	break;
            case HP_LOTTERY_GAME_API:
                if ($this->isPostMethod()) {
                    $raw_request = file_get_contents('php://input');

                    if (!empty($raw_request)) {
                        $request = json_decode($raw_request, true);
                    } else {
                        $request = [];
                    }
                } else {
                    $request = $_GET;
                }

                $this->CI->utils->debug_log('HP_LOTTERY_GAME_API (callback)', 'request', $request);

                if ($this->external_system->isGameApiActive($gamePlatformId) && !empty($request)) {
                    $game_api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
                    $data = $game_api->callback($request, $method);

                    $this->CI->utils->debug_log('HP_LOTTERY_GAME_API (callback)', 'response', $data);

                    if (empty($data)) {
                        $data = [
                            'returnCode' => 'FAILED',
                            'message' => 'Bad Parameters [method]'
                        ];
                    }
                } else {
                    $data = [
                        'returnCode' => 'FAILED',
                        'message' => 'Bad Parameters [method]'
                    ];
                }

                return $this->returnJsonResult($data);
            case TADA_SEAMLESS_GAME_API:
            case JILI_SEAMLESS_API:
            case JILI_GAME_API:
            case WAZDAN_SEAMLESS_GAME_API:
                if ($this->isPostMethod()) {
                    $raw_request = file_get_contents('php://input');

                    if (!empty($raw_request)) {
                        $request = json_decode($raw_request, true);
                    } else {
                        $request = [];
                    }
                } else {
                    $request = $_GET;
                }

                $this->CI->utils->debug_log(__METHOD__, '(callback)', 'request', $request);

                if ($this->external_system->isGameApiActive($gamePlatformId) && !empty($request)) {
                    $game_api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
                    $data = $game_api->callback($request, $method);

                    $this->CI->utils->debug_log(__METHOD__, '(callback)', 'response', $data);

                    if (empty($data)) {
                        $data = [
                            'returnCode' => 'FAILED',
                            'message' => 'Bad Parameters [method]'
                        ];
                    }
                } else {
                    $data = [
                        'returnCode' => 'FAILED',
                        'message' => 'Bad Parameters [method]'
                    ];
                }

                return $this->returnJsonResult($data);
			default :
				if ($method == 'queryGameListFromGameProvider') {
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$response =  $api->queryGameListFromGameProvider();
					
					return $this->returnJsonResult($response);
				}elseif ($method == 'queryBetLimit') {
					$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
					$response =  $api->queryBetLimit();
					
					return $this->returnJsonResult($response);
				}

				$this->utils->debug_log(__METHOD__, "case {$gamePlatformId} not implemented");
			break;
        }
	}

	private function isJSON($string){
	   return is_string($string) && is_array(json_decode($string, true)) ? true : false;
	}

	/**
	 * detail: validate tokens
	 *
	 * @param int $type
	 * @param string $token
	 * @return json
	 */
	public function validate_token($type, $token) {
		$this->load->model(array('common_token', 'player_model', 'affiliatemodel', 'users'));
		$id = null;
		$info = null;
		if ($type == 'player') {
			$id = $this->common_token->getPlayerIdByToken($token);
			if (!empty($id)) {
				$info = $this->player_model->getPublicInfoById($id);
			}
		} else if ($type == 'adminuser') {
			$id = $this->common_token->getAdminUserIdByToken($token);
			if (!empty($id)) {
				$info = $this->users->getPublicInfoById($id);
			}
		} else if ($type == 'affiliate') {
			$id = $this->common_token->getAffiliateIdByToken($token);
			if (!empty($id)) {
				$info = $this->affiliatemodel->getPublicInfoById($id);
			}
		} else {
			return $this->returnBadRequest();
		}
		$success = !empty($id);
		$this->returnJsonResult(array('success' => $success, 'public_info' => $info));
	}

	/**
	 * detail: get player id using order id
	 *
	 * @param int $oderId
	 */
	protected function getPlayerIdByOrderId($orderId) {
		//search walletaccount if starts with 'W'
		if (strtoupper(substr($orderId, 0, 1)) == 'W') {
			//search walletaccount
			$walletAccount = $this->getWalletAccountInfo($orderId);

			if ($walletAccount && array_key_exists('playerId', $walletAccount)) {
				return $walletAccount['playerId'];
			}
		} else {
			$ord = $this->getOrderInfo($orderId);
			if ($ord) {
				return $ord->player_id;
			}
		}
		return null;
	}

	/**
	 * detail: get wallet account records
	 *
	 * @param string $transCode
	 * @return array
	 */
	protected function getWalletAccountInfo($transCode) {
		$this->load->model(array('wallet_model'));
		$walletAccount = null;
		if (!empty($transCode)) {
			$walletAccount = $this->wallet_model->getWalletAccountByTransactionCode($transCode);
		}
		return $walletAccount;
	}

	/**
	 * detail: get order records
	 *
	 * @param int $orderId
	 * @return null|stdClass
	 */
	protected function getOrderInfo($orderId) {
		$this->load->model(array('sale_order'));
		$ord = null;
		if ($orderId) {
			$ord = $this->sale_order->getSaleOrderById($orderId);
		}
		return $ord;
	}
}

///END OF FILE////////////
