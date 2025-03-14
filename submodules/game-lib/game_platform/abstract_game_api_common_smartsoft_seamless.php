<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * SMARTSOFT Seamless Integration
 * OGP-31149
 * ? uses smartsoft_seamless__service_api for its service API
 *
 * Game Platform ID: 6326
 *
 */

abstract class Abstract_game_api_common_smartsoft_seamless extends Abstract_game_api {

    public $URI_MAP , $METHOD_MAP, $url, $method, $currency, $language, $force_lang ,$portal_name,$key ,$original_transactions_table,$use_monthly_transactions_table, $use_table_for_rebuild_seamless_history, $enable_signature_validation;

    const POST                  = 'POST';
    const GET                   = 'GET';
    const API_SUCCESS           =  0;

    const MD5_FIELDS_FOR_MERGE = [
        'updated_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'amount',
    ];

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(array('wallet_model','game_provider_auth','common_token','player_model', 'ip','game_logs','external_common_tokens'));

        $this->URI_MAP = array(
            self::API_queryForwardGame  => 'GameLauncher/Loader.aspx',
            self::API_queryDemoGame     => 'GameLauncher/Loader.aspx',
        );
    
        $this->METHOD_MAP = array(
            self::API_queryForwardGame => self::GET,                    
            self::API_queryDemoGame    => self::GET,                    
        ); 

        $this->url                              = $this->getSystemInfo('url');
        $this->currency                         = $this->getSystemInfo('currency');
        $this->language                         = $this->getSystemInfo('language', 'en-us');
        $this->force_lang                       = $this->getSystemInfo('force_lang', false);
        $this->portal_name                      = $this->getSystemInfo('portal_name');
        $this->key                              = $this->getSystemInfo('key');
        $this->use_monthly_transactions_table   = $this->getSystemInfo('use_monthly_transactions_table', false);
        $this->use_table_for_rebuild_seamless_history   = $this->getSystemInfo('use_table_for_rebuild_seamless_history', false);
        $this->enable_signature_validation      = $this->getSystemInfo('enable_signature_validation', true);
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return SMARTSOFT_SEAMLESS_GAME_API;
    }

    public function getCurrency(){
        return $this->currency;
    }

    public function queryForwardGame($playerName, $extra = null)
    {
        $apiName        = self::API_queryForwardGame;
        $player         = $this->CI->player_model->getPlayerByUsername($playerName);
        $player_id      = $player->playerId;
        $player_token   = $this->getPlayerToken($player_id);

        $params = [
            'GameCategory'  => $this->getGameCategory($extra['game_code']),
            'GameName'      => $extra['game_code'],
            'Token'         => $this->currency.'-'.$player_token,
            'ReturnUrl'     => null,
            'Lang'          => $this->getLauncherLanguage($this->language),
            'PortalName'    => $this->portal_name
        ];

        if($extra['game_mode'] == 'trial'){
            $params = [
                'GameCategory'  => $this->getGameCategory($extra['game_code']),
                'GameName'      => $extra['game_code'],
                'Token'         => 'DEMO',
                'Lang'          => $this->getLauncherLanguage($this->language),
                'PortalName'    => 'demo'
            ];
            $apiName        = self::API_queryDemoGame;
        }
        $url = $this->generateUrl($apiName,$params);
        return [
            'success' => true,
            'url'     => $url,
        ];
    }


    public function getGameCategory($gameCode=null){
        $defaultGameCategory = "Slots";
        $gamesCategories = [
            'Games' => [
                'Cappadocia','Balloon','FootballX','PlinkoX','BonusRoulette','AnimationRoulette',
            ],
            'XGames' => [
                'SpinX','JetX3','CricketX','CrazyHuntX','SlicerX','TowerX','JokerBuyBonus',
            ],
            'Roulette' => [
                'VirtualRoulette','VirtualBurningRoulette','VirtualClassicRoulette',
            ],
            'Keno' => [
                'ClassicKeno','RussianKeno','VipKeno',
            ],
            'JetX' => [
                'JetX'
            ],        
        ];

        if($gameCode){
            foreach($gamesCategories as $gamecategory => $gamecategories){
                if(in_array($gameCode,$gamecategories)){
                    return $gamecategory;
                }
            }
            return $defaultGameCategory;
        }
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
        $this->utils->debug_log("SMARTSOFT SEAMLESS: (createPlayer)");

        # create player on game provider auth
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for SMARTSOFT seamless api";
        if($return){
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfull create account for SMARTSOFT seamless api";
        }
        
        return array("success" => $success, "message" => $message);
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

        $this->CI->utils->debug_log("SMARTSOFT SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        $prevTableData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();

        if($checkOtherTable||$this->force_check_other_transaction_table){            
            $prevTable = $this->getTransactionsPreviousTable();   
            $this->CI->utils->debug_log("SMARTSOFT SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', 'prevTable', $prevTable);
            $prevTableData = $this->queryOriginalGameLogsWithTable($prevTable, $dateFrom, $dateTo, $use_bet_time);                               
        }

        $gameRecords = array_merge($currentTableData, $prevTableData);        
        //$this->processGameRecordsFromTrans($gameRecords);
        return $gameRecords;
    }


    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $this->utils->debug_log('SMARTSOFT-syncOrig', $table, $dateFrom, $dateTo);           
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';
        $actionType='AND original.trans_type in ("InitialBet", "PlaceBet")';
      
        $this->CI->utils->debug_log('SMARTSOFT SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.amount', 'original.after_balance', 'original.updated_at'));
        //result amount = win - bet
        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.player_id,
    original.player_name,
    original.transaction_id,
    original.transaction_type,
    original.amount,
    original.currency_code,
    original.source,
    original.game_name,
    original.round_id,
    original.game_number,
    original.cashier_transaction_id,
    original.currency,
    original.trans_type,
    original.trans_status as status,
    original.balance_adjustment_amount,
    original.balance_adjustment_method,
    original.before_balance,
    original.after_balance,
    original.external_uniqueid,
    original.game_platform_id,
    original.created_at,
    original.updated_at,
    original.response_result_id,
    original.extra_info,
        
    MD5(CONCAT({$md5Fields})) as md5_sum,
    gd.game_code as game_code,
    gd.game_name as game_name,
    gd.id as game_description_id,
    gd.game_name as game_description_name,
    gd.game_type_id

FROM {$table} as original
LEFT JOIN game_description as gd ON original.game_name = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE 
{$sqlTime} {$actionType};
EOD;
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];

		$this->CI->utils->debug_log('SMARTSOFT-syncSQL', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('SMARTSOFT SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        $bet_details = $row['extra_info']['TransactionInfo'];

        // $row['updated_at'] = date('Y-m-d H:i:s', ($row['updated_at']/1000));
        $this->CI->utils->debug_log('SMARTSOFT SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row[updated_at]', $row['updated_at']);

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
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => $row['bet_amount'],
                'real_betting_amount'   => $row['bet_amount'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance'],
            ],
            'date_info' => [
                'start_at'              => $row['transaction_date'],
                'end_at'                => $row['transaction_date'],
                'bet_at'                => $row['transaction_date'],
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
                'bet_type'              => null,
            ],
            'bet_details' => $this->formatBetDetails($bet_details),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('SMARTSOFT ', $data);
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

        $tableName                  = $this->getTransactionsTable();
        $getBetCloseRoundDetails    = isset($this->queryBetCloseRoundDetails($row['round_id'],$tableName)[0]) ? $this->queryBetCloseRoundDetails($row['round_id'],$tableName)[0] : null;
        $row['extra_info']          = isset($getBetCloseRoundDetails['extra_info']) ? json_decode($getBetCloseRoundDetails['extra_info'],true) : json_decode($row['extra_info'],true);
        $transaction_info           = $row['extra_info']['TransactionInfo'];
        $row['status']              = isset($getBetCloseRoundDetails['status']) ? $getBetCloseRoundDetails['status'] : $row['status'];
        $row['bet_amount']          = isset($transaction_info['TotalPlacedBet']) ? $transaction_info['TotalPlacedBet'] : $row['amount'];
        $row['win_amount']          = isset($transaction_info['TotalWon']) ? $transaction_info['TotalWon'] : 0;
        $row['after_balance']       = isset($getBetCloseRoundDetails['after_balance']) ? ($getBetCloseRoundDetails['after_balance']) : $row['after_balance'];

        if (isset($row['extra_info']['TransactionInfo']['GameName']) && $row['extra_info']['TransactionInfo']['GameName'] === "JetX") {
            $row['win_amount'] = $this->getBetWinAmount($row,$tableName);
        }

        $row['result_amount']       = $row['win_amount'] - $row['bet_amount'];
        $row['transaction_date']    = $this->gameTimeToServerTime($transaction_info['TransactionDate']);
    }

    public function getBetWinAmount($row, $table_name = null){
        $roundId = $row['round_id'];
        $betId = $row['external_uniqueid'];
$sql = <<<EOD
SELECT 
t.amount,
t.extra_info

FROM {$table_name} as t

WHERE t.trans_type = ? and t.round_id = ?
EOD;
        $type = 'WinAmount';
        $params = [
            $type,
            $roundId
        ];

        $data = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        $winAmount = null;
        if(!empty($data)){
            foreach ($data as $item) {
                $extraInfo = json_decode($item['extra_info'], true);
                if ($extraInfo['TransactionInfo']['BetTransactionId'] === $betId) {
                    $winAmount = $item['amount'];
                    break;
                }
            }
        }
        return $winAmount !== null ? $winAmount : 0;
    }

    public function queryBetCloseRoundDetails($round_id, $table_name){
        if(empty($round_id)){
            $round_id = '0';
        }
        $where="t.round_id IN ('$round_id')";
        $params = [];

        return $this->queryBetStatusFromTrans($where, $params, $table_name);        
    }

    public function queryBetStatusFromTrans($where, $params = [], $table_name = null){ 
        if($where){
            $where = ' AND ' . $where;
        }          

$sql = <<<EOD
SELECT 
t.trans_status as status,
t.extra_info,
t.after_balance

FROM {$table_name} as t

WHERE t.trans_type = 'CloseRound'
{$where}
EOD;

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $game_description_id=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game_name'], $row['game_name']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function getStandardDateTimeFormat($dateString){
        if($dateString){
            $dateTime = new DateTime($dateString);
            $formattedDate = $dateTime->format('Y-m-d H:i:s');
            return $this->gameTimeToServerTime($formattedDate);
        }
    }

    public function formatBetDetails($data){
        $bet_details = [];
        if(!empty($data)){
            $win_amount  = $data['TotalWon'] - $data['TotalPlacedBet'];
            if($win_amount < 0){
                $win_amount = 0;
            }
            $bet_details = [
                'round_id'          => $data['RoundId'],
                'betting_datetime'  => $data['TransactionDate'],
                'bet_amount'        => $data['TotalPlacedBet'],
                'win_amount'        => $win_amount, 
                'game_name'         => $data['GameName'], 
            ];
        }
        return $bet_details;
    }

    public function getTableNameFromDate($date){
        if(!$this->use_monthly_transactions_table){    
            return $this->original_transactions_table;
        }
        $date=new DateTime($date);
        $monthStr=$date->format('Ym');
        $tableName=$this->original_transactions_table.'_'.$monthStr;
        return $tableName;
    }

    public function queryTransactionByDateTime($startDate, $endDate){

$transTable = $this->getTransactionsTable();

#this is use for building seamless history that is for previous or other months (based on startDate)
if($this->getTableNameFromDate($startDate)!= $transTable && $this->use_table_for_rebuild_seamless_history){
    $transTable = $this->getTableNameFromDate($startDate);
}

$sql = <<<EOD
SELECT
t.player_id as player_id,
t.updated_at as transaction_date,
t.balance_adjustment_amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.transaction_id as transaction_id,
t.round_id as round_id,
t.external_uniqueid as external_uniqueid,
t.trans_type as trans_type,
t.balance_adjustment_method balance_adjustment_method,
t.balance_adjustment_amount balance_adjustment_amount,  
t.extra_info as extra_info
FROM {$transTable} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
AND trans_type in ('PlaceBet','InitialBet','WinAmount')
ORDER BY t.updated_at asc;

EOD;


        $params=[$this->getPlatformCode(),$startDate, $endDate];


        $this->CI->utils->debug_log('SMARTSOFT SEAMLESS GAME (queryTransactionByDateTime)', 'sql', $sql, 'params',$params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function processTransactions(&$transactions){
        $temp_game_records = [];

        if(!empty($transactions)){
            foreach($transactions as $transaction){

                $temp_game_record                       = [];
                $temp_game_record['player_id']          = $transaction['player_id'];
                $temp_game_record['game_platform_id']   = $this->getPlatformCode();
                $temp_game_record['transaction_date']   = $transaction['transaction_date'];
                $temp_game_record['amount']             = abs($transaction['amount']);
                $temp_game_record['before_balance']     = $transaction['before_balance'];
                $temp_game_record['after_balance']      = $transaction['after_balance'];
                $temp_game_record['round_no']     = $transaction['round_id'];
                $extra_info                             = @json_decode($transaction['extra_info'], true);
                $extra                                  = [];
                $extra['trans_type']                    = $transaction['trans_type'];
                $temp_game_record['extra_info']         = json_encode($extra);
                $temp_game_record['external_uniqueid']  = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type']  = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if($transaction['after_balance']<$transaction['before_balance']){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function generateUrl($apiName,$params){
        $this->CI->utils->debug_log('SMART SOFT SEAMLESS (generateUrl)', $apiName, $params);		
		$apiUri         = $this->URI_MAP[$apiName];
		$url            = $this->url . $apiUri;		

		$this->method   = $this->METHOD_MAP[$apiName];

		if($this->method == self::GET&&!empty($params)){
			$url = $url . '?' . http_build_query($params);
        }

		$this->CI->utils->debug_log('SMART SOFT SEAMLESS (generateUrl) :', $this->method, $url);
		return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = null) {
		$this->CI->utils->debug_log('SMART SOFT (processResultBoolean)', 'resultArr', $resultArr);	
        
        $success = false;

        if(isset($resultArr['status']['code']) && $resultArr['status']['code']==self::API_SUCCESS){
            $success = true;
        }

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('SMART SOFT got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

    public function getPlayerBalanceById($player_id){
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($player_id, $this->getPlatformCode());
        return $balance;
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
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like smartsoft_seamless_wallet_transactions');

			}catch(Exception $e){
				$this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
			}
		}
		return $tableName;
	}

    private function getLauncherLanguage($language){
        {
            if($this->force_lang && $this->language){
                return $this->language;
            }

            $language = strtolower($language);
            $lang='';
            switch ($language) {
                case 'cn':
                case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE:
                    $lang = 'cs';
                    break;
                case 'id':
                case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
                case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
                    $lang = 'id';
                    break;
                case 'vn':
                case 'vi':
                case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
                    $lang = 'vn';
                    break;
                case 'ko':
                case 'ko-kr':
                case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
                case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
                    $lang = 'ko';
                    break;
                case 'th':
                case LANGUAGE_FUNCTION::INT_LANG_THAI:
                case LANGUAGE_FUNCTION::PLAYER_LANG_THAI :
                    $lang = 'th';
                    break;
                case 'pt':
                case 'pt-br':
                case 'pt-pt':
                case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
                case LANGUAGE_FUNCTION::PLAYER_LANG_PORTUGUESE :
                    $lang = 'pt';
                    break;
                default: 
                    $lang = 'en-us';
                    break;
            }
            return $lang;
        }
    }
}  
/*end of file*/