<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/../QuickfireSoapClient.php';

class Game_api_mg_quickfire extends Abstract_game_api {

	private $currency;
	private $language;
	private $APIusername;
	private $APIpassword;
	private $client_id_for_live_dealer;

	const MAIN_WALLET_ID = 0;

	public $iframe_url;
	public $applicationid;
	public $productid;
	public $languagecode;
	public $brand;
	public $returnurl;
	public $logintype;

	//Demo
	public $demo_productid;
	public $demo_logintype;

	const DESKTOP_DEVICE_TYPE ='desktop';
	const MOBILE_DEVICE_TYPE ='mobile';

	public function __construct() {

		parent::__construct();

		$this->iframe_url = $this->getSystemInfo('iframe_url', 'https://redirect.qfdelivery.com/platform/Default.aspx');
		// $this->mobile_iframe_url = $this->getSystemInfo('mobile_iframe_url', 'https://mobile32.gameassists.co.uk/MobileWebServices_40/casino/game/launch/');

		#Slot Games Application ID change from 4023 to 163
		$this->applicationid = $this->getSystemInfo('applicationid', '163');
		$this->serverid = $this->getSystemInfo('serverid', '22617');
		$this->productid = $this->getSystemInfo('productid');
		$this->brand = $this->getSystemInfo('brand', 'bespoke');
		$this->variant = $this->getSystemInfo('variant', 'tng-demo');
		$this->languagecode = $this->getSystemInfo('languagecode', 'en');
		$this->variant = $this->getSystemInfo('variant', 'UAT');
		$this->lobbyName = $this->getSystemInfo('lobbyName', 'kinggamingUATcom');
		$this->returnurl = $this->getSystemInfo('returnurl', "http://player.staging.kinggaming.t1t.in/");
		$this->logintype = $this->getSystemInfo('logintype', 'VanguardSessionToken');

		#Demo Play Product ID (serverid/csid/casinoid) change from the static 2712 to bespoke %productId% value and updated/new demo parameters
		$this->demo_productid = $this->getSystemInfo('demo_productid', 'bespoke');
		$this->demo_logintype = $this->getSystemInfo('demo_logintype', 'fullupe');

		$this->Orion_API_username = $this->getSystemInfo('Orion_API_username', 'KingGamingUAT.com');
		$this->Orion_API_password = $this->getSystemInfo('Orion_API_password', 'testing34567');

		$this->currency = $this->getSystemInfo('currency');
		$this->APIusername = $this->getSystemInfo('APIusername');
		$this->APIpassword = $this->getSystemInfo('APIpassword');
		$this->whitelisted = $this->getSystemInfo('whitelisted');
		$this->country = $this->getSystemInfo('country');
		$this->token_timeout = $this->getSystemInfo('token_timeout');
		$this->qf_live_dealer = $this->getSystemInfo('qf_live_dealer','https://livegames.gameassists.co.uk/ETILandingPage/?');
		$this->exportPlayerDataUrl = $this->getSystemInfo('exportPlayerDataUrl','https://playcheck22.funlauncher.net/Playcheck/OperatorExport.csv');
		$this->authenticatePlayerUrl = $this->getSystemInfo('authenticatePlayerUrl','https://playcheck22.funlauncher.net/playcheck/default.aspx');
		$this->remove_http_option = false;
		$this->use_original_on_query_game_username = $this->getSystemInfo('use_original_on_query_game_username',true);
		$this->game_livedealer_url = $this->getSystemInfo('game_livedealer_url', 'https://webservice.basestatic.net/ETILandingPage/?');

		#live dealer part
		$this->operator_token_url = $this->getSystemInfo('operator_token_url','https://api.bazred.net/System/OperatorSecurity/V1/operatortokens');
		$this->APIKey = $this->getSystemInfo('APIKey','d42a062e-2264-4893-aa41-5cb1aa4d0f32');#live api key for live dealer
		$this->live_dealer_api_url = $this->getSystemInfo('live_dealer_api_url','https://api.bazred.net/LiveDealer');
		$this->live_dealer_api_version = $this->getSystemInfo('live_dealer_api_version','v1');
		$this->live_dealer_product_id = $this->getSystemInfo('live_dealer_product_id',22925);
		$this->client_id_for_live_dealer = $this->getSystemInfo('client_id_for_live_dealer', 7);
	}

	public function getAPIauth(){
		$data = array(
			"APIusername" => $this->APIusername,
			"APIpassword" => $this->APIpassword,
			"whitelisted" => $this->whitelisted
		);
		return $data;
	}

	public function getPlatformCode() {
		return MG_QUICKFIRE_API;
	}

	public function serviceApi($method, $result = null) {

		$this->CI->utils->debug_log('Game_api_mg_quickfire service API: ', $result);

	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = !empty($resultArr) && $resultArr['status_code'] == self::STATUS_SUCCESS;
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('MG_QUICKFIRE_API got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

		// create player on game provider auth
		$return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$success = false;
		$message = "Unable to create Account for MG quickfire";
		if($return){
			$success = true;
			$message = "Successfull create account for MG quickfire";
		}

		return array("success" => $success, "message" => $message);

	}

	public function loginByToken($token=null){
		$this->CI->load->model(array('player_model'));

		$playerId = $this->getPlayerIdByToken($token);

		if ($playerId) {

			$this->CI->common_token->disableToken($token);

			$token = $this->getPlayerToken($playerId);

			$gameUsername = $this->getGameUsernameByPlayerId($playerId);
			$username = $this->CI->player_model->getUsernameById($playerId);
			$balance = $this->queryPlayerBalance($username);
			$result = array(
				'success' => true,
				'token_attr' => $token,
				'loginname_attr' => $gameUsername,
				'currency_attr' => $this->currency,
				'country_attr' => $this->country,
				'city_attr' => '',
				'balance_attr' => $balance['balance'] * 100,
				'bonusbalance_attr' => 0,
				'wallet_attr' => 'vanguard', #always vanguard
				'extinfo' => ''
			);

		}else{

			$result = array(
				'success' => false,
				'error_code' => '6001'
			);

		}

		return $result;
	}

	public function login($gameUsername, $password = null) {

		if ($password == $this->getPasswordByGameUsername($gameUsername)) {

			$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			$username = $this->CI->player_model->getUsernameById($playerId);
			$balance = $this->queryPlayerBalance($username);
			$token = $this->getPlayerToken($playerId);
			$result = array(
				'success' => true,
				'token_attr' => $token,
				'loginname_attr' => $gameUsername,
				'currency_attr' => $this->currency,
				'country_attr' => $this->country,
				'city_attr' => '',
				'balance_attr' => $balance['balance'] * 100,
				'bonusbalance_attr' => 0,
				'wallet_attr' => 'vanguard', #always vanguard
				'extinfo' => ''
			);

		} else {
			$result = array(
				'success' => false,
				'error_code' => '6101'
			);
		}

		return $result;

	}


	public function queryPlayerBalance($playerName) {
		$this->CI->load->model(array('player_model'));

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

		$result = array(
			'success' => true,
			'balance' => $balance
		);

		return $result;
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id = null) {

		// $playerId = $this->getPlayerIdFromUsername($playerName);
		// $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		// $afterBalance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());
		// $responseResultId = NULL;

		// $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());

		// return array('success' => true);

		$external_transaction_id = $transfer_secure_id;
	    return array(
	        'success' => true,
	        'external_transaction_id' => $external_transaction_id,
	        'response_result_id ' => NULL,
	        'didnot_insert_game_logs'=>true,
	    );

	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {

		// $playerId = $this->getPlayerIdFromUsername($playerName);
		// $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		// $afterBalance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());
		// $responseResultId = NULL;

		// $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());

		// return array('success' => true);

		$external_transaction_id = $transfer_secure_id;
	    return array(
	        'success' => true,
	        'external_transaction_id' => $external_transaction_id,
	        'response_result_id ' => NULL,
	        'didnot_insert_game_logs'=>true,
	    );

	}

	public function queryPlayerInfo($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	public function changePassword($playerName, $oldPassword, $newPassword) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}
	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}
	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return $this->returnUnimplemented();
	}

	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	public function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	public function totalBettingAmount($playerName, $dateFrom, $dateTo) {
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}
	public function processResultForQueryTransaction($apiName, $params, $responseResultId, $resultXml) {
		return $this->returnUnimplemented();
	}


	/*

	    "pragmatic": {
	        "real": {
	            "serverid": "",
	            "moduleid": "",
	            "clientid": "",
	            "productid": "",
	            "applicationid": ""
	        },
	        "demo": {
	            "serverid": "",
	            "ModuleID": "",
	            "ClientID": "",
	            "ProductID": "",
	            "siteID": "",
	            "applicationID": ""
	        }
	    },
	    "ainsworth": {
	        "real": {
	            "serverid": "",
		        "applicationid": "",
		        "clienttype": "",
		        "moduleid": "",
		        "clientid": "",
		        "productid": "",
		        "siteid": ""
	        },
	        "demo": {
	            "serverid": "",
		        "applicationid": "",
		        "clienttype": "",
		        "moduleid": "",
		        "clientid": "",
		        "productid": "",
		        "siteid": ""
	        }
	    }
	*/
	public function queryForwardGame($playerName, $extra) {
		$redirect = false;
		if ($playerName) {

			$authtoken = $this->getPlayerTokenByUsername($playerName);
			$language = isset($extra['language']) ? $extra['language'] : $this->languagecode;


			if (substr( $extra['game_code'], 4, 10 ) == 'pragmatic_') {

				$pragmatic = $this->getSystemInfo('pragmatic');

				$game_code = substr( $extra['game_code'], 10 );

				$params['gameid'] = $game_code;
				$params['languagecode'] = $language;

				if ($extra['game_mode'] == 'real') {
					$params = $pragmatic['real'];
					$params['externalToken'] = $authtoken;
				} else {
					$params = $pragmatic['demo'];
					$params['externalToken'] = $authtoken;

				}

				$url = $this->iframe_url . '?' . http_build_query($params);

				$this->CI->utils->debug_log('talmg_quickfire: ', 'pragmatic', $url);

			} else if (substr( $extra['game_code'], 4, 10 ) == 'Ainsworth_') {

				$ainsworth = $this->getSystemInfo('ainsworth');

				if ($extra['game_mode'] == 'real') {
					$params = $ainsworth['real'];
				} else {
					$params = $ainsworth['demo'];
				}

				$params['moduleid'] =  $extra['module_id'];
				$params['clientid'] =  $extra['client_id'];
				$params['gameid'] = $extra['game_launch_code'];
				$params['externalToken'] = $authtoken;
				$params['languagecode'] = $language;

				$url = $this->iframe_url . '?' . http_build_query($params);

				$this->CI->utils->debug_log('talmg_quickfire: ', 'ainsworth', $url);

			} else if (substr( $extra['game_code'], 4, 4 ) == 'Leap') {

				$leap = $this->getSystemInfo('leap');

				$game_code = $extra['game_code'];

				if ($extra['game_mode'] == 'real') {
					$params = $leap['real'];
				} else {
					$params = $leap['demo'];
					$params['serverid'] = '22925';
				}

				$params['moduleid'] =  $extra['module_id'];
				$params['clientid'] =  $extra['client_id'];
				$params['gameid'] = $extra['game_launch_code'];
				$params['externalToken'] = $authtoken;
				$params['languagecode'] = $language;

				$url = $this->iframe_url . '?' . http_build_query($params);

				$this->CI->utils->debug_log('talmg_quickfire: ', 'leap', $url);
			}  else if (substr( $extra['game_code'], 4, 6 ) == 'Wazdan') {

				$leap = $this->getSystemInfo('wazdan');

				$game_code = $extra['game_code'];

				if ($extra['game_mode'] == 'real') {
					$params = $leap['real'];
				} else {
					$params = $leap['demo'];
					$params['serverid'] = '22925';
				}

				$params['moduleid'] =  $extra['module_id'];
				$params['clientid'] =  $extra['client_id'];
				$params['gameid'] = $extra['game_launch_code'];
				$params['externalToken'] = $authtoken;
				$params['languagecode'] = $language;

				$url = $this->iframe_url . '?' . http_build_query($params);

				$this->CI->utils->debug_log('talmg_quickfire: ', 'wazdan', $url);
			} else if (substr( $extra['game_code'], 4, 12 ) == 'BoomingGames') {

				$leap = $this->getSystemInfo('boominggames');

				$game_code = $extra['game_code'];

				if ($extra['game_mode'] == 'real') {
					$params = $leap['real'];
				} else {
					$params = $leap['demo'];
					$params['serverid'] = '22925';
				}

				$params['moduleid'] =  $extra['module_id'];
				$params['clientid'] =  $extra['client_id'];
				$params['gameid'] = $extra['game_launch_code'];
				$params['externalToken'] = $authtoken;
				$params['languagecode'] = $language;

				$url = $this->iframe_url . '?' . http_build_query($params);

				$this->CI->utils->debug_log('talmg_quickfire: ', 'boominggames', $url);
			} else if (substr( $extra['game_code'], 4, 19 ) == 'GamePlayInteractive') {

				$leap = $this->getSystemInfo('gameplay');

				$game_code = $extra['game_code'];

				if ($extra['game_mode'] == 'real') {
					$params = $leap['real'];
				} else {
					$params = $leap['demo'];
					$params['serverid'] = '22925';
				}

				$params['moduleid'] =  $extra['module_id'];
				$params['clientid'] =  $extra['client_id'];
				$params['gameid'] = $extra['game_launch_code'];
				$params['externalToken'] = $authtoken;
				$params['languagecode'] = $language;

				$url = $this->iframe_url . '?' . http_build_query($params);

				$this->CI->utils->debug_log('talmg_quickfire: ', 'gameplay', $url);
			} else if (substr( $extra['game_code'], 4, 6 ) == 'Gamevy') {

				$leap = $this->getSystemInfo('gamevy');

				$game_code = $extra['game_code'];

				if ($extra['game_mode'] == 'real') {
					$params = $leap['real'];
				} else {
					$params = $leap['demo'];
					$params['serverid'] = '22925';
				}

				$params['moduleid'] =  $extra['module_id'];
				$params['clientid'] =  $extra['client_id'];
				$params['gameid'] = $extra['game_launch_code'];
				$params['externalToken'] = $authtoken;
				$params['languagecode'] = $language;

				$url = $this->iframe_url . '?' . http_build_query($params);

				$this->CI->utils->debug_log('talmg_quickfire: ', 'gamevy', $url);
			} else if (substr( $extra['game_code'], 4, 9 ) == '1x2Gaming') {

				$leap = $this->getSystemInfo('1x2gaming');

				$game_code = $extra['game_code'];

				if ($extra['game_mode'] == 'real') {
					$params = $leap['real'];
				} else {
					$params = $leap['demo'];
					$params['serverid'] = '22925';
				}

				$params['moduleid'] =  $extra['module_id'];
				$params['clientid'] =  $extra['client_id'];
				$params['gameid'] = $extra['game_launch_code'];
				$params['externalToken'] = $authtoken;
				$params['languagecode'] = $language;

				$url = $this->iframe_url . '?' . http_build_query($params);

				$this->CI->utils->debug_log('talmg_quickfire: ', '1x2gaming', $url);
			} else if (substr( $extra['game_code'], 4, 8 ) == 'Pariplay') {

				$leap = $this->getSystemInfo('pariplay');

				$game_code = $extra['game_code'];

				if ($extra['game_mode'] == 'real') {
					$params = $leap['real'];
				} else {
					$params = $leap['demo'];
					$params['serverid'] = '22925';
				}

				$params['moduleid'] =  $extra['module_id'];
				$params['clientid'] =  $extra['client_id'];
				$params['gameid'] = $extra['game_launch_code'];
				$params['externalToken'] = $authtoken;
				$params['languagecode'] = $language;

				$url = $this->iframe_url . '?' . http_build_query($params);

				$this->CI->utils->debug_log('talmg_quickfire: ', 'pariplay', $url);
			} else if (substr( $extra['game_code'], 4, 7 ) == 'Playson') {

				$leap = $this->getSystemInfo('playson');

				$game_code = $extra['game_code'];

				if ($extra['game_mode'] == 'real') {
					$params = $leap['real'];
				} else {
					$params = $leap['demo'];
					$params['serverid'] = '22925';
				}

				$params['moduleid'] =  $extra['module_id'];
				$params['clientid'] =  $extra['client_id'];
				$params['gameid'] = $extra['game_launch_code'];
				$params['externalToken'] = $authtoken;
				$params['languagecode'] = $language;

				$url = $this->iframe_url . '?' . http_build_query($params);

				$this->CI->utils->debug_log('talmg_quickfire: ', 'playson', $url);
			} else if ($extra['game_code'] == 'live') {

				$url = $this->game_livedealer_url . http_build_query(array(
					'QFToken' => $authtoken,
					'languagecode' => $language,
					// 'CasinoID' => $this->serverid,
					'productId' => $this->productid,
					'ClientID' => 7,
					'slot' => 1,
					// 'VideoQuality' => 'HD',
					// 'ModuleID' => 70009,
					// 'ClientType' => 1,
					// 'UserType' => 0,
					// 'ProductID' => 2,
					// 'BetProfileID' => 0,
					// 'ActiveCurrency' => 'Credits',
					// 'LoginName' => '',
					// 'Password' => '',
					// 'StartingTab' => 'NULL',
					// 'CustomLDParam' => 'NULL',
				));

				if ($extra['device_type'] != "desktop") {
					$redirect = TRUE;
				}

				$this->CI->utils->debug_log('talmg_quickfire: ', 'live', $url);
			} else if ($extra['game_code'] == 'titanium') {
				//sample url
				// https://livegames.gameassists.co.uk/ETILandingPage/?QFToken=91746520-1d49-11e4-a88b-ba047c232e99&UL=en&CasinoID=1234&ClientID=7
				$url = $this->qf_live_dealer . http_build_query(array(
					'QFToken' => $authtoken,
					'languagecode' => $language,
					// 'CasinoID' => $this->serverid,
					'productId' => $this->productid,
					'ClientID' => 7,
					'FailedRedirect' => $extra['device_type'] == "desktop" ? $this->CI->utils->getSystemUrl('www') : $this->CI->utils->getSystemUrl('m'),
					'LogoutRedirect' => $extra['device_type'] == "desktop" ? $this->CI->utils->getSystemUrl('www') : $this->CI->utils->getSystemUrl('m')
				));
				$redirect = true;

				$this->CI->utils->debug_log('talmg_quickfire: ', 'titanium', $url);
			} else if (substr( $extra['game_code'], 4, 3 ) == 'LG_') {
				// For LG games client id is 7. Sample URL below:
				// https://webservice.basestatic.net/ETILandingPage?QFToken=c733178e315816b2eabfde2249b8a251&languagecode=en&ProductID=22925&ClientID=7 
				$url = $this->qf_live_dealer . http_build_query(array(
					'QFToken' => $authtoken,
					'languagecode' => $language,
					'ProductId' => $this->productid,
					'ClientID' => $this->client_id_for_live_dealer
				));
				$redirect = true;

				$this->CI->utils->debug_log('talmg_quickfire: ', 'LG live dealer', $url);
			} else if (substr( $extra['game_code'], 4, 3 ) == 'LD_') {
				// For LD games client id is 7. Sample URL below:
				// https://webservice.basestatic.net/ETILandingPage?QFToken=c733178e315816b2eabfde2249b8a251&languagecode=en&ProductID=22925&ClientID=7 
				$url = $this->qf_live_dealer . http_build_query(array(
					'QFToken' => $authtoken,
					'languagecode' => $language,
					'ProductId' => $this->productid,
					'ClientID' => $this->client_id_for_live_dealer
				));
				$redirect = true;

				$this->CI->utils->debug_log('talmg_quickfire: ', 'live dealer', $url);
			} else if (substr( $extra['game_code'], 4, 9 ) == 'LiveGames') {
				// For LiveGames games client id is 7. Sample URL below:
				// https://webservice.basestatic.net/ETILandingPage?QFToken=c733178e315816b2eabfde2249b8a251&languagecode=en&ProductID=22925&ClientID=7 
				$url = $this->qf_live_dealer . http_build_query(array(
					'QFToken' => $authtoken,
					'languagecode' => $language,
					'ProductId' => $this->productid,
					'ClientID' => $this->client_id_for_live_dealer
				));
				$redirect = true;

				$this->CI->utils->debug_log('talmg_quickfire: ', 'live dealer', $url);
			} else if (isset($extra['productid'])) {

				if ($extra['productid'] == 30) {

					/* OGP-9974
					Real Play:
					https://redirector3.valueactive.eu/Casino/Default.aspx?authToken=XXXX&serverid=22925&applicationID=7217&ModuleID=19663&ClientID=50300&ProductID=30&ul=en&gameID=5a032814ed1496000800000f&siteID=TNG&playmode=real

					Demo Play:
					https://redirector3.valueactive.eu/Casino/Default.aspx?serverid=2712&applicationID=7217&ModuleID=19663&ClientID=50300&ProductID=30&ul=en&gameID=5a032814ed1496000800000f&siteID=TNG&playmode=demo
					*/

					$params = array(
						'serverid' => '2712',
						'applicationid' => '7217',
						'moduleid' => '19663',
						'clientid' => '50300',
						'productid' => 30,
						'languagecode' => $language,
						'gameid' => $extra['game_code'],
						'playmode' => 'demo',
						'siteid' => 'TNG',
					);

					if ($extra['game_mode'] == 'real') {
						$params['externalToken'] = $authtoken;
						$params['serverid'] = '22925';
						$params['playmode'] = 'real';
						$params['logintype'] = $this->logintype;
					}else{
						$params['logintype'] = $this->demo_logintype;
					}

					$this->CI->utils->debug_log('talmg_quickfire: ', 'productid 30', $params);
				} else {

					$params = array(
						'serverid' => '1866',
						'applicationid' => '7217',
						'moduleid' => $extra['moduleid'],
						'clientid' => $extra['clientid'],
						'productid' => $extra['productid'],
						'languagecode' => $language,
						'gameid' => $extra['game_launch_code'],
						'playmode' => 'demo',
						'externalToken' => $authtoken
					);

					if ($extra['game_mode'] == 'real') {
						$params['externalToken'] = $authtoken;
						$params['serverid'] = '22925';
						$params['clienttype'] = 1;
						$params['siteid'] = 'TNG';
						$params['playmode'] = 'real';
						$params['logintype'] = $this->logintype;
					}else{
						$params['ispracticeplay'] = true;
						$params['logintype'] = $this->demo_logintype;
					}

				}

				$this->CI->utils->debug_log('talmg_quickfire: ', 'productid not 30', $params);

				$url = $this->iframe_url . '?' . http_build_query($params);

				$this->CI->utils->debug_log('talmg_quickfire: ', 'productid 30 or not', $url);

			} else {

					$params = array(
						'applicationid'	=> $this->applicationid,
						'productId'		=> $this->productid,
						'gameid'		=> $extra['game_launch_code'],
						'languagecode'	=> $language,
						'brand'			=> $this->brand,
						'loginType'		=> $this->logintype,
						'externalToken'	=> $authtoken,
						'returnUrl'		=> $this->returnurl,
						'host'			=> $extra['device_type'],
						// 'variant'		=> $this->variant,
					);

					if ($extra['game_mode'] != 'real') {
						// $params['sext1'] = 'demo';
						// $params['sext2'] = 'demo';
						$params['ispracticeplay'] = true;
						// $params['variant'] = $this->variant;
						// $params['serverid'] = $this->demo_serverid;
						$params['productId'] = $this->demo_productid;
						$params['logintype'] = $this->demo_logintype;
					}

					$url = $this->iframe_url . '?' . http_build_query($params);

					$this->CI->utils->debug_log('talmg_quickfire: ', 'MG games', $url);
			}


		}

		return array('success' => TRUE, 'url' => $url, 'is_redirect' => $redirect);

	}

	public function syncOriginalGameLogs($token) {

		$this->CI->load->model(array('wallet_model','game_logs','mg_quickfire_game_logs'));

		$getRollbackQueueDataResult 	= $this->getRollbackQueueData();
		$getCommitQueueDataResult 		= $this->getCommitQueueData();
		$getFailedEndGameQueueResult 	= $this->getFailedEndGameQueue();

		# getRollbackQueueDataResult
		if (isset($getRollbackQueueDataResult['success']) && $getRollbackQueueDataResult['success'] && ! empty($getRollbackQueueDataResult['data'])) {

			$response_result_id = $getRollbackQueueDataResult['response_result_id'];

			foreach ($getRollbackQueueDataResult['data'] as $data) {

				try {

					# DECLARE VARIABLES
					$game_username 		= $data['LoginName'];
					$external_uniqueid 	= $data['RowId'] ? : $data['RowIdLong'];
					$action_id 			= $data['MgsReferenceNumber'];

					# CHECK IF PLAYER EXIST
					$player_id = $this->getPlayerIdInGameProviderAuth($game_username);
					if (empty($player_id)) {
						throw new Exception("PLAYER NOT FOUND: " . $game_username);
					}

					# CHECK IF DATA HAS BEEN PROCESSED ALREADY THEN SEND MANUALLY VALIDATE IF PROCESSED ALREADY
					$game_record = $this->CI->mg_quickfire_game_logs->get_game_record_by_external_uniqueid($external_uniqueid);
					if ( ! empty($game_record)) {
						$this->manuallyValidateBet($external_uniqueid, 'RollbackQueue', $game_record['id']);
						throw new Exception("DATA HAS BEEN PROCESSED ALREADY 1: " . $external_uniqueid);
					}

					# CHECK IF BET RECORD EXISTS
					$bet_record = $this->CI->mg_quickfire_game_logs->get_bet_record_by_action_id($action_id);
					if (empty($bet_record)) {
						throw new Exception("BET RECORD NOT FOUND: " . $action_id);
					}

					$system 		= 'casino';
					$gamereference 	= $data['GameName'];
					$gameid 		= $data['TransactionNumber'];
					$playtype 		= 'refund';
					$actionid 		= $data['MgsReferenceNumber'];

					$game_record = $this->CI->mg_quickfire_game_logs->get_record($system, $game_username, $gamereference, $gameid, $playtype, $actionid);

					if ( ! empty($game_record)) {
						$this->manuallyValidateBet($external_uniqueid, 'CommitQueue', $game_record['id']);
						throw new Exception("DATA HAS BEEN PROCESSED ALREADY 2: " . $external_uniqueid);
					}

					$this->CI->wallet_model->lockAndTransForPlayerBalance($player_id, function () use ($player_id, $game_username, $external_uniqueid, $data, $response_result_id) {

						$success = FALSE;

						$game_record_id = $this->processQueueData('refund', $player_id, $game_username, $external_uniqueid, $data, $response_result_id);

						if ($game_record_id) {
							$manuallyValidateBetResult = $this->manuallyValidateBet($external_uniqueid, 'RollbackQueue', $game_record_id);
							$success = isset($manuallyValidateBetResult['success']) && $manuallyValidateBetResult['success'];
						}

						return $success;

					});

				} catch (Exception $e) {
					$this->CI->utils->error_log('mgquickfire error', $e);
				}

			}
		}

		# getCommitQueueDataResult
		if (isset($getCommitQueueDataResult['success']) && $getCommitQueueDataResult['success'] && ! empty($getCommitQueueDataResult['data'])) {

			$response_result_id = $getCommitQueueDataResult['response_result_id'];

			foreach ($getCommitQueueDataResult['data'] as $data) {

				try {

					# DECLARE VARIABLES
					$game_username 		= $data['LoginName'];
					$external_uniqueid 	= $data['RowId'] ? : $data['RowIdLong'];
					$action_id 			= $data['MgsReferenceNumber'];

					# CHECK IF PLAYER EXIST
					$player_id = $this->getPlayerIdInGameProviderAuth($game_username);
					if (empty($player_id)) {
						throw new Exception("PLAYER NOT FOUND: " . $game_username);
					}

					# CHECK IF DATA HAS BEEN PROCESSED ALREADY THEN SEND MANUALLY VALIDATE IF PROCESSED ALREADY
					$game_record = $this->CI->mg_quickfire_game_logs->get_game_record_by_external_uniqueid($external_uniqueid);
					if ( ! empty($game_record)) {
						$this->manuallyValidateBet($external_uniqueid, 'CommitQueue', $game_record['id']);
						throw new Exception("DATA HAS BEEN PROCESSED ALREADY 1: " . $external_uniqueid);
					}

					$system 		= 'casino';
					$gamereference 	= $data['GameName'];
					$gameid 		= $data['TransactionNumber'];
					$playtype 		= 'win';
					$actionid 		= $data['MgsReferenceNumber'];

					$game_record = $this->CI->mg_quickfire_game_logs->get_record($system, $game_username, $gamereference, $gameid, $playtype, $actionid);

					if ( ! empty($game_record)) {
						$this->manuallyValidateBet($external_uniqueid, 'CommitQueue', $game_record['id']);
						throw new Exception("DATA HAS BEEN PROCESSED ALREADY 2: " . $external_uniqueid);
					}

					$this->CI->wallet_model->lockAndTransForPlayerBalance($player_id, function () use ($player_id, $game_username, $external_uniqueid, $data, $response_result_id) {

						$success = FALSE;

						$game_record_id = $this->processQueueData('win', $player_id, $game_username, $external_uniqueid, $data, $response_result_id);

						if ($game_record_id) {
							$manuallyValidateBetResult = $this->manuallyValidateBet($external_uniqueid, 'CommitQueue', $game_record_id);
							$success = isset($manuallyValidateBetResult['success']) && $manuallyValidateBetResult['success'];
						}

						return $success;

					});

				} catch (Exception $e) {
					$this->CI->utils->error_log('mgquickfire error', $e);
				}

			}
		}

		# getFailedEndGameQueueResult
		if (isset($getFailedEndGameQueueResult['success']) && $getFailedEndGameQueueResult['success'] && ! empty($getFailedEndGameQueueResult['data'])) {
			foreach ($getFailedEndGameQueueResult['data'] as $data) {
				try {
					// $row_id = $data['RowId'] ? : $data['RowIdLong'];
					// $this->manuallyCompleteGame($row_id);
					$this->manuallyCompleteGame($data);
				} catch (Exception $e) {
					$this->CI->utils->error_log('mgquickfire error', $e);
				}
			}
		}

		return array('success' => TRUE);
	}

	public function processQueueData($play_type, $player_id, $game_username, $external_uniqueid, $data, $response_result_id) {
		$this->CI->utils->debug_log('mg_quickfire processQueueData: ' . $play_type, 'player_id', $player_id, 'game_username', $game_username, 'external_uniqueid', $external_uniqueid, 'data', $data, 'response_result_id', $response_result_id);

		$game_record_id = FALSE;

		$username = $this->CI->player_model->getUsernameById($player_id);

		//remove AM/PM
		$dateCreated = str_replace(['AM','PM'],'',$data['DateCreated']);
		$timestamp = date('Y-m-d H:i:s',strtotime($dateCreated));

		$insert_data = array(
			'system'					=> 'casino',
			'timestamp'					=> $timestamp,
			'token'						=> NULL,
			'seq'						=> NULL,
			'playtype'					=> $play_type,
			'gameid'					=> $data['TransactionNumber'],
			'gamereference'				=> $data['GameName'],
			'actionid'					=> $data['MgsReferenceNumber'],
			'actiondesc'				=> NULL,
			'amount'					=> $data['ChangeAmount'],
			'start'						=> false,
			'finish'					=> false,
			'offline'					=> false,
			'currency'					=> $data['TransactionCurrency'],
			'freegame'					=> NULL,
			'freegameofferinstanceid'	=> NULL,
			'freegamenumgamesplayed'	=> NULL,
			'freegamenumgamesremaining' => NULL,
			'clienttypeid'				=> NULL,
			'extinfo'					=> json_encode($data['ExtInfo']),
			'game_username'				=> $game_username,
			'external_uniqueid'			=> $external_uniqueid,
			'gameshortcode'				=> $data['GameName'],
			'response_result_id'		=> $response_result_id,
			'created_at'				=> date('Y-m-d H:i:s'),
		);
		$amount = $insert_data['amount'] / 100;

		$success = $this->CI->wallet_model->incSubWallet($player_id, $this->getPlatformCode(), $amount);

		$queryPlayerBalanceResult = $this->queryPlayerBalance($username);
		$insert_data['after_balance'] = $queryPlayerBalanceResult['balance'];

		if ($success) {

			$game_record_id = $this->CI->mg_quickfire_game_logs->insertGameLogs($insert_data);

			if ($game_record_id) {

				$data_for_merging = $this->CI->mg_quickfire_game_logs->getDataForMerging($insert_data['gamereference'], $insert_data['game_username'], $insert_data['gameid']);

				$this->mergeToGameLogs(
					$data_for_merging['system'],
					$data_for_merging['gamereference'],
					$data_for_merging['game_username'],
					$data_for_merging['gameid'],
					$data_for_merging['bet_amount'],
					$data_for_merging['refund_amount'],
					$data_for_merging['after_balance'],
					$data_for_merging['win_amount'],
					$data_for_merging['start_at'],
					$data_for_merging['end_at'],
					null,
					$data_for_merging['game_description_id'],
					$data_for_merging['game_type_id']
				);

			}

		}

		$this->CI->utils->debug_log('mg_quickfire: ' . $insert_data['playtype'], 'player_id', $player_id, 'amount', $amount, 'after_balance', $insert_data['after_balance'], 'success', $game_record_id);

		return $game_record_id;

	}

	public function testing_getDataForMerging(){
		$this->CI->load->model('mg_quickfire_game_logs');
		$gamereference = "MGS_LG_Baccarat_(TitaniumAndroidBrowser)";
		$game_username = "ibgtestt1dev";
		$data_for_merging = $this->CI->mg_quickfire_game_logs->getDataForMerging($gamereference, $game_username, 7);
		return $data_for_merging;
	}

	public function syncMergeToGameLogs($token) {

		$this->CI->load->model('player_model');
		$this->CI->load->model('game_logs');

        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeFrom->modify($this->getDatetimeAdjust());
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($dateTimeFrom->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));

        //observe the date format
        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate = $endDate->format('Y-m-d H:i:s');

		$this->CI->db->select('gamereference');
		$this->CI->db->select('game_username');
        $this->CI->db->select('gameid');
        $this->CI->db->select('game_type.game_type as system');
        $this->CI->db->select('game_description.id as game_description_id');
		$this->CI->db->select('game_description.game_type_id');

		$this->CI->db->select_min('IFNULL(mg_quickfire_game_logs.timestamp, mg_quickfire_game_logs.created_at)','start_at');
		$this->CI->db->select_max('IFNULL(mg_quickfire_game_logs.timestamp, mg_quickfire_game_logs.created_at)','end_at');
		$this->CI->db->select_sum("IF(playtype = 'bet', amount, 0)",'bet_amount');
		$this->CI->db->select_sum("IF(playtype = 'refund', amount, 0)",'refund_amount');
		$this->CI->db->select_max('CONCAT_WS(\'|\',mg_quickfire_game_logs.id,after_balance)','after_balance');
        $this->CI->db->select("SUM(CASE playtype WHEN 'win' THEN amount WHEN 'progressivewin' THEN amount ELSE 0 END) as win_amount", false);
        $this->CI->db->join('game_description', 'mg_quickfire_game_logs.gamereference = game_description.external_game_id AND game_description.game_platform_id ='.MG_QUICKFIRE_API, 'left');
        $this->CI->db->join('game_type', 'game_description.game_type_id = game_type.id', 'left');
		$this->CI->db->from('mg_quickfire_game_logs');

		$this->CI->db->group_by('system');
		$this->CI->db->group_by('gamereference');
		$this->CI->db->group_by('game_username');
		$this->CI->db->group_by('gameid');

		$this->CI->db->having('end_at >=',$startDate);
		$this->CI->db->having('end_at <=',$endDate);

		$query = $this->CI->db->get();
		$rows = $query->result_array();

        $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'processing', count($rows));

        $count = 0;
		foreach ($rows as $row) {

			$system 		= $row['system'];
			$gamereference 	= $row['gamereference'];
			$game_username 	= $row['game_username'];
			$gameid 		= $row['gameid'];
			$bet_amount 	= $row['bet_amount'];
			$refund_amount 	= $row['refund_amount'];
			$after_balance 	= $row['after_balance'];
			$win_amount 	= $row['win_amount'];
			$start_at 		= $row['start_at'];
			$end_at 		= $row['end_at'];
			$game_description_id 	= $row['game_description_id'];
			$game_type_id 		= $row['game_type_id'];

			$after_balance  = @explode('|', $after_balance)[1] ? : 0;

			$this->mergeToGameLogs($system, $gamereference, $game_username, $gameid, $bet_amount, $refund_amount, $after_balance, $win_amount, $start_at, $end_at,null, $game_description_id, $game_type_id);

		}

        $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'processed', $count);

		return array('success' => TRUE);

	}

	public function mergeToGameLogs($row_system, $row_gamereference, $row_game_username, $row_gameid, $row_bet_amount, $row_refund_amount, $row_after_balance, $row_win_amount, $row_start_at, $row_end_at, $status = NULL, $game_description_id = null, $game_type_id = null) {

		$success = TRUE;

		$player_id = $this->getPlayerIdByGameUsername($row_game_username);

		if ( ! empty($player_id)) {

			$player_username = $this->getUsernameByPlayerId($player_id);

			$external_uniqueid = implode('|', [$row_gamereference, $row_game_username, $row_gameid]);
			$round_id = $external_uniqueid;
			$external_uniqueid = hash('sha256', $external_uniqueid);

			$trans_amount 		 = $row_bet_amount / 100;
			$refund_amount 	     = $row_refund_amount / 100;
			$win_amount 	     = $row_win_amount / 100;

			$bet_amount 	     = $trans_amount - $refund_amount;
			$result_amount 	     = $win_amount - $bet_amount;

			$has_both_side 	     = $bet_amount >= $result_amount && $result_amount > 0 ? 1 : 0;
			$start_at 		     = $row_start_at;
			$end_at 		     = $row_end_at;


            $this->CI->load->model(array('game_description_model'));
            if(empty($game_description_id) && empty($game_type_id)){
            	list($game_description_id, $game_type_id) = $this->CI->game_description_model->checkGameDesc(
	                $this->getPlatformCode(), $row_gamereference, $row_gamereference, $row_system
	            );

	            if(empty($game_description_id)) {
	                list($game_description_id, $game_type_id) = $this->processUnknownGame(NULL, NULL, $row_gamereference, $row_system, $row_gamereference, array('game_code' => $row_gamereference));
	            }
            }

			$extra = [];
			$extra['trans_amount'] = $trans_amount;
			$extra['bet_details']  = ['game_id' => $row_gameid];
			$extra['table'] = $round_id;

			if ($status) {
				$extra['status'] = $status;
			}

			if ($refund_amount > 0 && $refund_amount >= $trans_amount) {
				$extra['status'] = Game_logs::STATUS_REFUND;
			}

			$success = $this->syncGameLogs(
				$game_type_id,  		# game_type_id
				$game_description_id,	# game_description_id
				$row_gamereference, 	# game_code
				$game_type_id, 			# game_type
				$row_gamereference, 	# game
				$player_id, 			# player_id
				$player_username, 		# player_username
				$bet_amount, 			# bet_amount
				$result_amount, 		# result_amount
				null,					# win_amount
				null,					# loss_amount
				$row_after_balance,		# after_balance
				$has_both_side, 		# has_both_side
				$external_uniqueid, 	# external_uniqueid
				$start_at,				# start_at
				$end_at,				# end_at
				null,					# response_result_id
				Game_logs::FLAG_GAME,	# flag
				$extra					# extra
			);

		}

		 return $success;

	}

	# CACHE QUERY
	private $getUsernameByPlayerId = array();
	private function getUsernameByPlayerId($player_id) {
		if ( ! isset($this->getUsernameByPlayerId[$player_id])) {
			$this->getUsernameByPlayerId[$player_id] = $this->CI->player_model->getUsernameById($player_id);
			return $this->getUsernameByPlayerId($player_id);
		}
		return $this->getUsernameByPlayerId[$player_id];
	}

	# CACHE QUERY
	private $getPlayerIdByGameUsername = array();
	public function getPlayerIdByGameUsername($game_username) {
		if ( ! isset($this->getUsernameByPlayerId[$game_username])) {
			$this->getUsernameByPlayerId[$game_username] = $this->getPlayerIdInGameProviderAuth($game_username);
			return $this->getPlayerIdByGameUsername($game_username);
		}
		return $this->getUsernameByPlayerId[$game_username];
	}

	public function getCallType($apiName, $params) {
		if($apiName == self::API_syncGameRecordsByPlayer ||
			$apiName == self::API_login ||
			$apiName == self::API_generateToken ||
			$apiName == self::API_syncLiveDealerGameLogs){
			return self::CALL_TYPE_HTTP;
		}

		return self::CALL_TYPE_SOAP;
	}

	public function generateUrl($apiName, $params) {

		if($apiName == self::API_syncLiveDealerGameLogs){
			$url =  $this->live_dealer_api_url . "/". $this->live_dealer_api_version . "/report/product/{$params['product']}/gameType/{$params['gameType']}";
			unset($params['gameType'],$params['product']);
			$url .= "?" . http_build_query($params);
			// echo "<pre>";
			// print_r($url);exit();
			return $url;
		}

		if($apiName == self::API_generateToken){
			return $this->operator_token_url;
		}

		if($apiName == self::API_syncGameRecordsByPlayer){
			unset($params['exportingSync'],$params['exportingCookie']);
			$url =  $this->exportPlayerDataUrl . "?" . http_build_query($params);
			return $url;
		}

		if($apiName == self::API_login){
			$url =  $this->authenticatePlayerUrl . "?" . http_build_query($params);
			return $url;
		}
		ini_set("soap.wsdl_cache_enabled", 0);
	    return realpath(dirname(__FILE__)).'/wsdl/'.$this->getPlatformCode().'/'.$this->getSystemInfo('wsdl_filename', 'Orion-2.16.wsdl');
	}

	/**
	 * overview : custom http call
	 *
	 * @param $ch
	 * @param $params
	 */
	protected function customHttpCall($ch, $params) {
		if(isset($params['exportingSync'])){
			$exportingCookie = $params['exportingCookie'];
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: ASP.NET_SessionId={$exportingCookie}"));
		}

		if(isset($params['APIKey'])){
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		}
	}

	protected function createSoapClient($url, $options) {
		return new QuickfireSoapClient($url, $options);
	}

	protected function makeSoapOptions($options) {
		$options['ignore_ssl_verify'] = true;
		$options['basic_auth_username'] = $this->Orion_API_username;
		$options['basic_auth_password'] = $this->Orion_API_password;
		return $options;
	}


	# ORION ##########################################################################

	public function getRollbackQueueData() {

		$apiMethod = 'GetRollbackQueueData';

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetRollbackQueueData',
		);

		$params = array(
			'serverIds' => array($this->serverid)
		);

		return $this->callApi($apiMethod, $params, $context);
	}

	public function processResultForGetRollbackQueueData($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$result = json_decode(json_encode($resultObj), TRUE);
		// $this->CI->utils->debug_log('processResultForGetRollbackQueueData processQueueData:',json_encode($result) );
		$data = array();
		if (isset($result['GetRollbackQueueDataResult']['QueueDataResponse'])) {
			$data = isset($result['GetRollbackQueueDataResult']['QueueDataResponse'][0])
				  ? $result['GetRollbackQueueDataResult']['QueueDataResponse']
				  : [$result['GetRollbackQueueDataResult']['QueueDataResponse']];
		}
		return array(TRUE, array('data' => $data));
	}

	public function getCommitQueueData() {

		$apiMethod = 'GetCommitQueueData';

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetCommitQueueData',
		);

		$params = array(
			'serverIds' => array($this->serverid)
		);

		return $this->callApi($apiMethod, $params, $context);
	}

	public function processResultForGetCommitQueueData($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$result = json_decode(json_encode($resultObj), TRUE);
		// $this->CI->utils->debug_log('processResultForGetCommitQueueData processQueueData:',json_encode($result) );
		$data = array();
		if (isset($result['GetCommitQueueDataResult']['QueueDataResponse'])) {
			$data = isset($result['GetCommitQueueDataResult']['QueueDataResponse'][0])
				  ? $result['GetCommitQueueDataResult']['QueueDataResponse']
				  : [$result['GetCommitQueueDataResult']['QueueDataResponse']];
		}
		return array(TRUE, array('data' => $data));
	}

	public function getFailedEndGameQueue() {

		$apiMethod = 'GetFailedEndGameQueue';

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetFailedEndGameQueue',
		);

		$params = array(
			'serverIds' => array($this->serverid)
		);

		return $this->callApi($apiMethod, $params, $context);
	}

	public function processResultForGetFailedEndGameQueue($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$result = json_decode(json_encode($resultObj), TRUE);
		// $this->CI->utils->debug_log('processResultForGetFailedEndGameQueue processQueueData:',json_encode($result) );
		$data = array();
		if (isset($result['GetFailedEndGameQueueResult']['GetFailedGamesResponse'])) {
			$data = isset($result['GetFailedEndGameQueueResult']['GetFailedGamesResponse'][0])
				  ? $result['GetFailedEndGameQueueResult']['GetFailedGamesResponse']
				  : [$result['GetFailedEndGameQueueResult']['GetFailedGamesResponse']];
		}
		return array(TRUE, array('data' => $data));
	}

	public function manuallyValidateBet($rowId, $unlockType, $external_reference_id) {

		$apiMethod = 'ManuallyValidateBet';

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForManuallyValidateBet',
		);

		$data = array(
			'RowIdLong' => $rowId,
			'ServerId' => $this->serverid,
			'UnlockType' => $unlockType, # RollbackQueue CommitQueue
		);

		if ($external_reference_id) {
			$data['ExternalReference'] = $external_reference_id;
		}

		$params = array(
			'validateRequests' => array($data)
		);

		return $this->callApi($apiMethod, $params, $context);
	}

	public function processResultForManuallyValidateBet($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$result = json_decode(json_encode($resultObj), TRUE);
		$success = isset($result['ManuallyValidateBetResult']) && $result['ManuallyValidateBetResult'];
		return array($success);
	}

	public function manuallyCompleteGame($data) {

		$apiMethod = 'ManuallyCompleteGame';

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForManuallyCompleteGame',
		);

		$params = array(
			'requests' => array(
				array(
					'RowId' => $data['RowId'],
					'RowIdLong' => $data['RowIdLong'],
					'ServerId' => $this->serverid
				)
			)
		);

		return $this->callApi($apiMethod, $params, $context);
	}

	public function processResultForManuallyCompleteGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$result = json_decode(json_encode($resultObj), TRUE);
		$success = isset($result['ManuallyCompleteGameResult']['CompleteGameResponse']['Success']) && $result['ManuallyCompleteGameResult']['CompleteGameResponse']['Success'];
		return array($success);
	}

	public function validateApiUser() {

		$apiMethod = 'ValidateApiUser';

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForValidateApiUser',
		);

		$params = array(
			'username' => $this->Orion_API_username,
			'password' => $this->Orion_API_password
		);

		return $this->callApi($apiMethod, $params, $context);
	}

	public function processResultForValidateApiUser($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$result = json_decode(json_encode($resultObj), TRUE);
		echo "<pre>";
		print_r (json_encode($resultObj, JSON_PRETTY_PRINT));
		echo "</pre>";
	}

	public function getPlayerIdByToken($token, $throw_exception = TRUE) {
		$this->CI->load->model(array('common_token', 'player_model'));

		$playerId = $this->CI->common_token->getPlayerIdByToken($token);

		if ($playerId) {
			$this->CI->common_token->updatePlayerToken($playerId, $token, $this->token_timeout);
		} else {

			if ($throw_exception && $this->CI->common_token->isTokenValid($playerId, $token)) {
				throw new Exception("The player token expired.", "6002");
			}

		}

		return $playerId;
	}

	public function queryBetDetailLink($playerUsername, $betId = null, $extra = null) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

		$transactionid = '';

		$temp = explode('|', $betId);

		if ( ! empty($temp)) {
			$transactionid = end($temp);
		}

		$params = array(
			'applicationid' => '1001',
			'serverid' => $this->serverid,
			'username' => $gameUsername,
			'appmode' => 'OperatorPlayCheckView',
			'adminuser' => $this->Orion_API_username,
			'password' => $this->Orion_API_password,
			'transactionid' => $transactionid,
		);

		$url = $this->iframe_url . '?' . http_build_query($params);

		return array('success' => true, 'url' => $url);
	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {
		return array('success' => true, 'balances' => array());
	}

    public function isSeamLessGame(){
        return true;
    }

    protected function makeHttpOptions($options) {
    	if($this->remove_http_option){
    		return ['ignore_ssl_verify' => $this->ignore_ssl_verify];
    	}
		return $options;
	}

    /**
	 * overiew : Authenticate game username on playcheck22.funlauncher.net, for exporting process
	 *
	 * @param  string $gameUsername
	 * @return array
	 */

    public function authenticateGameUsername($gameUsername){//example. ibgz13714728640
    	$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
    	$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForAuthenticateGameUsername',
            'gameUsername' => $gameUsername,
            'playerId' => $playerId
        );

        $params = array(
			'applicationid' => '1001',
			'serverid' => $this->serverid,
			'username' => $gameUsername,
			'appmode' => 'OperatorPlayCheckView',
			'adminuser' => $this->Orion_API_username,
			'password' => $this->Orion_API_password
		);
        $this->remove_http_option = true;
        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForAuthenticateGameUsername($params){
    	$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
    	$playerId = $this->getVariableFromContext($params, 'playerId');
    	$responseResultId = $this->getResponseResultIdFromParams($params);
    	$output = $params['extra'];

    	preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $output, $matches);

		$cookies = array();
		foreach($matches[1] as $item) {
		    parse_str($item, $cookie);
		    $cookies = array_merge($cookies, $cookie);
		}
		$current_cookie = $cookies['ASP_NET_SessionId'];

		return array(true, array(
			"cookie" => $current_cookie,
			"gameUsername" => $gameUsername,
			"playerId" => $playerId
		));
    }

    function checkGameUsernameHasPrefix($string){
    	$prefix = $this->getSystemInfo('prefix_for_username');
    	return substr($string, 0, strlen($prefix)) == $prefix;
    }

     /**
     * queryGameUsername
     * @param  string $dateFrom
     * @param  string $dateTo
     * @return array
     */
    public function queryGameUsername($dateFrom, $dateTo){
    	$this->CI->load->model(array('original_game_logs_model'));
        $sqlTime='qf.timestamp >= ? and qf.timestamp <= ?';
        $sql = <<<EOD
SELECT DISTINCT game_username
FROM mg_quickfire_game_logs as qf
WHERE
{$sqlTime}
EOD;
        $params=[$dateFrom,$dateTo];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return array_column($result,'game_username');
    }


    const DAY_TODAY = 0;
    const DAY_YESTERDAY = 1;
    /**
	 * overiew : Export game transaction of player
	 *
	 * @param  array $token
	 * @return array
	 */

    public function syncGameRecordsThroughExport($token = null){
    	$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d')));
		$startDate->modify($this->getDatetimeAdjust());

		$queryStartDate = $startDate;
		$queryEndDate = $endDate;
		$queryStartDate = $queryStartDate->format('Y-m-d 00:00:00');
        $queryEndDate = $queryEndDate->format('Y-m-d 23:59:59');


    	if($this->use_original_on_query_game_username){
    		$gameUsernames = $this->queryGameUsername($queryStartDate,$queryEndDate);
    	} else {
    		$this->CI->load->model(array('game_provider_auth'));
    		$gameUsernames = $this->CI->game_provider_auth->getAllGameRegisteredUsernames($this->getPlatformCode());
    	}
    	$this->CI->utils->debug_log('QUICKFIRE GAMEUSERNAMES', $gameUsernames);
    	$result = [];
    	if(!empty($gameUsernames)){
    		foreach ($gameUsernames as $key => $gameUsername) {
    			$has_prefix = $this->checkGameUsernameHasPrefix($gameUsername);
    			if(!$has_prefix){
    				continue;
    			}
    			$userResult = $this->authenticateGameUsername($gameUsername);

				$today = new DateTime(date('Y-m-d'));

				$fromDaysBack = self::DAY_TODAY;
				$toDaysBack = self::DAY_TODAY;

				if($startDate > $endDate){
					$this->CI->utils->debug_log('Invalid Date params');
					return array(false,array("message" => "Invalid Date params"));
				}

				if($startDate < $today){
					$fromDaysBack = (int) date_diff($startDate,$today)->days;
					#force yesterday fetch if no days difference
					if($fromDaysBack == self::DAY_TODAY){
						$fromDaysBack = self::DAY_YESTERDAY;
					}
				}

				if($endDate < $today){
					$toDaysBack = (int) date_diff($endDate,$today)->days;
				}

		   		/*  URL FORMAT
		   			https://playcheck22.funlauncher.net/Playcheck/OperatorExport.csv?fromDaysBack=8&toDaysBack=4&filter=
		   		*/

		    	$context = array(
		            'callback_obj' => $this,
		            'callback_method' => 'processResultForGetPlayerGameTransaction',
		            'cookie' => $userResult['cookie'],
					'gameUsername' => $userResult['gameUsername'],
					'playerId' => $userResult['playerId'],
					'fromDaysBack' => $fromDaysBack,
					'toDaysBack' => $toDaysBack
		        );

		        $params = array(
		            "fromDaysBack" => $fromDaysBack,
		            "toDaysBack" => $toDaysBack,
		            "exportingCookie" => $userResult['cookie'],
		            "exportingSync" => true
		        );

		        // echo "<pre>";
		        // print_r($params);
		        $this->remove_http_option = true;
		        $result[] =  $this->callApi(self::API_syncGameRecordsByPlayer, $params, $context);
		        sleep(3);
    		}
    	}
    	return array(true, $result);

  //   	$userResult = $this->authenticateGameUsername();
  //   	$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		// $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		// $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d')));
		// $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d')));
		// $startDate->modify($this->getDatetimeAdjust());

		// $today = new DateTime(date('Y-m-d'));

		// $fromDaysBack = 0;
		// $toDaysBack = 0;

		// if($startDate > $endDate){
		// 	$this->CI->utils->debug_log('Invalid Date params');
		// 	return array(false,array("message" => "Invalid Date params"));
		// }

		// if($startDate < $today){
		// 	$fromDaysBack = (int) date_diff($startDate,$today)->days;
		// }

		// if($endDate < $today){
		// 	$toDaysBack = (int) date_diff($endDate,$today)->days;
		// }

  //  		/*  URL FORMAT
  //  			https://playcheck22.funlauncher.net/Playcheck/OperatorExport.csv?fromDaysBack=8&toDaysBack=4&filter=
  //  		*/

  //   	$context = array(
  //           'callback_obj' => $this,
  //           'callback_method' => 'processResultForGetPlayerGameTransaction',
  //           'cookie' => $userResult['cookie'],
		// 	'gameUsername' => $userResult['gameUsername'],
		// 	'playerId' => $userResult['playerId'],
		// 	'fromDaysBack' => $fromDaysBack,
		// 	'toDaysBack' => $toDaysBack
  //       );

  //       $params = array(
  //           "fromDaysBack" => $fromDaysBack,
  //           "toDaysBack" => $toDaysBack,
  //           "exportingCookie" => $userResult['cookie'],
  //           "exportingSync" => true
  //       );

  //       return $this->callApi(self::API_syncGameRecordsByPlayer, $params, $context);
    }

    const MD5_FIELDS_FOR_MG_CUSTOM_REPORT = [];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MG_CUSTOM_REPORT = [];


    public function processResultForGetPlayerGameTransaction($params){
    	$this->CI->load->model(array('original_game_logs_model'));

    	/* FORMAT
    		[0] => Array
	        (
	            [0] => Transaction
	            [1] => Session
	            [2] => Time
	            [3] => Description
	            [4] => Wagered ($)
	            [5] => Payout ($)
	            [6] => Change ($)
	            [7] => Closing Balance ($)
	        )
    	*/

    	$extra = array(
    		"gameUsername" => $this->getVariableFromContext($params, 'gameUsername'),
    		"playerId" => $this->getVariableFromContext($params, 'playerId'),
    		"responseResultId" => $this->getResponseResultIdFromParams($params)
    	);

        $csvtext = isset($params['resultText']) ? $params['resultText'] : "";
        $arr = explode("\n", $csvtext);
		$gameRecords = array();
		if(!empty($arr)){
			foreach ($arr as &$line) {
			  $gameRecords[] = str_getcsv($line);
			}
		}

		unset($gameRecords[0]);//remove header column
		array_pop($gameRecords);//remove last data

		$gameRecords = array_values($gameRecords);//rebase
		$this->processGameRecords($gameRecords, $extra);

		list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
            'mg_quickfire_game_reports',
            $gameRecords,
            'external_uniqueid',
            'external_uniqueid',
            self::MD5_FIELDS_FOR_MG_CUSTOM_REPORT,
            'md5_sum',
            'id',
            self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MG_CUSTOM_REPORT
        );

        $dataResult = array(
			'data_count' => 0,
			'data_count_insert'=> 0,
			'data_count_update'=> 0
		);

        $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

        $dataResult['data_count'] = count($gameRecords);
		if (!empty($insertRows)) {
			$dataResult['data_count_insert'] += $this->updateOrInsertGameReports($insertRows, 'insert');
		}
		unset($insertRows);

		if (!empty($updateRows)) {
			$dataResult['data_count_update'] += $this->updateOrInsertGameReports($updateRows, 'update');
		}
		unset($updateRows);

		return array(true, $dataResult);
    }

    private function updateOrInsertGameReports($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                	$record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal('mg_quickfire_game_reports', $record);
                } else {
                    unset($record['id']);
                    // $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal('mg_quickfire_game_reports', $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function processGameRecords(&$gameRecords, $extra) {
    	/* FORMAT
    		[0] => Array
	        (
	            [0] => Transaction
	            [1] => Session
	            [2] => Time
	            [3] => Description
	            [4] => Wagered ($)
	            [5] => Payout ($)
	            [6] => Change ($)
	            [7] => Closing Balance ($)
	        )
    	*/
		if(!empty($gameRecords)){
			foreach($gameRecords as $index => $record) {

				$data['transaction'] = isset($record[0]) ? $record[0] : null;
				$dateTime = new DateTime($this->serverTimeToGameTime(rtrim($record[2], " APMapm")));
				$dateTime = $dateTime->format('Y-m-d H:i:s');

				$data['session'] = isset($record[1]) ? $record[1] : null;
				$data['time'] = isset($record[2]) ? $this->gameTimeToServerTime($dateTime) : null;

				$data['description'] = isset($record[3]) ? $record[3] : null;
				$data['wagered'] = isset($record[4]) ? $record[4] : null;
				$data['payout'] = isset($record[5]) ? $record[5] : null;
				$data['win_loss'] = isset($record[6]) ? $record[6] : null;
				$data['balance'] = isset($record[7]) ? $record[7] : null;
				$data['game_username'] = isset($extra['gameUsername']) ? $extra['gameUsername'] : null;
				$data['player_id'] = isset($extra['playerId']) ? $extra['playerId'] : null;
				//$data['status'] = null;//in case if have status
				// $data['game_code'] = null; //in case if already provided
				$data['response_result_id'] = isset($extra['responseResultId']) ? $extra['responseResultId'] : null;
				$data['external_uniqueid'] = isset($record[0]) ? $record[0].'-'.$record[1].'-'.$extra['playerId']: null;
				$gameRecords[$index] = $data;

				unset($data);
			}
		}
	}

	/**
     * will check timeout, if timeout then call again
     * @return token
     */
    public function getAvailableApiToken(){
        $token = $this->getCommonAvailableApiToken(function(){
           return $this->getOperatorToken();
        });
        $this->utils->debug_log("QUICKFIRE LIVE DEALER Bearer Token: ".$token);
        return $token;
    }

	public function getOperatorToken(){
		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetOperatorToken',
        );

        $params = array(
            "APIKey" => $this->APIKey,
        );

		$this->remove_http_option = true;
        return $this->callApi(self::API_generateToken, $params, $context);
	}

	public function processResultForGetOperatorToken($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$success = false;
		$result = [];
		if(isset($resultJsonArr['AccessToken'])){
			$success = true;
			$result['token'] = $resultJsonArr['AccessToken'];

            $token_timeout = new DateTime($this->utils->getNowForMysql());
            $minutes = ((int)$resultJsonArr['ExpiryInSeconds']/60)-1;
            $token_timeout->modify("+".$minutes." minutes");
            $result['api_token']=$resultJsonArr['AccessToken'];
            $result['api_token_timeout_datetime']=$token_timeout->format('Y-m-d H:i:s');

		}
		return array($success, $result);
	}
	/*
		id  Value          Description
		0	Baccarat	   Baccarat
		1	Blackjack	   Blackjack
		2	Roulette	   Roulette
		3	Sicbo	       Sicbo
		4	CasinoHoldem   CasinoHoldem
	*/
	const LIVEDEALER_GAMETYPE = [0,1,2,3,4];
	const API_syncLiveDealerGameLogs = "syncLiveDealerGameLogs";
	const ORIGINAL_LIVE_DEALER_LOGS_TABLE_NAME = 'mgquickfire_livedealer_gamelogs';
	const MD5_FIELDS_FOR_ORIGINAL_LIVEDEALER=['placeBetTime','betPool','ticketStatusId','completed','actionStatusID','betPool','betDetails'];
	const MD5_FLOAT_AMOUNT_FIELDS_LIVEDEALER=['betAmount','gainAmount','amount'];

	public function syncLiveDealerGameLogs($token){
		// $row_gamereference = 'MGS_LG_Baccarat_(TitaniumAndroidBrowser)';
		// $row_game_username = 'ibgjkwx1957';
		// $row_gameid = '14304';
		// $external_uniqueid = implode('|', [$row_gamereference, $row_game_username, $row_gameid]);
		// $external_uniqueid = hash('sha256', $external_uniqueid);
		$this->remove_http_option = true;

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());

		$startDate = $startDate->format('Y-m-d\TH:i:s\Z');
		$endDate   = $endDate->format('Y-m-d\TH:i:s\Z');

		// $date_params = array(
		// 	"startDate" => $startDate,
		// 	"endDate" => $endDate,
		// );
		// echo "<pre>";
		// print_r($date_params);exit();
		$result = array();
		$game_types = self::LIVEDEALER_GAMETYPE;
		if(!empty($game_types)){
			foreach ($game_types as $game_type) {
				$this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+5 minutes', function($startDate, $endDate) use(&$result, $game_type)  {
					$startDate = $startDate->format('Y-m-d\TH:i:s\Z');
					$endDate   = $endDate->format('Y-m-d\TH:i:s\Z');
					$context = array(
						'callback_obj' => $this,
						'callback_method' => 'processResultForSyncLiveDealerGameLogs',
					);

			        $params = array(
			            'startTime' => $startDate,
						'endTime' => $endDate,
						'gameType' => $game_type,
						'product' => $this->live_dealer_product_id
					);

					$this->CI->utils->debug_log('-----------------------quickfire syncLiveDealerGameLogs params ----------------------------',$params);
					$response =  $this->callApi(self::API_syncLiveDealerGameLogs, $params, $context);
					$result[] = $response;
					return $result;
			    });
			}
		}

		return array(true,$result);
	}

	public function processResultForSyncLiveDealerGameLogs($params) {
		$this->CI->load->model(array('original_game_logs_model'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$dataResult = array(
			'data_count' => 0,
			'data_count_insert'=> 0,
			'data_count_update'=> 0
		);
		$success = false;
		if(!empty($resultJsonArr)){
			$gameRecords = $this->processLiveGameRecords($resultJsonArr, $responseResultId);
			$this->CI->original_game_logs_model->removeDuplicateUniqueid($gameRecords, 'external_uniqueid', function(){ return true;});
			if(!empty($gameRecords)){
				$success = true;
				list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
	                self::ORIGINAL_LIVE_DEALER_LOGS_TABLE_NAME,
	                $gameRecords,
	                'external_uniqueid',
	                'external_uniqueid',
	                self::MD5_FIELDS_FOR_ORIGINAL_LIVEDEALER,
	                'md5_sum',
	                'id',
	                self::MD5_FLOAT_AMOUNT_FIELDS_LIVEDEALER
	            );

	            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

	            $dataResult['data_count'] = count($gameRecords);
				if (!empty($insertRows)) {
					$dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert', self::ORIGINAL_LIVE_DEALER_LOGS_TABLE_NAME);
				}
				unset($insertRows);

				if (!empty($updateRows)) {
					$dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update', self::ORIGINAL_LIVE_DEALER_LOGS_TABLE_NAME);
				}
				unset($updateRows);
			}
		}
		return array($success, $dataResult);
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType, $table){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                	$record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($table, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

	public function processLiveGameRecords($gameRecords, $responseResultId) {
		$rows = array();
		if(!empty($gameRecords)){
			foreach($gameRecords as $index => $record) {

				$data['placeBetTime'] = isset($record['placeBetTime']) ? $this->gameTimeToServerTime($record['placeBetTime']) : null;
				$data['tableCode'] = isset($record['tableCode']) ? $record['tableCode'] : null;
				$data['roundId'] = isset($record['roundId']) ? $record['roundId'] : null;
				$data['betPool'] = isset($record['betPool']) ? $record['betPool'] : null;
				$data['currencyIsoCode'] = isset($record['currencyIsoCode']) ? $record['currencyIsoCode'] : null;
				$data['ticketStatusId'] = isset($record['ticketStatusId']) ? $record['ticketStatusId'] : null;
				$data['completed'] = isset($record['completed']) ? $record['completed'] : null;
				$data['betAmount'] = isset($record['betAmount']) ? $this->gameAmountToDB($record['betAmount']) : null;
				$data['gainAmount'] = isset($record['gainAmount']) ? $this->gameAmountToDB($record['gainAmount']) : null;
				$data['ipAddress'] = isset($record['ipAddress']) ? $record['ipAddress'] : null;
				$data['userName'] = isset($record['userName']) ? $record['userName'] : null;
				$data['playerMode'] = isset($record['playerMode']) ? $record['playerMode'] : null;
				$data['userTransNumber'] = isset($record['userTransNumber']) ? $record['userTransNumber'] : null;
				$data['betDetails'] = isset($record['betDetails']) ? json_encode($record['betDetails']) : null;

				#betdetails
				if(!empty($record['betDetails'])){
					foreach ($record['betDetails'] as $key => $bet_data) {
						$data['amount'] = isset($bet_data['amount']) ? $this->gameAmountToDB($bet_data['amount']) : null;
						$data['description'] = isset($bet_data['description']) ? $bet_data['description'] : null;
						$data['actionStatusID'] = isset($bet_data['actionStatusID']) ? $bet_data['actionStatusID'] : null;
						$data['externalBalanceActionID'] = isset($bet_data['externalBalanceActionID']) ? $bet_data['externalBalanceActionID'] : null;
						$data['external_uniqueid'] = $data['externalBalanceActionID'];
						$data['response_result_id'] = $responseResultId;
						$rows[] = $data;

					}
				}
				unset($data);
			}
		}
		return $rows;
	}

	public function getHttpHeaders($params)
    {
    	if(isset($params['startTime']) && isset($params['endTime'])){
    		$bearer_token = $this->getAvailableApiToken();
			$headers = array(
                "Authorization" => "Bearer {$bearer_token}"
            );
            return $headers;
    	}
    }

    public function compareOriginalAndLivedealerActionIds($playerId, $from, $to){

		$params = array(
			"startDate" => $from,
			"endDate" => $to,
			"gameUsername" => $this->getGameUsernameByPlayerId($playerId)
		);

		$query_data = $this->queryPlayerGamelogs($params);

		$missing_data = array_filter($query_data, function ($element)  {
			return empty($element['live_dealer_amount']);
		});

		$data = array_filter($query_data, function ($element)  {
			return !empty($element['live_dealer_amount']);

		});

		$dataResult = array(
			"data_not_match" => array(
				"count" => count($data),
				"list" => $data
			),
			"data_missing" => array(
				"count" => count($missing_data),
				"list" => $missing_data
			),

		);

		return $dataResult;
    }

    private function queryPlayerGamelogs($params){
    	$this->CI->load->model(array('original_game_logs_model'));
    	$startDate = $params['startDate'];
		$endDate   = $params['endDate'];
		$gameUsername = $params['gameUsername'];

		$sqlTime='mq.timestamp >= ? and mq.timestamp <= ? and game_username=?';
		$sql = <<<EOD
SELECT mq.actionid as action_id, (mq.amount / 100) as original_amount,
ml.amount as live_dealer_amount
FROM mg_quickfire_game_logs as mq
LEFT JOIN mgquickfire_livedealer_gamelogs as ml ON mq.actionid = ml.external_uniqueid
WHERE

{$sqlTime}
and (ml.amount is null or ml.amount != mq.amount / 100)

EOD;

        $params=[$startDate,$endDate, $gameUsername];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    private function queryLiveDealerLogs($params){
    	$this->CI->load->model(array('original_game_logs_model'));
    	$startDate = $params['startDate'];
		$endDate   = $params['endDate'];
		$gameUsername = $params['gameUsername'];

		$sqlTime='mq.placeBetTime >= ? and mq.placeBetTime <= ? and userName=?';
        $sql = <<<EOD
SELECT external_uniqueid as action_id, amount
FROM mgquickfire_livedealer_gamelogs as mq
WHERE

{$sqlTime}

EOD;

        $params=[$startDate,$endDate, $gameUsername];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }
}

/*end of file*/