<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Lightning Seamless Integration
 * OGP-34123
 * ? uses lightning_seamless_service_api for its service API
 *
 * Game Platform ID: 6498
 *
 */

abstract class Abstract_game_api_common_lightning_seamless extends Abstract_game_api
{    
    # Fields in lightning_seamless_wallet_transactions we want to detect changes for update
	const MD5_FIELDS_FOR_ORIGINAL = [
		'bet_amount',
		'result_amount',
		'payout_amount',
		'valid_amunt',
		'total_amount',
		'game_status',
		'status',
		'room_id',
	];

	# Values of these fields will be rounded when calculating MD5
	const MD5_FLOAT_AMOUNT_FIELDS = [
		'bet_amount',
		'result_amount',
		'payout_amount',
		'valid_amount',
		'total_amount'
	];

	# Fields in game_logs we want to detect changes for merge and when md5_sum
	const MD5_FIELDS_FOR_MERGE = [
		'bet_amount',
		'before_balance',
		'after_balance',
		'result_amount',
		'payout_amount',
		'bet_amount',
		'status',
	];

	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
		'bet_amont',
		'valid_amount',
		'payout_amont',
		'result_amount',
	];

    const GET = 'GET';
    const POST = 'POST';
    const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';

    const STATUS_CODE_SUCCESS = 200;
    const SUCCESS_CODE = "Succeeded";
    const SUCCESS_MESSAGE = "success";

    const ROOM_THEME_HORIZONTAL_VERSION = 0;
    const ROOM_THEME_VERTICAL_VERSION = 1;

    const DEFAULT_ROOM_THEME_VERSION = self::ROOM_THEME_HORIZONTAL_VERSION;
    const DEVICE_TYPE_PC = 0;
    const DEVICE_TYPE_HTML5 = 1;
    
    const DEMO = 'demo';

    public $URI_MAP = [
        self::API_login => "/api/auth/login",
        self::API_queryForwardGame => "/api/game/forward-game",
        self::API_createPlayer => "/api/user",
    ];
    

    public $agent_name;
    public $agent_key;
    public $force_disable_home_link;
    public $use_third_party_token;
    public $enable_merging_rows;
    public $use_monthly_transactions_table;
    public $allowed_days_to_check_previous_monthly_table;
    public $force_check_other_transaction_table;
    public $game_list_url;
    public $api_url;
    public $return_url;
    public $deposit_url;
    public $game_list_api_url;
    public $currency;
    public $language;
    public $force_language;
    public $original_transactions_table;
    public $method;
    public $launcher_mode;
    private $token = null;
    private $theme_version;
    public $allow_multiple_saving_of_transactions_on_single_request;
    public $allow_token_validation;
    public $enable_write_response_result_to_dir;
    public $append_agent_prefix;

    public function __construct()
    {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->game_list_api_url = $this->getSystemInfo('game_list_api_url', 'https://www.cmsbetconstruct.com/');
        $this->return_url = $this->getSystemInfo('return_url');
        $this->deposit_url = $this->getSystemInfo('deposit_url', '');
        $this->currency = $this->getSystemInfo('currency');
        $this->language = $this->getSystemInfo('language', 'en');
        $this->force_language = $this->getSystemInfo('force_language', '');
        $this->original_transactions_table = 'lightning_seamless_wallet_transactions';
        $this->agent_name = $this->getSystemInfo('agent_name', null);
        $this->agent_key = $this->getSystemInfo('agent_key', "");
        $this->theme_version = $this->getSystemInfo('theme_version', self::DEFAULT_ROOM_THEME_VERSION);
        $this->allow_multiple_saving_of_transactions_on_single_request = $this->getSystemInfo('allow_multiple_saving_of_transactions_on_single_request',true);
        $this->allow_token_validation = $this->getSystemInfo('allow_token_validation', true);
        $this->enable_write_response_result_to_dir = $this->getSystemInfo('enable_write_response_result_to_dir', true);
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username', '');

        $this->force_disable_home_link = $this->getSystemInfo('force_disable_home_link', false);
        $this->use_third_party_token = $this->getSystemInfo('use_third_party_token', true);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', false);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', true);
        $this->allowed_days_to_check_previous_monthly_table = $this->getSystemInfo('allowed_days_to_check_previous_monthly_table', 1);
        $this->force_check_other_transaction_table = $this->getSystemInfo("force_check_other_transaction_table", false);
        $this->launcher_mode = $this->getSystemInfo('launcher_mode', 'singleOnly');

        // fix exceed game username length
        $this->append_agent_prefix = $this->getSystemInfo('append_agent_prefix', false);
    }


    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode()
    {
        return LIGHTNING_SEAMLESS_GAME_API;
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
        $header = array(
            "Content-Type" => "application/json",
        );

        if($this->token !== null){
            $header['Authorization'] = "Bearer ". $this->token;
        }
        $this->CI->utils->debug_log('LIGHTNING_SEAMLESS_GAME_API: @getHttpHeaders params', $header);
        return $header;
    }
    protected function customHttpCall($ch, $params)
    {
		switch($this->method){
			case self::METHOD_POST:
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
				break;
			case self::METHOD_GET:
				$params=http_build_query($params);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::METHOD_GET);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				break;

		}
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $is_querytransaction = false)
    {
        $success = false;
        if (isset($resultArr['code']) && $resultArr['code']== self::SUCCESS_CODE) {
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('LIGHTNING_SEAMLESS_GAME_API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }  


	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        $this->CI->load->model(array('agency_model', 'player_model'));
        $agentPrefix = $playerPrefix = '';

        if($this->append_agent_prefix){
            $player = $this->CI->player_model->getPlayerByUsername($playerName);
            $this->CI->utils->debug_log("LIGHTNING_SEAMLESS_GAME_API @createPlayer player", $player);
            $this->CI->utils->debug_log("LIGHTNING_SEAMLESS_GAME_API @createPlayer player last_query", $this->CI->db->last_query());
            if(!empty($player->agent_id)){
                $agentPrefix = $this->CI->agency_model->getPlayerPrefixByAgentId($player->agent_id);
                $this->CI->utils->debug_log("LIGHTNING_SEAMLESS_GAME_API @createPlayer agent_prefix", $agentPrefix);
                $this->CI->utils->debug_log("LIGHTNING_SEAMLESS_GAME_API @createPlayer agent_prefix last_query", $this->CI->db->last_query());
                if(!empty($agentPrefix)){
                    $playerPrefix .= $agentPrefix;
                }
            }
        }
        $playerPrefix .= $this->prefix_for_username;

        $this->CI->utils->debug_log('LIGHTNIGN_SEAMLESS_GAME_API playerPrefix', $playerPrefix);


        $extra = [
            'prefix' => $playerPrefix,
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
        ];

        $this->CI->utils->debug_log("LIGHTNING_SEAMLESS_GAME_API @createPlayer extra params", $extra);
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);
        
        $params = array(
            "userName" => (string)$gameUsername,
            "nickName" => (string)$gameUsername,
            "device" => self::DEVICE_TYPE_HTML5,
            "currency" => strtoupper($this->currency),
        );

		$this->method = self::METHOD_POST;
        $this->generateToken($playerName);
		return $this->callApi(self::API_createPlayer, $params, $context);

    }

	public function processResultForCreatePlayer($params)
	{
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

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

    protected function saveResponseResult($success, $apiName, $params, $resultText, $statusCode, $statusText = null,
        $extra = null, $field = null, $dont_save_response_in_api = false, $costMs=null) {
        
        if(!empty($extra)){
            $extra = json_decode($extra);
            $extra[]['header'] = $this->getHttpHeaders();
            $extra = json_encode($extra);
        }
        //save to db
        $this->CI->load->model("response_result");
        $flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        if($field==null){
            $field=[];
        }
        //try add decoded_result_text
        $decoded_result_text=$this->getDecodedResultText($resultText, $apiName, $params, $statusCode);
        if(!empty($decoded_result_text)){
            $field['decoded_result_text']=$decoded_result_text;
        }

        return $this->CI->response_result->saveResponseResult($this->SYSTEM_TYPE_ID, $flag, $apiName, json_encode($params), $resultText, $statusCode, $statusText,
            $extra, $field, $dont_save_response_in_api, null, $costMs, $this->transfer_request_id, $this->_proxySettings);
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

    public function queryForwardGame($playerName, $extra = null)
    {
        $this->CI->utils->debug_log("LIGHTNING_SEAMLESS_GAMES_API @queryForwardGame extra value", $extra);
        $this->method = self::POST;
        $player_id = $this->getPlayerIdFromUsername($playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $isDemo = !(isset($extra['game_mode']) && $extra['game_mode'] == 'real');
    
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'player' => null,
            'player_id' => $player_id,
            'isDemo' => $isDemo
        ];
        
        $return_url = $this->getReturnUrl($extra);
    
        $gameCode = isset($extra['game_code']) && !empty($extra['game_code']) ? $extra['game_code'] : null;

        $language = $this->selectLanguage($extra);

        $this->CI->utils->debug_log("LIGHTNING_SEAMLESS_GAMES_API @queryForwardGame language params", $language);
    
        $params = [
            'userName' => (string)$gameUsername,
            'language' => (string)$language,
            'roomId' => (string)$gameCode,
            'roomTheme' => (int)$this->theme_version,
        ];

        $this->CI->utils->debug_log("LIGHTNING_SEAMLESS_GAMES_API @queryForwardGame final params", $params);
    
        $this->generateToken($playerName);
        $this->CI->utils->debug_log('LIGHTNING_SEAMLESS_GAME_API: @queryForwardGame current $this->token', $this->token);
        $this->CI->utils->debug_log("LIGHTNING_SEAMLESS_GAME_API @queryForwardGame: PARAMS", $params);
    
        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params)
	{
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$isDemo = $this->getVariableFromContext($params, 'isDemo');

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

		$result = array(
			"response_result_id" => $responseResultId,
			"success" => $success,
			'player' => $gameUsername,
		);

		if($success){
			// $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
			if(isset($resultArr['data']['url']))
            {
                $url = isset($resultArr['data']['url']) ? $resultArr['data']['url'] : null;
                $data['url'] = $url;
                $data['is_demo'] = $isDemo;

                if(!empty($url) && $isDemo){
                    $url = $this->rebuildLaunchUrl($data);
                }
                $result['url'] = $url;
            }
		}

		return array($success, $result);
	}

    private function rebuildLaunchUrl($data=[]){
        $url = isset($data['url']) ? $data['url'] : null;
        $isDemo = isset($data['is_demo']) ? $data['is_demo'] : false;

        $urlComponents = parse_url($url);

        parse_str($urlComponents['query'], $queryParams);
        if($isDemo)  $queryParams['token'] = self::DEMO;

        $newQueryString = http_build_query($queryParams);
        return  $urlComponents['scheme'] . '://' . $urlComponents['host'] . $urlComponents['path'] . '?' . $newQueryString;
    }

    private function getReturnUrl($extra){
        if (isset($extra['home_link']) && !empty($extra['home_link'])) {
            $return_url = $extra['home_link'];
        } elseif (isset($extra['extra']['t1_lobby_url']) && !empty($extra['extra']['t1_lobby_url'])) {
            $return_url = $extra['extra']['t1_lobby_url'];
        } elseif (!empty($this->return_url)) {
            $return_url = $this->return_url;
        } else {
            $return_url = $this->getHomeLink();
        }
        return $return_url;
    }
    private function generateToken($playerName){
        $player_id = $this->getPlayerIdFromUsername($playerName);
        $this->utils->debug_log("LIGHTNING_SEAMLESS_GAME_API: (generateToken)");   
        $this->method = self::POST;
        $params = [
            'agentUserName' => $this->agent_name,
            'agentKey' => $this->agent_key
        ];
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGenerateToken',
            'player_id' => $player_id,
        );

        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForGenerateToken($params){
        $token=null;
        $playerId = $this->getVariableFromContext($params, 'player_id');
		$this->CI->utils->debug_log('LIGHTNING_SEAMLESS_GAME_API: (generateToken)', $params);	

		$resultArr = $this->getResultJsonFromParams($params);   

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
        $this->CI->utils->debug_log('LIGHTNING_SEAMLESS_GAME_API: @generateToken response', $resultArr);

        $token = $this->token = isset($resultArr['data']['token']) ? $resultArr['data']['token'] : null;
        $this->saveExternalCommonToken($token, $playerId);
        return $token;
	}

    private function saveExternalCommonToken($token, $playerId){
        $this->CI->load->model(['external_common_tokens']);
        if($this->use_third_party_token && $token != null){
            #save token here
            $extra = [
                'game_platform_id' => $this->getPlatformCode(),
                'raw_token' => $token
            ];
            $this->CI->external_common_tokens->addPlayerTokenWithExtraInfo($playerId, md5($token), json_encode($extra), $this->getPlatformCode(),$this->currency);
        }
    }

    private function selectLanguage($extra){
        $language = $this->language;

		if(isset($extra['language']) && !empty($extra['language'])){
            $language = $extra['language'];
        }else{
            $language = $this->language;
        }

		if($this->force_language && !empty($this->force_language)){
            $language = $this->force_language;
        }	
		$language = $this->getLauncherLanguage($language);
        return $language;
    }

    private function buildLaunchUrl($params){
       $params = http_build_query($params);
        return $this->api_url.$this->URI_MAP[self::API_queryForwardGame].'?'.$params;
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

        $this->CI->utils->debug_log("LIGHTNING_SEAMLESS_GAME_API: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        $prevTableData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();

        if($checkOtherTable||$this->force_check_other_transaction_table){            
            $prevTable = $this->getTransactionsPreviousTable();             
            $this->CI->utils->debug_log("LIGHTNING_SEAMLESS_GAME_API: (queryOriginalGameLogs) tables used", 'original_transactions_table', 'prevTable', $prevTable);
            $prevTableData = $this->queryOriginalGameLogsWithTable($prevTable, $dateFrom, $dateTo, $use_bet_time);                               
        }
        return array_merge($currentTableData, $prevTableData);      
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';
        if($use_bet_time){
            $sqlTime='`original`.`created_at` >= ? AND `original`.`created_at` <= ?';
        }

        $this->CI->utils->debug_log('LIGHTNING_SEAMLESS_GAME_API GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.room_id', 'original.bet_amount','original.payout_amount', 'original.valid_amount', 'original.result_amount','original.result_amount','original.status', 'original.before_balance', 'original.after_balance'));


        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.total_amount,
    original.bet_amount,
    original.payout_amount,
    original.valid_amount as valid_bet_amount,
    original.result_amount,
    original.room_id as game_id,
    original.player_id,
    original.bet_id,
    original.transaction_id,
    original.bet_id as round_id,

    original.trans_type,
    original.status,
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
LEFT JOIN game_description as gd ON original.room_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE 
original.game_platform_id=? 
AND {$sqlTime}
;
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];
        
		$this->CI->utils->debug_log('LIGHTNING_SEAMLESS_GAME_API (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);  
    }


    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('LIGHTNING_SEAMLESS_GAME_API GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        $this->CI->utils->debug_log('LIGHTNING_SEAMLESS_GAME_API GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row[updated_at]', $row['updated_at']);

        $bet_amount = isset($row['bet_amount']) ? $row['bet_amount'] : 0;
        $valid_bet_amount = isset($row['valid_bet_amount']) ? $row['valid_bet_amount'] : 0;
        $payout_amount = isset($row['payout_amount']) ? $row['payout_amount'] : 0;
        $result_amount = isset($row['result_amount']) ? $row['result_amount'] : 0;
        $username = $this->getGameUsernameByPlayerId($row['player_id']);
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
                'player_username'       => $username
            ],
            'amount_info' => [
                'bet_amount'            => $bet_amount,
                'result_amount'         => $result_amount,
				'bet_for_cashback'      => $valid_bet_amount,
				'real_betting_amount'   => $bet_amount,
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
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => $row['balance_adjustment_method'],
            ],
            'bet_details' =>            $this->preprocessBetDetails($row, null, true),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
        $this->utils->debug_log('LIGHTNING_SEAMLESS_GAME_API makeParamsForInsertOrUpdateGameLogsRow parameters:', $data);
        return $data;;
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

    public function queryPlayerBalance($gameUsername) {
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = [
            'success' => true,
            'balance' => $balance
        ];

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
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like ' . $this->original_transactions_table);

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
t.player_id,
t.updated_at as transaction_date,
t.result_amount as amount,
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
        
        $params=[$this->getPlatformCode(),$startDate, $endDate];

        $this->CI->utils->debug_log('LIGHTNING_SEAMLESS_GAME_API (queryTransactionByDateTime)', 'sql', $sql, 'params',$params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }
        
    public function processTransactions(&$transactions){
        $this->CI->utils->debug_log('LIGHTNING_SEAMLESS_GAME_API process transaction', $transactions);
        $temp_game_records = [];

        if(!empty($transactions)){
           
            foreach($transactions as $transaction){
                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
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
    
    public function defaultBetDetailsFormat($row) {
        $bet_details = [];

        if (isset($row['game_english_name'])) {
            $bet_details['game_name'] = $row['game_english_name'];
        }
        if (isset($row['bet_id'])) {
            $bet_details['round_id'] = $row['bet_id'];
        }
        if (isset($row['external_uniqueid'])) {
            $bet_details['bet_id'] = $row['external_uniqueid'];
        }

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] = $row['bet_amount'];
        }
        if (isset($row['payout_amount'])) {
            $bet_details['win_amount'] = $row['payout_amount'];
        }

        if (isset($row['created_at'])) {
            $bet_details['betting_datetime'] = $row['created_at'];
        }

        return $bet_details;
    }

    public function getLauncherLanguage($language){
		switch (strtolower($language)) {
            case Language_function::INT_LANG_ENGLISH:
            case "en":
            case "en_us":
            case "en-us":
                return "en";
                break;
            case Language_function::INT_LANG_CHINESE:
             case "zh-hans":
                return "zh-hans";
                break;

            case Language_function::INT_LANG_INDONESIAN:
            case "id-id":
                return "id";
                break;
            case Language_function::INT_LANG_VIETNAMESE:
			case "vi-vn":
            case "vi-vi":
                return "vi-vn";
                break;
            case Language_function::INT_LANG_PORTUGUESE_BRAZIL:
            case Language_function::INT_LANG_PORTUGUESE:
            case "pt-br":
            case "pt-BR":
            case "pt_br":
            case "pt":
                return "pt-br";
                break;
            case Language_function::INT_LANG_THAI:
            case "th-th":
                return "th";
                break;
            case "hi-hi":
                return "hi";
                break;
            default:
                return "en";
                break;
        }
    }
        
    public function dumpData($data){
        print_r(json_encode($data));exit;
    }

}

/*end of file*/