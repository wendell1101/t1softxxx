<?php
/**
 * Cherry Gaming Game Integration
 * OGP-28807
 *
 * @author  Kristallynn Tolentino
 *
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_cherry_gaming_seamless extends Abstract_game_api {

    const POST = 'POST';
    
    const SUCCESS = 200;

    const ERROR_CODE_SUCCESS = [
        'ErrorCode' => 0,
        'ErrorMsg' => 'Success'
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'round_id',
        'status',
        'updated_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'result_amount',
    ];

    const TRANSACTION_TYPE_BET = 'bet';
    const TRANSACTION_TYPE_WIN = 'win';
    const TRANSACTION_TYPE_LOSS = 'loss';
    const TRANSACTION_TYPE_CANCEL = 'cancel';
    const TRANSACTION_TYPE_REFUND = 'refund';

    //Methods
    const REGISTER_PLAYER = 'RegisterPlayer';
    const VERIFY_PLAYER = 'VerifyPlayer';
    const LOGIN = 'Login';
    const QUERY_BET_RECORD ='QueryBetRecord';

    //Mode
    const MODE_NORMAL = 0;
    const MODE_TIME_INTERVAL = 1;
    const MODE_TRANSACTION_ID = 2;

    public $lobby_code;
    public $api_key;

    public $original_transaction_table_name;
    public $returnUrl;

    public function __construct() {
        parent::__construct();

        $this->api_url = $this->getSystemInfo('url', 'https://web-stage.jack-poker.com/api/web');
        $this->lang = $this->getSystemInfo('lang');
        $this->currency = $this->getSystemInfo('currency', 'BRL');

        $this->lobby_code = $this->getSystemInfo('lobby_code', 'V530');
        $this->api_key = $this->getSystemInfo('api_key', 'BDEE109EA0C340DB98F54DD054C99345');
        $this->returnUrl = $this->getSystemInfo('returnUrl');

        $this->original_transaction_table_name = 'cherry_gaming_seamless_wallet_transactions';
    }

    public function getTransactionsTable(){
        return $this->original_transaction_table_name;
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return CHERRY_GAMING_SEAMLESS_GAME_API;
    }

    public function generateUrl($apiName, $params) {
        return $this->api_url;
    }

    protected function customHttpCall($ch, $params) {
        if($params["actions"]["method"] == self::POST)
        {
            $function = $params["actions"]['function'];

            unset($params["actions"]);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, TRUE);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params["main_params"]));

        }
	}

     /**
     * Check if a player already exists in the CG platform.
     * 
     * @param Method String VerifyPlayer
     * @param ApiKey String Lobby API key
     * @param PlayerName String Player name (2-20 characters)
     * 
     * @return json
     */
    public function isPlayerExist($playerName){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$main_params = array(
            'Method' => self::VERIFY_PLAYER,
            'ApiKey' => $this->api_key,
            'PlayerName' => $gameUsername,
		);

        $params = array(
            "main_params" => $main_params,
            "actions" => [
                "function" => self::API_isPlayerExist, 
                "method" => self::POST
            ]
        );

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array();

		if($success){
			if(isset($resultArr['IsExist'])&&$resultArr['IsExist']){
				$result['exists'] = true;
			}else{
                $result['exists'] = false;
            }
		}else{
			$result['exists'] = false;
		}

		return array($success, $result);
    }

    /**
     * Create a player in CG platform with designated currency. Each player can only have 1 currency which cannot be changed after creation.
     * 
     * @param Method String RegisterPlayer
     * @param ApiKey String Lobby API key
     * @param PlayerName String Player name (2-20 characters)
     * @param Currency String
     * 
     * @return json
     */
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
            "Method" => self::REGISTER_PLAYER,
            "ApiKey" => $this->api_key,
            "PlayerName" => $gameUsername,
            "Currency" => $this->currency
        ];

        $params = array(
            "main_params" => $main_params,
            "actions" => [
                "function" => self::API_createPlayer, 
                "method" => self::POST
            ]
        );

		return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success
        );
        
        if($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
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

    //Login then launch game 
    public function login($playerName, $password = null, $extra = null) 
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

    	$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername
		);

        $main_params = [
            "Method" => self::LOGIN,
            "ApiKey" => $this->api_key,
            "PlayerName" => $gameUsername,
            "Currency" => $this->currency
        ];

        $params = array(
            "main_params" => $main_params,
            "actions" => [
                "function" => self::API_login, 
                "method" => self::POST
            ]
        );

		return $this->callApi(self::API_login, $params, $context);
	}

    public function processResultForLogin($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        $this->CI->utils->debug_log('CG resultArr: (' . __FUNCTION__ . ')', $resultArr);

        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success,
            "token" => $resultArr['Token'],
            "gamename" => $resultArr['DisplayName']
        );

        $this->CI->utils->debug_log('CGSuccess: (' . __FUNCTION__ . ')', $success);

        if($success){
            $result['url'] = $resultArr['LaunchURL'];
        }

        return array($success, $result);
	}

    public function queryForwardGame($playerName,$extra=[]) {
        $result = $this->login($playerName);
        $getTokenResult = $result['token'];
        $lang = isset($extra['lang']) ? $extra['lang'] : $this->lang;
        $game_code = isset($extra['game_code']) ? "&Table=".$extra['game_code'] : '';
        $returnurl = isset($this->returnUrl) ? "&ReturnUrl=".$this->returnUrl : '';

        if($this->utils->is_mobile()) {
            $platform = 1;
        }else{
            $platform = 0;
        }

        $url = $result['url']."?PlayerName=".$result['gamename']."&Token=".$getTokenResult."&LobbyCode=".$this->lobby_code."&Lang=".$lang.$returnurl."&Platform=".$platform."&WebView=0".$game_code;
			
        return array("success" => true, "url" => $url);
    }

    public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en-us':
            case 'en_us':
            case 'EN':
            case 'en':
                $lang = 'en'; #english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh-cn':
            case 'zh_cn':
            case 'ZH':
            case 'zh':
                $lang = 'zh'; #chinese
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
                $lang = 'pt'; #Portuguese
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = '') {
        $success = false;

        $this->CI->utils->debug_log('CG: (' . __FUNCTION__ . ')', $resultArr);

        if(isset($resultArr['ErrorCode']) && $resultArr['ErrorCode'] == self::ERROR_CODE_SUCCESS['ErrorCode']){
            $success = true;
        }else{
            $this->setResponseResultToError($responseResultId);
        }

        return $success;
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    ////////////////////////////////////////////

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

        $sqlTime='transaction.updated_at >= ? and transaction.updated_at <= ?';

        if($use_bet_time) {
            $sqlTime='transaction.start_at >= ? and transaction.start_at <= ?';
        }
        $sql = <<<EOD
SELECT
    transaction.id as sync_index,
    transaction.amount as amount,
    transaction.total_bet_amount as bet_amount,
    transaction.result_amount,
    transaction.before_balance,
    transaction.after_balance,
    transaction.status,
    transaction.start_at,
    transaction.end_at,
    transaction.transaction_type,
    transaction.game_id,

    transaction.external_unique_id as external_uniqueid,
    transaction.updated_at,
    transaction.response_result_id,
    transaction.round_id,
    transaction.md5_sum,

    transaction.player_id,

    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.game_code as game_code,
    game_description.game_type_id
FROM
    {$this->original_transaction_table_name} as transaction
    LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE
transaction_type != "refund" and transaction_type != "bet"  and {$sqlTime} and transaction.game_platform_id = ?

EOD;

        $params = [
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;

    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {

        if(empty($row['md5_sum']))
        {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

        $bet_amount = isset($row['bet_amount']) ? $this->gameAmountToDBGameLogsTruncateNumber($row['bet_amount']) : 0;
        $data = [
            'game_info' => [
                'game_type_id'          => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'   => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'             => null,
                'game'                  => isset($row['game_description_name']) ? $row['game_description_name'] : null,
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => null
            ],
            'amount_info' => [
                'bet_amount'            => $bet_amount,
                'result_amount'         => $this->gameAmountToDBGameLogsTruncateNumber($row['result_amount']),
                'bet_for_cashback'      => $bet_amount,
                'real_betting_amount'   => $bet_amount,
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $this->gameAmountToDBGameLogsTruncateNumber($row['after_balance']),
            ],
            'date_info' => [
                'start_at'              => $row['start_at'],
                'end_at'                => $row['end_at'],
                'bet_at'                => $row['start_at'],
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
                'bet_type'              => null
            ],
            'bet_details' => "",
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('CG ', $data);
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

        switch($row['status']) {
            case 'ok':
            case 'SETTLED':
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            case 'CANCELLED':
                $row['note'] = 'Cancelled';
                $row['status'] = Game_logs::STATUS_CANCELLED;
                break;
            default:
                $row['status'] = Game_logs::STATUS_PENDING;
                break;
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
                $unknownGame->game_type_id, $row['game_id'], $row['game_id']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }


    public function timestamps_milliseconds() {
        $date = new DateTimeImmutable();
        $timestampMs = (int) ($date->getTimestamp() . $date->format('v'));
        return $timestampMs;
    }

    public function queryTransactionByDateTime($startDate, $endDate){

        $sql = <<<EOD
SELECT
t.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_unique_id as external_uniqueid,
t.transaction_type trans_type,
t.extra_info extra_info
FROM {$this->original_transaction_table_name} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;

        $params=[$this->getPlatformCode(),$startDate, $endDate];

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
                    $temp_game_record['transaction_date'] = $transaction['transaction_date'];
                    $temp_game_record['amount'] = $this->gameAmountToDBGameLogsTruncateNumber($transaction['amount']);
                    $temp_game_record['before_balance'] = $this->gameAmountToDBGameLogsTruncateNumber($transaction['before_balance']);
                    $temp_game_record['after_balance'] = $this->gameAmountToDBGameLogsTruncateNumber($transaction['after_balance']);
                    $temp_game_record['round_no'] = $transaction['round_no'];
                    $extra_info = @json_decode($transaction['extra_info'], true);
                    $extra=[];
                    $extra['trans_type'] = $transaction['trans_type'];
                    $extra['extra'] = $extra_info;
                    $temp_game_record['extra_info'] = json_encode($extra);
                    $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                    if($transaction['trans_type'] == self::TRANSACTION_TYPE_BET || $transaction['trans_type'] == self::TRANSACTION_TYPE_LOSS){
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                    }elseif($transaction['trans_type'] == self::TRANSACTION_TYPE_CANCEL || $transaction['trans_type']==self::TRANSACTION_TYPE_WIN){
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                    }

                    $temp_game_records[] = $temp_game_record;
                    unset($temp_game_record);
                }
            }

            $transactions = $temp_game_records;

            $this->CI->utils->debug_log('CG: (' . __FUNCTION__ . ')', 'transactions:', $transactions);
        }    
        
    public function queryBetRecord($transactionId){
        
    	$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processQueryBetRecord',
		);

        $main_params = [
            "Method" => self::QUERY_BET_RECORD,
            "ApiKey" => $this->api_key,
            "Mode" => self::MODE_TRANSACTION_ID,
            "TxnID" => $transactionId
        ];

        $params = array(
            "main_params" => $main_params,
            "actions" => [
                "function" => 'queryBetRecord', 
                "method" => self::POST
            ]
        );

		return $this->callApi('queryBetRecord', $params, $context);
	}

    public function processQueryBetRecord($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        $this->CI->utils->debug_log('CG resultArr: (' . __FUNCTION__ . ')', $resultArr);

        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success,
            "resultArr" => $resultArr
        );

        return array($success, $result);
	}
}