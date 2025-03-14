<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/promo_module.php';
require_once dirname(__FILE__) . '/modules/promo_api_module.php';
require_once dirname(__FILE__) . '/modules/transfer_api_module.php';
require_once dirname(__FILE__) . '/modules/player_deposit_module.php';
require_once dirname(__FILE__) . '/modules/pub_api_module.php';
require_once dirname(__FILE__) . '/modules/one_time_async_module.php';

/**
 * Class Async
 *
 * @property Player_message_library $player_message_library
 * @property Common_token $common_token
 * @property Game_logs $game_logs
 */
class Async extends BaseController {

	use promo_module;
	use promo_api_module;
	use transfer_api_module;
    use player_deposit_module;
	use pub_api_module;
	use one_time_async_module;

	function __construct() {
		parent::__construct();
		$this->load->library(array('session', 'lib_queue', 'authentication', 'form_validation'));
		$this->load->model(array('queue_result'));
		//CHECK BY SESSION
		$this->lang->load('main');
	}

	public function refresh_session($type, $id, $sessionId) {

		$this->config->set_item('app_debug_log', APPPATH . 'logs/refresh_session.log');

		$this->load->library(array('session'));
		$currSessionId = $this->session->userdata('session_id');
		if (empty($currSessionId)) {
			$currSessionId = 0;
		}
		$adminId = $this->authentication->getUserId();

		//type = admin or player
		//id = admin id or player id
		//session id
		//return empty gif
		//
		$debug_session_lost = $this->config->item('debug_session_lost');
		if ($debug_session_lost || $sessionId != $currSessionId) {
			$matchStr = ($sessionId == $currSessionId ? 'match_session' : 'not_match_session');
			$matchIdStr = ($adminId == $id ? 'match_id' : 'not_match_id');
			$this->utils->debug_log($type . ' id', $id, 'sessionId', $sessionId, 'current session id', $currSessionId, 'current admin id', $adminId, $matchStr, $matchIdStr);
		}
		header('Content-type: image/gif');
		# The transparent, beacon image
		echo chr(71) . chr(73) . chr(70) . chr(56) . chr(57) . chr(97) .
		chr(1) . chr(0) . chr(1) . chr(0) . chr(128) . chr(0) .
		chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) .
		chr(33) . chr(249) . chr(4) . chr(1) . chr(0) . chr(0) .
		chr(0) . chr(0) . chr(44) . chr(0) . chr(0) . chr(0) . chr(0) .
		chr(1) . chr(0) . chr(1) . chr(0) . chr(0) . chr(2) . chr(2) .
		chr(68) . chr(1) . chr(0) . chr(59);
	}

	public function admin_main_js($site_name) {
		//load site
		$this->load->model('static_site');
		// $site = $this->static_site->getSiteById($site_id);
		$site = $this->static_site->getSiteByName($site_name);
		//set language
		$this->utils->loadLanguage($site->lang);

		$this->load->library(array('authentication'));
		$host = $this->config->item('admin_server_host'); // @$_SERVER['HTTP_HOST'];
		//TODO move to CDN
		$assetBaseUrl = $site->asset_url . "/resources/player";
		$debugLog = $this->utils->isDebugMode() ? 'true' : 'false';
		$logged = $this->authentication->isLoggedIn() ? 'true' : 'false';
		$adminUsername = $this->authentication->getUsername();
		$adminId = $this->authentication->getUserId();
		$token = $this->authentication->getAdminToken();

		$origin = $this->config->item('enabled_origin') ? "http://" . $host : "*";
		$langText = json_encode(array('button_login' => $this->getLang("lang.logIn"),
			'form_field_username' => $this->getLang('form.field.username'),
			'form_field_password' => $this->getLang('form.field.password'),
			'form_register' => $this->getLang('lang.register'),
			'header_trial_game' => $this->getLang('header.trial_game'),
			'button_logout' => $this->getLang('header.logout'),
			'button_membership' => $this->getLang('sidemenu.membership'),
			'header_memcenter' => $this->getLang('header.memcenter'),
			'header_deposit' => $this->getLang('header.deposit'),
			'header_withdrawal' => $this->getLang('header.withdrawal'),
			'header_mainwallet' => $this->getLang('header.mainwallet'),
			'header_information' => $this->getLang('header.information'),
		)
		);
		// $styleClass = json_encode(array(
		// 	'username' => 'ui-input fn-left J-verify',
		// 	'password' => 'ui-input fn-left J-verify',
		// 	'login' => 'fn-left ui-btn ui-btn-red J-submit',
		// 	'register' => 'fn-left ui-btn ui-btn-brown J-regist-btn',
		// ));

		$templates = json_encode(array(
			'login_template' => $site->login_template,
			'logged_template' => $site->logged_template,
		)
		);

		// $utilsJs = $this->get_utils_js();

		// json : convert json between string and json object
		// underscore: utils and template
		// popup: modal dialog
		// snackbar: tooltip, popup message https://github.com/FezVrasta/snackbarjs
		// jqueryMessage: post message between iframe and parent
		// web_socket, swfobject, WebSocketMain.swf: web socket
		// web_push: push from server
		$js = <<<EOF
require.config({
	baseUrl:'{$assetBaseUrl}',
	paths: {
		json: 'json3.min',
		jquery: 'jquery-1.11.3.min',
		underscore: 'underscore-min',
		domReady: 'domReady',
		popup: 'jquery.bpopup',
		snackbar: 'snackbar.min',
		jqueryMessage: 'jquery.ba-postmessage',
		handlebars: 'handlebars',
		pushmessage: 'pushmessage'
	},
	map: {
		//private jquery, never conflict with public jquery
		'*': { 'jquery': 'jquery-private' },
		'jquery-private': { 'jquery': 'jquery' }
	},
});
//dynamic variables: login status or any needed variables
define('variables',['jquery'], function($){
	return {
		host: '{$host}',
		assetBaseUrl: '{$assetBaseUrl}',
		origin: '{$origin}',
		logged: {$logged},
		debugLog: {$debugLog},
		token: '{$token}',
		role: 'player',
		adminId: '{$adminId}',
		adminUsername: '{$adminUsername}',
		langText: $langText,
		templates: $templates
	};
});


define(['jquery', 'utils', 'web_push', 'pushmessage'], function($, utils, webPush, pushmessage){
	utils.initMessage();
	pushmessage.init();
	//init webPush
	webPush.init();
	utils.safelog('main done');
});

EOF;

		$this->returnJS($js);

	}

	public function query_balance($role) {
		//get player id from session
		if ($this->authentication->isLoggedIn()) {
			$systemId = $this->input->post('systemId');
			$state = $this->input->post('_state');
			$username = $this->authentication->getUsername();
			$token = $this->callApi($systemId, $role, 'queryPlayerBalance', array($username), $state);
			return $this->returnJsonResult(array('job_token' => $token));
		} else {
			return $this->returnErrorStatus(403, true);
		}
	}

	private function callApi($systemId, $role, $funcName, $params, $state) {
		$callerType = null;
		$caller = null;
		if ($role == 'player') {
			$callerType = Queue_result::CALLER_TYPE_PLAYER;
			$caller = $this->authentication->getPlayerId();
		} else {
			$callerType = Queue_result::CALLER_TYPE_ADMIN;
			$caller = $this->authentication->getUserId();
		}

		$token = $this->lib_queue->addApiJob($systemId, $funcName, $params, $callerType, $caller, $state);
		return $token;
	}

	/**
	 *
	 * JSONP
	 *
	 */
	public function query_queue_results($role, $unread = 'true') {
		if ($this->authentication->isLoggedIn()) {
			$onlyUnread = $unread == 'true';
			$callerType = null;
			$caller = null;
			if ($role == 'player') {
				$callerType = Queue_result::CALLER_TYPE_PLAYER;
				$caller = $this->authentication->getPlayerId();
			} else {
				$callerType = Queue_result::CALLER_TYPE_ADMIN;
				$caller = $this->authentication->getUserId();
			}
			//return queue_results
			$this->load->model(array('queue_result'));
			$updateToRead = true;
			$rows = $this->queue_result->getResultListByCaller($callerType, $caller, $onlyUnread, $updateToRead);

		} else {
			return $this->returnErrorStatus(403, true);
		}
		$this->returnJsonpResult($rows);
	}

	/**
	 *
	 * JSONP
	 *
	 */
	public function get_user_info($platformId = PT_API) {
		$this->load->library(array('player_functions', 'salt', 'language_function'));
		// $this->utils->debug_log('sesssion status', $this->session->userdata('status'));
		if ($this->authentication->isLoggedIn()) {
			$playerId = $this->authentication->getPlayerId();
			$playerUsername = $this->authentication->getUsername();
			$this->load->model(array('game_provider_auth'));
			$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($playerId, $platformId);
			// $this->utils->debug_log('loginInfo', $loginInfo, 'playerId', $playerId, 'platformId', $platformId);
			if (empty($loginInfo)) {

				// $ptPasswordResult = $this->player_functions->getPlayerPassword($playerId, PT_API);
				$playerDetails = $this->player_functions->getPlayerById($playerId);
				$passwordResult = $this->salt->decrypt($playerDetails['password'], $this->getDeskeyOG());
				$loginInfo = (object) array('login_name' => $playerUsername, 'password' => $passwordResult);

				// $playerGamePasswordPT = $ptPasswordResult ? $ptPasswordResult->password : $passwordPT;
			}
			// TODO add to queue
			// list($loadSucc, $managerName) = $this->utils->loadExternalSystemLib(PT_API); // $this->game_platform_manager->initApi(1);
			// if ($loadSucc) {
			// 	$api = $this->$managerName;
			// 	$result = $api->isPlayerExist($playerUsername);

			// 	if ($result['exists'] == false) {
			// 		$api->createPlayer($playerUsername, $playerId, $passwordPT);
			// 	}
			// }

			$currentLang = $this->language_function->getCurrentLanguage(); //  $this->session->userdata('currentLanguage');

			//lang for PT
			if ($currentLang == '1') {
				$lang = 'en';
			} else {
				$lang = 'zh-cn';
			}
            $api = $this->utils->loadExternalSystemLibObject($platformId);
            if($api) {
                $lang = $api->getSystemInfo('languagecode', $lang);
            }
			$this->returnJsonpResult(array('key' => $loginInfo->login_name, 'secret' => $loginInfo->password, 'lang' => $lang));
		} else {
			return $this->returnErrorStatus(403, true);
		}
	}

	/**
	 *
	 * JSONP
	 *
	 */
	public function check_login_status() {
		if ($this->authentication->isLoggedIn()) {
			$this->returnJsonpResult(array('success' => true));
		} else {
			$this->returnJsonpResult(array('success' => false));
		}
	}

	public function getGamePlatforms($jsonp = false) {
		if ($this->authentication->isLoggedIn()) {
			$this->load->model(array('player_model', 'game_provider_auth'));
			$playerId = $this->authentication->getPlayerId();
			$game_platforms = $this->game_provider_auth->getGamePlatforms($playerId);
			if ($jsonp) {
				return $this->returnJsonpResult(array('success' => true, 'data' => $game_platforms));
			} else {
				return $this->returnJsonResult(array('success' => true, 'data' => $game_platforms));
			}
		}
		return $this->returnErrorStatus(403, true);
	}

	public function player_query_balance_by_platform($game_platform_id) {
		if ($this->authentication->isLoggedIn()) {
			$this->utils->debug_log('player_query_balance_by_platform ======================= Start ===============', 'Start');
			$this->load->model(array('player_model', 'wallet_model', 'game_provider_auth'));
			$playerId = $this->authentication->getPlayerId();
			$playerUsername = $this->authentication->getUsername();
			if (!$this->external_system->isGameApiActive($game_platform_id)) {
				$this->utils->debug_log("player_query_balance_by_platform - game api [$game_platform_id] not active");
				$result[$game_platform_id] = Array(
					'success' => false,
					'balance' => 0,
				);
				return $result;
			}
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
			$isRegisteredFlag = $this->game_provider_auth->isRegisterd($playerId, $api->getPlatformCode());
			$this->utils->debug_log('player_query_balance_by_platform - isRegisteredFlag', $isRegisteredFlag, $game_platform_id);
			$isBlockedUsernameInDBFlag = $this->game_provider_auth->isBlockedUsernameInDB($playerId, $api->getPlatformCode());
			$this->utils->debug_log('player_query_balance_by_platform - isBlockedUsernameInDBFlag', $isRegisteredFlag, $game_platform_id);
			$result = Array();
			if (!$isRegisteredFlag) {
				$this->utils->debug_log('player_query_balance_by_platform - isRegisteredFlag - false', $game_platform_id);
				$result[$game_platform_id] = Array(
					'success' => false,
					'balance' => 0,
				);
				return $result;
			}
			if ($isBlockedUsernameInDBFlag) {
				$this->utils->debug_log('player_query_balance_by_platform - isBlockedUsernameInDBFlag - false', $game_platform_id);
				$result[$game_platform_id] = Array(
					'success' => false,
					'balance' => 0,
				);
				return $result;
			}
			$this->utils->debug_log('player_query_balance_by_platform - start query balance', $game_platform_id);
			$balance = $api->queryPlayerBalance($playerUsername);
			$this->utils->debug_log('player_query_balance_by_platform - balance', $balance, $game_platform_id);
			if($balance['success'] && !isset($balance['balance'])){
				$result[$game_platform_id] = Array(
					'success' => false,
					'balance' => 0,
				);
				$this->utils->error_log('return success=true, but no balance', $api->getPlatformCode());
			}
			if ($balance['success'] && isset($balance['balance'])) {
				$result[$game_platform_id] = Array(
					'success' => $balance['success'],
					'balance' => $this->utils->floorCurrencyForShow($balance['balance']),
				);
			} else {
				$result[$game_platform_id] = Array(
					'success' => $balance['success'],
					'balance' => 0,
				);
			}

			$this->utils->debug_log('player_query_balance_by_platform ======================= End ===============', 'End');
			return $result;
		}

		return $this->returnErrorStatus(403, true);
	}

	public function player_query_balance_for_betgames($game_platform_id) {
		if ($this->authentication->isLoggedIn()) {
			$this->utils->debug_log('player_query_balance_by_platform ======================= Start ===============', 'Start');
			$this->load->model(array('player_model', 'wallet_model', 'game_provider_auth'));
			$playerId = $this->authentication->getPlayerId();
			$playerUsername = $this->authentication->getUsername();
			if (!$this->external_system->isGameApiActive($game_platform_id)) {
				$this->utils->debug_log("player_query_balance_by_platform - game api [$game_platform_id] not active");
				$result = Array(
					'success' => false,
					'balance' => 0,
				);
				return $result;
			}
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
			$isRegisteredFlag = $this->game_provider_auth->isRegisterd($playerId, $api->getPlatformCode());
			$this->utils->debug_log('player_query_balance_by_platform - isRegisteredFlag', $isRegisteredFlag, $game_platform_id);
			$isBlockedUsernameInDBFlag = $this->game_provider_auth->isBlockedUsernameInDB($playerId, $api->getPlatformCode());
			$this->utils->debug_log('player_query_balance_by_platform - isBlockedUsernameInDBFlag', $isRegisteredFlag, $game_platform_id);
			$result = Array();
			if (!$isRegisteredFlag) {
				$this->utils->debug_log('player_query_balance_by_platform - isRegisteredFlag - false', $game_platform_id);
				$result = Array(
					'success' => false,
					'balance' => 0,
				);
				return $result;
			}
			if ($isBlockedUsernameInDBFlag) {
				$this->utils->debug_log('player_query_balance_by_platform - isBlockedUsernameInDBFlag - false', $game_platform_id);
				$result = Array(
					'success' => false,
					'balance' => 0,
				);
				return $result;
			}
			$this->utils->debug_log('player_query_balance_by_platform - start query balance', $game_platform_id);
			$balance = $api->queryPlayerBalance($playerUsername);
			$this->utils->debug_log('player_query_balance_by_platform - balance', $balance, $game_platform_id);
			if($balance['success'] && !isset($balance['balance'])){
				$result = Array(
					'success' => false,
					'balance' => 0,
				);
				$this->utils->error_log('return success=true, but no balance', $api->getPlatformCode());
			}
			if ($balance['success'] && isset($balance['balance'])) {
				$result = Array(
					'success' => $balance['success'],
					'balance' => $this->utils->floorCurrencyForShow($balance['balance']),
				);
			} else {
				$result = Array(
					'success' => $balance['success'],
					'balance' => 0,
				);
			}

			$this->utils->debug_log('player_query_balance_by_platform ======================= End ===============', 'End');
			return $this->returnJsonResult($result);
		}

		return $this->returnErrorStatus(403, true);
	}

	public function player_query_wallet($jsonp = false) {
		if ($this->authentication->isLoggedIn()) {
			$this->load->model(array('wallet_model'));

			$playerId = $this->authentication->getPlayerId();

			$bigWallet = $this->wallet_model->getBigWalletByPlayerId($playerId);

			$frozen = $this->utils->formatCurrencyNumber($bigWallet['main']['frozen']);

			$mainWallet = $this->utils->formatCurrencyNumber($bigWallet['main']['total_nofrozen']);

			if ($jsonp) {
				return $this->returnJsonpResult(Array('mainWallet' => $mainWallet, 'frozen' => $frozen));
			} else {
				return $this->returnJsonResult(Array('mainWallet' => $mainWallet, 'frozen' => $frozen));
			}
		}
		return $this->returnErrorStatus(403, true);
	}

    public function player_transfer_balance(){
		if (!$this->authentication->isLoggedIn()) {
            return $this->returnErrorStatus(403, true);
		}

        $player_id = $this->authentication->getPlayerId();
        $playerName = $this->authentication->getUsername();

        $transfer_from = $this->input->get('transfer_from');
        $transfer_to = $this->input->get('transfer_to');
        $amount = $this->input->get('amount');

        $result = $this->utils->verifyWalletTransfer($player_id, $playerName, $transfer_from, $transfer_to, $amount);

        if($result['status'] === 'error'){
            return $this->returnCommon(self::MESSAGE_TYPE_ERROR, $result['message']);
        }

        $promo_cms_id = $this->input->post('promo_cms_id');
        if($promo_cms_id){
            $playerPromoId = $this->CI->process_player_promo($player_id, $promo_cms_id);

            if ($playerPromoId == '-1') {
                //ignore promotion
                $this->utils->debug_log('ignore trigger playerPromoId', $playerPromoId);
            } else if (!empty($playerPromoId)) {
                $this->utils->debug_log('trigger by playerPromoId', $playerPromoId);
                //force
                $promo_result = [];
                $this->triggerTransferPromotion($player_id, $amount, $transfer_from, $transfer_to, $result['transferTransId'], $promo_result, $playerPromoId);

                // } else if ($this->utils->isEnabledPromotionRule('allow_auto_trigger_sub_wallet')) {
                // 	$this->utils->debug_log('trigger auto');
                // 	//auto
                // 	$this->triggerTransferPromotion($player_id, $amount, $transfer_from, $transfer_to, $transferTransId, $result);

                $this->utils->debug_log('result', $promo_result);
            } else {
                $this->utils->debug_log('no any trigger');
            }
//
//            if ($promo_result['success'] || $promo_result['success'] == 'true') {
//                if ($this->input->is_ajax_request()) {
//                    $this->returnJsonResult(array('status' => 'success', 'msg' => $result['message']));
//                    return;
//                }
//                $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $result['message']);
//            }
        }

        $data = [
            'walletInfo' => $this->utils->getSimpleBigWallet($player_id)
        ];

        return $this->returnCommon(self::MESSAGE_TYPE_SUCCESS, $result['message'], $data);
    }

    public function player_transfer_all_balance(){
        if (!$this->authentication->isLoggedIn()) {
            return $this->returnErrorStatus(403, true);
        }

        $player_id = $this->authentication->getPlayerId();
        $playerName = $this->authentication->getUsername();

		$transfer_to = $this->input->get('transfer_to');

		$this->load->model(array('external_system', 'wallet_model'));
		if ((!$this->CI->external_system->isGameApiActive($transfer_to) || $this->CI->external_system->isGameApiMaintenance($transfer_to)) && Wallet_model::MAIN_WALLET_ID<>$transfer_to) {
			$this->utils->debug_log('player_transfer_all_balance - game is inactive or under maintenance');
			return $this->returnCommon(self::MESSAGE_TYPE_ERROR, lang('goto_game.sysMaintenance'));
		}

        $result = $this->utils->transferAllWallet($player_id, $playerName, $transfer_to);

        if(!$result['success']){
            return $this->returnCommon(self::MESSAGE_TYPE_ERROR, $result['message']);
        }

        $data = [
            'walletInfo' => $this->utils->getSimpleBigWallet($player_id)
        ];

        return $this->returnCommon(self::MESSAGE_TYPE_SUCCESS, $result['message'], $data);
    }

	public function player_query_all_balance($api_id = null, $jsonp = 'false') {

		$jsonp=$jsonp=='true';

		//check player session
		if ($this->authentication->isLoggedIn()) {
			$this->utils->debug_log('player_query_all_balance ======================= Start ===============', 'Start');
			$playerId = $this->authentication->getPlayerId();
			$playerUsername = $this->authentication->getUsername();

			//set cache flag
			// $this->getRefreshBalanceCacheFlag();
			$manager = $this->utils->loadGameManager();
			$this->load->model(array('player_model', 'wallet_model', 'game_provider_auth'));

			$controller = $this;
			$subwallets = array();
			$frozen = 0;
			$mainWallet = 0;
			$success = array();

			$this->lockAndTransForPlayerBalance($playerId, function () use ($api_id, $controller, $playerId, $playerUsername, &$mainWallet, &$frozen, &$subwallets) {

                //OGP-1140
                $balances = Array();
                if (!empty($api_id)) {
                    $balances = $this->player_query_balance_by_platform($api_id);
                    $this->utils->debug_log('player_query_balance_by_platform - balances', $balances);
                }

                $game_platforms = $this->game_provider_auth->getGamePlatforms($playerId);
                $this->utils->debug_log('player_query_all_balance - balances', $balances);
                $this->utils->debug_log('player_query_all_balance - game_platforms', $game_platforms);
                // OGP-208
                if (!empty($balances)){
                    foreach ($balances as $api_id => $api_res) {
                        $success[$api_id] = $api_res['success'];
                    }
                }
                // OGP-1093
                // $game_platforms = $this->game_provider_auth->getGamePlatforms($playerId);
                foreach ($game_platforms as $gp) {
                    if (!empty($gp['register'])) {
                        $success[$gp['id']] = true;
                    }
                }

				$bigWallet = $this->wallet_model->getOrderBigWallet($playerId);

				$frozen = $this->utils->formatCurrencyNumber($bigWallet['main']['frozen']); //$this->player_model->getPendingBalanceById($playerId);

				$mainWallet = $this->utils->formatCurrencyNumber($bigWallet['main']['total_nofrozen']); //$this->wallet_model->getMainWalletBalance($playerId);

				foreach ($bigWallet['sub'] as $apiId => $subWallet) {
					$subwallets[$apiId] = $controller->utils->formatCurrencyNumber($subWallet['total_nofrozen']);
				}

				$this->utils->debug_log('lockAndTransForPlayerBalance - before balances', $playerId, $balances, $mainWallet, $frozen, $subwallets, $bigWallet);

				if ( ! empty($balances)) {
					foreach ($balances as $apiId => $apiRlt) {
						if ($apiRlt['success']) {

							$api = $controller->utils->loadExternalSystemLibObject($apiId);

							if ($api->isSeamLessGame()) {

								$this->utils->debug_log('SEAMLESS START', $playerUsername, $apiRlt);

								$result = $api->queryPlayerBalance($playerUsername);

								if ($result['success']) {
									$subwallets[$apiId] = $controller->utils->formatCurrencyNumber($result['balance']);
								}

								unset($balances[$apiId]);

								$this->utils->debug_log('SEAMLESS END', $playerUsername, $result);

							} else {
								$balance = $apiRlt['balance'];
								$subwallets[$apiId] = $controller->utils->formatCurrencyNumber($balance);
							}

							// 		$api = $controller->utils->loadExternalSystemLibObject($apiId);
							// 		// $this->utils->debug_log('apiId', $apiId, 'balance', $balance);
							// 		$api->updatePlayerSubwalletBalance($playerId, $balance);
						}
					}

					$this->utils->debug_log('lockAndTransForPlayerBalance - after balances', $playerId, $balances, $mainWallet, $frozen, $subwallets, $bigWallet);

					$controller->wallet_model->updateSubWalletsOnBigWallet($playerId, $balances);

					if($this->isWalletUpdated($bigWallet, $subwallets)) {
						# Only record balance history when there is a change
						$controller->wallet_model->recordPlayerAfterActionWalletBalanceHistory(Wallet_model::BALANCE_ACTION_REFRESH, $playerId, null, -1, 0, null, null, null, null, null);
					}
				}

				return true;

			});

			$this->utils->debug_log('player_query_all_balance - result', array(
				'api_id'		=> $api_id,
				'mainwallet'    => $mainWallet,
				'frozen'        => $frozen,
				'subwallets'    => $subwallets ,
				'success'       => $success
			));

			$this->utils->debug_log('player_query_all_balance ======================= End ===============', 'End');
			// return $this->returnJsonResult(array('mainwallet' => $mainWallet, 'frozen' => $frozen, 'subwallets' => $subwallets));
			if ($jsonp) {
				return $this->returnJsonpResult(array(
					'api_id'		=> $api_id,
					'mainwallet'    => $mainWallet,
					'frozen'        => $frozen,
					'subwallets'    => $subwallets ,
					'success'       => $success
				));
			} else {
				return $this->returnJsonResult(array(
					'api_id'		=> $api_id,
					'mainwallet'    => $mainWallet,
					'frozen'        => $frozen,
					'subwallets'    => $subwallets ,
					'success'       => $success
				));
			}
		}

		return $this->returnErrorStatus(403, true);
	}

	# Returns whether the given updatedSubWallet makes change to the original bigWallet
	private function isWalletUpdated($bigWallet, $updatedSubWallets) {
		foreach($updatedSubWallets as $apiId => $subWalletBalance) {
			$oldSubWalletBalance = $bigWallet['sub'][$apiId]['total'];
			if($this->utils->compareFloat($oldSubWalletBalance, $subWalletBalance) != 0) {
				$this->utils->debug_log("Wallet [$apiId] updated from [$oldSubWalletBalance] to [$subWalletBalance]");
				return true;
			}
		}
		$this->utils->debug_log("Wallet has no update");
		return false;
	}

	public function player_query_all_balance_jsonp() {
		//check player session
		if ($this->authentication->isLoggedIn()) {
			$playerId = $this->authentication->getPlayerId();
			$playerUsername = $this->authentication->getUsername();
			$manager = $this->utils->loadGameManager();
			$this->load->model(array('player_model', 'wallet_model', 'game_provider_auth'));

			//sync all balance
			$balances = $manager->queryBalanceOnAllPlatforms($playerUsername);
			$controller = $this;
			$subwallets = array();
			$frozen = 0;
			$mainWallet = 0;

			// OGP-208
			$success = array();
			foreach ($balances as $api_id => $api_res) {
				$success[$api_id] = $api_res['success'];
			}
			// OGP-1093
			$game_platforms = $this->game_provider_auth->getGamePlatforms($playerId);
			foreach ($game_platforms as $gp) {
				if (!empty($gp['register'])) {
					$success[$gp['id']] = true;
				}
			}


			$this->lockAndTransForPlayerBalance($playerId, function () use ($controller, $playerId, $balances, &$mainWallet, &$frozen, &$subwallets) {

				$bigWallet = $this->wallet_model->getBigWalletByPlayerId($playerId);

				$frozen = $this->utils->formatCurrencyNumber($bigWallet['main']['frozen']); //$this->player_model->getPendingBalanceById($playerId);

				$mainWallet = $this->utils->formatCurrencyNumber($bigWallet['main']['total_nofrozen']); //$this->wallet_model->getMainWalletBalance($playerId);

				foreach ($bigWallet['sub'] as $apiId => $subWallet) {
					$subwallets[$apiId] = $controller->utils->formatCurrencyNumber($subWallet['total_nofrozen']);
				}

				foreach ($balances as $apiId => $apiRlt) {
					if ($apiRlt['success']) {
						$balance = $apiRlt['balance'];
						$subwallets[$apiId] = $controller->utils->formatCurrencyNumber($balance);
						// 		$api = $controller->utils->loadExternalSystemLibObject($apiId);
						// 		// $this->utils->debug_log('apiId', $apiId, 'balance', $balance);
						// 		$api->updatePlayerSubwalletBalance($playerId, $balance);
					}
				}

				$controller->wallet_model->updateSubWalletsOnBigWallet($playerId, $balances);

				//after balance
				$controller->wallet_model->recordPlayerAfterActionWalletBalanceHistory(Wallet_model::BALANCE_ACTION_REFRESH,
					$playerId, null, -1, 0, null, null, null, null, null);

				return true;

			});
			return $this->returnJsonpResult(array(
				'mainwallet'	=> $mainWallet,
				'frozen'		=> $frozen,
				'subwallets'	=> $subwallets,
				'success'		=> $success
			));
		}

		return $this->returnErrorStatus(403, true);
	}

	private function monitor_api() {
		# TODO createPlayerByPlatform

		# load model
		// $this->load->model(array('external_system', 'player_model', 'transactions'));

		// // get first player
		// $where = array('playerId =' => 1);
		// $player = $this->player_model->getPlayer($where);
		// $playerName = $player['gameName'];
		// $playerId = $player['playerId'];
		// $password = $player['password'];

		// # get all game api
		// $game_api = $this->external_system->getAllSytemGameApi();

		// # get all game api balance by game_api_id
		// $game_balance = array();
		// foreach ($game_api as $g) {
		// 	$platformId = $g['id'];

		// 	// check if player exists
		// 	$platformStats = $this->checkIfPlayerExistInPlatform($platformId, $playerName);
		// 	$platformStats;

		// 	// if player not exist
		// 	if (!$platformStats) {
		// 		// then create player
		// 		$this->createPlayerByPlatform($platformId, $playerName, $playerId, $password, null, null);
		// 	}

		// 	// queryPlayerBalance
		// 	$game_api = $this->utils->loadExternalSystemLibObject($platformId);
		// 	$isRegisteredFlag = $this->game_provider_auth->isRegisterd($playerId, $platformId);
		// 	if ($isRegisteredFlag) {
		// 		$game_balance[$platformId] = $game_api->queryPlayerBalance($playerName);
		// 	}
		// }

		// // test result
		// // var_dump($game_balance);
		// return $game_balance;
	}

	private function checkIfPlayerExistInPlatform($platformId, $playerName) {
		$game_api = $this->utils->loadExternalSystemLibObject($platformId);
		$platformStats = $game_api->isPlayerExist($playerName);
		// var_dump(array('Player' => $playerName, 'Platform' => $platformId, 'Stats' => $platformStats));
		return $platformStats['success'];
	}

	private function createPlayerByPlatform($platformId, $playerName, $playerId, $password) {
		$game_api = $this->utils->loadExternalSystemLibObject($platformId);
		$player = $game_api->createPlayer($playerName, $playerId, $password); // doesn't work
		return $player['success'];
	}

	private function monitor_api2($player_id = 1) {

		// $player_id = (int) $player_id;

		// $this->startTrans();
		// $this->load->model(array('transactions'));
		// $rows = 50;
		// $this->utils->markProfilerStart('select_newest_transactions');
		// $result = $this->transactions->selectNewestTransactions($rows);
		// $this->utils->markProfilerEnd('select_newest_transactions');
		// $this->utils->printProfilerLog('select_newest_transactions');

		// $this->load->model(array('sale_order'));
		// $rows = 50;
		// $this->utils->markProfilerStart('select_newest_saleorders');
		// $result = $this->sale_order->selectNewestSaleOrders($rows);
		// $this->utils->markProfilerEnd('select_newest_saleorders');
		// $this->utils->printProfilerLog('select_newest_saleorders');

		// $this->load->model(array('wallet_model'));
		// $rows = 50;
		// $this->utils->markProfilerStart('select_newest_withdrawals');
		// $result = $this->wallet_model->selectNewestWithdrawals($rows);
		// $this->utils->markProfilerEnd('select_newest_withdrawals');
		// $this->utils->printProfilerLog('select_newest_withdrawals');

		// $this->load->model(array('users'));
		// $rows = 50;
		// $this->utils->markProfilerStart('select_newest_admin_users');
		// $result = $this->users->selectNewestAdminUsers($rows);
		// $this->utils->markProfilerEnd('select_newest_admin_users');
		// $this->utils->printProfilerLog('select_newest_admin_users');

		// $this->load->model(array('game_logs'));
		// $rows = 50;
		// $this->utils->markProfilerStart('select_newest_game_logs');
		// $result = $this->game_logs->selectNewestGameLogs($rows);
		// $this->utils->markProfilerEnd('select_newest_game_logs');
		// $this->utils->printProfilerLog('select_newest_game_logs');

		// // $this->load->model(array('vipsetting'));
		// // $this->utils->markProfilerStart('select_vipsettingcashbackrule');
		// // $result = $this->vipsetting->selectVipsettingcashbackrule();
		// // $this->utils->markProfilerEnd('select_vipsettingcashbackrule');
		// // $this->utils->printProfilerLog('select_vipsettingcashbackrule');

		// $this->load->model(array('player'));
		// $this->utils->markProfilerStart('select_ci_player_sessions');
		// $result = $this->player->selectCiPlayerSessions();
		// $this->utils->markProfilerEnd('select_ci_player_sessions');
		// $this->utils->printProfilerLog('select_ci_player_sessions');

		// $this->load->model(array('users'));
		// $this->utils->markProfilerStart('select_ci_admin_sessions');
		// $result = $this->users->selectCiAdminSessions();
		// $this->utils->markProfilerEnd('select_ci_admin_sessions');
		// $this->utils->printProfilerLog('select_ci_admin_sessions');

		// $this->load->model(array('users'));
		// $this->utils->markProfilerStart('select_ci_admin_sessions');
		// $result = $this->users->selectCiAdminSessions();
		// $this->utils->markProfilerEnd('select_ci_admin_sessions');
		// $this->utils->printProfilerLog('select_ci_admin_sessions');

		// $this->load->model(array('affiliate'));
		// $this->utils->markProfilerStart('select_ci_aff_sessions');
		// $result = $this->affiliate->selectCiAffiliateSessions();
		// $this->utils->markProfilerEnd('select_ci_aff_sessions');
		// $this->utils->printProfilerLog('select_ci_aff_sessions');

		// $this->select_and_update_test_player($player_id);
		// $this->select_and_update_wallet_for_testplayer('deposit', $player_id);

		// $succ = $this->endTransWithSucc();

		// if (!$succ) {
		// 	return $this->returnJsonResult(array('result' => 0));
		// } else {
		// 	return $this->returnJsonResult(array('result' => 1));
		// }

	}

	protected function select_and_update_test_player($player_id) {

		$this->load->model(array('player'));

		$player_id = (int) $player_id;
		$player = $this->player->getPlayerById($player_id);

		$languages = array('Chinese', 'English');
		$chosen_language = $languages[rand(0, 1)];
		$countries = array('Australia', 'United States', 'China', 'Philippines', 'Japan', 'Korea, South');
		$country_and_citizen = rand(0, 5);
		$chosen_country = $countries[$country_and_citizen];
		$citizenships = array('Australian', 'American', 'Chinese', 'Filipino', 'Japanese', 'Korean');
		$citizenship = $citizenships[$country_and_citizen];
		$address = rand(9, 999) . ' Street' . rand(1, 500);
		$city = 'City' . rand(9, 999);
		$contact_number = rand(8, 100) . rand(8, 100) . rand(8, 100);
		$imTypes1 = array('QQ', 'Skype', 'MSN');
		$imTypesValues1 = array(
			'QQ' => $this->getRandomNumberString(),
			'Skype' => $this->getRandomString(),
			'MSN' => $this->getRandomString(),
		);
		$im_type_rand1 = rand(0, 2);
		$im_type1 = $imTypes1[$im_type_rand1];
		$im_account1 = $imTypesValues1[$im_type1];

		$imTypes2 = array('QQ', 'Skype', 'MSN');
		$imTypesValues2 = array(
			'QQ' => $this->getRandomNumberString(),
			'Skype' => $this->getRandomString(),
			'MSN' => $this->getRandomString(),
		);

		$im_type_rand2 = rand(0, 2);
		$im_type2 = $imTypes2[$im_type_rand2];
		$im_account2 = $imTypesValues2[$im_type2];

		$data = array(
			'language' => ($player['language']) ? $player['language'] : $chosen_language,
			'residentCountry' => ($player['country']) ? $player['country'] : $chosen_country,
			'country' => ($player['country']) ? $player['country'] : $chosen_country,
			'address' => ($player['address']) ? $player['address'] : $address,
			'city' => ($player['city']) ? $player['city'] : $city,
			'contactNumber' => ($player['contactNumber']) ? $player['contactNumber'] : $contact_number,
			'imAccount' => ($player['imAccountType']) ? $player['imAccount'] : $im_account1,
			'imAccountType' => ($player['imAccountType']) ? $player['imAccountType'] : $im_type1,
			'imAccount2' => ($player['imAccountType2']) ? $player['imAccount2'] : $im_account2,
			'imAccountType2' => ($player['imAccountType2']) ? $player['imAccountType2'] : $im_type1,
			'citizenship' => ($player['country']) ? $player['citizenship'] : $citizenship,
			'birthplace' => ($player['city']) ? $player['city'] : $city,
		);
		$this->utils->markProfilerStart('select_and_update_test_player');
		$this->player->editPlayerDetails($data, $player_id);
		$this->utils->markProfilerEnd('select_and_update_test_player');
		$this->utils->printProfilerLog('select_and_update_test_player');
		return;

	}

	protected function select_and_update_wallet_for_testplayer($transaction, $player_id) {

		$this->load->model(array('payment_account', 'sale_order', 'banktype', 'player_model', 'playerbankdetails', 'banktype'));
		$player_id = (int) $player_id;

		#DEPOSIT sample local offline bank

		$this->utils->markProfilerStart('select_and_update_wallet_for_testplayer');

		$depositRule = $this->player_model->getPlayerDepositRule($player_id);
		$minDeposit = $depositRule[0]['minDeposit'];
		$maxDeposit = $depositRule[0]['maxDeposit'];
		$this->benchmark->mark('end_get_player_deposit_rule');

		#Generate random deposit between minimum and max deposit
		$depositAmount = rand($minDeposit, $maxDeposit);
		$playerBankDetailsId = '';
		$dwIp = $this->input->ip_address();
		$geolocation = $this->utils->getGeoplugin($dwIp);

		#Make selections of bank accounts to be used
		$bankAccts = array();
		$bankAccts[0] = 'new';
		$bankAccts[1] = 'preferred';
		$whichAcct = $bankAccts[rand(0, 1)];

		#new or use existing  bank accounts
		if ($whichAcct == 'new') {
			#create new acct
			$playerBankDetailsId = (int) $this->createTestPlayerSampleBankDetAcct($player_id, $transaction);
			//echo 'new acct';
		} else {
			#choose from existing banktype accounts
			$playerBankAccounts = $this->banktype->getDepositBankDetails($player_id);
			$playerBankDetailsIds = array();
			#check if he has bankdetails already
			#if yes choose and use it
			if ($playerBankAccounts) {
				foreach ($playerBankAccounts as $v) {
					array_push($playerBankDetailsIds, $v['playerBankDetailsId']);
				}
				$playerBankDetailsId = (int) $playerBankDetailsIds[rand(0, (count($playerBankDetailsIds) - 1))];
				//echo 'existing';
			} else {
				#if not create it
				$playerBankDetailsId = (int) $this->createTestPlayerSampleBankDetAcct($player_id, $transaction);
				//echo 'first acct';
			}

		}

		$depositPromoId = null;
		$playerPromoId = null;
		$defaultCurrency = $this->config->item('default_currency');
		$flag = Payment_account::FLAG_MANUAL_LOCAL_BANK;

		#Get payment account for local offline
		$paymentAccount = $this->payment_account->getAvailableAccount($player_id, $flag, $bankTypeId = null);
		$sale_order_id = '';

		if ($paymentAccount) {
			$payment_account_id = $paymentAccount->payment_account_id;
			$saleOrder = $this->sale_order->createDepositOrder(Sale_order::PAYMENT_KIND_DEPOSIT, $payment_account_id, $player_id, $depositAmount, $defaultCurrency, $playerPromoId, $dwIp, $geolocation['geoplugin_city'] . ',' . $geolocation['geoplugin_countryName'], $playerBankDetailsId);
			$sale_order_id = $saleOrder['id'];
			//echo 'success';
		} else {
			//echo 'Please provide payment accounts for the player level';
		}

		$depositApproval = array('approved', 'declined');
		$dA = $depositApproval[rand(0, 1)];
		$approvalProcess = $dA;
		switch ($dA) {
		case 'approved':
			#Approve deposit
			try {
				$saleOrder = $this->sale_order->getSaleOrderWithPlayerById($sale_order_id);
				if (!empty($saleOrder)) {
					$player = $saleOrder->player;
					$reason = 'Approved- Async test';
					$show_reason = rand(0, 1);

					$this->startTrans();
					$this->sale_order->approveSaleOrder($sale_order_id, $reason, $show_reason);
					$this->saveAction('async test', 'Approve Deposit Request', "User " . $this->authentication->getUsername() . " has successfully approve deposit request of " . $player->username . ".");
					$success = $this->endTransWithSucc();
				}
				//echo 'approved';
			} finally {
				// release it
				$action = 'admin_deposit';
				$rlt = $this->releaseActionById($sale_order_id, $action);

			}
			break;

		case 'declined':

			try {
				$saleOrder = $this->sale_order->getSaleOrderWithPlayerById($sale_order_id);
				if (!empty($saleOrder)) {
					$reason = 'Declined- Async test';
					$show_reason = rand(0, 1);

					$this->startTrans();
					$this->sale_order->declineSaleOrder($sale_order_id, $reason, $show_reason);
					$this->saveAction('async test', 'Declined Deposit/Withdrawal Request', "User " . $this->authentication->getUsername() . " has declined deposit/withdrawal request.");
					$success = $this->endTransWithSucc();
				}
				//echo 'declined';
			} finally {
				// release it
				$action = 'admin_deposit';
				$rlt = $this->releaseActionById($sale_order_id, $action);

			}

			break;
		}

		$this->utils->markProfilerEnd('select_and_update_wallet_for_testplayer');
		$this->utils->printProfilerLog('select_and_update_wallet_for_testplayer');
		return;
	}

	protected function createTestPlayerSampleBankDetAcct($player_id, $transaction_type = NULL) {

		$this->load->model(array('player', 'playerbankdetails'));
		$bankTypeId = rand(1, 20);
		$fullName = 'Firstname' . rand(1, 200) . ' ' . 'Lastname' . rand(300, 600);
		$depositAccountNo = '';
		do {
			$depositAccountNo = $this->getRandomNumberString(10);
		} while ($this->playerbankdetails->checkBankAccountNumber($depositAccountNo));

		$data = array(
			'playerId' => $player_id,
			'bankTypeId' => $bankTypeId,
			'bankAccountNumber' => $depositAccountNo,
			'bankAccountFullName' => $fullName,
			'dwBank' => ($transaction_type == NULL) ? 0 : $transaction_type, //0 is deposit// create acct from deposit -- 1 for withdrawal
			'isRemember' => '1', //1 is default
			'status' => '0', //0 is active
		);
		//var_dump($data);
		return $this->player->addBankDetailsByDeposit($data);
	}

	protected function getRandomString($length = 8) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$string = '';

		for ($i = 0; $i < $length; $i++) {
			$string .= $characters[mt_rand(0, strlen($characters) - 1)];
		}

		return $string;
	}
	protected function getRandomNumberString($length = 8) {
		$characters = '0123456789';
		$string = '';

		for ($i = 0; $i < $length; $i++) {
			$string .= $characters[mt_rand(0, strlen($characters) - 1)];
		}

		return $string;
	}

	public function admin_support_live_chat() {
		$this->load->library(array('permissions', 'authentication'));
		$this->permissions->setPermissions();

		if (!$this->permissions->checkPermissions('admin_support_live_chat') ||
			!$this->utils->isEnabledFeature('show_admin_support_live_chat')) {
			return $this->showErrorAccessJS('no permission for admin support live chat');
		}

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();

		// $this->language_function->setCurrentLanguage($this->language_function->langStrToInt($siteLang));
		// //set language
		// $this->utils->loadLanguage($siteLang);

		// $key = $this->utils->getConfig('live_chat_encrypt_key1');
		// $add_key = $this->utils->getConfig('live_chat_encrypt_key2');
		// $username = $playerUsername; // . rand(1, 10000);
		// $linkurl=$this->utils->getSystemUrl('admin');
		$linkurl = $this->utils->getSystemUrl('admin') . '/user_management/viewUser/' . $userId;
		// $usernameEncrypted = lhSecurity::encryptBase64($username, $key, $add_key);

		require_once __DIR__ . '/../libraries/lib_livechat.php';

		// $this->load->library(array('lib_livechat'));
		$userInfo = array('username' => $username, 'from' => $this->utils->getConfig('server_name'),
			'hostname' => gethostname(), 'linkurl' => $linkurl);
		$chat_options = $this->utils->getConfig('admin_support_live_chat');
		// $chat_options['www_chat_options']['onlylink']=$onlylink; //overwrite
		$js = Lib_livechat::getChatJs($chat_options, $userInfo);

		$this->returnJS($js);

	}

	public function set_language($language) {

		$this->output->set_header('Access-Control-Allow-Origin: ' . $this->getAvailableOrigin());
		$this->output->set_header('Access-Control-Allow-Credentials: true');

		$this->language_function->setCurrentLanguage($language);

		// $this->session->set_userdata('lang', $language);
		$arr = array('status' => 'success');

		if ($this->authentication->isLoggedIn()) {
			//update player
			$this->load->model(['player_model']);
			$this->player_model->setLanguageToPlayer($this->authentication->getPlayerId(), $language);
		}

		$this->returnJsonResult($arr);
	}

	public function register_demo() {
		$demo_admin = $this->utils->getConfig('demo_admin');
		$demo_player = $this->utils->getConfig('demo_player');

		$flds = $this->input->get();

		$this->utils->debug_log('register_demo get fields', $flds);

		$email_address = @$flds['email_address'];
		$full_name = @$flds['full_name'];
		$phone_number = @$flds['phone_number'];
		if (empty($email_address) || empty($full_name) || empty($phone_number)) {
			$success = false;
			$admin_url = '';
			$player_url = '';
		} else {
			//create admin link, login by token
			$this->load->model(['users', 'common_token', 'player_model', 'queue_result']);
			$this->load->library(['email_setting']);
			$userId = $this->users->getIdByUsername($demo_admin);
			$admin_token = $this->getAdminToken($userId);

			$admin_url = site_url('/auth/login_by_token/' . $admin_token);
			$playerId = $this->player_model->getPlayerIdByUsername($demo_player);
			$player_token = $this->common_token->getPlayerToken($playerId);
			$player_url = $this->utils->getSystemUrl('player') . '/iframe/auth/login_with_token/' . $player_token;

			//save to info and send mail
			$this->db->insert('visit_demo_account', [
				'json_data' => $this->utils->encodeJson($flds),
				'created_at' => $this->utils->getNowForMysql(),
			]);

			$to = $this->utils->getConfig('demo_account_send_mail_address');
			$from = null;
			$fromName = null;
			$subject = 'Get Registration from ' . $full_name;
			$body = '<html><body><pre style="font-size: 150%;">' . json_encode($flds, JSON_PRETTY_PRINT) . '</pre></body></html>';

			$emailData = array(
				'from' => $from,
				'from_name' => $fromName,
				'subject' => $subject,
				'body' => $body,
			);

			try {
				$rlt = $this->email_setting->sendEmail($to, $emailData, ['print_debug' => false]);
				$this->utils->debug_log('send email ' . $to, $rlt);
			} catch (Exception $e) {
				$this->utils->error_log('send mail error:' . $to, $emailData, $e);
			}

			// $this->utils->sendMail($to, $from, $fromName, $subject, $body, Queue_result::CALLER_TYPE_ADMIN, 1);

			//send email
			$success = true;
		}

		$this->returnJsonpResult(['success' => $success, 'admin_url' => $admin_url, 'player_url' => $player_url]);
		// echo 'admin: '.$admin_url.' player_url: '.$player_url;
	}

	/**
	 * overview : get game decription id by game platform id and game name
	 *
	 * @param $gamePlatformId
	 * @param $gameName
	 * @return mixed
	 */
	public function queryGameByPlatformAndName($gamePlatformId, $gameName){
//
//		// Cross-Origin Resource Sharing Header
//		header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
//		header('Access-Control-Allow-Methods: GET, POST');
//		header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept');

		if($gameName){
			$gameName = urldecode($gameName);
			$gameName = trim($gameName);
		}

		$this->load->model(array('game_description_model'));
		$game_descriptions = $this->game_description_model->getGameDescriptionByGamePlatformIdAndGameName($gamePlatformId, $gameName);

		$this->returnJsonResult($game_descriptions);

//		$this->returnJsonpResult([
//			'success' => $success,
//			'id' => $game_description['id'],
//			'game_code' => $game_description['game_code']
//		]);
	}

	/**
	 * to get availabe subwallet list for logged player
	 * @param  string $ignore_0
	 * @return json
	 */
	public function available_subwallet_list($ignore_0 = 'true'){
        $this->load->library(['authentication']);
        $this->load->model(['wallet_model']);

        $ignore_0 = ($ignore_0 == 'true') ? TRUE : FALSE;

        //query logged player id
        $player_id = $this->authentication->getPlayerId();
        if(empty($player_id)){
        	$message = lang('No available player');

            return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $message);
        }

        $result = $this->wallet_model->getAvailableRefreshWalletListByPlayerId($player_id, $ignore_0);
        $result['walletInfo'] = $this->utils->getSimpleBigWallet($player_id);

        if(!$result['success']){
            return $this->returnCommon(BaseController::MESSAGE_TYPE_ERROR, $result['message']);
        }

        return $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, NULL, $result);
	}

    public function getPlayerAvailablePromoList( $promoCategory ){
        // $playerId, $promoCmsSettingId = null, $promoCategory = null, $pagination = []
        $promoList = [];
        $playerId = $this->authentication->getPlayerId();
        $promoCmsSettingId = null;
        $pagination = [];
        $result = $this->utils->getPlayerAvailablePromoList($playerId, $promoCmsSettingId, $promoCategory, $pagination);

        if(!empty($result['promo_list'])){
            foreach($result['promo_list'] as &$promo_item){

                $promo_item['promoDetails4lestCo'] = html_entity_decode($promo_item['promoDetails'], ENT_QUOTES, 'UTF-8');
                $promo_item['iframeRequestPromoUri'] = site_url("iframe_module/request_promo/" . $promo_item['promoCmsSettingId']);


                $promo_item['promoThumbnailUrl'] = $this->utils->getPromoThumbnailsUrl($promo_item['promoThumbnail']);

                /// ref. to player/application/views/stable_center2/mobile/promotion/promotion.php
                if(file_exists($this->utils->getPromoThumbnails() . $promo_item['promoThumbnail']) && !empty($promo_item['promoThumbnail'])){
                    $promoThumbnail = $this->utils->getPromoThumbnailRelativePath(FALSE) . $promo_item['promoThumbnail'];
                }else{
                    if(!empty($promo_item['promoThumbnail'])){
                        $promoThumbnail = $this->utils->imageUrl('promothumbnails/' . $promo_item['promoThumbnail']);
                    }else{
                        $promoThumbnail = $this->utils->imageUrl('promothumbnails/default_promo_cms_1.jpg');
                    }
                }
                //
                /// Add/append the query, v=PRODUCTION_VERSION to promoThumbnail
                $parsed_query = [];
                $parsed_url = parse_url($promoThumbnail);
                if( empty($parsed_url['query'])){
                    $add_ver = true;
                }else{
                    parse_str( $parsed_url['query'], $parsed_query );
                }
                // add the query, v=PRODUCTION_VERSION
                $parsed_query['v'] = PRODUCTION_VERSION;
                $parsed_url['query'] = http_build_query($parsed_query);
                $promoThumbnail = $this->utils->build_url($parsed_url);
                $promo_item['promoThumbnailUrl4mobile'] = $promoThumbnail;
                // $promo_item['promoThumbnailUrl4mobile'] = $this->utils->getPromoThumbnailsUrl($promo['promoThumbnail'], true);

            }
        }

        return $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, NULL, $result);
    } // EOF getPlayerAvailablePromoList


    public function get_unread_messages() {
        $this->load->library(['player_message_library']);
        $player_id = $this->authentication->getPlayerId();
        if(!empty($player_id)){
            $unread_messages = $this->player_message_library->getPlayerUnreadMessages($player_id);
            $data = array(
                'messages' => $unread_messages,
                'chatUnread' => count($unread_messages),
            );

            if ($this->utils->getConfig('enabled_new_broadcast_message_job')) {
				$player_registr_date = $this->player_model->getPlayerRegisterDate($player_id);
	            $broadcast_messages = $this->player_message_library->getPlayerAllBroadcastMessages($player_id, $player_registr_date);
	             $this->utils->debug_log(__METHOD__, 'get_unread_messages',$broadcast_messages);
	            if (!empty($broadcast_messages)) {
	                $data['chatUnread'] = $data['chatUnread'] + count($broadcast_messages);
	            }
	        }
	        $this->utils->debug_log(__METHOD__, 'data',$data);
        }else{
            $data = array(
                'chatUnread' => 0,
                'messages' => [],
            );
        }
        //echo var_dump($data);
        return $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, NULL, $data);
    }

    public function get_unread_last_messages(){
		$this->load->library(['player_message_library']);
        $player_id = $this->authentication->getPlayerId();

        $data = array(
            'chatUnread' => 0,
            'messages' => [],
        );

        if(!empty($player_id)){
			$limit = 1;
			$flag = "'admin'";
			$where = 'cd.flag = '. $flag .' and ';
            $last_messages = $this->player_message_library->getPlayerLastMessages($player_id, $limit, null, $where);
            $this->utils->printLastSQL();
            if (count($last_messages) > 0) {
				if ($last_messages[0]['cd_status'] == Internal_message::MESSAGE_DETAILS_UNREAD) {
					$last_messages[0]['detail'] = htmlspecialchars_decode($last_messages[0]['detail']);
					$data = array(
		                'messages' => $last_messages[0],
		                'chatUnread' => count($last_messages),
		            );
	            }
            }
        }
        return $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, NULL, $data);
    }

    public function get_message($message_id, $broadcast_id = null) {
        $this->load->library(['player_message_library', 'notify_in_app_library']);

        if(!$this->authentication->isLoggedIn()){
            return $this->returnCommon(self::MESSAGE_TYPE_ERROR, lang('not login'), NULL);
        }

        $player_id = $this->authentication->getPlayerId();
        $username = $this->authentication->getUsername();

        $this->utils->debug_log(__METHOD__, 'message_id', $message_id ,'broadcast_id', $broadcast_id,'player_id', $player_id);

        if ($message_id == 'null' && !empty($broadcast_id)) {
			$message_id = $this->player_message_library->addNewMessageFromBroadcast($broadcast_id, $username);
			$data = $this->player_message_library->getMessageByIdForPlayer($player_id, $message_id);
		} else {
			$data = $this->player_message_library->getMessageByIdForPlayer($player_id, $message_id);
        }

        $this->utils->debug_log(__METHOD__, 'data', $data);

        if(!empty($data)){
            $status = BaseController::MESSAGE_TYPE_SUCCESS;
            $message = NULL;

            $player_id = $data['topic']['playerId'];
            $source_method = __METHOD__;
            $this->notify_in_app_library->triggerOnUpdatedMessageStatusToReadEvent($player_id, $source_method);
        }else{
            $status = BaseController::MESSAGE_TYPE_ERROR;
            $message = lang('cs.message_not_found');
            $data = NULL;
        }
        //echo var_dump($data);
        return $this->returnCommon($status, $message, $data);
    }

    /**
     * overview : reply to chat
     *
     * @param   int		$chat_id
     * @return  mixed
     */
    public function reply_message($message_id, $broadcast_id = null) {
        $this->load->library(['player_message_library']);
        if(!$this->authentication->isLoggedIn()){
            return $this->returnCommon(self::MESSAGE_TYPE_ERROR, lang('not login'), NULL);
        }

        $_POST = $_GET;

        if(!$this->player_message_library->run_validation(Player_message_library::VALIDATION_TYPE_REPLY)){
            return $this->returnCommon(self::MESSAGE_TYPE_ERROR, validation_errors(), NULL);
        }

        $username = $this->authentication->getUsername();
		$message = $this->stripHTMLtags($this->input->post('message', true));
		$message = $this->utf8convert($message);
		$message = addslashes($this->utils->emoji_mb_htmlentities($message));

		$this->utils->debug_log(__METHOD__, 'message_id', $message_id ,'broadcast_id', $broadcast_id,'username', $username);

		// if ($message_id == 'null' && !empty($broadcast_id)) {
		// 	$message_id = $this->player_message_library->addNewMessageFromBroadcast($broadcast_id, $username);
		// }

        $result = $this->player_message_library->replyMessage($message_id, $username, $message);

        $this->utils->debug_log(__METHOD__, 'result', $result);

        if ($result['status']) {
            $status = self::MESSAGE_TYPE_SUCCESS;
            $message = lang('mess.17');
        } else {
            $status = self::MESSAGE_TYPE_ERROR;
            $message = $result['message'];
        }

        return $this->returnCommon($status, $message, NULL, $this->utils->getPlayerMessageUrl());
    }

    protected function utf8convert($mesge, $key = null) {
		if (is_array($mesge)) {
			foreach ($mesge as $key => $value) {
				$mesge[$key] = utf8convert($value, $key);
			}
		} elseif (is_string($mesge)) {
			$fixed = mb_convert_encoding($mesge, "UTF-8", "auto");
			return $fixed;
		}
		return $mesge;
	}

    public function variables(){

    	$this->load->model(['multiple_db_model','http_request']);
		$this->load->library(['player_main_js_library', 'session']);
    	$db=$this->db;
    	if($this->utils->isEnabledMDB()){
    		//switch to super
    		$db=$this->multiple_db_model->getSuperDBFromMDB();
    	}
		//init token in session
        $payload=$this->input->get('callback');
        $this->_initSimpleCSRF(empty($payload) ? 'callback' : $payload);
    	$this->utils->debug_log('variables use db :'.$db->getOgTargetDB());
		$result=null;
    	$success=$this->multiple_db_model->runDBTransOnly($db, $result, function($db, &$result){

	        // check aff domain get tracking code or null
	        $trackingCode = $this->getTrackingCode();
	        if(!empty($trackingCode)){
				$this->setTrackingCodeToSession($trackingCode);
				if(!$this->getSession('visit_record_id')){
					$visit_record_id = $this->http_request->recordPlayerRegistration(null,$trackingCode, null);
					$this->session->set_userdata('visit_record_id',$visit_record_id);
				}
			}

			$trackingSourceCode = $this->getTrackingSourceCode();
			if(!empty($trackingSourceCode)) {
				$this->session->set_userdata('tracking_source_code', $trackingSourceCode);
			}

	        // check aff domain get tracking code or null
	        $trackingCode = $this->getAgentTrackingCode();
	        if(!empty($trackingCode)){
	            $this->setAgentTrackingCodeToSession($trackingCode);
	        }
	        $agentTrackingSourceCode = $this->getAgentTrackingSourceCode();
	        $this->session->set_userdata('agent_tracking_source_code', $agentTrackingSourceCode);

		    /* @var $player_main_js_library Player_main_js_library */
		    $variables = $this->player_main_js_library->getVariables();

		    $result=$variables;

		    return true;

    	});
    	if($success){
	    	return $this->returnCommon(self::MESSAGE_TYPE_SUCCESS, null, $result);
    	}else{
    		$this->utils->error_log('load variable failed', $success, $result);
		    return $this->returnCommon(self::MESSAGE_TYPE_ERROR, null, $result);
    	}
    }

	public function checkRmbme(){
		$rememberme_token = $this->CI->input->cookie('remember_me');
        // $sess_og_player = $this->CI->input->cookie('sess_og_player');
        // $sess = $this->CI->session->userdata('session_id');

		$result = [];
		if($this->operatorglobalsettings->getSettingJson('remember_password_enabled') && $rememberme_token) {
			$this->CI->load->model(['player_login_token','player_model']);
            $player_id = $this->CI->player_login_token->getPlayerId($rememberme_token);
			$username = $this->CI->player_model->getUsernameById($player_id);
			$password_holder = $this->CI->session->userdata('password_holder');
            if(empty($password_holder)) {
                $password_holder = $this->CI->utils->generateRandomCode(14);
                $this->CI->session->set_userdata('password_holder', $password_holder);
            }
			if(!empty($username) && !empty($password_holder)){
				// $variables['ui']['rmbme'] = $remember_me;
				// $variables['ui']['rmbme_username'] = $username;
				// $variables['ui']['rmbme_password_holder'] = $password_holder;
				// $result['sess'] = $sess;
				// $result['rmbme_username'] = $username;
				// $result['rmbme_password_holder'] = $password_holder;
				// $result['rememberme_token'] = $rememberme_token;
				$result['rmbme_username'] = $username;
				$result['rmbme_password_holder'] = $password_holder;
			}
		}
		// return $this->returnCommon(self::MESSAGE_TYPE_SUCCESS, null, $result);
		return $this->returnJsonpResult(['success' => true, 'result' => $result]);
	}

	public function buildDeadsimplechatHtml(){
		$playerUsername = $this->CI->authentication->getUsername();
		$VIP_level = $this->CI->authentication->getPlayerCurrentLevel();
		$result = [];
		if(!empty($playerUsername) && !empty($VIP_level)){
			$buildDeadsimplechatHtml = $this->CI->utils->buildDeadsimplechatHtml($VIP_level,$playerUsername);
			$result['buildDeadsimplechatHtml'] = $buildDeadsimplechatHtml;
		}
		return $this->returnJsonpResult(['success' => true, 'result' => $result]);
	}

	public function getChatHTML(){
		$playerUsername = $this->CI->authentication->getUsername();
		$VIP_level = $this->CI->authentication->getPlayerCurrentLevel();
		$result = [];
		$this->utils->debug_log(__METHOD__ . ' start getChatHTML',
		[
			"playerUsername" => $playerUsername,
			"VIP_level" => $VIP_level
		]);
		if(!empty($playerUsername) && !empty($VIP_level)){
			$p2p_chat_html = $this->CI->utils->getChatHTML($VIP_level,$playerUsername);
			$result['p2p_chat'] = $p2p_chat_html;
		}else {
            $html = '<span class="deadsimplechat_vip_message">'.lang('deadsimplechat warning message').'</span>';
			$result['p2p_chat'] = $html;
        }
		$this->utils->debug_log(__METHOD__ . ' return getChatHTML',
		[
			"result" => $result
		]);

		return $this->returnJsonpResult(array('success' => true, 'result' => $result));
	}

    public function launch_game_rwb(){
		$player_name = $this->authentication->getUsername();
		$success = true;
		$result = array();
		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance(RWB_API)) {
            $success = false;
        }
        if($success) {
        	$api  = $this->utils->loadExternalSystemLibObject(RWB_API);
        	$isPlayerExist = $api->isPlayerExist($player_name)[1];
        	if(isset($isPlayerExist['exists']) && $isPlayerExist['exists']){
        		$api_response = $api->queryForwardGame($player_name);
				$result = array(
					'integration_key' => $api_response['integration_key'],
					'userId' => $api_response['userId'],
					'authToken' => $api_response['authToken']
				);
        	}
        }

    	$data = array('success' => $success,'result' => $result);
    	return $this->returnJsonpResult($data);
    }

    public function player_logout(){
        $logout_result = $this->authentication->logout();

        $data = [
            'redirect_url' => $logout_result['redirect_url']
        ];

        return $this->returnCommon(self::MESSAGE_TYPE_SUCCESS, '', $data);
    }

    public function AutoLockVerifyPassword(){
        $password = $this->input->get('password');

        $api_auto_lock_verify_password_call_times = (int)$this->session->userdata('api_auto_lock_verify_password_call_times');
        $api_auto_lock_verify_password_call_times++;

        $this->session->set_userdata('api_auto_lock_verify_password_call_times', $api_auto_lock_verify_password_call_times);

        $player_auto_lock_password_failed_attempt = $this->operatorglobalsettings->getSettingIntValue('player_auto_lock_password_failed_attempt', $this->utils->getUploadConfig('player_auto_lock_password_failed_attempt'));

        $data = [
            'try_times' => $api_auto_lock_verify_password_call_times
        ];

        if (!$this->authentication->isLoggedIn()) {
            return $this->returnCommon(self::MESSAGE_TYPE_ERROR, NULL, $data);
        }

        if($api_auto_lock_verify_password_call_times > $player_auto_lock_password_failed_attempt){
            $this->player_logout();
            return $this->returnCommon(self::MESSAGE_TYPE_ERROR, sprintf(lang('player_auto_lock_password_failed_attempt_reach_to_limit'), $player_auto_lock_password_failed_attempt), $data);
        }

        $playerId = $this->authentication->getPlayerId();

        $playerDetails = $this->player_functions->getPlayerById($playerId);
        $passwordResult = $this->salt->decrypt($playerDetails['password'], $this->getDeskeyOG());

        $verify_result = ($passwordResult === $password);

        $data['verify_result'] = $verify_result;

        if($verify_result){
            $status = self::MESSAGE_TYPE_SUCCESS;
            $message = NULL;
            $this->session->set_userdata('api_auto_lock_verify_password_call_times', 0);
        }else{
            $status = self::MESSAGE_TYPE_ERROR;
            $message = sprintf(lang('player_auto_lock_password_check_failed'), $api_auto_lock_verify_password_call_times);
        }

        return $this->returnCommon($status, $message, $data);
    }

    public function player_notify(){
        if (!$this->authentication->isLoggedIn()) {
            return $this->returnCommon(self::MESSAGE_TYPE_ERROR, lang('not login'), NULL);
        }

        $player_id = $this->authentication->getPlayerId();

        $this->load->library(['player_notification_library']);

        /* @var $player_notification_library Player_notification_library */
        $player_notification_library = $this->player_notification_library;

        $data = $player_notification_library->getNotify($player_id);

        return $this->returnCommon(self::MESSAGE_TYPE_SUCCESS, NULL, $data);
    }

    public function player_is_notify(){
        if (!$this->authentication->isLoggedIn()) {
            return $this->returnCommon(self::MESSAGE_TYPE_ERROR, lang('not login'), NULL);
        }

        $player_id = $this->authentication->getPlayerId();

        $this->load->library(['player_notification_library']);

        /* @var $player_notification_library Player_notification_library */
        $player_notification_library = $this->player_notification_library;

        $notify_id = $this->input->get_post('notify_id');

        $data = $player_notification_library->setIsNotify($player_id, $notify_id);

        return $this->returnCommon(self::MESSAGE_TYPE_SUCCESS, NULL, $data);
    }

	public function player_trackingevent(){
		if(!$this->config->item('enable_player_action_trackingevent_system')){
			return $this->returnCommon(self::MESSAGE_TYPE_ERROR, NULL, NULL);
		}

        if (!$this->authentication->isLoggedIn()) {
            return $this->returnCommon(self::MESSAGE_TYPE_ERROR, lang('not login'), NULL);
        }

        $player_id = $this->authentication->getPlayerId();

        $this->load->library(['player_trackingevent_library']);

        /* @var $player_trackingevent_library player_trackingevent_library */
        $player_trackingevent_library = $this->player_trackingevent_library;

        $data = $player_trackingevent_library->getNotify($player_id);

        return $this->returnCommon(self::MESSAGE_TYPE_SUCCESS, NULL, $data);
    }

    public function player_set_trackingevent_done(){
		if(!$this->config->item('enable_player_action_trackingevent_system')){
			return $this->returnCommon(self::MESSAGE_TYPE_ERROR, NULL, NULL);
		}
        if (!$this->authentication->isLoggedIn()) {
            return $this->returnCommon(self::MESSAGE_TYPE_ERROR, lang('not login'), NULL);
        }

        $player_id = $this->authentication->getPlayerId();

        $this->load->library(['player_trackingevent_library']);

        /* @var $player_trackingevent_library player_trackingevent_library */
        $player_trackingevent_library = $this->player_trackingevent_library;

        $notify_id = $this->input->get_post('notify_id');

        $data = $player_trackingevent_library->setIsNotify($player_id, $notify_id);

        return $this->returnCommon(self::MESSAGE_TYPE_SUCCESS, NULL, $data);
    }

    /**
	 * detail: update player nttech bet setting
	 *
	 * @return json
	 */
    public function update_player_nttech_bet_setting() {
    	$this->load->library(array('permissions'));
		if (!$this->permissions->checkPermissions('set_player_bet_limit_for_api')) {
			$data_result = array('success' => false,'result' => "No permission permission");
			return $this->returnJsonResult($data_result);
		} else {
			$data = json_decode($this->input->post('data'),true);

			$update_by = $data['update_by'];
        	$setting = $data['setting'];
			$amount = $data['amount'];
			$selected  = $data['selected'];
			$username = $data['username'];
			$game_platform_id = $data['game_platform_id'];
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
			$bet_format = null;
			if($update_by == "by-id"){
				$bet_format = array(
					$selected => array(
						"LIVE" => array(
							"limitId" => (array)$setting
						)
					)
				);
			}

			if($update_by == "by-value"){
				$bet_format = array(
					$selected => array(
						"LIVE" => array(
							"maxbet" => $amount[0],
							"minbet" => $amount[1],
							"mindraw" => $amount[2],
							"matchlimit" => $amount[3],
							"maxdraw" => $amount[4]
						)
					)
				);
			}
			$response = $api->setMemberBetSetting($username,$bet_format);
			return $this->returnJsonResult($response);
		}
    }


	/**
	 * detail: update player oneworks bet setting
	 *
	 * @param json data
	 * @param player_name
	 * @return json
	 */
	public function update_player_oneworks_bet_setting() {
		$this->load->library(array('permissions'));
		if (!$this->permissions->checkPermissions('set_player_bet_limit_for_api')) {
			$data_result = array('success' => false,'result' => "No permission permission");
			return $this->returnJsonResult($data_result);
		} else {
			$success = false;
			$newSetting = array();
			$result = array();
			$data_post = json_decode($this->input->post('data'),true);
			$gameId = json_decode($this->input->post('gameId'));
			$api = $this->utils->loadExternalSystemLibObject($gameId);
			if(!empty($data_post)){
				foreach ($data_post as $key => $value) {
					$data = array(
						'sport_type' => $value['id'],
	                	'min_bet' => $value['min_bet'],
	                	'max_bet' => $value['max_bet'],
	                	'max_bet_per_match' => $value['max_bet_per_match']
					);

					$multiplier = isset($api->max_payout_per_match_multiplier)?$api->max_payout_per_match_multiplier:8;
					$data['max_payout_per_match'] = isset($value['max_payout_per_match'])?$value['max_payout_per_match']:$data['max_bet_per_match']*$multiplier;

					if(!empty($value['max_bet_per_ball'])){
						$data['max_bet_per_ball'] = $value['max_bet_per_ball'];
					}
					$newSetting[] = $data;
				}
			}
			$playerId = json_decode($this->input->post('playerId'));
			$player_info = $this->player_model->getPlayerInfoById($playerId);
			$response = $api->setMemberBetSetting($player_info['username'],$newSetting);
			if($response['success']){//check if success
				$success = true;
				$result = array(
						'error_code' => $response['result']['error_code']
					);
			}
			$data_result = array('success' => $success,'result' => $result);
			return $this->returnJsonResult($data_result);
		}
	}

	public function update_player_ogplus_bet_setting() {
		$this->load->library(array('permissions'));
		if (!$this->permissions->checkPermissions('set_player_bet_limit_for_api')) {
			$data_result = array('success' => false,'result' => "No permission permission");
			return $this->returnJsonResult($data_result);
		} else {
			$success = false;
			$data = $this->input->post('data');
			$gameId = json_decode($this->input->post('gameId'));
			if(!empty($data)) {
				$data_post = json_decode($data,true);
					$data = array(
						'min_bet' => $data_post['min_bet'],
						'max_bet' => $data_post['max_bet']
					);
			}
			$playerId = json_decode($this->input->post('playerId'));
			$api = $this->utils->loadExternalSystemLibObject($gameId);
			$player_info = $this->player_model->getPlayerInfoById($playerId);
			$response = $api->updateMemberBetSetting($player_info['username'],$data['min_bet'],$data['max_bet']);
			if($response['success']) {
				$success = true;
				$result = array(
					'error_code' => $response['result']['data']
				);
			} else {
				$success = false;
				$result = array(
					'error_code' => $response['result']['data']['message']
				);
			}

			$data_result = array('success' => $success, 'result' => $result);

			return $this->returnJsonResult($data_result);
		}

	}

    /**
	 * detail: update player tg bet setting
	 *
	 * @param json data
	 * @param player_name
	 * @return json
	 */
    public function update_player_tg_bet_setting() {
		$this->load->library(array('permissions'));
		if (!$this->permissions->checkPermissions('set_player_bet_limit_for_api')) {
			$data_result = array('success' => false,'result' => "No permission");
			return $this->returnJsonResult($data_result);
		} else {
			$success = false;
			$data = $this->input->post('data',true);
			$gameId = json_decode($this->input->post('gameId'));
			if(!empty($data)) {
				$data_post = json_decode($data,true);
					$data = array(
						'min_bet'  => $data_post['min_bet'],
						'max_bet'  => $data_post['max_bet'],
						'min_tie'  => $data_post['min_tie'],
						'max_tie'  => $data_post['max_tie'],
						'min_pair' => $data_post['min_pair'],
						'max_pair' => $data_post['max_pair']
					);
			}
			$playerId = json_decode($this->input->post('playerId'));
			$api = $this->utils->loadExternalSystemLibObject($gameId);
			$player_info = $this->player_model->getPlayerInfoById($playerId);
			$response = $api->setMemberBetSetting($player_info['username'],$data);
			if($response['success']) {
				$success = true;
			} else {
				$success = false;
			}

			$data_result = array('success' => $success);

			return $this->returnJsonResult($data_result);
		}

	}

	public function accept_declined_isb_fr($method,$freeround_id){
		$method_list = array('accept','decline');
		$player_name = $this->authentication->getUsername();
		$success = true;
		$result = array();
		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance(ISB_API) || !in_array($method, $method_list)) {
            $success = false;
        }
        if($success) {
        	$api  = $this->utils->loadExternalSystemLibObject(ISB_API);
        	if($method == "accept"){
        		$resultJson = $api->acceptPlayerFreeRound($player_name,$freeround_id);
        	} else {
        		$resultJson = $api->cancelPlayerFreeRound($player_name,$freeround_id);
        	}

        	if(strtolower($resultJson['status']) != "ok"){
	            $success = false;
	        }
	        $result = array(
					'method' => $method,
					'round'  => $freeround_id,
					'status' => $resultJson
			);
        }

    	$data = array('success' => $success,'result' => $result);
    	return $this->returnJsonpResult($data);
	}

	/**
	 * Get Bet Detail Link of AE SLOTS GAME API
	 *
	 * @param string $gameUsername the player name in game
	 * @param string $round_id the round id
	 *
	 * @return string the url of bet detail link
	*/
	public function get_bet_detail_link_of_ae_slots($gameUsername,$round_id){
		$success = true;
		$result = array();
		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance(AE_SLOTS_GAMING_API)){
            $success = false;
		}

		if($success){
			$api  = $this->utils->loadExternalSystemLibObject(AE_SLOTS_GAMING_API);
			$response = $api->queryBetDetailLink($gameUsername,$round_id);
			//$game_history_url = !empty($response['game_history_url']) ?  $response['game_history_url'] : null;
			if(isset($response['url'])&&!empty($response['url'])){
				redirect($response['url'], 'refresh');
			}
		}

		return null;
	}

	/**
	 * Get Bet Detail Link of QUEEN MAKER GAME API
	 *
	 * @param string $gameUsername the player name in game
	 * @param string $round_id the round id
	 *
	 * @return string the url of bet detail link
	*/
	public function get_bet_detail_link_of_queen_maker($gameUsername,$round_id,$game_platform_id=QUEEN_MAKER_GAME_API){
		$success = true;
		$result = array();
		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)){
            $success = false;
		}

		if($success){
			$api  = $this->utils->loadExternalSystemLibObject($game_platform_id);
			$response = $api->queryBetDetailLink($gameUsername,$round_id);
			//$game_history_url = !empty($response['url']) ?  $response['url'] : null;
			//redirect($game_history_url, 'refresh');
			if(isset($response['url'])&&!empty($response['url'])){
				redirect($response['url'], 'refresh');
			}
		}

		return null;
    }

	/**
	 * Get Bet Detail Link of GAME API
	 *
	 * @param string $gamePlatformId the game platform id
	 * @param string $player_username the player name in game
	 * @param string $reference_id the bet detail round id/reference id
	 *
	 * @return string the url of bet detail link
	*/
	public function get_bet_detail_link_of_game_api($gamePlatformId, $player_username, $reference_id, $extra = null){
		$success = true;
		$result = array();
		$oApi = $this->utils->loadExternalSystemLibObject($gamePlatformId);
		if (!empty($oApi)) {
			if ($gamePlatformId == DT_API) {
				$extra['language'] = $this->language_function->getCurrentLanguage();
			}

			$response = $oApi->queryBetDetailLink($player_username, $reference_id, $extra);

			if ($gamePlatformId == KINGPOKER_GAME_API && $response['success'] && isset($response['string']) && !empty($response['string'])) {
				echo $response['string'];
				return;
			}
			if(in_array($gamePlatformId, [
				PGSOFT_SEAMLESS_API, T1_PGSOFT_SEAMLESS_API,
				PGSOFT2_SEAMLESS_API, T1_PGSOFT2_SEAMLESS_API,
				PGSOFT3_SEAMLESS_API, T1_PGSOFT3_SEAMLESS_API,
				IDN_PGSOFT_SEAMLESS_API, T1_IDN_PGSOFT_SEAMLESS_API,
				]) ){
				if($oApi->getSystemInfo('enabled_queryBetDetailLinkHTML')){
					$view = $this->load->view('games_report_template/game_history-6353', ['data' => $response['html']]);
					return $view;
				}
			}

            $evolution_seamless_game_apis = [
                //? Main Game Apis
                EVOLUTION_SEAMLESS_GAMING_API,
                EVOLUTION_NETENT_SEAMLESS_GAMING_API,
                EVOLUTION_NLC_SEAMLESS_GAMING_API,
                EVOLUTION_REDTIGER_SEAMLESS_GAMING_API,
                EVOLUTION_BTG_SEAMLESS_GAMING_API,
                IDN_EVOLUTION_SEAMLESS_GAMING_API,
                IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API,
                IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API,
                IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API,
                IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API,
        
                //? t1 Game Apis
                T1_EVOLUTION_SEAMLESS_GAME_API,
                T1_EVOLUTION_NETENT_SEAMLESS_GAMING_API,
                T1_EVOLUTION_NLC_SEAMLESS_GAMING_API,
                T1_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API,
                T1_EVOLUTION_BTG_SEAMLESS_GAMING_API,
                T1_IDN_EVOLUTION_SEAMLESS_GAMING_API,
                T1_IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API,
                T1_IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API,
                T1_IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API,
                T1_IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API,
            ];

            if (in_array($gamePlatformId, $evolution_seamless_game_apis)) {
                $result = $oApi->fetchBetDetailLink($reference_id);
                return $this->load->view('games_report_template/fetch_bet_detail_link_with_basic_auth', ['data' => $result]);
            }

			if (!empty($response['url'])) {
				redirect($response['url'], 'refresh');
			}
			return null;
		}
		return null;
    }

    /**
	 * Get game list of platform
	 *
	 * @param string $gamePlatformId the game platform id
	 * @param string $gameTypeCode the game type
	 *
	 * @return array
	*/
    public function get_gamelist_by_platform($gamePlatformId = null){
    	$this->load->library(['game_list_lib','language_function']);
        $this->load->model('game_description_model');

        #params
        $gameTypeCode = $this->input->get('game_type');
        $filterBy = $this->input->get('search_string');
        $language = $this->input->get('language');
        $lang_key = $this->get_lang_code($language);
        $append_target_db=$this->input->get('append_target_db');
        // convert to bool
		$append_target_db=$append_target_db=='true' || $append_target_db=='1';
        $sqlInfo= null;
        $extra = array(
        	"home_link" => $this->input->get('home_link'),
			"cashier_link" => $this->input->get('cashier_link'),
        	'append_target_db'=>$append_target_db,
        );

        #check cache data by key
        $CACHE_PREFIX = 'get_gameList_by_platform_';
        $is_mobile = $this->utils->is_mobile();
		$cache_key = "{$CACHE_PREFIX}{$gamePlatformId}_{$gameTypeCode}_{$filterBy}_{$language}{$is_mobile}";
		if($append_target_db){
			$cache_key.='_'.$this->utils->getActiveTargetDB();
		}
        $game_list_from_cache = $this->utils->getJsonFromCache($cache_key);
        if(!empty($game_list_from_cache)){
        	$this->utils->debug_log('get_gamelist_by_platform from cache', true);
        	return $this->returnJsonResult($game_list_from_cache);
        }

        $filterByDevice = true;
        $gameTagCode = null;
        $gameCode = null;
        $showInSiteOnly = true;
        #game list data
    	$game_list =  $this->game_description_model->queryByCode($gamePlatformId, $gameTypeCode, $gameCode, $sqlInfo, $showInSiteOnly, $gameTagCode, $filterByDevice);
        $main_platform_id = $this->game_list_lib->filter_sub_game_api($gamePlatformId);
        $without_demo_platforms = (array) $this->utils->getConfig('without_demo_platforms');

        #reprocess game list data
        if(!empty($game_list)){
        	foreach ($game_list as $key => $game) {
        		if($game['game_unique_code'] == "unknown"){
    				unset($game_list[$key]);
    				continue;
    			}
    			$extra['game_type'] = $game['game_type_unique_code'];
    			$game['game_platform_id'] = $main_platform_id;
				$game['game_launch_code'] = $game['game_code'] = $game['game_unique_code'];
				if(isset($game["game_launch_code_other_settings"])){
					$game_launch_code_other_settings = json_decode($game["game_launch_code_other_settings"],true);
					if(isset($game_launch_code_other_settings['game_launch_code'])){
						$game['game_launch_code'] = $game_launch_code_other_settings['game_launch_code'];
					}
                }
    			$game_name = isset($game['game_name_detail'][$lang_key]) ? $game['game_name_detail'][$lang_key] : $game['game_name_detail']['en'];
    			$game_image_path_details = $this->CI->game_list_lib->processGameImagePath($game);
    			$data_game['game_name'] = $game_name;
    			$data_game['game_type'] = $game['game_type_unique_code'];
    			$data_game['game_web_url'] = $this->generate_lobby_game_url($gamePlatformId, $game['game_launch_code'], $language, 'real', $extra);
    			$data_game['game_trial_url'] = $this->generate_lobby_game_url($gamePlatformId, $game['game_launch_code'], $language, 'trial', $extra);
    			$data_game['game_image_url'] = isset($game_image_path_details[$lang_key]) ? $game_image_path_details[$lang_key] : $game_image_path_details['en'];
    			$data_game['display_trial'] = in_array($gamePlatformId, $without_demo_platforms) ? 'hide' : 'show';
    			$game_list[$key] = $data_game;
				unset($data_game);
        	}
        }

        #filter array by value
        if(!empty($filterBy)){
        	$game_list = array_filter($game_list, function ($var) use ($filterBy) {
			    return stripos($var['game_name'],$filterBy) !== false;
			});
        }

        #reset values of array after filter
        $game_list = array_values($game_list);

        #save data on cache if not empty
        if(!empty($game_list)){
        	$this->utils->saveJsonToCache($cache_key, $game_list, 300);
        }

        return $this->returnJsonResult($game_list);
    }

    protected function generate_lobby_game_url($gamePlatformId, $launchCode, $language, $mode, $extra){
		/*
        	sample url
        	player_center/launch_game_by_lobby/2004/AcesandEights100Hand/en-us/real/_null/_null/_null/newtab/?home_link=http%3A%2F%2Fplayer.og.local
        */
        $gameType = "_null";
        if(isset($extra['game_type'])){
        	$gameType = $extra['game_type'];
        	unset($extra['game_type']);
        }
        if(empty($extra)){
            $extra=[];
        }
        if($this->utils->isEnabledMDB() && $extra['append_target_db']){
            $extra[Multiple_db::__OG_TARGET_DB]=$this->utils->getActiveTargetDB();
        }
        $extra_params = "?". http_build_query($extra);
		return '/player_center/launch_game_by_lobby/'.$gamePlatformId.'/'.$launchCode.'/'. $language.'/'.$mode.'/_null/'.$gameType.'/_null/newtab/'.$extra_params;
	}

	protected function get_lang_code($lang = "en-us",$useInt = false){
		$this->CI->load->library(['language_function']);
		switch (strtolower($lang)) {
			case 'zh-cn':
				return ($useInt) ? language_function::INT_LANG_CHINESE : language_function::ISO2_LANG[language_function::INT_LANG_CHINESE];
				break;
			case 'id-id':
				return ($useInt) ? language_function::INT_LANG_INDONESIAN : language_function::ISO2_LANG[language_function::INT_LANG_INDONESIAN];
				break;
			case 'vi-vn':
				return ($useInt) ? language_function::INT_LANG_VIETNAMESE : language_function::ISO2_LANG[language_function::INT_LANG_VIETNAMESE];
				break;
			case 'ko-kr':
				return ($useInt) ? language_function::INT_LANG_KOREAN : language_function::ISO2_LANG[language_function::INT_LANG_KOREAN];
				break;
			case 'th-th':
				return ($useInt) ? language_function::INT_LANG_THAI : language_function::ISO2_LANG[language_function::INT_LANG_THAI];
				break;
			default:
				return ($useInt) ? language_function::INT_LANG_ENGLISH : language_function::ISO2_LANG[language_function::INT_LANG_ENGLISH];
				break;
		}
	}

	public function getLeagueIdAndName($game_platform_id = SBOBET_API){
		$post = file_get_contents('php://input');
		$params = json_decode($post,true);
		#params
		$leagueNameKeyWord = isset($params['leagueNameKeyWord']) ?  $params['leagueNameKeyWord'] : null;
		$fromDate = isset($params['fromDate']) ?  $params['fromDate'] : null;
		$endDate = isset($params['endDate']) ?  $params['endDate'] : null;
		$sportType = isset($params['sportType']) ?  $params['sportType'] : null;


		$success = true;
		$result = array();
		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $success = false;
        }
        if($success) {
        	$api  = $this->utils->loadExternalSystemLibObject($game_platform_id);
        	$response = $api->getLeagueIdAndName($leagueNameKeyWord, $fromDate, $endDate, $sportType);
        	if($response['success']){
        		if(isset($response['result']['leagues'])){
        			$result = $response['result']['leagues'];
        		}
        	}
        }
    	$data = array('success' => $success,'result' => $result, 'total' => count($result));
    	return $this->returnJsonResult($data);
    }

    public function getLeagueBetSetting($game_platform_id = SBOBET_API){
    	$post = file_get_contents('php://input');
		$params = json_decode($post,true);
		#params
		$leagueId = isset($params['leagueId']) ?  $params['leagueId'] : 14493;
		$isLive = isset($params['isLive']) ?  $params['isLive'] : false;

		$success = true;
		$result = array();
		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $success = false;
        }
        if($success) {
        	$api  = $this->utils->loadExternalSystemLibObject($game_platform_id);
        	$response = $api->getLeagueBetSetting($leagueId, $isLive);
        	if($response['success']){
        		if(isset($response['result']['leagueBetBetSettings'])){
        			$result = $response['result']['leagueBetBetSettings'];
        		}
        	}
        }
    	$data = array('success' => $success,'result' => $result);
    	return $this->returnJsonResult($data);
    }

    public function setLeagueBetSetting($game_platform_id = SBOBET_API){
    	$post = file_get_contents('php://input');
		$params = json_decode($post,true);
		#params
		$leagueId = isset($params['leagueId']) ?  $params['leagueId'] : null;
		$isLive = isset($params['isLive']) ?  $params['isLive'] : false;
		$minBet = isset($params['minBet']) ?  $params['minBet'] : null;
		$maxBet = isset($params['maxBet']) ?  $params['maxBet'] : null;
		$maxBetRatio = isset($params['maxBetRatio']) ?  $params['maxBetRatio'] : null;
		$groupType = isset($params['groupType']) ?  $params['groupType'] : null;

		$success = true;
		$result = array();
		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $success = false;
        }
        if($success) {
        	$api  = $this->utils->loadExternalSystemLibObject($game_platform_id);
        	$response = $api->setLeagueBetSetting($leagueId, $isLive, $minBet, $maxBet, $maxBetRatio, $groupType);
        	$success = $response['success'];
        }
    	$data = array('success' => $success);
    	return $this->returnJsonResult($data);
    }

    public function get_pinnacle_hot_events($sports = "soccer", $locale = null, $oddsFormat = null, $game_platform_id=null){
		$success = true;
		$result = array();
		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
		$game_platform_id = is_null($game_platform_id) ? PINNACLE_API : $game_platform_id;
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $success = false;
        }
        if($success) {
        	$api  = $this->utils->loadExternalSystemLibObject($game_platform_id);
        	$response = $api->getHotEvents($sports, $locale, $oddsFormat);
        	if(isset($response['success']) && isset($response['response_result_id'])){
        		unset($response['success']);
        		unset($response['response_result_id']);
        	}
        	$result = $response;
        }

    	$data = array('success' => $success,'result' => $result);
    	return $this->returnJsonResult($data);
    }

    public function addLuckyWheelPromoFunds($token = '') {
        $this->load->model(['fast_track_bonus_crediting', 'common_token']);
        $player_id = $this->authentication->getPlayerId();
        if($player_id === false) {
            $player_id = $this->common_token->getPlayerIdByToken($token);
            if(!$player_id) {
                $response = [
                    'success' => false,
                    'message' => lang('lucky_wheel.not_logged_in')
                ];
                return $this->returnJsonResult($response);
            }
        }
        $bonus = null;
        $transaction = null;
        $transaction_result = $this->lockAndTransForPlayerBalance("{$player_id}-lucky_wheel", function() use($player_id, &$bonus, &$response, &$transaction)  {
            $this->load->library(['player_cashback_library']);
            $user_id = 0;
            $reason = 'Bonus Credit Funds Auto Add Cashback';
            $transaction = null;

            $bonus = $this->fast_track_bonus_crediting->getFirstAvailableBonusFunds($player_id, 'wheel');

            if(empty($bonus)) {
                return false;
            }

            $params = json_decode($bonus['request_params'], true);
            $result = $this->player_cashback_library->autoAddCashbackToBalance($params['user_id'], 'Main Wallet', Transactions::AUTO_ADD_CASHBACK_TO_BALANCE, $params['amount'], $user_id, $reason, $transaction);

            $result['success'] = true;
            if(!$result['success']){
                if (isset($rlt['message']) && !empty($rlt['message'])) {
                    return false;
                }
            }else{
                $response = [
                    'success' => true,
                    'message' => $params['amount'],
                ];
                $this->fast_track_bonus_crediting->updateBonusFundsById($bonus['id'], $transaction['id']);
                return true;
            }

        });

        if(!$transaction_result) {
            $response = [
                'success' => false,
                'message' => lang('lucky_wheel.spin_expired')
            ];
        }

        return $this->returnJsonResult($response);
    }

	public function initPlayerTrackingInfo() {
		$cpaInfo = '';
		if($this->authentication->isLoggedIn()) {
			$playerId = $this->authentication->getPlayerId();
			$postData = $this->input->post();
			if(!empty($postData)) {
				// parse_str($_SERVER['QUERY_STRING'], $query_params);
				// set_cookie('reg_track', json_encode($query_params), 3600 * 24);
				$this->utils->debug_log("============initPlayerTrackingInfo============ playerId : [$playerId]", $postData);
				if($this->utils->safeGetArray($postData, 'rec') && $this->utils->safeGetArray($postData, 'clickid')){
					$cpaInfo = $postData;
					$cpaInfo = json_encode($postData);
					$this->player_model->updateCPAId($playerId, $cpaInfo);
				}
			}
		}
        return $this->returnJsonpResult([
			'success' => true,
			'message' => 'initPlayerTrackingInfo success',
			'data'	  => $cpaInfo,
		]);
	}

    public function player_query_total_turnover() {
		if ($this->authentication->isLoggedIn() && $this->utils->getConfig('display_player_turnover')) {
			$playerId = $this->authentication->getPlayerId();
			$this->load->model(array('total_player_game_month'));
			$month = idate("m");
            $year = idate("Y");

            $key = "player_query_total_turnover-{$year}-{$month}-{$playerId}";
    		$result = $this->utils->getJsonFromCache($key);
    		// $this->utils->deleteCache($key);

    		if(empty($result)){
    			$totalTurnover = $this->total_player_game_month->sumGameLogsByPlayer($playerId, $year, $month);
    			$result = array(
					'total_turnover'	=> $totalTurnover
				);
				$ttl = 300;
	    		$this->utils->saveJsonToCache($key, $result, $ttl);
	    		return $this->returnJsonpResult($result);
	    	}

	    	return $this->returnJsonpResult($result);

			// $totalTurnover = $this->total_player_game_month->sumGameLogsByPlayer($playerId, $year, $month);
			// return $this->returnJsonpResult(array(
			// 	'total_turnover'	=> $totalTurnover,
			// 	// 'total_turnover_month' => date('F')
			// ));
		}
		return $this->returnJsonpResult(array(
			'total_turnover'	=> 0,
			// 'total_turnover_month' => date('F')
		));
	}

	public function player_resetpassword() {
		$data = array('status' => 'error', 'variables' => []);
		if ($this->authentication->isLoggedIn() && $this->utils->getConfig('force_reset_password_after_operator_reset_password_in_sbe')) {
			$this->load->model(['player_model']);
			$this->load->library(['player_main_js_library', 'session']);
			$player_id = $this->authentication->getPlayerId();

			if(!empty($player_id)){
				$force_reset_password = $this->player_model->isResetPasswordByAdmin($player_id);
				$variables = $this->player_main_js_library->getVariables();
				$variables['player_resetpassword']['force_reset_password'] = $force_reset_password;

				$data = array(
					'status' => 'success',
					'variables' => $variables,
				);

				$this->utils->debug_log(__METHOD__, 'data',$data);
				return $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, NULL, $data);
			}
		}
		return $this->returnCommon(self::MESSAGE_TYPE_ERROR, $data);
	}

	public function get_bet_detail_link($gamePlatformId, $external_uniqueid)
	{
		$libraries = ['authentication', 'user_agent'];

		$isAdminAccount = $this->utils->isAdminSubProject();
		$isPlayerAccount = $this->utils->isPlayerSubProject();

		if($isAdminAccount){
			$libraries[] = 'permissions';
		}	
		$this->load->library($libraries);

		if (!$this->authentication->isLoggedIn()) {
			redirect('/');
		}
		
		if ($isAdminAccount && !$this->permissions->checkPermissions(['gamelogs', 'report_gamelogs'])) {
			redirect($this->agent->referrer());
		}

		$this->game_api = $this->utils->loadExternalSystemLibObject($gamePlatformId);

		$data['except_fields'] = $this->game_api->getSystemInfo('except_bet_detail');
		$data['add_fields'] = $this->game_api->getSystemInfo('add_bet_detail');

		$except_fields = is_array($data['except_fields']) ? $data['except_fields'] : [];
		$result = $this->getResult($gamePlatformId, $external_uniqueid, $isPlayerAccount);

		if (!$result) {
			show_404();
		}

		$this->processAdditionalFields($data['add_fields'], $result);
		$this->processExceptFields($except_fields, $result);

		$view = $this->load->view('games_report_template/bet_detail', ['data' => $result]);

		return $view;
	}

	private function getResult($gamePlatformId, $external_uniqueid, $isPlayerAccount)
	{
		return !$isPlayerAccount
			? $this->game_logs->getGamelogsBetDetailsByUniqueId($gamePlatformId, $external_uniqueid)
			: $this->game_logs->getGamelogsBetDetailsByUniqueId($gamePlatformId, $external_uniqueid, $this->authentication->getPlayerId());
	}

    public function get_bet_detail_link_with_token($token, $gamePlatformId, $externalUniqueId) {
        $this->load->model(['common_token', 'game_logs']);

        $playerInfo = $this->common_token->getPlayerInfoByToken($token);
        $onErrorRedirect = $this->input->get('on_error_redirect');
        $postMessageOnError = $this->input->get('post_message_on_error');

        if (empty($playerInfo)) {
            if ($postMessageOnError) {
                $post_message['error_message'] = lang("Invalid player token.");
                return $this->load->view('iframe/player/view_post_message_closed', $post_message);
            }

            if (!empty($onErrorRedirect)) {
                return redirect($onErrorRedirect);
            }

            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, 'invalid token');

            return redirect($this->goPlayerHome());
        }

        $gameApi = $this->utils->loadExternalSystemLibObject($gamePlatformId);

        $data['except_fields'] = $gameApi->getSystemInfo('except_bet_detail');
        $data['add_fields'] = $gameApi->getSystemInfo('add_bet_detail');

        $exceptFields = is_array($data['except_fields']) ? $data['except_fields'] : [];
        $result = $this->game_logs->getGamelogsBetDetailsByUniqueId($gamePlatformId, $externalUniqueId);

        if (!$result) {
            show_404();
        }

        $this->processAdditionalFields($data['add_fields'], $result);
        $this->processExceptFields($exceptFields, $result);

        return $this->load->view('games_report_template/bet_detail', ['data' => $result]);
    }

	private function processAdditionalFields($add_fields, &$result)
	{
		if (is_array($add_fields)) {
			foreach ($add_fields as $item) {
				if (!empty($item) && is_array($item)) {
					$this->processAddFields($item, $result);
				}
			}
		}
	}


	private function processAddFields($add_fields, &$result)
	{
		$resultArray = json_decode($result, true);

		foreach ($add_fields as $key => $value) {
			if (is_array($resultArray) && array_key_exists($key, $resultArray)) {
				$resultArray[$key] = is_array($resultArray[$key]) ?
					array_merge($resultArray[$key], (array)$value) : $value;
			} else {
				$resultArray[$key] = $value;
			}
		}

		$result = json_encode($resultArray);
	}

	private function processExceptFields($except_fields, &$result)
	{
		if ($result !== null) {
			// Check if $result is a multi-result array (array of arrays/objects)
			if (is_array($result) && isset($result[0]) && (is_array($result[0]) || is_object($result[0]))) {
				foreach ($result as &$item) {
					$this->processExceptFields($except_fields, $item);
				}
			} elseif (is_array($result)) {
				// Single-dimensional or nested array
				$result = array_diff_key($result, array_flip($except_fields));
	
				// Recursively apply to nested arrays or objects within the array
				foreach ($result as &$value) {
					if (is_array($value) || is_object($value)) {
						$this->processExceptFields($except_fields, $value);
					}
				}
			} elseif (is_object($result)) {
				foreach ($result as $key => $value) {
					if (in_array($key, $except_fields)) {
						unset($result->$key);
					} elseif (is_array($value) || is_object($value)) {
						// Recursively apply to nested structures
						$this->processExceptFields($except_fields, $result->$key);
					}
				}
			} elseif (is_string($result) && !empty($result)) {
				$result = json_decode($result);
				if (is_object($result) || is_array($result)) {
					$this->processExceptFields($except_fields, $result);
					$result = json_encode($result);
				}
			}
		}
	}

}

///END OF FILE/////////
