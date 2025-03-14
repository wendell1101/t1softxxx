<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Spinomenal Seamless Integration
 * OGP-30817
 * ? uses spinomenal_seamless_service_api for its service API
 *
 * Game Platform ID: 6318
 *
 */

abstract class Abstract_game_api_common_spinomenal_seamless extends Abstract_game_api
{    # Fields in game_logs we want to detect changes for merge and when md5_sum
    const MD5_FIELDS_FOR_MERGE = [
        'external_uniqueid',
        'round_id',
        'win_amount',
        'bet_amount',
        'is_round_finish',
        'before_balance',
        'after_balance',
        'result_amount',
        'trans_status',
        'ref_transaction_id'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'win_amount',        
        'before_balance',
        'after_balance',
        'result_amount'
    ];
    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet_amount',
        'win_amount',        
    ];
    const METHOD = [
        'GET' => 'GET',
        'POST' => 'POST'
    ];

    const CLIENT_PC = 1;
    const CLIENT_MOBILE = 2;

    const REAL_PLAYER = 0;
    const TEST_USER = 1;
    const VIP_PLAYER = 1;

    const TRANSACTION_TYPE_BET_AND_WIN = 'BetAndWin';
	const TRANSACTION_END_ROUND = 'EndRound';
	const TRANSACTION_TYPE_CANCEL_BET = 'CancelBet';
	const TRANSACTION_TYPE_WIN = 'Win';
	const TRANSACTION_TYPE_BONUS = 'Bonus';
	const TRANSACTION_TYPE_FREE_ROUNDS_START = 'FreeRounds_Start';
	const TRANSACTION_TYPE_FREE_ROUNDS_WIN = 'FreeRounds_Win';
	const TRANSACTION_TYPE_FREE_ROUNDS_END = 'FreeRounds_End';

    const API_CANCEL_FREE_ROUND_BY_ASSIGN_CODE = '/FreeRounds/CancelByAssignCode';
    const API_CANCEL_FREE_ROUND_BY_ROUND_CODE = '/FreeRounds/CancelByFreeRoundsCode';

    const API_GET_FREE_ROUNDS_BY_PLAYER_ID = '/reporting/GetFreeRoundsByPlayerId';
    const API_GET_FREE_ROUNDS_BY_ID = '/reporting/GetFreeRoundsById';

    public $URI_MAP = [
        self::API_queryForwardGame => "/GameLauncher/LaunchGame",
        self::API_createFreeRoundBonus => '/FreeRounds/Create',
        self::API_cancelFreeRoundBonus => self::API_CANCEL_FREE_ROUND_BY_ASSIGN_CODE,
        self::API_queryFreeRoundBonus => self::API_GET_FREE_ROUNDS_BY_PLAYER_ID,
        self::API_getLanguages => "/reporting/GetLanguages",
    ];

    public $original_transactions_table;
    public $api_url, $launch_url, $sync_time_interval, $currency, $language;
    public $enable_home_link, $home_link, $cashier_link, $ip, $return_url;
    public $api_username, $api_password, $use_third_party_token, $partner_id, $private_key;

    public function __construct()
    {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->launch_url = $this->getSystemInfo('launch_url');

        $this->return_url = $this->getSystemInfo('return_url');
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+5 minutes');
        $this->currency = $this->getSystemInfo('currency');
        $this->language = $this->getSystemInfo('language', 'en');

        $this->original_transactions_table = 'spinomenal_seamless_wallet_transactions';

        $this->partner_id = $this->getSystemInfo('partner_id', null);
        $this->private_key = $this->getSystemInfo('private_key', null);

        $this->use_third_party_token = $this->getSystemInfo('use_third_party_token', false);

        $this->enable_home_link = $this->getSystemInfo('enable_home_link', true);
        $this->home_link = $this->getSystemInfo('home_link');
        $this->cashier_link = $this->getSystemInfo('cashier_link');
    }


    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode()
    {
        return SPINOMENAL_SEAMLESS_GAME_API;
    }

    public function getCurrency()
    {
        return $this->currency;
    }


    public function generateUrl($apiName, $params)
    {
        $uri = $this->URI_MAP[$apiName];

        $url = $this->api_url . $uri;
        return $url;
    }


    public function getHttpHeaders($params = [])
    {
        return array(
            "Content-Type" => "application/json",
            "Accept" => "application/json"
        );
    }

    protected function customHttpCall($ch, $params)
    {
        if ($params["actions"]["method"] == self::METHOD["POST"]) {
            $function = $params["actions"]['function'];

            unset($params["actions"]);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params["main_params"]));
        }
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $is_querytransaction = false)
    {
        $success = false;
        if ((isset($resultArr['ErrorCode']) && $resultArr['ErrorCode'] == 0)) {
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('SPINOMENAL SEAMLESS GAME API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for SPINOMENAL";
        if ($return) {
            $success = true;
            $message = "Successfull create account for SPINOMENAL.";
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array("success" => $success, "message" => $message);
    }

    public function depositToGame($userName, $amount, $transfer_secure_id = null)
    {
        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs' => true,
        );
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id = null)
    {
        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs' => true,
        );
    }

    public function getLauncherLanguage($language) {
        return $this->getGameLauncherLanguage($language, [
    # default 'key' => 'change value only',
            'en_us' => 'en_US',
            'zh_cn' => 'zh_CN',
            'id_id' => 'id_ID',
            'vi_vn' => 'en_US',
            'ko_kr' => 'ko_KR',
            'th_th' => 'th_TH',
            'hi_in' => 'hi_IN',
            'pt_pt' => 'pt_PT',
            'es_es' => 'es_ES',
            'kk_kz' => 'en_US',
            'pt_br' => 'pt_PT',
            'ja_jp' => 'ja_JP',
        ]);
    }

    public function queryForwardGame($playerName, $extra = null)
    {
        $player_id = $this->getPlayerIdFromUsername($playerName);
        $token = $this->getPlayerToken($player_id);

        $gameUsername = null;
        $player = null;
        $isDemo = 1;

        // use /reporting/GetLanguages API to get languages list
        $language = !empty($this->language) ? $this->language : $this->getLauncherLanguage(isset($extra['language']) ? $extra['language'] : null);

        if(isset($extra['game_mode']) && $extra['game_mode'] == 'real'){
            $isDemo = 0;
            $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
            $player = $this->CI->player_model->getPlayerByUsername($playerName);
        }

        $this->CI->utils->debug_log('SPINOMENAL Player Details: ', $extra, $playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $is_mobile = $extra['is_mobile'];
        if ($is_mobile) {
            $clienttype = self::CLIENT_MOBILE;
        } else {
            $clienttype = self::CLIENT_PC;
        }

        if (isset($extra['home_link']) && !empty($extra['home_link'])) {
            $return_url = $extra['home_link'];

        } else if (isset($extra['extra']['t1_lobby_url']) && !empty($extra['extra']['t1_lobby_url'])) {
            $return_url = $extra['extra']['t1_lobby_url'];
        } else if (!empty($this->return_url)) {
            $return_url = $this->return_url;
        } else {
            $return_url = $this->getHomeLink();
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'player' => $player,
            'isDemo' => $isDemo,
            'return_url' => $return_url
        );

        $gameCode = !empty($extra['game_code']) ? $extra['game_code'] : null;

        $timestamp = $this->getFormattedTimeStamp();
        $main_params = [
            "PartnerId" => $this->partner_id,
            // "GameCode" => "Tower_1ReelCaribbeanTreasure-CW",
            "GameCode" => $gameCode,
            "LangCode" => $language,
            "GameToken" => $token,
            "IsDemoMode" => $isDemo,
            "UrlsInput" => [
                "HomeUrl" => $this->home_link,
                "CashierUrl" => $this->cashier_link
            ],
            "PlayerInput" => [
                "PlayerId" => $gameUsername,
                "Currency" => $this->currency,
                "Sig" => MD5($timestamp. $gameUsername . $this->private_key),
                "TimeStamp" => $timestamp,
                "TypeId" => self::REAL_PLAYER,
                "SessionOptions" => [
                    "DisableBuyFeature" => true
                ]
            ]
        ];

        if (!empty($gameCode)) {
            $params['gid'] = $gameCode;
        }

        $gameMode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
        if (in_array($gameMode, $this->demo_game_identifier)) {
            $params['ist'] = 1;
        }

        $params = array(
			"main_params" => $main_params,
			"actions" => [
				"function" => self::API_queryForwardGame,
				"method" => self::METHOD["POST"]
			]
		);

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params){
        $player = $this->getVariableFromContext($params, 'player');
        $return_url = $this->getVariableFromContext($params, 'return_url');
        $playerId = isset($player->playerId) ? $player->playerId : null; 
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);

		$statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = array();    


        $result['url'] = $return_url;
        
        if($success){
            $this->CI->load->model(['external_common_tokens']);

            $token = isset($resultArr['GameToken']) ? $resultArr['GameToken'] : null;

            if($this->use_third_party_token && $token != null){
                #save token here
                $this->CI->external_common_tokens->addPlayerToken($playerId, $token, $this->getPlatformCode(),$this->currency);
            }
            if(isset($resultArr['Url']))
            {
                $result['url'] = $resultArr['Url'];
            }
        }

        $this->CI->utils->debug_log("spinomenal launch url: " , $result['url']);
        $this->CI->utils->debug_log("spinomenal launch game status: " , $success);
        return array($success, $result);
    }

    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }

    public function createFreeRound($playerName, $extra=null) {
        $extra = (array) $extra;
        $game_username = $this->getGameUsernameByPlayerUsername($playerName);

        $start_date = isset($extra['start_date']) ? $extra['start_date'] : $this->getFormattedTimeStamp();
        $expired_date = isset($extra['expire_date']) ? $extra['expire_date'] : null;
        $timestamp = $this->getFormattedTimeStamp();
        $free_rounds_name = isset($extra['name']) ? $extra['name'] : null;
        $assign_code = isset($extra['assign_code']) ? $extra['assign_code'] : null;
        $free_rounds_code = isset($extra['assign_code']) ? $extra['assign_code'] : null;
        $free_rounds_count = isset($extra['count']) ? $extra['count'] : null;
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $player_id = isset($extra['player_username']) ? $extra['player_username'] : null;
        $stake = isset($extra['stake']) ? $extra['stake'] : null;
        $lines = isset($extra['lines']) ? $extra['lines'] : null;


        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreateFreeRound',
            'expired_at' => $expired_date,
            'transaction_id' => $assign_code,
            'currency' => $this->currency,
            'game_username' => $game_username,
            'free_rounds' => $free_rounds_count,
            'extra' => ['game_code' => $game_code],
            'raw_data' => $extra,
            'player_id' => $player_id,
            'raw_data' => json_encode($extra)
        );


        $main_params = array(
            "FreeRoundsName" => $free_rounds_name,
            "FreeRoundsCode" => $free_rounds_code,
            "FreeRoundsAmount" => $free_rounds_count,
            "PartnerId" => $this->partner_id,
            "Sig" => $this->generateSignature([$timestamp, $this->partner_id, $assign_code, $this->private_key]),
            "TimeStamp" => $timestamp,
            "PlayerId" => $player_id,
            "Currency" => $this->currency,
            "AssignCode" => $assign_code,
            "GamesSettings" => [
              [
                "GameCode" => $game_code,
                "Stake" => $stake,
                "Lines" => $lines
              ]
            ],
            "StartDate" => $start_date,
            "ExpireDate" => $expired_date
        );

        $params = array(
			"main_params" => $main_params,
			"actions" => [
				"function" => self::API_createFreeRoundBonus,
				"method" => self::METHOD["POST"]
			]
		);

        return $this->callApi(self::API_createFreeRoundBonus, $params, $context);
    }


    public function processResultForCreateFreeRound($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $game_username = $this->getVariableFromContext($params, 'player_id');        
        $player_id = $this->getPlayerIdInGameProviderAuth($game_username, $this->getPlatformCode());

        $free_rounds = $this->getVariableFromContext($params, 'free_rounds');
        $transaction_id = $this->getVariableFromContext($params, 'transaction_id');
        $currency = $this->getVariableFromContext($params, 'currency');
        $expired_at = $this->getVariableFromContext($params, 'expired_at');
        $extra = $this->getVariableFromContext($params, 'extra');
        $raw_data = $this->getVariableFromContext($params, 'raw_data');

        if ($success){
            $return = [
                'transaction_id' => $transaction_id,
                'expiration_date' => $expired_at,
            ];
            $this->CI->load->model(array('free_round_bonus_model'));

            $data = [
                'player_id' => $player_id,
                'game_platform_id' => $this->getPlatformCode(),
                'free_rounds' => $free_rounds,
                'transaction_id' => $transaction_id,
                'currency' => $currency,
                'expired_at' => $expired_at,
                'extra' => $extra,
                'raw_data' => $raw_data,
            ];
            $this->CI->free_round_bonus_model->insertTransaction($data);
        }
        else {
            $return = [
                'message' => isset($resultArr['ErrorMessage']) ? $resultArr['ErrorMessage'] : null ,
            ];
        }
        return array($success, $return);
    }

    
    public function cancelFreeRound($transaction_id, $extra = []) {
        $extra = (array) $extra;
        $timestamp = $this->getFormattedTimeStamp();
        $unique_code = null;
        $assign_code = isset($extra['AssignCode']) ? $extra['AssignCode'] : null;
        $free_round_code = isset($extra['FreeRoundCode']) ? $extra['FreeRoundCode'] : null;
        if($assign_code){
            $unique_code = $assign_code;
            $this->URI_MAP[self::API_cancelFreeRoundBonus] = self::API_CANCEL_FREE_ROUND_BY_ASSIGN_CODE;
        }
        if($free_round_code){
            $unique_code = $free_round_code;
            $this->URI_MAP[self::API_cancelFreeRoundBonus] = self::API_CANCEL_FREE_ROUND_BY_ROUND_CODE;
        }

 
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCancelFreeRound',
            'transaction_id' => $unique_code,
        );

        $main_params = array(
            "PartnerId" =>  $this->partner_id,
            "Sig" => $this->generateSignature([$timestamp, $this->partner_id, $assign_code, $this->private_key]),
            "TimeStamp" => $timestamp,
            "AssignCode" => $unique_code

        );

        $params = array(
			"main_params" => $main_params,
			"actions" => [
				"function" => self::API_cancelFreeRoundBonus,
				"method" => self::METHOD["POST"]
			]
		);

        return $this->callApi(self::API_cancelFreeRoundBonus, $params, $context);
    }

    public function processResultForCancelFreeRound($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $transaction_id = $this->getVariableFromContext($params, 'transaction_id');

        if ($success){
            $return = [
                'transaction_id' => $transaction_id,
            ];

            $this->CI->load->model(array('free_round_bonus_model'));
            $this->CI->free_round_bonus_model->cancelTransaction($transaction_id, $this->getPlatformCode());
        }
        else {
            $return = [
                'message' => isset($resultArr['ErrorMessage']) ? $resultArr['ErrorMessage'] : null ,
            ];
        }
        return array($success, $return);
    }

    public function queryFreeRound($playerName, $extra = []) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryFreeRound',
        );

        $timestamp = $this->getFormattedTimeStamp();
        $main_params = array(
            "PartnerId" =>  $this->partner_id,
            "Sig" => $this->generateSignature([$timestamp, $this->partner_id, $this->private_key]),
            "TimeStamp" => $this->getFormattedTimeStamp(),
            "PlayerId" => $playerName
        );         

        $params = array(
			"main_params" => $main_params,
			"actions" => [
				"function" => self::API_queryFreeRoundBonus,
				"method" => self::METHOD["POST"]
			]
		);

        return $this->callApi(self::API_queryFreeRoundBonus, $params, $context);
    }

    public function processResultForQueryFreeRound($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        if ($success){
            $return = [
                'free_round_list' => $resultArr['Result'],
            ];
        }
        else {
            $return = [
                'message' => isset($resultArr['ErrorMessage']) ? $resultArr['ErrorMessage'] : null
            ];
        }
        return array($success, $return);
    }

    public function generateSignature($data = []){
		return md5(implode('',$data));
	}	

    public function syncOriginalGameLogs($token = false)
    {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token)
    {
        
        $enabled_game_logs_unsettle = true;
        return $this->commonSyncMergeToGameLogs(
            $token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle
        );
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $original_transactions_table = $this->getTransactionsTable();
        
        $currentTableData = $this->queryOriginalGameLogsWithTable($original_transactions_table, $dateFrom, $dateTo, $use_bet_time);   

        $this->CI->utils->debug_log("SPINOMENAL SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        $prevTableData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();

        if($checkOtherTable||$this->force_check_other_transaction_table){            
            $prevTable = $this->getTransactionsPreviousTable();             
            $this->CI->utils->debug_log("SPINOMENAL SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', 'prevTable', $prevTable);
            $prevTableData = $this->queryOriginalGameLogsWithTable($prevTable, $dateFrom, $dateTo, $use_bet_time);                               
        }
        $gameRecords = array_merge($currentTableData, $prevTableData);        
        //$this->processGameRecordsFromTrans($gameRecords);
        return $gameRecords;
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';


        $this->CI->utils->debug_log('SPINOMENAL SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.round_id', 'original.trans_status','original.is_round_finish','original.win_amount', 'original.bet_amount', 'original.before_balance', 'original.after_balance', 'original.external_uniqueid', 'original.ref_transaction_id'));

        $validTransactionTypes = [
            self::TRANSACTION_TYPE_BET_AND_WIN, 
            self::TRANSACTION_END_ROUND, 
            self::TRANSACTION_TYPE_WIN, 
            self::TRANSACTION_TYPE_BONUS, 
            self::TRANSACTION_TYPE_FREE_ROUNDS_WIN, 
            self::TRANSACTION_TYPE_FREE_ROUNDS_END
        ];

        $transTypes = "('" . implode("', '", $validTransactionTypes) . "')";

        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.bet_amount,
    original.win_amount,
    original.result_amount,
    original.game_code as game_id,
    original.spinomenal_player_id,
    original.transaction_id,
    original.round_id,
    original.is_round_finish,
    original.is_retry,
    original.ref_transaction_id,
    original.currency,
    original.trans_type,
    original.trans_status,
    original.trans_status as status,
    original.player_id,
    original.balance_adjustment_method,
    original.before_balance,
    original.after_balance,
    original.response_result_id,
    original.external_uniqueid,
    original.game_platform_id,
    original.created_at,
    original.updated_at,
    original.raw_data,
    MD5(CONCAT({$md5Fields})) as md5_sum,
    gd.game_code as game_code,
    gd.game_name as game_name,
    gd.id as game_description_id,
    gd.game_name as game_description_name,
    gd.game_type_id,
    gd.english_name as game_english_name
FROM {$table} as original
LEFT JOIN game_description as gd ON original.game_code = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE 
original.trans_type IN {$transTypes}
and {$sqlTime}
;
EOD;


        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];


		$this->CI->utils->debug_log('SPINOMENAL SEAMLESS GAME (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);

        $rlt = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        $this->CI->utils->debug_log("SPINOMENAL_SEAMLESS_GAME_API queryOriginalGameLogs raw query:" . $this->CI->db->last_query());
        return $rlt;
    }


    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('SPINOMENAL SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        // $row['updated_at'] = date('Y-m-d H:i:s', ($row['updated_at']/1000));
        $this->CI->utils->debug_log('SPINOMENAL SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row[updated_at]', $row['updated_at']);

        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_id'],
                'game_type'             => null,
                'game'                  => $row['game_description_name']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['spinomenal_player_id']
            ],
            'amount_info' => [
                'bet_amount'            => isset($row['bet_amount']) ? $this->gameAmountToDBTruncateNumber($row['bet_amount']) : 0,
                'result_amount'         => isset($row['result_amount']) ? $this->gameAmountToDBTruncateNumber($row['result_amount']) : 0,
				'bet_for_cashback'      => isset($row['bet_amount']) ? $this->gameAmountToDBTruncateNumber($row['bet_amount']) : 0,
				'real_betting_amount'   => isset($row['bet_amount']) ? $this->gameAmountToDBTruncateNumber($row['bet_amount']) : 0,
				'win_amount'            => 0,
				'loss_amount'           => 0,
                'after_balance'         => 0,
            ],
            'date_info' => [
                'start_at'              => $row['created_at'],
                'end_at'                => $row['updated_at'],
                'bet_at'                => $row['created_at'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['trans_status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => $row['balance_adjustment_method'],
            ],
            'bet_details' => $this->formatBetDetails($row), 
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('SPINOMENAL after_balance:', $data);
        return $data;

    }

    private function formatBetDetails($row){
        // $rawData = json_encode($row['raw_data']);

       return  [
            'game_name' => $row['game_english_name'],
            'bet_amount' => $row['bet_amount'],
            'win_amount' => $row['win_amount'],
            'round_id' => $row['round_id'],
            'bet_type' => $row['balance_adjustment_method'],
            'bet_id' => $row['round_id'],
            'betting_time' => $row['created_at'],
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        if(isset($row['bet_type'])){
            $row['bet_type'] = $row['bet_type'];
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
                $unknownGame->game_type_id, $row['game_code'], $row['game_code']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function queryPlayerBalanceByPlayerId($playerId) {
        $this->CI->load->model(['player_model']);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

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
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like spinomenal_seamless_wallet_transactions');

			}catch(Exception $e){
				$this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
			}
		}

		return $tableName;
	}

    public function queryTransactionByDateTime($startDate, $endDate){
        $transTable = $this->getTransactionsTable();

        
        
$sql = <<<EOD
SELECT
t.spinomenal_player_id as player_id,
t.updated_at as transaction_date,
t.result_amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_uniqueid as external_uniqueid,
t.balance_adjustment_method as trans_type,
t.raw_data as extra_info
FROM {$transTable} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;
        
        
        $params=[$this->getPlatformCode(),$startDate, $endDate];

        $this->CI->utils->debug_log('Spinomenal SEAMLESS GAME (queryTransactionByDateTime)', 'sql', $sql, 'params',$params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }
        
    public function processTransactions(&$transactions){
        $this->CI->utils->debug_log('Spinomenal process transaction', $transactions);
        $temp_game_records = [];

        if(!empty($transactions)){
           
            foreach($transactions as $transaction){
                $temp_game_record = [];
                $temp_game_record['player_id'] = $this->getPlayerIdByGameUsername($transaction['player_id']);
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];
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

    
	public function getFormattedTimeStamp(){
		$utc_time = new DateTime('now', new DateTimeZone('UTC'));
		// Format the UTC time as [4 digit year][2 digit month][2 digit day][2 digit hour][2 digit minute][2 digit seconds]
		return (string)$utc_time->format('YmdHis');
	}
        
    public function dumpData($data){
        print_r(json_encode($data));exit;
    }

    public function getLanguages($extra=null) {
        $extra = (array) $extra;
        $timestamp = $this->getFormattedTimeStamp();

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetLanguages',
        ];

        $main_params = [
            "PartnerId" => $this->partner_id,
            "Sig" => md5($timestamp . $this->partner_id . $this->private_key),
            "TimeStamp" => $timestamp,
        ];

        $params = [
            "main_params" => $main_params,
            "actions" => [
                "function" => self::API_getLanguages,
                "method" => self::METHOD["POST"],
            ],
        ];

        return $this->callApi(self::API_getLanguages, $params, $context);
    }


    public function processResultForGetLanguages($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        return array($success, $resultArr);
    }
}

/*end of file*/