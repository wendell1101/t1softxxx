<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting platform code
 * * Generate URL
 * * Get Player's Session ID
 * * Create Player
 * * Login/Logout player
 * * Change Password
 * * Check Player Information
 * * Check Player Balance
 * * Deposit To game
 * * Withdraw from Game
 * * Check Player if Exist
 * * Synchronize Original Game Logs
 * * Synchronize and Merge Inteplay to Game Logs
 * * Check Login Status
 * * Block/Unblock player
 * * Check Forward Game
 * * Get Game Description Information
 * Behaviors not implemented
 * * Update Player's information
 * * Check Player Daily Balance
 * * Check Game Records
 * * Check Login Token
 * * Check total betting amount
 * * Check Transaction
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 * @deprecated 2.0
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10 
 * @copyright 2013-2022 tot
 */
class Game_api_inteplay extends Abstract_game_api {

	private $api_url;
	private $operator_key;
	private $currency 	= 'CNY';
	private $country 	= 'CH';
	private $language 	= 'en';

	private $game_url 	= '';

	const URI_MAP = array(
		self::API_createPlayer 			=> 'player/register.htm',
		self::API_login 				=> 'player/login.htm',
		self::API_logout 				=> 'player/logout.htm',
		self::API_changePassword		=> 'player/changePass.htm',
		self::API_queryPlayerInfo		=> 'player/userInfo.htm',
		self::API_queryPlayerBalance	=> 'player/userBalance.htm',
		self::API_depositToGame			=> 'player/deposit.htm',
		self::API_withdrawFromGame		=> 'player/withdrawal.htm',
		self::API_checkLoginStatus		=> 'player/playerSession.htm',
		self::API_syncGameRecords		=> 'gamereport/getGameSetInfoByGameKeyReport.htm'
	);

	const SUCCESS_CODE = 0;
	const START_PAGE = 1;
	const GAME_TIMEZONE = 'UTC';
	const SYSTEM_TIMEZONE = 'Asia/Hong_Kong';

	public function __construct() {
		parent::__construct();
		$this->api_url 		= $this->getSystemInfo('url');
		$this->operator_key = $this->getSystemInfo('key');
	}

	public function getPlatformCode() {
		return INTEPLAY_API;
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		$params_string = http_build_query($params);
		return $this->api_url . '/SystemRemote/api-v1/' . $apiUri . "?" . $params_string;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultJson, $playerName) {
		$success = ( ! empty($resultJson)) && $resultJson['code'] == self::SUCCESS_CODE;

		if ( ! $success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('INTEPLAY got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson);
		}

		return $success;
	}

	# START CREATE PLAYER #################################################################################################################################

		// RESULT: {"code":0,"message":"Normal"}

		public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

			parent::createPlayer($playerName, $playerId, $password, $email, $extra);

			$playerName = $this->getGameUsernameByPlayerUsername($playerName);

			$context = array(
				'callback_obj' 		=> $this,
				'callback_method' 	=> 'processResultForCreatePlayer',
				'playerName' 		=> $playerName,
				'playerId' 			=> $playerId,
			);

			$params = array(
				'operatorKey' 	=> $this->operator_key,		#	true	字符串类型：字母(区分大小写)、数字，或者两者组合	运营商Key由GMS后台定义，例如OperatorName01
				'username' 		=> $playerName,				#	true	字符串类型：字母(区分大小写)、数字、中文，特殊字符，或者任意两者或三者组合	注册玩家登录用户名 ，长度范围为[5，20]
				'password' 		=> $password,				#	true	字符串类型：字母(区分大小写)、数字，或者字母和数字组合	注册玩家登录密码，长度范围为[6，20]
				'currency' 		=> $this->currency,			#	true	字符串类型：大写字母，范围Currency Standard: ISO 4217, e.g. USD	注册玩家的所用的货币，币种标准：ISO 4217币种代码（Currency Code）
				'balance' 		=> 0,						#	true	浮点数类型：双精度实型（double）	注册玩家初始账户余额，例如0.0
				'country' 		=> $this->country,			#	false	字符串类型：大写字母，范围ISO Country Code, e.g. US	ISO国家地区代码（ISO Country Code）
				'language' 		=> $this->language,			#	false	字符串类型：小写字母，范围Language Standard: ISO 639-1, e.g. en	语言标准：ISO 639-1语言代码（ISO 639-1 Code）
			);

			return $this->callApi(self::API_createPlayer, $params, $context);

		}

		public function processResultForCreatePlayer($params) {

			$responseResultId 	= $this->getResponseResultIdFromParams($params);
			$resultJson 		= $this->getResultJsonFromParams($params);
			$playerName 		= $this->getVariableFromContext($params, 'playerName');
			$playerId 			= $this->getVariableFromContext($params, 'playerId');
			$success 			= $this->processResultBoolean($responseResultId, $resultJson, $playerName);

			return array($success, $resultJson);

		}

	# END CREATE PLAYER #################################################################################################################################



	# START LOGIN #################################################################################################################################

		// RESULT: {"operatorKey":"Casino1","sessionKey":"091d7e4d-ec2f-44ae-877c-e4c663d3f428","userName":"Test01","currency":"CNY","playerBalance":0,"loginTime":1457000673248,"code":0,"message":"Normal"}

		public function login($playerName, $password = null) {

			$playerName = $this->getGameUsernameByPlayerUsername($playerName);

			$context = array(
				'callback_obj' 		=> $this,
				'callback_method' 	=> 'processResultForLogin',
				'playerName' 		=> $playerName,
			);

			$params = array(
				'operatorKey' 	=> $this->operator_key,			#	true	字符串类型：字母(区分大小写)、数字，或者两者组合	运营商Key由GMS后台定义，例如OperatorName01
				'username' 		=> $playerName,					#	true	字符串类型：字母(区分大小写)、数字、中文，特殊字符，或者任意两者或三者组合	账号登录用户名 ，长度范围为[5，20]
				'password' 		=> $password,					#	true	字符串类型：字母(区分大小写)、数字，或者字母和数字组合	账号登录密码，长度范围为[6，20]
			);

			return $this->callApi(self::API_login, $params, $context);

		}

		public function processResultForLogin($params) {

			$responseResultId 	= $this->getResponseResultIdFromParams($params);
			$resultJson 		= $this->getResultJsonFromParams($params);
			$playerName 		= $this->getVariableFromContext($params, 'playerName');
			$success 			= $this->processResultBoolean($responseResultId, $resultJson, $playerName);

			return array($success, $resultJson);

		}

	# END LOGIN #################################################################################################################################



	# START LOGOUT #################################################################################################################################

		// {"code":0,"message":"Normal"}

		public function logout($playerName, $password = null) {

			$playerName = $this->getGameUsernameByPlayerUsername($playerName);

			$context = array(
				'callback_obj' 		=> $this,
				'callback_method' 	=> 'processResultForLogout',
				'playerName' 		=> $playerName,
			);

			$params = array(
				'operatorKey' 	=> $this->operator_key,		# true	字符串类型：字母(区分大小写)、数字，或者两者组合	运营商Key由GMS后台定义，例如OperatorName01
				'username' 		=> $playerName,				# true	字符串类型：字母(区分大小写)、数字、中文，特殊字符，或者任意两者或三者组合	账号登录用户名 ，长度范围为[5，20]
			);

			return $this->callApi(self::API_logout, $params, $context);

		}

		public function processResultForLogout($params) {

			$responseResultId 	= $this->getResponseResultIdFromParams($params);
			$resultJson 		= $this->getResultJsonFromParams($params);
			$playerName 		= $this->getVariableFromContext($params, 'playerName');
			$success 			= $this->processResultBoolean($responseResultId, $resultJson, $playerName);

			return array($success, $resultJson);

		}

	# END LOGOUT #################################################################################################################################



	# START CHANGE PASSWORD #################################################################################################################################

		// {"code":0,"message":"Normal"}

		public function changePassword($playerName, $oldPassword, $newPassword) {

			$playerName = $this->getGameUsernameByPlayerUsername($playerName);

			$context = array(
				'callback_obj' 		=> $this,
				'callback_method' 	=> 'processResultForChangePassword',
				'playerName' 		=> $playerName,
				'newPassword' 		=> $newPassword,
			);

			$params = array(
				'operatorKey' 	=> $this->operator_key,		# true	字符串类型：字母(区分大小写)、数字，或者两者组合	运营商Key由GMS后台定义，例如OperatorName01
				'username' 		=> $playerName,				# true	字符串类型：字母(区分大小写)、数字、中文，特殊字符，或者任意两者或三者组合	账号登录用户名 ，长度范围为[5，20]
				'oldPass' 		=> $oldPassword,			# true	字符串类型：字母(区分大小写)、数字，或者字母和数字组合	账号登录旧密码，长度范围为[6，20]
				'newPass' 		=> $newPassword,			# true	字符串类型：字母(区分大小写)、数字，或者字母和数字组合	账号登录新密码，长度范围为[6，20]
			);

			return $this->callApi(self::API_changePassword, $params, $context);

		}

		public function processResultForChangePassword($params) {

			$responseResultId 	= $this->getResponseResultIdFromParams($params);
			$resultJson 		= $this->getResultJsonFromParams($params);
			$playerName 		= $this->getVariableFromContext($params, 'playerName');
			$newPassword 		= $this->getVariableFromContext($params, 'newPassword');
			$success 			= $this->processResultBoolean($responseResultId, $resultJson, $playerName);

			if ($success) {

				if ($playerId = $this->getPlayerIdInPlayer($playerName)) {
					$this->updatePasswordForPlayer($playerId, $newPassword);
				} else $this->CI->utils->debug_log('cannot find player', $playerName);

			}

			return array($success, $resultJson);

		}

	# END CHANGE PASSWORD #################################################################################################################################



	# START QUERY PLAYER INFO #################################################################################################################################

		// {"operatorKey":"Casino1","sessionKey":"368d8b0d-f20a-463a-a801-c2014d446571","userName":"Test02","currency":"CNY","playerBalance":20000,"loginTime":1448644611921,"code":0,"message":"Normal"}

		public function queryPlayerInfo($playerName) {

			$rlt = $this->checkLoginStatus($playerName);
			if ( ! $rlt['success']) {
				$rlt = $this->login($playerName, $this->getPasswordString($playerName));
				if ( ! $rlt['success']) {
					return array(
						'success' => false,
					);
				}
			}

			$playerName = $this->getGameUsernameByPlayerUsername($playerName);

			$context = array(
				'callback_obj' 		=> $this,
				'callback_method' 	=> 'processResultForQueryPlayerInfo',
				'playerName' 		=> $playerName,
			);

			$params = array(
				'operatorKey' 	=> $this->operator_key,		# true	字符串类型：字母(区分大小写)、数字，或者两者组合	运营商Key由GMS后台定义，例如OperatorName01
				'username'		=> $playerName,				# true	字符串类型：字母(区分大小写)、数字、中文，特殊字符，或者任意两者或三者组合	账号登录用户名 ，长度范围为[5，20]
			);

			return $this->callApi(self::API_queryPlayerInfo, $params, $context);

		}

		public function processResultForQueryPlayerInfo($params) {

			$responseResultId 	= $this->getResponseResultIdFromParams($params);
			$resultJson 		= $this->getResultJsonFromParams($params);
			$playerName 		= $this->getVariableFromContext($params, 'playerName');
			$success 			= $this->processResultBoolean($responseResultId, $resultJson, $playerName);

			return array($success, $resultJson);

		}

	# END QUERY PLAYER INFO #################################################################################################################################



	# START QUERY PLAYER BALANCE #################################################################################################################################

		// {"operatorKey":"Casino1","sessionKey":"368d8b0d-f20a-463a-a801-c2014d446571","userName":"Test02","currency":"CNY","playerBalance":20000,"loginTime":1448644611921,"code":0,"message":"Normal"}

		public function queryPlayerBalance($playerName) {

			$playerName = $this->getGameUsernameByPlayerUsername($playerName);

			$context = array(
				'callback_obj' 		=> $this,
				'callback_method' 	=> 'processResultForQueryPlayerBalance',
				'playerName' 		=> $playerName,
			);

			$params = array(
				'operatorKey' 	=> $this->operator_key,		# true	字符串类型：字母(区分大小写)、数字，或者两者组合	运营商Key由GMS后台定义，例如OperatorName01
				'username'		=> $playerName,				# true	字符串类型：字母(区分大小写)、数字、中文，特殊字符，或者任意两者或三者组合	账号登录用户名 ，长度范围为[5，20]
			);

			return $this->callApi(self::API_queryPlayerBalance, $params, $context);

		}

		public function processResultForQueryPlayerBalance($params) {

			$responseResultId 	= $this->getResponseResultIdFromParams($params);
			$resultJson 		= $this->getResultJsonFromParams($params);
			$playerName 		= $this->getVariableFromContext($params, 'playerName');
			$success 			= $this->processResultBoolean($responseResultId, $resultJson, $playerName);

			$balance = 0;
			if ($success && isset($resultJson['playerBalance'])) {

				$balance  = @floatval($resultJson['playerBalance']);

				if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
					$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $balance);
				} else {
					$this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
				}

			} else {
				$success = false;
			}

			return array($success, array(
				'balance' => $balance,
			));

		}

	# END QUERY PLAYER BALANCE #################################################################################################################################



	# START DEPOSIT TO GAME #################################################################################################################################

		// {"code":0,"message":"Normal"}

		public function depositToGame($playerName, $amount, $transfer_secure_id=null) {

			$rlt = $this->checkLoginStatus($playerName);
			if ( ! $rlt['success']) {
				$rlt = $this->login($playerName, $this->getPasswordString($playerName));
				if ( ! $rlt['success']) {
					return array(
						'success' => false,
					);
				}
			}

			$playerName 	= $this->getGameUsernameByPlayerUsername($playerName);
			$transaction_id = random_string('alnum');

			$context = array(
				'callback_obj' 		=> $this,
				'callback_method' 	=> 'processResultForDepositToGame',
				'playerName' 		=> $playerName,
			);

			$params = array(
				'operatorKey' 	=> $this->operator_key,		# true	字符串类型：字母(区分大小写)、数字，或者两者组合	运营商Key由GMS后台定义，例如OperatorName01
				'username'		=> $playerName,				# true	字符串类型：字母(区分大小写)、数字、中文，特殊字符，或者任意两者或三者组合	账号登录用户名 ，长度范围为[5，20]
				'currency'		=> $this->currency,			# true	字符串类型：大写字母，范围Currency Standard: ISO 4217, e.g. USD	玩家币种，币种标准：ISO 4217币种代码（Currency Code）
				'amount'		=> $amount,					# true	浮点数类型：双精度实型（double）范围，大于0	玩家充值金额
				'transactionID'	=> $transaction_id,			# true	字符串类型：字母、数字、特殊字符（“-”、“_”）几者组合	交易id，来自于GMS系统，运营商需要确保transaction id在Inteplay后台和运营商系统的唯一性
			);

			return $this->callApi(self::API_depositToGame, $params, $context);

		}

		public function processResultForDepositToGame($params) {

			$responseResultId 	= $this->getResponseResultIdFromParams($params);
			$resultJson 		= $this->getResultJsonFromParams($params);
			$playerName 		= $this->getVariableFromContext($params, 'playerName');
			$success 			= $this->processResultBoolean($responseResultId, $resultJson, $playerName);

			return array($success, array(
				'external_transaction_id' => null,
			));

		}

	# END DEPOSIT TO GAME #################################################################################################################################



	# START WITHDRAW FROM GAME #################################################################################################################################

		// {"code":0,"message":"Normal"}

		public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {

			$rlt = $this->checkLoginStatus($playerName);
			if ( ! $rlt['success']) {
				$rlt = $this->login($playerName, $this->getPasswordString($playerName));
				if ( ! $rlt['success']) {
					return array(
						'success' => false,
					);
				}
			}

			$playerName 	= $this->getGameUsernameByPlayerUsername($playerName);
			$transaction_id = random_string('alnum');

			$context = array(
				'callback_obj' 		=> $this,
				'callback_method' 	=> 'processResultForWithdrawFromGame',
				'playerName' 		=> $playerName,
			);

			$params = array(
				'operatorKey' 	=> $this->operator_key,		# true	字符串类型：字母(区分大小写)、数字，或者两者组合	运营商Key由GMS后台定义，例如OperatorName01
				'username'		=> $playerName,				# true	字符串类型：字母(区分大小写)、数字、中文，特殊字符，或者任意两者或三者组合	账号登录用户名 ，长度范围为[5，20]
				'currency'		=> $this->currency,			# true	字符串类型：大写字母，范围Currency Standard: ISO 4217, e.g. USD	玩家币种，币种标准：ISO 4217币种代码（Currency Code）
				'amount'		=> $amount,					# true	浮点数类型：双精度实型（double）范围，大于0	玩家取现金额
				'transactionID'	=> $transaction_id,			# true	字符串类型：字母、数字、特殊字符（“-”、“_”）几者组合	交易id，来自于GMS系统，运营商需要确保transaction id在Inteplay后台和运营商系统的唯一性
			);

			return $this->callApi(self::API_withdrawFromGame, $params, $context);

		}

		public function processResultForWithdrawFromGame($params) {

			$responseResultId 	= $this->getResponseResultIdFromParams($params);
			$resultJson 		= $this->getResultJsonFromParams($params);
			$playerName 		= $this->getVariableFromContext($params, 'playerName');
			$success 			= $this->processResultBoolean($responseResultId, $resultJson, $playerName);

			return array($success, array(
				'external_transaction_id' => null,
			));

		}

	# END WITHDRAW FROM GAME #################################################################################################################################



	# START IS PLAYER EXIST #################################################################################################################################

		public function isPlayerExist($playerName) {

			$playerName = $this->getGameUsernameByPlayerUsername($playerName);

			$context = array(
				'callback_obj' 		=> $this,
				'callback_method' 	=> 'processResultForIsPlayerExist',
				'playerName' 		=> $playerName,
			);

			$params = array(
				'operatorKey' 	=> $this->operator_key,		# true	字符串类型：字母(区分大小写)、数字，或者两者组合	运营商Key由GMS后台定义，例如OperatorName01
				'username'		=> $playerName,				# true	字符串类型：字母(区分大小写)、数字、中文，特殊字符，或者任意两者或三者组合	账号登录用户名 ，长度范围为[5，20]
			);

			return $this->callApi(self::API_queryPlayerBalance, $params, $context);

		}

		public function processResultForIsPlayerExist($params) {

			$responseResultId 	= $this->getResponseResultIdFromParams($params);
			$resultJson 		= $this->getResultJsonFromParams($params);
			$playerName 		= $this->getVariableFromContext($params, 'playerName');
			$success 			= $this->processResultBoolean($responseResultId, $resultJson, $playerName);

			return array((bool) $resultJson, array(
				'exists' => $success,
			));

		}

	# END IS PLAYER EXIST #################################################################################################################################

	# START SYNC ORIGINAL GAME LOGS #################################################################################################################################

	public function syncOriginalGameLogs($token) {

		$currentPage = self::START_PAGE;
		$syncId = parent::getValueFromSyncInfo($token, 'syncId');
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
		$startDate->modify($this->getDatetimeAdjust());
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$endDate = new DateTime($endDate->format('Y-m-d H:i:s'));
		$this->CI->utils->debug_log('from', $startDate, 'to', $endDate);

		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForSyncGameRecords',
			'syncId' 			=> $syncId,
		);

		do {

			$rlt = $this->callApi(self::API_syncGameRecords, array(
				'pageSize'			=> 10,
				'pageNumber'		=> $currentPage,
				'operatorKey'		=> 'Casino1',
				'playname'			=> null,
				'gameKey'			=> null,
				'gameSetKey'		=> null,
				'provider'			=> null,
				'status'			=> null,
				'startDate'			=> $startDate->format('Y-m-d H:i:s'),
				'endDate'			=> $endDate->format('Y-m-d H:i:s'),
				'orderByColumn'		=> 'create_time',
				'orderAscDesc'		=> 'DESC',
				'isPlayerCurrency'	=> 1,
			), $context);

			$success = $rlt && $rlt['success'];

			if ($success) {
				$total_pages = $rlt['totalPages'];
				if ($currentPage < $total_pages) {
					$this->CI->utils->debug_log('page', $currentPage++, 'total_pages', $total_pages, 'done', false, 'result', $rlt);
					continue;
				} else {
					$this->CI->utils->debug_log('page', $currentPage++, 'total_pages', $total_pages, 'done', true, 'result', $rlt);
					break;
				}
			}

		} while(TRUE);

		return array('success' => $success);
	}

	public function processResultForSyncGameRecords($params) {
		$this->CI->load->model('inteplay_game_logs');

		$params['resultText'] = urldecode($params['resultText']);

		$responseResultId 	= $this->getResponseResultIdFromParams($params);
		$resultJson 		= $this->getResultJsonFromParams($params);

		$gameRecords = $resultJson['pageList'];
		if ($gameRecords) {

			$availableRows = $this->CI->inteplay_game_logs->getAvailableRows($gameRecords);
			$this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords));

			foreach ($availableRows as $record) {
				$this->CI->inteplay_game_logs->insertInteplayGameLogs($record);
			}
		}

		$success = $this->processResultBoolean($responseResultId, $resultJson, NULL);
		$result['totalPages'] = $resultJson['pageCount'];

		return array($success, $result);
	}

	# END SYNC ORIGINAL GAME LOGS #################################################################################################################################



	# START SYNC MERGE TO GAME LOGS #################################################################################################################################

	public function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'inteplay_game_logs'));

		$dateTimeFrom 	= clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo 	= clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		$rlt = array('success' => true);
		$result = $this->CI->inteplay_game_logs->getInteplayGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));

		$cnt = 0;
		if ($result) {

			$unknownGame = $this->getUnknownGame();

			foreach ($result as $inteplay_data) {

				if ( ! $player_id = $this->getPlayerIdInGameProviderAuth($inteplay_data->playname)) continue;

				$cnt++;

				$player = $this->CI->player_model->getPlayerById($player_id);

				$bet_amount 			= $this->gameAmountToDB($inteplay_data->totalBet);
				$result_amount 			= $this->gameAmountToDB( - $inteplay_data->totalWinLose);
				$game_description_id 	= $inteplay_data->game_description_id;
				$game_type_id 			= $inteplay_data->game_type_id;

				if (empty($game_description_id)) {
					$game_description_id 	= $unknownGame->id;
					$game_type_id 			= $unknownGame->game_type_id;
				}

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$inteplay_data->game_code,
					$game_type_id,
					$inteplay_data->gameKey,
					$player_id,
					$player->username,
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$inteplay_data->gameSetKey,
					$inteplay_data->createTimeStr,
					$inteplay_data->createTimeStr,
					null # response_result_id
				);

			}
		}
		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

		return $rlt;
	}

	# START SYNC MERGE TO GAME LOGS #################################################################################################################################


	# IMPROVISED / UTILS

	public function checkLoginStatus($playerName) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForCheckLoginStatus',
			'playerName' 		=> $playerName,
		);

		return $this->callApi(self::API_checkLoginStatus, array(
			'operatorKey' 	=> $this->operator_key,		# true	字符串类型：字母(区分大小写)、数字，或者两者组合	运营商Key由GMS后台定义，例如OperatorName01
			'username'		=> $playerName,				# true	字符串类型：字母(区分大小写)、数字、中文，特殊字符，或者任意两者或三者组合	账号登录用户名 ，长度范围为[5，20]
		), $context);
	}

	public function processResultForCheckLoginStatus($params) {

		$responseResultId 	= $this->getResponseResultIdFromParams($params);
		$resultJson 		= $this->getResultJsonFromParams($params);
		$playerName 		= $this->getVariableFromContext($params, 'playerName');
		$success 			= $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		return array($success, $success ? array(
			'session_key' => $resultJson['sessionKey'],
		) : null);
	}

	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$result = $this->blockUsernameInDB($playerName);
		return array("success" => $result);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$result = $this->unblockUsernameInDB($playerName);
		return array("success" => $result);
	}

	public function queryForwardGame($playerName, $extra) {

		$rlt = $this->checkLoginStatus($playerName);
		if ( ! $rlt['success']) {
			$rlt = $this->login($playerName, $this->getPasswordString($playerName));
			if ( ! $rlt['success']) {
				return array(
					'success' => false,
				);
			}
		}

		$session_key 	= $rlt['session_key'];
		$game_id 		= $extra['game_id'];
		$language 		= $extra['language'];

		// $url = sprintf('%s/GameLobby/game.htm?operatorKey=%s&gameId=%s&sessionKey=%s&language=%s', $this->api_url, $this->operator_key, $game_id, $session_key, $language);

		return array(
			'success' => true,
			'url' => $this->api_url . '/GameLobby/game.htm',
			'params' => array(
				'operatorKey' 	=> $this->operator_key,
				'gameId' 		=> $game_id,
				'sessionKey' 	=> $session_key,
				'language' 		=> $language,
			),
		);
	}

	public function gameAmountToDB($amount) {
		$amount = floatval($amount);
		return round($amount, 2);
	}

	# NOT IMPLEMENTED ##############################################################################################################################################################################

	public function updatePlayerInfo($playerName, $infos) {
		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return $this->returnUnimplemented();
	}

	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return $this->returnUnimplemented();
	}

	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return $this->returnUnimplemented();
	}

	public function checkLoginToken($playerName, $token) {
		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return $this->returnUnimplemented();
	}

	public function totalBettingAmount($playerName, $dateFrom, $dateTo) {
		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra) {
		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return $this->returnUnimplemented();
	}

}
