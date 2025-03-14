<?php
require_once dirname(__FILE__) . '/game_api_common_sbobet.php';

class Game_api_afb_sbobet_seamless extends Game_api_common_sbobet {

	const ORIGINAL_GAMELOGS_TABLE = 'sbobet_seamless_game_logs';
  	const ORIGINAL_TRANSACTION_TABLE = 'sbobet_seamless_game_transactions';
    const LOBBY_DEFAULT_CODE = 0;
    const AFB_GPID = 1016;

	public function getPlatformCode(){
		return AFB_SBOBET_SEAMLESS_GAME_API;
  }

  public function __construct(){
    parent::__construct();
  	$this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
    $this->original_transactions_table = self::ORIGINAL_TRANSACTION_TABLE;
  	$this->default_lauch_game_type = "SeamlessGame";
  	$this->company_key = $this->getSystemInfo('company_key');

    $this->seamless_debit_transaction_type = $this->getSystemInfo('seamless_debit_transaction_type', ['RollbackDeduct', 'TipDeduct', 'AdditionalDeduct', 'CancelDeduct', 'DeductDeduct']);
  }

  public function isSeamLessGame()
  {
      return true;
  }
    
  public function getTransactionsTable(){
      return $this->original_transactions_table;
  }

  /** 
   * Deposit to Game
  */
  public function depositToGame($playerName,$amount,$transfer_secure_id=null)
  {
    $external_transaction_id = $transfer_secure_id;
    $this->CI->utils->debug_log("SBOBET_GAMING_API depositToGame");

    return [
       "success" => true,
       "external_transaction_id" => $external_transaction_id,
       "response_result_id" => null,
       "didnot_insert_game_logs" => true
    ];
  }

  /** 
   * Withdraw From Game
  */
  public function withdrawFromGame($playerName,$amount,$transfer_secure_id=null)
  {
    $external_transaction_id = $transfer_secure_id;
    $this->CI->utils->debug_log("SBOBET_GAMING_API withdrawFromGame");

    return [
       "success" => true,
       "external_transaction_id" => $external_transaction_id,
       "response_result_id" => null,
       "didnot_insert_game_logs" => true
    ];
  }

  public function getCompanyKey(){
  	return $this->company_key;
  }

  public function getGameCurrency(){
  	return $this->currency;
  }

  public function queryPlayerBalance($playerName)
  {
    $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
    $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
    $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

    $result = array(
        'success' => true, 
        'balance' => $balance
    );

    return $result;
  }

  public function queryTransactionByDateTime($startDate, $endDate){
      $this->CI->load->model(array('original_game_logs_model'));

$sql = <<<EOD
SELECT 
p.playerId as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.transaction_id as round_no,
t.unique_transaction_id as external_uniqueid,
t.transaction_type trans_type
FROM {$this->original_transactions_table} as t
JOIN player p on p.username = t.gameusername
WHERE `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?  AND t.game_platform_id = ?
ORDER BY t.updated_at asc;

EOD;

$params=[$startDate, $endDate, $this->getPlatformCode()];

      $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
      return $result;
  }

  public function syncOriginalGameLogs($token = false){
    return $this->returnUnimplemented();
  }

  public function syncMergeToGameLogs($token) {
      $enabled_game_logs_unsettle=true;
      return $this->commonSyncMergeToGameLogs($token,
              $this,
              [$this, 'queryOriginalGameLogsFromTrans'],
              [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
              [$this, 'preprocessOriginalRowForGameLogs'],
              $enabled_game_logs_unsettle);
  }

   /**
     * queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time){
        $sql = <<<EOD
SELECT
  trans.id as sync_index,
  trans.sbe_round_id as external_uniqueid,
  IFNULL(gamelogs.result_amount,gamelogs.bet_amount*-1) as result_amount,
  gamelogs.bet_amount as bet_amount,
  gamelogs.bet_amount as real_betting_amount,
  gamelogs.status as sbe_status,
  gamelogs.created_at as start_at,
  gamelogs.created_at as bet_at,
  gamelogs.updated_at as end_at,
  trans.transaction_id as round_number,
  gamelogs.game_id as game_name,
  gamelogs.game_id as game_code,
  trans.after_balance as after_balance,
  trans.amount as trans_amount,
  trans.transaction_type as type,
  MD5(CONCAT(IFNULL(gamelogs.result_amount,gamelogs.bet_amount*-1), gamelogs.bet_amount, gamelogs.status, gamelogs.updated_at)) as md5_sum,
  
  gd.game_type_id,
  gd.id as game_description_id,
  gd.game_type_id,
  p.player_id as player_id

  
  
FROM
  sbobet_seamless_game_transactions AS trans
  JOIN sbobet_seamless_game_logs AS gamelogs ON trans.sbe_round_id = gamelogs.external_uniqueid 
  JOIN game_provider_auth as p ON trans.gameusername = p.login_name and p.game_provider_id = ?
  LEFT JOIN game_description as gd ON gamelogs.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
  gamelogs.updated_at >= ?
  AND gamelogs.updated_at <= ?
  AND trans.game_platform_id = ?
EOD;

        
        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        $this->CI->original_game_logs_model->removeDuplicateUniqueid($result, 'external_uniqueid', function(){ return 2;});
        $result = array_values($result);
        return $result;
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row) {
        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            // echo "pasok";exit();
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, ['sbe_status', 'end_at'], ['bet_amount', 'result_amount']);
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_name'],
                'game_type' => null,
                'game' => $row['game_name'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => null,
            ],
            'amount_info' => [
                'bet_amount' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'result_amount' => isset($row['result_amount']) ? $row['result_amount'] : 0,
                'bet_for_cashback' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'real_betting_amount' => isset($row['real_betting_amount']) ? $row['real_betting_amount'] : 0,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => null,
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => [],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if(strtolower($row['type']) == 'bonusadd'){
            $this->CI->load->model(array('game_description_model'));
            $row['real_betting_amount'] = $row['bet_amount'] = 0;
            $row['result_amount'] = $row['trans_amount'];
            $row['game_code'] = $row['game_name'] = 'bonusadd';
            $game_details = $this->CI->game_description_model->getGameDetailsByExternalGameIdAndGamePlatform($this->getPlatformCode(), $row['game_code'], true);
            if(!empty($game_details)){
                $row['game_description_id'] = $game_details['game_description_id'];
                $row['game_type_id'] = $game_details['game_type_id'];
            }
        }

        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        switch (strtolower($row['sbe_status'])) {
          case 'settled':
              $row['status'] = GAME_LOGS::STATUS_SETTLED;
            break;
          case 'void':
              $row['status'] = GAME_LOGS::STATUS_CANCELLED;
            break;
          
          default:
              $row['status'] = GAME_LOGS::STATUS_PENDING;
            break;
        }
    }

    public function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['game_name'];
        $external_game_id = $row['game_code'];

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id);
    }

    public function queryForwardGame($playerName, $extra = null) 
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$params = array(
			"companyKey" => $this->company_key,
			"username" => $gameUsername,
			"portfolio" => $this->default_lauch_game_type,
			"serverId" => $this->server_id,
			"method" => self::URI_MAP[self::API_login]
		);

		$context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'extra' => $extra,
            'player_name' => $playerName
        ];
        $login = $this->callApi(self::API_login, $params, $context);
        if(isset($login['success']) && $login['success']){
            $urlParams = array(
                'lang' => $this->getLauncherLanguage($extra['language']),
                'gpId' => self::AFB_GPID,
                'gameid' => isset($extra['game_code']) ? $extra['game_code'] : self::LOBBY_DEFAULT_CODE,
                'device' => (isset($extra['is_mobile']) && $extra['is_mobile']) ? 'm' : 'd'
            );
            $url = isset($login['url']) ? $this->utils->getServerProtocol() . ":" .$login['url'] : null;
            $url .= '&'.http_build_query($urlParams);
            $login['url'] = $url;
        } else{
            $login['url'] = null;
        }
        return $login;
	}

	public function processResultForLogin($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
        return array($success, $resultArr);
	}

	private function getLauncherLanguage($language)
    {
        switch ($language) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE :
                $language = 'zh-cn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
                $language = 'id-id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
                $language = 'vi-vn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
                $language = 'ko-kr';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case Language_function::PLAYER_LANG_THAI :
                $language = 'th-th';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case Language_function::PLAYER_LANG_PORTUGUESE :
                $language = 'pt-pt';
                break;
            // case LANGUAGE_FUNCTION::INT_LANG_INDIA:
            // case Language_function::INT_LANG_PORTUGUESE :
            //     $language = 'in';
            //     break;
            default:
                $language = 'en';
                break;
        }
        return $language;
    }

    public function queryGameList(){
        $params = array(
            "companyKey" => $this->company_key,
            "serverId" => $this->server_id,
            "GpId" => self::AFB_GPID,
            "IsGetAll" => false,
            "method" => self::URI_MAP[self::API_getGameProviderGamelist]
        );

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameList',
        ];

        return $this->callApi(self::API_getGameProviderGamelist, $params, $context);
    }

    public function processResultForQueryGameList($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = false;
        $result = [];
        if(isset($resultArr['seamlessGameProviderGames'])){
            $success = true;
            $result['games'] = $resultArr['seamlessGameProviderGames'];
        }
        return array($success, $result);
    }

    public function queryGameListFromGameProvider($extra = null){
        return $this->queryGameList();
    }


}
/*end of file*/