<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * PGsoft Seamless Integration
 * OGP-24231
 * ? uses pgsoft_seamless_game_service_api for its service API
 *
 * Game Platform ID: 6009
 *
 */

abstract class Abstract_game_api_common_pgsoft_seamless extends Abstract_game_api {
    public $original_transactions_table;
    public $secret_key;
    public $operator_token;
    public $currency;
    public $language;
    public $game_list_language;
    public $game_list_status;
    public $method;

	const POST = 'POST';
	const GET = 'GET';
	const PUT = 'PUT';

    public function __construct() {
        parent::__construct();

		$this->api_url = $this->getSystemInfo('url');
        $this->api_domain = $this->api_url . '/external';
        $this->data_grab_api_domain = $this->api_url . '/external-datagrabber';
		$this->public_url = $this->getSystemInfo('public_url');
		$this->launch_url = $this->getSystemInfo('launch_url');
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+5 minutes');
        $this->currency = $this->getSystemInfo('currency');
        $this->language = $this->getSystemInfo('language', 'en');

        $this->operator_name = $this->getSystemInfo('operator_name', null);
        $this->operator_token = $this->getSystemInfo('operator_token', null);
        $this->secret_key = $this->getSystemInfo('operator_key', null);
        $this->operator_salt = $this->getSystemInfo('operator_salt', null);

        $this->enable_home_link = $this->getSystemInfo('enable_home_link', true);
        $this->home_link = $this->getSystemInfo('home_link');

        $this->launch_using_api_login = $this->getSystemInfo('launch_using_api_login', true);

        $this->lang = $this->getSystemInfo('lang','en_US');
        $this->force_lang = $this->getSystemInfo('force_lang', false);

        $this->bet_history_url = $this->getSystemInfo('bet_history_url', null);
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');


        $this->URI_MAP = array(
            self::API_login => '/Login/v1/LoginGame',
            self::API_createPlayer => '/Player/v1/Create',
            self::API_logout => '/Player/v1/Kick',
            self::API_blockPlayer => '/Player/v1/Suspend',
            self::API_unblockPlayer => '/Player/v1/Reinstate',
            self::API_isPlayerExist => '/Player/v1/Check',
            self::API_queryForwardGame => '/index.html',
            self::API_queryBetDetailLink => '/external/Login/v1/LoginProxy',
            self::API_queryGameListFromGameProvider => '/external/Game/v2/Get',
        );

        $this->URI_MAP_v2 = array(
            self::API_queryForwardGame => '/external-game-launcher/api/v1/GetLaunchURLHTML',
            self::API_queryBetDetailLink => '/external/Login/v1/LoginProxy',
            self::API_queryGameListFromGameProvider => '/external/Game/v2/Get',
        );

        $this->enabled_new_queryforward =$this->getSystemInfo('enabled_new_queryforward', false);
        $this->used_html_on_launching = $this->getSystemInfo('used_html_on_launching', true);

        $this->enabled_queryBetDetailLinkHTML = $this->getSystemInfo('enabled_queryBetDetailLinkHTML', false);
        $this->game_list_language = $this->getSystemInfo('game_list_language', 'en-us'); // Language of data content:en-us: English (Default), zh-cn: Chinese
        $this->game_list_status = $this->getSystemInfo('game_list_status', 1); // Status of games: 0: Inactive, games 1: Active, gamesDefault: All status of games
    }


    const PLAYER_IS_INACTIVE = 0;
    const PLAYER_IS_ACTIVE = 1;
    const PLAYER_IS_SUSPENDED = 3;

    const REAL_GAME = 1;
    const TOURNAMENT_GAME = 3;

    const BET_TYPE_REAL = 'Real game';
    const BET_TYPE_REAL_CODE = 1;

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_ROLLBACK = 'rollback';

    const MD5_FIELDS_FOR_MERGE = [
        'status',
        'updated_at',
        // 'parent_bet_id',
        'round_id',
        'start_at',
        'end_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'amount',
    ];

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return PGSOFT_SEAMLESS_API;
    }

    public function getCurrency() {
        return $this->currency;
    }


    public function generateUrl($apiName, $params) {
		$apiUri = $this->URI_MAP[$apiName];

        //PgSoftAPIDomain
        $url = $this->api_domain . $apiUri;
        if(($this->enabled_new_queryforward && $apiName == self::API_queryForwardGame) || in_array($apiName, [self::API_queryBetDetailLink, self::API_queryGameListFromGameProvider])){
            $guid = trim($this->createGUID(), '{}');
            $url_params = array(
                "trace_id" => $guid
            );
            $url = $this->api_url . $this->URI_MAP_v2[$apiName]. "?". http_build_query($url_params);
        }
        if($this->enabled_queryBetDetailLinkHTML
            && $apiName == self::API_queryBetDetailLink 
            && isset($params['url_type']) 
            && $params['url_type'] == "web-history"){
            $url = $this->api_url . '/external-game-launcher/api/v1/GetLaunchURLHTML?'. http_build_query($params);
        }

		return $url;
	}

	protected function customHttpCall($ch, $params) {
        if(isset($params['url_type']) && $params['url_type'] == "game-entry"){
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        } elseif ($this->method == self::POST){
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);     
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
		// if($this->method == self::POST){
		// 	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		// 	curl_setopt($ch, CURLOPT_POST, true);
		// 	curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($params));
		// 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// }
	}

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $extra = array_merge([
            'prefix' => $this->prefix_for_username,
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
            'check_username_only' => true
        ], is_null($extra) ? [] : $extra);


        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'secret_key' => $this->secret_key,
			'operator_token' => $this->operator_token,
			'player_name' => $gameUsername,
			'nickname' => $gameUsername,
			'currency' => $this->currency
		);
        $this->method = self::POST;

		return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
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

    public function isPlayerExist($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			'secret_key' => $this->secret_key,
			'operator_token' => $this->operator_token,
			'player_name' => $gameUsername
		);

        $this->method = self::POST;

		return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array();

		if($success){
			$result['exists'] = true;
		}else{
			// Player not Exists
			if(isset($resultArr['error']['code'])&&$resultArr['error']['code']==1305){
				$success = true;
				$result['exists'] = false;
			}else{
				$result['exists'] = null;
			}

		}

		return array($success, $result);
    }




    public function blockPlayer($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForBlockPlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			'secret_key' => $this->secret_key,
			'operator_token' => $this->operator_token,
			'player_name' => $gameUsername
		);

        $this->method = self::POST;

		return $this->callApi(self::API_blockPlayer, $params, $context);
    }

    public function processResultForBlockPlayer($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array();

		if($success){
			$result['blocked'] = isset($resultArr['data']['action_result']) && $resultArr['data']['action_result'] == 1;

            if ($result['blocked']){
                $success = $this->blockUsernameInDB($gameUsername);
            }
		}

		return array($success, $result);
    }
    public function unblockPlayer($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUnblockPlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			'secret_key' => $this->secret_key,
			'operator_token' => $this->operator_token,
			'player_name' => $gameUsername
		);

        $this->method = self::POST;

		return $this->callApi(self::API_unblockPlayer, $params, $context);
    }

    public function processResultForUnblockPlayer($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array();

		if($success){
			$result['unblocked'] = isset($resultArr['data']['action_result']) && $resultArr['data']['action_result'] == 1;
            if ($result['unblocked']){
                $success = $this->unblockUsernameInDB($gameUsername);
            }
		}

		return array($success, $result);
    }



    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        $this->method = self::POST;

        return $result;
    }

    public function depositToGame($userName, $amount, $transfer_secure_id = null){
        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id = null){
        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function getLauncherLanguage($language){
        if($this->force_lang){
        	return $this->lang;
        }
        $lang='';
        $language = strtolower($language);
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-US':
            case 'en_US':
            case 'en-us':
            case 'en_us':
                $lang = 'en'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh':
            case 'cn':
            case 'zh-CN':
            case 'zh_CN':
            case 'zh-cn':
            case 'zh_cn':
                $lang = 'zh'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-ID':
            case 'id_ID':
            case 'id-id':
            case 'id_id':
                $lang = 'id';
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vn':
            case 'vi-VN':
            case 'vi_VN':
            case 'vi-vn':
            case 'vi_vn':
                $lang = 'vi';
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko':
            case 'kr':
            case 'ko-KR':
            case 'ko_KR':
            case 'ko-kr':
            case 'ko_kr':
                $lang = 'ko';
                break;
            case Language_function::INT_LANG_INDIA:
            case 'hi':
            case 'hi-IN':
            case 'hi_IN':
            case 'hi-in':
            case 'hi_in':
                $lang = 'hi';
                    break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
            case 'pt-PT':
            case 'pt_PT':
            case 'pt-pt':
            case 'pt_pt':
                $lang = 'pt';
                break;
            case Language_function::INT_LANG_SPANISH:
            case 'es':
            case 'es-ES':
            case 'es_ES':
            case 'es-es':
            case 'es_es':
                $lang = 'es';
                break;
            case Language_function::INT_LANG_JAPANESE:
            case 'ja':
            case 'ja-JA':
            case 'ja_JA':
            case 'ja-ja':
            case 'ja_ja':
            case 'ja-JP':
            case 'ja_JP':
            case 'ja-jp':
            case 'ja_jp':
                $lang = 'ja';
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

    public function queryForwardGame($playerName,$extra=[]) {
        $extra['playerName'] = $playerName;

        if($this->launch_using_api_login){
            $player = $this->login($playerName);
            if(!isset($player['token']) && empty($player['token'])){
                return array(
                    'success'=>true,
                    'url'=> null
                );
            }
            $token = $player['token'];
        }else{
            $this->CI->utils->debug_log('PGsoft Seamless Game API queryForwardGame $player', $player);
            $token = $this->getPlayerTokenByUsername($playerName);
        }

        if(empty($token)){
            return array(
                'success'=>false,
                'url'=> null
            );
        }

        if($this->enabled_new_queryforward){
            return $this->queryForwardGameV2($token, $extra);
        }

        //required params
        $params = [];
        $params['url'] = array(
            'GameId' => $extra['game_code'], //? Unique identity for each game i.e. game_code
        );
		$params['url_parameters'] = array(
			'btt' => $extra['game_mode'] == 'tournament' ? self::TOURNAMENT_GAME : self::REAL_GAME, //? Game launch mode
			'ot' => $this->operator_token, //? Unique identity of operator
			'ops' => $token, //? Token generated by operator system
        );

        //optional params
        if (isset($extra['language']) && !empty($extra['language'])) {
            $params['url_parameters']['l'] = $this->getLauncherLanguage($extra['language']); //? language
        } else {
            $params['url_parameters']['l'] = $this->language;
        }
        if (isset($extra['home_link']) && $this->enable_home_link) {
            $params['url_parameters']['f'] = $extra['home_link']; //? Game exit URL
        }
        
        //extra checking for home link
        if(isset($extra['extra']['home_link'])) {
            $params['url_parameters']['f'] = $extra['extra']['home_link'];
        }


        #remove home link if disable_home_link is set to TRUE 
        if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
            unset($params['url_parameters']['f']);
        }

        $this->method = self::GET;

        $url = $this->launch_url . '/' . $params['url']['GameId'] . $this->URI_MAP[self::API_queryForwardGame] . '?' . http_build_query($params['url_parameters']);

        $this->CI->utils->debug_log('PGsoft QueryForward url', $url, 'extra', $extra, $url, 'params', $params);

        return array(
            'success'=>true,
            'url'=> $url
        );
    }


    public function login($playerName, $password = null, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $player_token = $this->getPlayerTokenByUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);


		$params = array(
			'operator_token' => $this->operator_token,
			'secret_key' => $this->secret_key,
			'player_session' => $player_token,
			'operator_player_session' => $player_token,
			'player_name' => $gameUsername,
			'currency' => $this->currency,
			'nickname' => $playerName,
		);

        $this->method = self::POST;

		return $this->callApi(self::API_login, $params, $context);
	}

    public function processResultForLogin($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);

		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();
		if($success){
            $result['gameUsername'] = $resultArr['data']['player_name'];
            $result['playerName'] = $playerName;
            $result['token'] = $resultArr['data']['player_session'];
		}

		return array($success, $result);
	}

    public function logout($playerName, $password = null, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);


		$params = array(
			'operator_token' => $this->operator_token,
			'secret_key' => $this->secret_key,
			'player_name' => $gameUsername,
		);

        $this->method = self::POST;

		return $this->callApi(self::API_logout, $params, $context);
	}

    public function processResultForLogout($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);

		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();
		if($success){
            $result['success'] = $resultArr['data']['action_result'];
		}

		return array($success, $result);
	}

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $original_transactions_table = $this->getTransactionsTable();

        $currentTableData = $this->queryOriginalGameLogsWithTable($original_transactions_table, $dateFrom, $dateTo, $use_bet_time);        

        $this->CI->utils->debug_log("PGSOFT SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        $prevTableData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();
        if($checkOtherTable||$this->force_check_other_transaction_table){            
            $prevTable = $this->getTransactionsPreviousTable();             
            $this->CI->utils->debug_log("PGSOFT SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', 'prevTable', $prevTable);
            $prevTableData = $this->queryOriginalGameLogsWithTable($prevTable, $dateFrom, $dateTo, $use_bet_time);                               
        }
        $gameRecords = array_merge($currentTableData, $prevTableData);
        //$this->processGameRecordsFromTrans($gameRecords);
        return $gameRecords;
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';
        if ($use_bet_time) {
            $sqlTime = '`original`.`created_at` >= ? AND `original`.`created_at` <= ?';
        }
        $this->CI->utils->debug_log('PGSOFT SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.bet_amount', 'original.after_balance', 'original.win_amount', 'original.updated_time', 'original.parent_bet_id'));
        //result amount = win - bet
        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.response_result_id,
    original.external_uniqueid,
    original.updated_time as bet_at,
    original.updated_time as start_at,
    original.updated_at as end_at,
    original.updated_time as updated_at,
    original.player_id,
    original.parent_bet_id as round_id,
    original.bet_id,
    original.bet_type,
    original.transaction_id,
    original.player_name,
    original.trans_type,
    original.after_balance,
    original.before_balance,
    original.bet_amount,
    original.win_amount,
    original.transfer_amount,
    original.trans_status as status,
    original.balance_adjustment_method,
    original.game_id as game,
    MD5(CONCAT($md5Fields)) as md5_sum,
    gd.game_code,
    gd.game_code as game_id,
    gd.english_name as game_name,
    gd.id as game_description_id,
    gd.game_name as game_description_name,
    gd.game_type_id
FROM {$table} as original
LEFT JOIN game_description as gd ON original.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE original.trans_type='cashTransferInOut' AND
$sqlTime;
EOD;
    
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];
    
        $this->CI->utils->debug_log('PGSOFT SEAMLESS GAME (queryOriginalGameLogs)', 'sql', $sql, 'params', $params);

        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        foreach ($results as $result) {
            // update round if already settled
            if ($result['status'] == Game_logs::STATUS_PENDING) {
                $row = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($table, [
                    'player_id' => $result['player_id'],
                    // 'parent_bet_id' => $result['parent_bet_id'],
                    'parent_bet_id' => $result['round_id'],
                    'trans_status' => Game_logs::STATUS_SETTLED,
                ]);

                if (!empty($row)) {
                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($table, ['external_uniqueid' => $result['external_uniqueid']], ['trans_status' => Game_logs::STATUS_SETTLED]);
                }
            }
        }

        return $results;
    }
    
    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('PGSOFT SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        $row['updated_at'] = date('Y-m-d H:i:s', ($row['updated_at']/1000));
        $this->CI->utils->debug_log('PGSOFT SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row[updated_at]', $row['updated_at']);
        $row['start_at'] = date('Y-m-d H:i:s', ($row['start_at']/1000));
        $row['bet_at'] = date('Y-m-d H:i:s', ($row['bet_at']/1000));
    
        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game_description_name']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_name']
            ],
            'amount_info' => [
                'bet_amount'            => $row['bet_amount'],
                'result_amount'         => $row['transfer_amount'],
                'bet_for_cashback'      => $row['bet_amount'],
                'real_betting_amount'   => $row['bet_amount'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance'],
            ],
            'date_info' => [
                'start_at'              => $row['start_at'],
                'end_at'                => $row['end_at'],
                'bet_at'                => $row['bet_at'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => $row['bet_type'],
            ],
            'bet_details' => $this->preprocessBetDetails($row),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
            'additional_details' => [
                'GAMEPROVIDER' => 'PGSOFT',
                'SESSIONID' => $row['sync_index'],
                'TRANSACTIONID' => $row['external_uniqueid'],
                'GAMENAME' => $row['game_name'],
                'OUTLET' => '',
                'PLAYERACCOUNT' =>  $row['player_name'],
                'PLAYERTYPE' => 'ONLINE',
                'GAMEDATE' => $row['bet_at'],
                'TOTALSTAKES' => $row['bet_amount'],
                'TOTALWINS' => $row['win_amount'],
                'PC1' => '',
                'PC2' => '',
                'PC3' => '',
                'PC4' => '',
                'PC5' => '',
                'JW1' => '',
                'JW2' => '',
                'JW3' => '',
                'JW4' => '',
            ],
        ];

        $this->utils->debug_log('PGSOFT ', $data);
        return $data;

    }

    /**
    *
    * perpare original rows, include process unknown game, pack bet details, convert game status
    *
    * @param  array &$row
    */
    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        if(isset($row['bet_type'])){
            $row['bet_type'] = $row['bet_type'] == self::BET_TYPE_REAL_CODE ? self::BET_TYPE_REAL : '';
        }

        if ($row['status'] != Game_logs::STATUS_REFUND) {
            $row['status'] = Game_logs::STATUS_SETTLED;
        }
    }

    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $game_description_id=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game'], $row['game']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function queryTransactionByDateTime($startDate, $endDate){

$transTable = $this->getTransactionsTable();


$sql = <<<EOD
SELECT
t.player_id as player_id,
t.updated_time as transaction_date,
t.transfer_amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.external_uniqueid as round_no,
t.external_uniqueid as external_uniqueid,
t.trans_type as trans_type
FROM {$transTable} as t
WHERE t.game_platform_id = ? and `t`.`updated_time` >= ? AND `t`.`updated_time` <= ?
ORDER BY t.updated_time asc;

EOD;


        $startDate = strval(strtotime($startDate) * 1000);
        $endDate = strval(strtotime($endDate) * 1000);
        $params=[$this->getPlatformCode(),$startDate, $endDate];


        $this->CI->utils->debug_log('PGSOFT SEAMLESS GAME (queryTransactionByDateTime)', 'sql', $sql, 'params',$params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function processTransactions(&$transactions){
        $temp_game_records = [];

        if(!empty($transactions)){
            foreach($transactions as $transaction){

                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = date('Y-m-d H:i:s', ($transaction['transaction_date']/1000));
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $extra_info = @json_decode($transaction['extra_info'], true);
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if($transaction['after_balance']<$transaction['before_balance']){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }


    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = '') {
        $success = false;
        if(!empty($resultArr['data'])){
            $success = true;
        } else {
            $this->setResponseResultToError($responseResultId);

            $this->utils->debug_log('PGsoft Seamless has an error', $apiName, $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }

    public function queryPlayerBalanceByPlayerId($playerId) {

        $useReadonly = true;
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode(), $useReadonly);

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }

    public function getTransactionsTable(){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transactions_table;
        }

        $date=new DateTime();
        $monthStr=$date->format('Ym');
        
        return $this->initGameTransactionsMonthlyTableByDate($monthStr);        
    }

	public function initGameTransactionsMonthlyTableByDate($yearMonthStr){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transactions_table;
        }

		$tableName=$this->original_transactions_table.'_'.$yearMonthStr;
		if (!$this->CI->utils->table_really_exists($tableName)) {
			try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like pgsoft_seamless_wallet_transactions');

			}catch(Exception $e){
				$this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
			}
		}

		return $tableName;
	}

    public function queryForwardGameV2($token, $extra = null) {
        $btt_real = 1;
        $btt_tournament = 3;

        
		$extraLanguage = '';
        
        if( isset($extra['language']) && !empty($extra['language']) ){
            $extraLanguage = $extra['language'];
        }

		$language = $this->getLanguagePrecedence( $extraLanguage, $this->language );

        $extra_args = array(
            "l" => $language,
            "btt" =>  $this->getGamelaunchCode($extra['game_mode']),
            "ot" => $this->operator_token,
            "ops" => $token, 
            "f" => isset($extra['home_url']) ? $extra['home_url'] : $this->getSystemInfo('return_slot_url',null), 
        );
        $this->CI->utils->debug_log('PGSOFT: (queryForwardGameV2)', 'extra_args:', $extra_args);

        $params = array(
            "operator_token" => $this->operator_token,
            "path" => "/{$extra['game_code']}/index.html",
            "extra_args" => http_build_query($extra_args),
            "url_type" => "game-entry",
            "client_ip" =>  $this->CI->utils->getIP(),
        );

        $this->CI->utils->debug_log('PGSOFT: (queryForwardGameV2)', 'params:', $params);

        // $guid = trim($this->createGUID(), '{}');
        // $url_params = array(
        //     "trace_id" => $guid
        // );
        // $url = $this->api_url . $this->URI_MAP[self::API_queryForwardGame]. "?". http_build_query($url_params);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $extra['playerName'],
        );

        $this->CI->utils->debug_log('PARIPLAY: (' . __FUNCTION__ . ')', 'PARAMS:', $params);
        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $html = $this->getResultTextFromParams($params);
        $url = null;
        if(!$this->used_html_on_launching){
            $url = $this->getUrlOnHtmlString($html);
            // preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $html, $match);
        }
        
        $result = array("url" => null, "html" => null, "is_html" => $this->used_html_on_launching);
        $success = false;
        if(!empty($html) && $this->used_html_on_launching){
            $result['html'] = $html;
            $success = true;
        } elseif(!empty($url) && !$this->used_html_on_launching){
            $result['url'] = $url;
            $success = true;
        }
        return array($success, $result);
    }

    function getUrlOnHtmlString($string) {
        $regex = '/https?\:\/\/[^\" \n]+/i';
        preg_match_all($regex, $string, $matches);
        if(isset($matches[0][0])){
            return $matches[0][0];
        }
        return null;
    }

    private function createGUID()
    {
        if (function_exists('com_create_guid')){
            return com_create_guid();
        } else {
            mt_srand((double)microtime()*10000);
            //optional for php 4.2.0 and up.
            $set_charid = strtoupper(md5(uniqid(rand(), true)));
            $set_hyphen = chr(45);
            // "-"
            $set_uuid = chr(123)
            .substr($set_charid, 0, 8).$set_hyphen
            .substr($set_charid, 8, 4).$set_hyphen
            .substr($set_charid,12, 4).$set_hyphen
            .substr($set_charid,16, 4).$set_hyphen
            .substr($set_charid,20,12)
            .chr(125);
            return $set_uuid;
        }
    }

    private function getGamelaunchCode($mode){
        switch ($mode) {
            case 'real': # Real game
                $bet_type = 1;
                break;
            case "tournament": # Tournament game
                $bet_type = 3;
                break;
            default:
                $bet_type = 1;
                break;
        }
        return $bet_type;
    }

    public function queryBetDetailLink($playerUsername, $external_unique_id = NULL, $extra = NULL){

        if ($this->force_bet_detail_default_format) {
            return parent::queryBetDetailLink($playerUsername, $external_unique_id, $extra);
        }
        $this->CI->utils->debug_log('PGsoft Seamless Game API -- queryBetDetailLink',$playerUsername ,$external_unique_id, $extra);
        $external_unique_id = explode('-',$external_unique_id);
        $bet_id = $external_unique_id[0];
        $bet_parent_id = $external_unique_id[1];
		$params = array(
			'operator_token' => $this->operator_token,
			'secret_key'     => $this->secret_key
		);
        $guid = trim($this->createGUID(), '{}');
        $context = array(
            'callback_obj'      => $this,
            'callback_method'   => 'processResultForqueryBetDetailLink',
            'bet_id'            => $bet_id,
			'bet_parent_id'     => $bet_parent_id,
			'guid'              => $guid,
        );
        $this->method = self::POST;
        return $this->callApi(self::API_queryBetDetailLink, $params, $context);
    }
    
    public function processResultForqueryBetDetailLink($params){
        $this->CI->utils->debug_log('processResultForqueryBetDetailLink--params', $params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode       = $this->getStatusCodeFromParams($params);
		$resultArr        = $this->getResultJsonFromParams($params);
        $success          = isset($resultArr['data']['operator_session']) ? true : false;
        $bet_id           = $this->getVariableFromContext($params, 'bet_id');
		$bet_parent_id    = $this->getVariableFromContext($params, 'bet_parent_id');
		$guid             = $this->getVariableFromContext($params, 'guid');
        $operator_session = $resultArr['data']['operator_session'];

        $url_params = [
            'trace_id' => $guid,
            't'        => $operator_session,
            'psid'     => $bet_parent_id,
            'sid'      => $bet_id,
            'lang'     => 'en',
            'type'     => 'operator',
        ];

        if($this->enabled_queryBetDetailLinkHTML){
            $extra_args = array(
                "t"         => $operator_session,
                "psid"      => $bet_parent_id,
                "sid"       => $bet_id,
                "type"      => 'operator',
            );
    
            $data =  $this->queryBetDetailLinkHTML($extra_args);
            $result['html'] = isset($data['html']) ? $data['html'] : null;
            return array($success, $result);
        }

        $bet_url = $this->bet_history_url ."?". http_build_query($url_params);
        $result['url']   = $bet_url;
        $this->CI->utils->debug_log('PGsoft Seamless Game API -- bethistory', $url_params, $bet_url);
		return array($success, $result);
    }

    public function queryBetDetailLinkHTML($extra_args){
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForqueryBetDetailLinkHTML',
        );

        $params = array(
            "path"          => "/history/redirect.html",
            "extra_args"    => http_build_query($extra_args),
            "url_type"      => "web-history",
            "client_ip" =>  $this->CI->utils->getIP()
        );

        $this->method = self::POST;
        $result=  $this->callApi(self::API_queryBetDetailLink, $params, $context);
        return $result;
    }

    public function processResultForqueryBetDetailLinkHTML($params){
        $resultText = $this->getResultTextFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
        $this->CI->utils->debug_log('emman-test', $resultText);
        $result = ['html' => $resultText];
        return [true, $result];
    }

    public function preprocessOriginalRowForBetDetails($row, $extra = []) {
        // print_r($row);exit;
        $bet_details = $row;

        if (isset($row['bet_id'])) {
            $bet_details['bet_id'] = $row['bet_id'];
        }

        if (isset($row['win_amount'])) {
            $bet_details['win_amount'] = $row['win_amount'];
        }

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] = $row['bet_amount'];
        }

        if (isset($row['end_at'])) {
            $bet_details['settlement_datetime'] = $row['end_at'];
        }

        if (isset($row['bet_at'])) {
            $bet_details['betting_datetime'] = $row['bet_at'];
        }

        if (isset($row['match_name'])) {
            $bet_details['match_name'] = $row['match_name'];
        }

        if (isset($row['round_id'])) {
            $bet_details['round_id'] = $row['round_id'];
        }

        if (isset($row['bet_type'])) {
            $bet_details['bet_type'] = $row['bet_type'];
        }

        if (isset($row['game_name'])) {
            $bet_details['game_name'] = $row['game_name'];
        }

        // print_r($bet_details);exit;
        return $bet_details;
    }

    #OGP-34427
    public function getProviderAvailableLanguage() {
        return $this->getSystemInfo('provider_available_langauge', ['en','zh-cn','id-id','vi-vi','ko-kr','th-th','pt']);
    }

    private function getLanguagePrecedence( $extraLang, $playerDefaultLang) {

        $language = $playerDefaultLang;
        
        if (!empty($extraLang)) {
            $language = $extraLang;
        }
        return $this->getLauncherLanguage($language);
    }

    public function getUnsettledRounds($dateFrom, $dateTo) {
        $this->CI->load->model(array('original_game_logs_model'));
        $original_transactions_table = $this->getTransactionsTable();

        $transaction_type = 'cashTransferInOut';
        $status = Game_logs::STATUS_PENDING;
        $sqlTime = 'original.created_at BETWEEN ? AND ?';

        $sql = <<<EOD
SELECT 
original.created_at AS transaction_date,
original.trans_type AS transaction_type,
original.trans_status AS transaction_status,
original.game_platform_id,
original.player_id,
original.bet_id AS transaction_id,
original.transaction_id AS round_id,
original.bet_amount as amount,
original.bet_amount as deducted_amount,
original.win_amount as added_amount,
original.external_uniqueid,
gd.id as game_description_id,
gd.game_type_id
FROM {$original_transactions_table} AS original
LEFT JOIN game_description AS gd ON original.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
original.trans_type = ? AND original.trans_status = ? AND {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $transaction_type,
            $status,
            $dateFrom,
            $dateTo
        ];

        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        $this->CI->utils->debug_log('PGSOFT_SEAMLESS (getUnsettledRounds)', 'game_platform_id', $this->getPlatformCode(), 'params', $params, 'sql', $sql, 'results', $results);

        return $results;
    }

    public function checkBetStatus($data) {
        $this->CI->load->model(['seamless_missing_payout']);
        $data['status'] = Seamless_missing_payout::NOT_FIXED;

        $result = [
            'game_platform_id' => $data['game_platform_id'],
            'success' => false,
            'exists' => false,
        ];

        $inserted = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report', $data);

        if ($inserted) {
            $result['success'] = $result['exists'] = true;
        } else {
            $this->CI->utils->error_log('PGSOFT_SEAMLESS (checkBetStatus) Error insert missing payout', 'result', $result);
        }

        return $result;
    }

    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_game_logs_model', 'seamless_missing_payout']);
        $original_transactions_table = $this->getTransactionsTable();

        $sql = <<<EOD
SELECT 
trans_status
FROM {$original_transactions_table}
WHERE
game_platform_id = ? AND external_uniqueid = ? 
EOD;

        $params =[
            $game_platform_id,
            $external_uniqueid,
        ];

        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        if (!empty($result)) {
            return array('success' => true, 'status' => $result['trans_status']);
        }

        return array('success' => false, 'status' => Game_logs::STATUS_PENDING);
    }

    public function queryGameListFromGameProvider($extra = []) {
        $this->method = self::POST;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        ];

        $params = [
            'operator_token' => isset($extra['operator_token']) ? $extra['operator_token'] : $this->operator_token,
            'secret_key' => isset($extra['secret_key']) ? $extra['secret_key'] : $this->secret_key,
            'currency' => isset($extra['currency']) ? $extra['currency'] : $this->currency,
            'language' => isset($extra['language']) ? $extra['language'] : $this->game_list_language, // Language of data content:en-us: English (Default), zh-cn: Chinese
            'status' => isset($extra['status']) ? $extra['status'] : $this->game_list_status, // Status of games: 0: Inactive, games 1: Active, gamesDefault: All status of games
        ];

        if ($this->game_list_status == 'all') {
            unset($params['status']);
        }

        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params){
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
        $result = [];

        if ($success) {
            $result = !empty($resultArr) ? $resultArr : [];
        }

        return array($success, $result);
    }
}

/*end of file*/