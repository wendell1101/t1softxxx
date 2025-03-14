<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Gets platform code
 * * Login/logout to the website
 * * Create Player
 * * Update Player's info
 * * Delete Player
 * * Block/Unblock Player
 * * Deposit to Game
 * * Withdraw from Game
 * * Check Player's balance
 * * Check Game Records
 * * Computes Total Betting Amount
 * * Check Transaction
 * * Check Forward Game
 * * Synchronize Original Game Logs
 * * Get BBIN Records
 * * Extract xml record
 * * Synchronize Game Records
 * * Check Player's Balance
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */


/* ===========  MTECH Game Provider List ===========================
	id 		game 					platform type
	1 		PlayTech 				Slots, lives
	2 		MicroGame 				Slots, lives
	3 		KaiyuanGaming 			Video cards with players
	4 		PocketGames 			Slots
	5 		OneTouch 				Video table
	6 		IBC 					Sport
	7 		PragmaticPlay 			Slots
	8 		Habanero 				Slots
	9 		OrientalGame 			Lives
	11		BBIN 					Slots, lives, Sport, Lottery
	================================================================= */

abstract class Game_api_common_mtech extends Abstract_game_api {

	public $api_url;
	public $operator_code;
	public $secret_key;
	public $extraparameter;

    const BBIN_GAME_KINDS = [
        "sports" => 109, // as per game provider, game kind id 1 is no longer used. use gamekind=109 to get datas of BB sport.
        "live" => 3,
        "slots" => 5,
        "lottery" => 12,
        "newbbsports" => 20,
        "fishhunter" => 21,
        "fishmaster" => 22,
        // "bbtips" => 99, as per game provider, 99 is not a valid game kind id
        "xbblive" => 75,
        "xbbcasino" => 76,
        "bbpcasino" => 107
        // "jackpot" => "JP",
    ];
    const NEW_VERSION_BBSPORTS = 31;
    const NEW_VERSION_FISHHUNTER = 30;
    const NEW_VERSION_FISHMASTER = 38;

    const BBIN_LOTTERY_GAME_TYPES = ["OTHER", "LT"];
    const BBIN_SLOTS_SUB_GAME_KIND = ["1","2","3","5"];
    const BBIN_LIVE_GAME_CODES = ['live','xbb'];

    const SUCCESS = 0;
    const ERROR_CODE_USER_EXIST = 12;

	public function __construct() {
		parent::__construct();

		$this->api_url 			= $this->getSystemInfo('url','https://api.mtechgame.com/generic');
		$this->operator_code 	= $this->getSystemInfo('operator_code');
		$this->secret_key 		= $this->getSystemInfo('secret_key');
        $this->extraparameter   = $this->getSystemInfo('extraparameter');
        $this->record_per_page  = $this->getSystemInfo('record_per_page', 500);
        $this->use_updated_check_transfer_status = $this->getSystemInfo('use_updated_check_transfer_status', false);
        $this->sleep_time = $this->getSystemInfo('sleep_time', 60);
        $this->new_version = $this->getSystemInfo('new_version', false);
	}

	public function getPlatformCode() {
		return $this->returnUnimplemented();
	}

	public function getMTechGameProviderId() {
		return $this->returnUnimplemented();
	}

	public function generateUrl($apiName, $params) {
		return $this->api_url;
	}

	protected function customHttpCall($ch, $params) {
		$data_string = json_encode($params);
        // echo $data_string;exit;
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data_string))
		);
    }

	public function processResultBoolean($responseResultId, $resultJson, $playerName = null, $callType = null) {
		$success = !empty($resultJson) && empty($resultJson['ErrorCode']);

        if( ($callType == "createplayer" && $resultJson['ErrorCode'] == self::SUCCESS) || $resultJson['ErrorCode'] == self::ERROR_CODE_USER_EXIST) {
            $success = true;
        }

        if ($success && !empty($callType) && $callType == "gamehistory") {
            $success = !isset($resultJson["Params"]["data"]["Code"]) || empty($resultJson["Params"]["data"]["Code"]);
        }

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log("========== MTECH API GOT ERROR (".$this->getPlatformCode().") =============", $resultJson['ErrorMessage'], $playerName);
		}

		return $success;
	}

    public function md5ToUpper($string) {
        return strtoupper(md5($string));
    }

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'gameUsername' => $gameUsername
        );

		$sign = $this->md5ToUpper($this->operator_code."&".$password."&".$gameUsername."&".$this->secret_key);

		$params = array(
			'gameprovider' =>  $this->getMTechGameProviderId(),
            'command' =>  "CREATE_ACCOUNT",
            'sign' =>  $sign,
            'params' =>  [
            	'username' =>  $gameUsername,
            	'operatorcode' =>  $this->operator_code,
            	'password' =>  $password,
            ]
        );

        if(!empty($this->extraparameter)){
            $params['params']['extraparameter'] = $this->extraparameter;
        }

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName, 'createplayer');

        $result = array(
            'player' => $gameUsername,
            'exists' => false
        );

        if($success){
            # update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result['exists'] = true;
        }

		return array($success, $result);
	}

	public function changePassword($playerName, $oldPassword, $newPassword) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);
		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForChangePassword',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'newPassword' => $newPassword,
            'gameUsername' => $gameUsername
        );

		$sign = $this->md5ToUpper($this->operator_code."&".$gameUsername."&".$this->secret_key);
		$this->extraparameter["newpassword"] = $newPassword;

		$params = array(
			'gameprovider' =>  $this->getMTechGameProviderId(),
            'command' =>  "CHANGE_PLAYER_INFO",
            'sign' =>  $sign,
            'params' =>  [
            	'username' =>  $gameUsername,
            	'operatorcode' =>  $this->operator_code,
            	'extraparameter' =>  [
            		'newpassword' => $newPassword,
            	],
            ]
        );

        if(!empty($this->extraparameter)){
            $params['params']['extraparameter'] = $this->extraparameter;
        }

		return $this->callApi(self::API_changePassword, $params, $context);
    }

    function processResultForChangePassword($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $newPassword = $this->getVariableFromContext($params, 'newPassword');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
        if ($success) {
            $this->updatePasswordForPlayer($playerId, $newPassword);
        }
		return array($success, $resultJsonArr);
	}


	public function isPlayerExist($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($gameUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $sign = $this->md5ToUpper($this->operator_code."&".$password."&".$gameUsername."&".$this->secret_key);

		$params = array(
			'gameprovider' =>  $this->getMTechGameProviderId(),
            'command' =>  "GET_BALANCE",
            'sign' =>  $sign,
            'params' =>  [
            	'username' =>  $gameUsername,
            	'operatorcode' =>  $this->operator_code,
            	'password' =>  $password,
            ]
        );

        if(!empty($this->extraparameter)){
            $params['params']['extraparameter'] = $this->extraparameter;
        }

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        if (empty($resultJsonArr)) {
            $result['exists'] = null;
        } else {
            $success = true;
            $result['exists'] = (bool) empty($resultJsonArr['ErrorCode']);
        }

		return array($success, $result);
    }

	public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($gameUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $sign = $this->md5ToUpper($this->operator_code."&".$password."&".$gameUsername."&".$this->secret_key);

		$params = array(
			'gameprovider' =>  $this->getMTechGameProviderId(),
            'command' =>  "GET_BALANCE",
            'sign' =>  $sign,
            'params' =>  [
            	'username' =>  $gameUsername,
            	'operatorcode' =>  $this->operator_code,
            	'password' =>  $password,
            ]
        );

        if(!empty($this->extraparameter)){
            $params['params']['extraparameter'] = $this->extraparameter;
        }

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        if ($success) {
        	// $result['balance'] = $resultJsonArr["Params"]["Balance"];
            $balance = $resultJsonArr["Params"]["Balance"];
            $result['balance'] = floatval($this->gameAmountToDB($balance));
        }

		return array($success, $result);
    }


	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($gameUsername);
        $external_trans_id = $this->getSecureId('transfer_request', 'secure_id', false, null);
        $amount = $this->dBtoGameAmount($amount);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'amount'=>$amount,
            'external_transaction_id' => $external_trans_id,
        );

        $sign = $this->md5ToUpper($amount."&".$this->operator_code."&".$password."&".$external_trans_id."&".$gameUsername."&".$this->secret_key);

		$params = array(
			'command' =>  "DEPOSIT",
			'gameprovider' =>  $this->getMTechGameProviderId(),
            'sign' =>  $sign,
            'params' =>  [
            	'username' =>  $gameUsername,
            	'password' =>  $password,
            	'operatorcode' =>  $this->operator_code,
            	'serialno' =>  $external_trans_id,
            	'amount' =>  $amount,
            ]
        );

        if(!empty($this->extraparameter)){
            $params['params']['extraparameter'] = $this->extraparameter;
        }

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $amount = $this->getVariableFromContext($params, 'amount');
        $resultJsonArr = $this->getResultJsonFromParams($params);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        $result = array(
        	'response_result_id' => $responseResultId,
        	'external_transaction_id' => $external_transaction_id,
            'reason_id' => self::REASON_UNKNOWN,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN
        );

        if($success) {
            $result['didnot_insert_game_logs']=true;
            $success = true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            //try add reason id
        }
        return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($gameUsername);
        $external_trans_id = $this->getSecureId('transfer_request', 'secure_id', false, null);
        $amount = $this->dBtoGameAmount($amount);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'amount' => $amount,
            'external_transaction_id' => $external_trans_id,
        );

        $sign = $this->md5ToUpper($amount."&".$this->operator_code."&".$password."&".$external_trans_id."&".$gameUsername."&".$this->secret_key);

		$params = array(
			'gameprovider' =>  $this->getMTechGameProviderId(),
			'command' =>  "WITHDRAW",
            'sign' =>  $sign,
            'params' =>  [
            	'username' =>  $gameUsername,
            	'password' =>  $password,
            	'operatorcode' =>  $this->operator_code,
            	'serialno' =>  $external_trans_id,
            	'amount' =>  $amount,
            ]
        );

        if(!empty($this->extraparameter)){
            $params['params']['extraparameter'] = $this->extraparameter;
        }

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $amount = $this->getVariableFromContext($params, 'amount');
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
        $result = array(
        	'response_result_id' => $responseResultId,
        	'external_transaction_id'=>$external_transaction_id,
            'reason_id'=>self::REASON_UNKNOWN,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN
        );

        if($success) {
            $result['didnot_insert_game_logs']=true;
            $success = true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            //try add reason id
        }
        return array($success, $result);
    }

    public function queryForwardGame($playerName, $extra=null) {
        $result = $this->login($playerName, $extra);
        $this->CI->utils->debug_log("========== MTECH API RESPONSE (".$this->getPlatformCode().") =============", $result);
        $url = null;

        if ($result['success']) {
            $url = $result["Params"]["url"];
        }

        return array(
            'success' => $result['success'],
            'url' => $result["Params"]["url"]
        );
    }

    public function login($playerName, $extra = null) {
        $this->CI->load->model(array('player_model'));

        if(isset($extra['game_mode'])){
            $extra["is_fun_game"] = strtolower($extra['game_mode']) != "real";
        }
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($gameUsername);

        $playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);

        $this->createPlayer($playerName, $playerId, $password); // to create if no game account

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $sign = $this->md5ToUpper($this->operator_code."&".$password."&".$gameUsername."&".$this->secret_key);

		$params = array(
			'gameprovider' =>  $this->getMTechGameProviderId(),
			'command' =>  "LOGIN",
            'sign' =>  $sign,
            'params' =>  [
            	'username' =>  $gameUsername,
            	'password' =>  $password,
            	'operatorcode' =>  $this->operator_code,
            	'gamecode' =>  !empty($extra["game_type"]) ? $extra["game_type"] : "",
            	'isfreegame' =>  (bool) $extra["is_fun_game"],
            	'ismobile' =>  (bool) $extra["is_mobile"],
            	'language' =>  $this->getGameLaunchLanguage($extra["language"]),
            ]
        );

        if(!empty($this->extraparameter)){
            $params['params']['extraparameter'] = $this->extraparameter;

            if(in_array($params['params']['gamecode'], self::BBIN_LIVE_GAME_CODES)){
                $params['params']['extraparameter']['gametype'] = isset($extra['game_code']) ? $extra['game_code'] : '';
                $params['params']['extraparameter']['game'] = isset($extra['game']) ? $extra['game'] : '';
            }
        }

        if ($this->getPlatformCode() == MTECH_HB_API) {
            $params['params']['gamecode'] = !empty($extra["game_code"])?$extra["game_code"]:"";
        }

        return $this->callApi(self::API_login, $params, $context);
    }

    public function getGameLaunchLanguage($lang) {
        switch (strtolower($lang)) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en-us':
                return "en";
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn-zh':
            case 'zh-cn':
                return "CN";
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id-id':
                return "id";
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi-vi':
            case 'vi-vn':
                return "vi";
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
                return "kr";
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
                return "th";
                break;
            default:
                return "en";
                break;
        }
    }

    public function processResultForLogin($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        return array($success, $resultJsonArr);
    }

    public function syncOriginalGameLogs($token = false) {
        $isAdjustDateTime = false;
        $dates = array();


        $startDate          = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate            = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDate          = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate            = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate->modify($this->getDatetimeAdjust());
        $syncStartDate    = $startDate->format('Y-m-d H:i:s');
        $syncEndDate      = $endDate->format('Y-m-d H:i:s');

        # for bbin mtech game api
        if ($this->getPlatformCode() == MTECH_BBIN_API) {
            $syncStartTime    = $startDate->format('H:i:s');
            $syncEndTime      = $endDate->format('H:i:s');
            $dates = $this->CI->utils->dateRange($this->CI->utils->formatDateForMysql($startDate), $this->CI->utils->formatDateForMysql($endDate));
            $result = [];

            if (empty($dates)) {
                return;
            }

            $minDate = min($dates);
            $maxDate = max($dates);

            # tag set date adjustment to true is multiple date range
            if (count($dates) > 1) {
                $isAdjustDateTime = true;
            }

            foreach ($dates as $date) {
                if ($isAdjustDateTime) {
                    $syncStartDate    = $date . " 00:00:00";
                    $syncEndDate      = $date . " 23:59:59";

                    if ($date == $minDate) {
                        $syncStartDate = $date . " " .$syncStartTime;
                    }

                    if ($date == $maxDate) {
                        $syncEndDate = $date . " " .$syncEndTime;
                    }
                }

                if (!empty(self::BBIN_GAME_KINDS)) {
                    foreach (self::BBIN_GAME_KINDS as $game_kind) {
                        # set game type bbin lottery
                        if ($game_kind == self::BBIN_GAME_KINDS["lottery"]) {
                            foreach (self::BBIN_LOTTERY_GAME_TYPES as $lottery_game_type) {
                                $result[] = $this->_continueSync($syncStartDate, $syncEndDate, $game_kind, $lottery_game_type);
                                sleep($this->sleep_time);
                            }
                        } else if($game_kind == self::BBIN_GAME_KINDS["slots"]) {
                            foreach (self::BBIN_SLOTS_SUB_GAME_KIND as $slots_sub_game_kind) {
                                $result[] = $this->_continueSync($syncStartDate, $syncEndDate, $game_kind, null, $slots_sub_game_kind);
                                sleep($this->sleep_time);
                            }

                        } else {
                            $result[] = $this->_continueSync($syncStartDate, $syncEndDate, $game_kind);
                            sleep($this->sleep_time);
                        }
                    }
                }
            }

            $result['success'] = true;
            return $result;
        }

        return $this->_continueSync($syncStartDate, $syncEndDate);

    }

    public function _continueSync($startDate, $endDate, $gameKind = null, $gameType = null, $subGameKind = null, $page = 1) {
        $result = $this->syncMTechOriginalLogs($startDate, $endDate, $gameKind, $gameType, $subGameKind, $page);
        $this->CI->utils->debug_log("========== MTECH API (".$this->getPlatformCode().") syncMTechOriginalLogs  result =============", json_encode($result));

        if ($this->getPlatformCode() == MTECH_BBIN_API) {
            if ($result['success'] && ($page <= $result['total_pages'])) {
                sleep($this->sleep_time);
                return $this->_continueSync($startDate, $endDate, $gameKind, $gameType, $subGameKind, $page);
            }
        }

        return $result;
    }

    public function syncMTechOriginalLogs($startDate, $endDate, $gameKind, $gameType, $subGameKind, &$page) {
        if($this->new_version){
            if($gameKind == self::BBIN_GAME_KINDS["newbbsports"]){
                $gameKind = self::NEW_VERSION_BBSPORTS;
            }
            if($gameKind == self::BBIN_GAME_KINDS["fishhunter"]){
                $gameKind = self::NEW_VERSION_FISHHUNTER;
            }
            if($gameKind == self::BBIN_GAME_KINDS["fishmaster"]){
                $gameKind = self::NEW_VERSION_FISHMASTER;
            }
        }
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'game_kind' => $gameKind,
        );

        $sign = $this->md5ToUpper($this->operator_code."&".$this->secret_key);

        $params = array(
            'gameprovider' =>  $this->getMTechGameProviderId(),
            'command' =>  "GET_GAME_DATA",
            'sign' =>  $sign,
            'params' =>  [
                'operatorcode' =>  $this->operator_code,
                'extraparameter' =>  $this->extraparameter,
            ]
        );

        $params['params']['extraparameter']['count'] = $this->record_per_page;
        $params['params']['extraparameter']['page'] = $page;
        $params['params']['extraparameter']['startdate'] = $startDate;
        $params['params']['extraparameter']['enddate'] = $endDate;

        if ($this->getPlatformCode() == MTECH_BBIN_API) {
            $params['params']['extraparameter']['gamekind'] = $gameKind;
            $params['params']['extraparameter']['gametype'] = $gameType;
            $params['params']['extraparameter']['subgamekind'] = $subGameKind;
        }

        if ($this->getPlatformCode() == MTECH_OG_API) {
            $last_sync_id = $this->CI->external_system->getLastSyncId($this->getPlatformCode());
            $vendor_id = (!empty($last_sync_id)) ? $last_sync_id : 0;
            $params['params']['extraparameter']['id'] = $vendor_id;
        }

        $page++;
        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model('original_game_logs_model');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, null, 'gamehistory');

        $extra = [
            "success" => $success,
            "response_result_id" => $responseResultId,
        ];

        if ($this->getPlatformCode() == MTECH_BBIN_API) {
            $gameKind = $this->getVariableFromContext($params, 'game_kind');
            $extra["game_kind"] = $gameKind;
        }

        $result = $this->syncOriginalGameLogsToDB($resultJsonArr, $extra);

        return array($success, $result);
    }

    abstract public function syncOriginalGameLogsToDB($resultJsonArr, $extra = null);

    public function queryTransaction($transactionId, $extra) {
        $playerName = $extra['playerName'];
        $playerId = $extra['playerId'];

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($gameUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'playerId'=>$playerId,
            'external_transaction_id' => $transactionId
        );

        $sign = $this->md5ToUpper($this->operator_code."&".$password."&".$transactionId."&".$gameUsername."&".$this->secret_key);

        $params = array(
            'gameprovider' =>  $this->getMTechGameProviderId(),
            'command' =>  "CHECK_TRANSFER_STATUS",
            'sign' =>  $sign,
            'params' =>  [
                'username' =>  $gameUsername,
                'operatorcode' =>  $this->operator_code,
                'password' =>  $password,
                'serialNo' =>  $transactionId,
            ]
        );

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $transactionId = $this->getVariableFromContext($params, 'external_transaction_id');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr);

        $this->CI->utils->debug_log("========== MTECH API QUERY REPONSE (".$this->getPlatformCode().") =============", $resultJsonArr, 'transaction id', $transactionId);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$transactionId,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if ($this->use_updated_check_transfer_status){
            if($success) {
                if ($resultJsonArr['Params']['status'] == 'Successful'){
                    $this->CI->utils->debug_log("========== MTECH check_transfer_status success");
                    $result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
                } else if ($resultJsonArr['Params']['status'] == 'Failed') {
                    $this->CI->utils->debug_log("========== MTECH check_transfer_status failed");
                    $result['reason_id'] = $this->getErrorCode($resultJsonArr['ErrorCode']);
                    $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                } else if ($resultJsonArr['Params']['status'] == 'Not Found') {
                    $this->CI->utils->debug_log("========== MTECH check_transfer_status not exist");
                    $result['reason_id'] = $this->getErrorCode($resultJsonArr['ErrorCode']);
                    $result['status'] = self::REASON_INVALID_TRANSACTION_ID; //transfer not exist
                } else if ($resultJsonArr['Params']['status'] == 'Pending') {
                    $this->CI->utils->debug_log("========== MTECH check_transfer_status pending");
                    $result['reason_id'] = $this->getErrorCode($resultJsonArr['ErrorCode']);
                    $result['status'] = self::COMMON_TRANSACTION_STATUS_PROCESSING;
                }
            } else {
                $this->CI->utils->debug_log("========== MTECH check_transfer_status failed");
                $result['reason_id'] = $this->getErrorCode($resultJsonArr['ErrorCode']);
                $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
        }

        if($success) {
            $result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $result['reason_id'] = $this->getErrorCode($resultJsonArr['ErrorCode']);
            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }

    private function getErrorCode($apiErrorCode) {
        $reasonCode = self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;

        switch ((int)$apiErrorCode) {
            case 9:
                $reasonCode = self::REASON_INVALID_TRANSFER_AMOUNT;
            case 13:
                $reasonCode = self::REASON_NOT_FOUND_PLAYER;
            case 14:
                $reasonCode = self::REASON_DUPLICATE_TRANSFER;
            case 15:
                $reasonCode = self::REASON_DUPLICATE_TRANSFER;
                break;
        }

        return $reasonCode;
    }

    #Support Gameprovider:
    #6 - IBC、9 - OrientalGame
    public function updatePlayerBetLimitGroup($gameprovider, $video_roulette_limit_id, $type){
        $this->CI->load->model('game_provider_auth');
        $gameUsername = $this->CI->game_provider_auth->getAllGameRegisteredUsernames($this->getPlatformCode());
        $cnt = 0;
        foreach($gameUsername as $username) {
            $this->updatePlayerBetLimit($gameprovider, $username, $video_roulette_limit_id, $type);
            $cnt ++;
        }
        return "Count ".$cnt;

    }

    public function updatePlayerBetLimit($gameprovider, $gameUsername, $video_roulette_limit_id=null, $type=null) {

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForUpdatePlayerBetLimitGroup',
            'gameUsername' => $gameUsername
        );

        $sign = $this->md5ToUpper($this->operator_code."&".$gameUsername."&".$this->secret_key);
        $this->extraparameter["limit"] = $video_roulette_limit_id;
        $this->extraparameter["type"] = $type;

        $params = array(
            'gameprovider' =>  $gameprovider,
            'command' =>  "CHANGE_WIN_LIMIT",
            'sign' =>  $sign,
            'params' =>  [
                'username' =>  $gameUsername,
                // 'password' =>  $password,
                'operatorcode' =>  $this->operator_code,
                'extraparameter' =>  [
                    'limit' => $video_roulette_limit_id,
                    'type' => $type, // 1 = video, 2 = roulette
                ],
            ]
        );

        if(!empty($this->extraparameter)){
            $params['params']['extraparameter'] = $this->extraparameter;
        }

        $this->CI->utils->debug_log('updatePlayerBetLimit ==========================>', $params);
        return $this->callApi(self::API_updatePlayerInfo, $params, $context);
    }

    public function processResultForUpdatePlayerBetLimitGroup($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        $this->CI->utils->debug_log('processResultForUpdatePlayerBetLimitGroup ==========================>', $resultArr);
        if ($resultArr['ErrorCode'] == 0 && $resultArr['Params']['success'] == true){
            return array($success, $resultArr);
        } else {
            $myfile = fopen("logs.txt", "a") or die("Unable to open file!");
            $txt = $gameUsername.', '.$resultArr['ErrorMessage'];
            fwrite($myfile, "\n". $txt);
            fclose($myfile);
        }

    }


    public function syncMergeToGameLogs($token) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    public function updatePlayerInfo($playerName, $infos) {
        return $this->returnUnimplemented();
    }

    public function logout($playerName, $password = null) {
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



    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return array(false, null);
    }

    public function onlyTransferPositiveInteger(){
        if ($this->getPlatformCode() == MTECH_BBIN_API) {
            return true;
        }
        return false;
    }


}

/*end of file*/
