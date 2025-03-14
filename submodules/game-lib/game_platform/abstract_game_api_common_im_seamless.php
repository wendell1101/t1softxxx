<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * IM Seamless Integration
 * OGP-30943
 * ? uses im_seamless_service_api for its service API
 *
 * Game Platform ID: 6324
 *
 */

abstract class Abstract_game_api_common_im_seamless extends Abstract_game_api
{   
    #specification docs

    const SUCCESS = 0;
    const INVALID_MERCHANT_CODE = 500;
    const PLAYER_DOES_NOT_EXIST = 504;
    const REQUIRED_FIELD_EMPTY = 505;
    const INVALID_PLAYER_ID = 506;
    const INVALID_PRODUCT_WALLET = 508;
    const INVALID_LANGUAGE = 518;
    const INVALID_GAME_CODE = 521;
    const INVALID_IP_ADDRESS = 522;
    const GAME_NOT_ACTIVE = 533;
    const FAILED_TO_START_GAME = 536;
    const PLAYER_NOT_CREATED = 540;
    const PLAYER_INACTIVE = 542;
    const GAME_NOT_ACTIVATED = 546;
    const MINIMUM_INTERVAL = 557;
    const INVALID_TRAY = 559;
    const INVALID_BET_LIMIT_ID = 561;
    const FAILED_TO_SEND_RESPONSE = 563;

    const RESPONSE_CODES = [
        self::SUCCESS => "Successful.",
        self::INVALID_MERCHANT_CODE => "Invalid merchant code",
        self::PLAYER_DOES_NOT_EXIST => "Player does not exist",
        self::REQUIRED_FIELD_EMPTY => "Required field cannot be empty or null",
        self::INVALID_PLAYER_ID => "Invalid player ID",
        self::INVALID_PRODUCT_WALLET => "Invalid Product Wallet",
        self::INVALID_LANGUAGE => "Invalid language",
        self::INVALID_GAME_CODE => "Invalid game code",
        self::INVALID_IP_ADDRESS => "Invalid IP address",
        self::GAME_NOT_ACTIVE => "Game is not active",
        self::FAILED_TO_START_GAME => "Failed to start game (app already running)",
        self::PLAYER_NOT_CREATED => "Player was not created successfully or inactive at provider side. Applicable to Transfer Wallet product only.",
        self::PLAYER_INACTIVE => "Player is inactive",
        self::GAME_NOT_ACTIVATED => "Game is not activated to the Operator",
        self::MINIMUM_INTERVAL => "The API is called within the minimum interval allowed",
        self::INVALID_TRAY => "Invalid Tray or Tray is not supported by the ProductWallet.",
        self::INVALID_BET_LIMIT_ID => "Invalid BetLimitID or BetLimitID is not supported by the ProductWallet.",
        self::FAILED_TO_SEND_RESPONSE => "System has failed to send a response. Please contact support."
    ];

    const CLIENT_PC = 1;
    const CLIENT_MOBILE = 2;

    const IS_DOWNLOAD = 0;

    const IM_ESPORTS_PRODUCT_WALLET = 401;
    const IM_SPORTBOOKS_WALLET = 301;

    const IM_ESPORTS_GAME_CODE = "IMSB";
    const IM_SPORTSBOOK_GAME_CODE = "ESPORTSBULL";

    const PRODUCT_WALLET_CODES = [
        self::IM_SPORTSBOOK_GAME_CODE => self::IM_ESPORTS_PRODUCT_WALLET ,
        self::IM_ESPORTS_GAME_CODE => self::IM_SPORTBOOKS_WALLET
    ];

    const ALLOWED_GAME_IDS = [
        self::IM_ESPORTS_PRODUCT_WALLET,
        self::IM_SPORTBOOKS_WALLET
    ];

    const TYPE_PLACE_BET = "PlaceBet";

    const MD5_FIELDS_FOR_MERGE = [
        'external_uniqueid',
        'amount',
        'before_balance',
        'after_balance',
        'result_amount',
        'trans_status',
        'transaction_id'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'amount',
        'credit_amount',        
        'before_balance',
        'after_balance',
    ];
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'amount'
    ];
    const METHOD = [
        'GET' => 'GET',
        'POST' => 'POST',
        'PUT' => 'PUT'
    ];

    public $URI_MAP = [
        self::API_createPlayer => "/Player/Register",
        self::API_queryForwardGame => "/Game/NewLaunchGame",
        self::API_isPlayerExist => "/Player/CheckExists",
        self::API_changePassword => "/Player/ResetPassword",
    ];
    const MOBILE_LAUNCH_URL = "/Game/NewLaunchMobileGame";

    public $original_transactions_table;
    public $api_url, $launch_url, $sync_time_interval, $currency, $language;
    public $enable_home_link, $home_link, $cashier_link, $ip, $return_url;
    public $api_username, $api_password, $use_third_party_token;
    public $merchant_code;
    public function __construct()
    {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->merchant_code = $this->getSystemInfo('merchant_code');
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+5 minutes');
        $this->currency = $this->getSystemInfo('currency');
        $this->language = $this->getSystemInfo('language', 'en');

        $this->original_transactions_table = 'im_seamless_wallet_transactions';
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
        return IM_SEAMLESS_GAME_API;
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

    public function getDefaultGroup()
	{
		return 1;
	}
  
    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $is_querytransaction = false)
    {
        $success = false;
        if ((isset($resultArr['Code']) && $resultArr['Code'] == self::SUCCESS) && (isset($resultArr['Message']) && $resultArr['Message'] == self::RESPONSE_CODES[self::SUCCESS])) {
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('IM SEAMLESS GAME API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
	{
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);


		$main_params = [
			"MerchantCode" => $this->merchant_code,
            "PlayerId" => $gameUsername,
            "Currency" =>  $this->currency,
            "Password" => $password
		];

		$params = array(
			"main_params" => $main_params,
			"actions" => [
				"function" => self::API_createPlayer,
				"method" => self::METHOD["POST"]
			]
		);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params)
	{
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

		$result = array(
			"response_result_id" => $responseResultId,
			"success" => $success,
			'player' => $gameUsername,
			'exists' => false
		);

		if ($success) {
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
			$result['exists'] = true;
		}

		return array($success, $result);
	}

    	/**
	 * @param string playerName
	 * @return array ("success"=>boolean, "exist"=>boolean)
	 */
	public function isPlayerExist($playerName)
	{
		$this->CI->utils->debug_log('IM_SEAMLESS_GAME_API (isPlayerExist)', $playerName);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$main_params = [
            "MerchantCode" => $this->merchant_code,
            "PlayerId" => $gameUsername
		];

		$params = array(
			"main_params" => $main_params,
			"actions" => [
				"function" => self::API_isPlayerExist,
				"method" => self::METHOD["POST"]
			]
		);
		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	public function processResultForIsPlayerExist($params)
	{
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array(
			"response_result_id" => $responseResultId,
			"success" => $success,
			"exists" => false
		);

		if ($success) {
			$result['exists'] = true;
		} else {
			$result['code'] = isset($resultArr['Code']) ? $resultArr['Code'] : null;
			$result['msg'] = isset($resultArr['Message']) ? $resultArr['Message'] : null;
			$result['exists'] = false;
		}

		return array($success, $result);
	}

    public function queryForwardGame($playerName, $extra = null)
    {
        $apiName = self::API_queryForwardGame;
        $player_id = $this->getPlayerIdFromUsername($playerName);
        $token = $this->getPlayerToken($player_id);
        $gameUsername = null;
        $player = null;
        $isDemo = true;
        if(isset($extra['game_mode']) && $extra['game_mode'] == 'real'){
            $isDemo = false;
            $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
            $player = $this->CI->player_model->getPlayerByUsername($playerName);
        }

        $this->CI->utils->debug_log('IM Player Details: ', $extra, $playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $is_mobile = $extra['is_mobile'];
        if ($is_mobile) {
            $this->URI_MAP[self::API_queryForwardGame] = self::MOBILE_LAUNCH_URL;
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

        $productWalletCode = null;
        $productWalletCodes = self::PRODUCT_WALLET_CODES;

        $productWalletCode = isset($productWalletCodes[$gameCode]) ? $productWalletCodes[$gameCode] : null;


        $staticGameCode = self::IM_SPORTSBOOK_GAME_CODE;
        $staticProductWallet = self::IM_ESPORTS_PRODUCT_WALLET;

        $main_params = [
            "MerchantCode" => $this->merchant_code,
            "PlayerId" => $gameUsername,
            "GameCode" => $gameCode,
            "Language" => $this->language,
            "IpAddress" => $this->CI->utils->getIP(),
            "ProductWallet" => $productWalletCode,
            "IsDownload" => self::IS_DOWNLOAD,
        ];
        $params = array(
			"main_params" => $main_params,
			"actions" => [
				"function" => $apiName,
				"method" => self::METHOD["POST"]
			]
		);

        return $this->callApi($apiName, $params, $context);
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
            $token = isset($resultArr['SessionToken']) ? $resultArr['SessionToken'] : null;

            if($this->use_third_party_token && $token != null){
                #save token here
                $this->CI->external_common_tokens->addPlayerToken($playerId, $token, $this->getPlatformCode(),$this->currency);
            }

            if(isset($resultArr['GameUrl'])){
                $result['url'] = $resultArr['GameUrl'];
            }
        }

        $this->CI->utils->debug_log("im launch url: " , $result['url']);
        $this->CI->utils->debug_log("im launch game status: " , $success);
        return array($success, $result);
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

    public function changePassword($playerName, $oldPassword, $newPassword) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'playerName' => $playerName,
			'password' => $newPassword,
		);

		$main_params = array(
			'MerchantCode' => $this->merchant_code,
            'PlayerId' => $playerName,
            'Password' => $newPassword
		);

        $params = array(
			"main_params" => $main_params,
			"actions" => [
				"function" => self::API_changePassword,
				"method" => self::METHOD["POST"]
			]
		);

        return $this->callApi(self::API_changePassword, $params, $context);

	}

	public function processResultForChangePassword($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->convertResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		if ($success) {

			$playerName = $this->getVariableFromContext($params, 'playerName');
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);

			if ($playerId) {
				$password = $this->getVariableFromContext($params, 'password');
				$this->updatePasswordForPlayer($playerId, $password);
			} else {
				$this->CI->utils->debug_log('IM SEAMLESS: cannot find player', $playerName);
			}

		}

		return array($success, $resultJson);
	}

    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
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

        $this->CI->utils->debug_log("IM SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        $prevTableData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();

        if($checkOtherTable||$this->force_check_other_transaction_table){            
            $prevTable = $this->getTransactionsPreviousTable();             
            $this->CI->utils->debug_log("IM SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', 'prevTable', $prevTable);
            $prevTableData = $this->queryOriginalGameLogsWithTable($prevTable, $dateFrom, $dateTo, $use_bet_time);                               
        }
        $gameRecords = array_merge($currentTableData, $prevTableData);        
        //$this->processGameRecordsFromTrans($gameRecords);
        return $gameRecords;
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ? AND original.trans_type = ?';


        $this->CI->utils->debug_log('IM SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.product_wallet', 'original.im_player_id','original.transaction_id','original.bet_id', 'original.action_id', 'original.before_balance', 'original.after_balance', 'original.external_uniqueid', 'original.amount'));

        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.amount,
    original.converted_amount,
    original.result_amount,
    original.game_id,
    original.im_player_id,
    original.transaction_id,
    original.bet_id,
    original.action_id,
    original.type,
    original.game_type,  
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
    gd.game_type_id
FROM {$table} as original
LEFT JOIN game_description as gd ON original.game_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE {$sqlTime};
EOD;

        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            self::TYPE_PLACE_BET
		];

		$this->CI->utils->debug_log('IM SEAMLESS GAME (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);

        return  $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }


    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('IM SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        $this->CI->utils->debug_log('IM SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row[updated_at]', $row['updated_at']);

        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_id'],
                'game_type'             => $row['game_type'],
                'game'                  => $row['game_description_name']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['im_player_id']
            ],
            'amount_info' => [
                'bet_amount'            => isset($row['converted_amount']) ? $row['converted_amount'] : 0,
                'result_amount'         => isset($row['result_amount']) ? $this->dBtoGameAmount($row['result_amount']) : 0,
				'bet_for_cashback'      => isset($row['converted_amount']) ? $row['converted_amount'] : 0,
				'real_betting_amount'   => isset($row['converted_amount']) ? $row['converted_amount'] : 0,
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
                'round_number'          => $row['bet_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => $row['balance_adjustment_method'],
            ],
            'bet_details' => $row['raw_data'],
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('IM after_balance:', $data);
        return $data;
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
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like im_seamless_wallet_transactions');

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
t.im_player_id as player_id,
t.updated_at as transaction_date,
t.amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.bet_id as round_no,
t.external_uniqueid as external_uniqueid,
t.balance_adjustment_method as trans_type,
t.raw_data as extra_info
FROM {$transTable} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;
        
        
        // $startDate = strval(strtotime($startDate) * 1000);
        // $endDate = strval(strtotime($endDate) * 1000);
        $params=[$this->getPlatformCode(),$startDate, $endDate];

        

        $this->CI->utils->debug_log('IM SEAMLESS GAME (queryTransactionByDateTime)', 'sql', $sql, 'params',$params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }
        
    public function processTransactions(&$transactions){
        $this->CI->utils->debug_log('IM process transaction', $transactions);
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
        
    public function dumpData($data){
        print_r(json_encode($data));exit;
    }

}

/*end of file*/