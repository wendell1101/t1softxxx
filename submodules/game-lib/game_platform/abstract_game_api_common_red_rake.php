<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * API NAME: REDRAKE_GAMING_API
 * Ticket Number: OGP-14094
 * Wallet type: Seamless
 * 
 * Related Files:
 * routes.php
 * red_rake_service_api.php
 * 
 * @see Red Rake Gaming Integration Manual V3.28
 * @category Game API
 * @copyright 2013-2022 tot
 * @author Jason Miguel
 */

 abstract class Abstract_game_api_common_red_rake extends Abstract_game_api{

    /** 
     * Fields in table, we want to detect changes for update in fields
     * @var constant MD5_FIELDS_FOR_ORIGINAL 
    */
    const MD5_FIELDS_FOR_ORIGINAL = [
       "game_id",
       "game_name",
       "session_id",
       "player_id",
       "player_name",
       "currency",
       "round_id",
       "bet_amount",
       "real_bet_amount",
       "result_amount",
       "before_balance",
       "after_balance",
       "is_bet_loss",
       "is_bonus_loss",
       "start_at",
       "end_at"
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
       "bet_amount",
       "real_bet_amount",
       "result_amount",
       "before_balance",
       "after_balance"
    ];

    # Fields in game_logs table, we want to detect changes for merge, and when .md5_sum is empty
    const MD5_FIELDS_FOR_MERGE = [
       "game_code",
       "session_id",
       "player_id",
       "currency",
       "round_id",
       "bet_amount",
       "result_amount",
       "before_balance",
       "after_balance",
       "is_bet_loss",
       "is_bonus_loss",
       "status"
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
      "bet_amount",
      "result_amount",
      "before_balance",
      "after_balance"
    ];

    const GET_PARAMS_NOT_IN_SIG = "operator";
    const ACTION_ROLLBACK = "refund";
    const ACTION_BET = "bet";
    const ACTION_WIN = "win";
    const ACTION_BONUS_WIN = "bonusWin";

    protected $game_launch_url;
    protected $partner_id;
    protected $signature_secret;
    protected $url_home;
    protected $rcinterval;
    protected $rcelapsed;
    protected $operator;
    protected $jurisdiction;

    public function __construct()
    {
      parent::__construct();

      $this->original_gamelogs_table = $this->getOriginalTable();
      $this->game_launch_url = $this->getSystemInfo("game_launch_url","");
      $this->partner_id = $this->getSystemInfo("partner_id","");
      $this->signature_secret = $this->getSystemInfo("signature_secret","");
      $this->url_home = $this->getSystemInfo("url_home","");
      $this->rcinterval = $this->getSystemInfo("rcinterval","");
      $this->rcelapsed = $this->getSystemInfo("rcelapsed","");
      $this->operator = $this->getSystemInfo("operator","");
      $this->jurisdiction = $this->getSystemInfo("jurisdiction","");
    }

    public function isSeamLessGame()
    {
       return true;
    }

    /**
     * Generate URL
     */
    public function generateUrl($apiName,$params)
    {
       return $this->returnUnimplemented();
    }

    public function processResultBoolean($responseResultId, $resultArr, $username=null)
    {
      return $this->returnUnimplemented();
    }

    /** 
     * It will create Player
    */
    public function createPlayer($playerName,$playerId,$password,$email=null,$extra=null)
    {
       // it will create player on game_provider_auth table
       $return = parent::createPlayer($playerName,$playerId,$password,$email,$extra);
       $success = false;
       $message = "Unable to create account for REDRAKE_GAMING_API";

       if($return){
          $success = true;
          $message = "Unable to create account for REDRAKE_GAMING_API";
       }

       $this->CI->utils->debug_log("REDRAKE_GAMING_API createPlayer is:",$success);

       return [
         "success" => $success,
         "message" => $message
       ];
    }

    /** 
     * Game Launch
     * 
     * @param string $playerName
     * @param array $extra
     * 
     * @return array
     *
    */
    public function queryForwardGame($playerName,$extra = null)
    {
       $player_id = $this->getPlayerIdInPlayer($playerName);

       $params = [
         "gameid" => isset($extra["game_code"]) ? $extra["game_code"] : null,
         "sessionid" => $this->getPlayerTokenByUsername($playerName),
         "accountid" =>  !empty($player_id) ? $player_id : null,
         "lang" => $this->getLauncherLanguage($extra["language"]),
         "currency" => $this->currency_type,
         "mode" => ($extra["game_mode"] == "real") ? "real" : "demo",
         "jurisdiction" => $this->jurisdiction
       ];
       
       # for url home
       if(! empty($this->url_home)){
         $params["urlhome"] = $this->url_home;
       }

       # for rcinterval
       if(! empty($this->rcinterval)){
         $params["rcinterval"] = $this->rcinterval;
       }

      # for rcinterval
      if(! empty($this->rcelapsed)){
         $params["rcelapsed"] = $this->rcelapsed;
      }

      # for operator
      if(! empty($this->operator)){
         $params["operator"] = $this->operator;
      }

       $sig = $this->generateSig($params);

       if(! empty($sig)){
          $params["sig"] = $sig;
       }
       
      $query_params = http_build_query($params);

      $url = $this->game_launch_url.$this->partner_id."?".$query_params;

      $this->CI->utils->debug_log("REDRAKE_GAMING_API queryForwardGame url:",$url);

      return [
          "success" => true,
          "url" => $url
        ];
      
    }

    /**
   * The signature describes the hex representation
   * of a RFC 2104-compliant HMAC with the SHA256 hash algorithm.
   * Alphabetically order GET params of the URL (without sig),
   * concat with '|$key=$val' and calculates hash hmac with
   * sha256 and your secret
   *
   * @param array $params
   * 
   * @return string
   */
   public function generateSig($params)
   {

      $sig=null;

      foreach (explode(",",self::GET_PARAMS_NOT_IN_SIG) as $key){
         unset($params[$key]);
      }

      ksort($params);

      foreach ($params as $key => $val) {
         $sig .= "|$key=$val";
      }
      
      return hash_hmac('sha256', $sig, $this->signature_secret, false);
   }

   public function getLauncherLanguage($currentLang) 
   {
     switch ($currentLang) {
           case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
           case "zh":
               $language = 'zh';
               break;
           case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
           case "id":
               $language = 'id';
               break;
           case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
           case "vi":
               $language = 'vi';
               break;
           case "en":
               $language = 'en';
               break;
           case LANGUAGE_FUNCTION::INT_LANG_THAI:
               $language = 'th';
               break;
           case "th":
               $language = 'th';
               break;
           default:
               $language = 'en';
               break;
       }
       return $language;
  }

    /** 
     * Deposit to Game
    */
    public function depositToGame($playerName,$amount,$transfer_secure_id=null)
    {
      $external_transaction_id = $transfer_secure_id;

      $this->CI->utils->debug_log("REDRAKE_GAMING_API depositToGame");

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

      $this->CI->utils->debug_log("REDRAKE_GAMING_API depositToGame");

      return [
         "success" => true,
         "external_transaction_id" => $external_transaction_id,
         "response_result_id" => null,
         "didnot_insert_game_logs" => true
      ];
    }

    /**
     * Query Transaction
     */
    public function queryTransaction($transactionId,$extra)
    {
       return $this->returnUnimplemented();
    }

    /** 
     * Query Player Balance
    */
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

    /**
     * Sync Original Game Logs
     */
    public function syncOriginalGameLogs($token=false)
    {
       $startDate = clone parent::getValueFromSyncInfo($token,'dateTimeFrom');
       $endDate = clone parent::getValueFromSyncInfo($token,'dateTimeTo');

       $startDate = new DateTime($startDate->format("Y-m-d H:i:s"));
       $startDate = new DateTime($startDate->format("Y-m-d H:i:s"));
       $startDate->modify($this->getDatetimeAdjust());

       # observer of the date format
       $startDate = $startDate->format("Y-m-d H:i:s");
       $endDate = $endDate->format("Y-m-d H:i:s");

       $this->CI->utils->debug_log("REDRAKE_GAMING_API start SyncOriginalGameLogs with date: ",$startDate,$endDate);

       $dataResult = [
         "data_count" => 0,
         "data_count_insert" => 0,
         "data_count_update" => 0
       ];

       $this->CI->load->model([
               "red_rake_game_transactions_model",
               "original_game_logs_model"
                ]);

       $gameRecords = $this->CI->red_rake_game_transactions_model->getRoundID($startDate,$endDate,$this->getPlatformCode());

       if(! is_null($gameRecords)){
         
         # process rows of original game logs
         $this->processGameRecords($gameRecords);

         list($insertRows,$updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
            $this->getOriginalTable(),
            $gameRecords,
            "external_unique_id",
            "external_unique_id",
            self::MD5_FIELDS_FOR_ORIGINAL,
            "md5_sum",
            "id",
            self::MD5_FLOAT_AMOUNT_FIELDS
         );

         $this->CI->utils->debug_log("REDRAKE_GAMING_API syncOriginalGameLogs process row count is:",count($gameRecords),"Inserted Rows is:",count($insertRows),"Updated Rows is:",count($updateRows));

         $dataResult["data_count"] = count($gameRecords);

         if(is_array($gameRecords) && count($gameRecords) > 0){
            $dataResult["data_count_insert"] = $this->updateOrInsertOriginalGameLogs($insertRows,"insert");
         }
         unset($insertRows);

         if(is_array($gameRecords) && count($gameRecords) > 0){
            $dataResult["data_count_update"] = $this->updateOrInsertOriginalGameLogs($updateRows,"update");
         }
         unset($updateRows);
       }
       
       return [
         true,
         $dataResult
       ];
    }

    /** 
     * Process necessary rows for Original Game Logs
     * 
     * @param array $gameRecords
     * 
     * @return void
    */
    public function processGameRecords(&$gameRecords)
    {
       
      if(! is_null($gameRecords)){

         $this->CI->load->model("red_rake_game_transactions_model");

         foreach($gameRecords as $index => $record){

            $round_id = isset($record["round_id"]) ? $record["round_id"] : null;

            $bet_result = $this->CI->red_rake_game_transactions_model->getOriginalTransactions($round_id,$this->getPlatformCode());

            # check first if bet transaction have result, it cound be refund,win(0 = means loss),BonusWin
            if(count($bet_result) > 0){
               foreach($bet_result as $value){

                  if(isset($value["action"])){
                     # check if refund bet
                     if($value["action"] == self::ACTION_ROLLBACK){
                        $data["status"] = Game_logs::STATUS_CANCELLED;
                        $data["result_amount"] = null;
                        $data["end_at"] = isset($value["timestamp"]) ? $value["timestamp"] : null;
                     }elseif($value["action"] == self::ACTION_BET){
                        $data["game_id"] = isset($value["game_id"]) ? $value["game_id"] : null;
                        $data["game_name"] = isset($value["game_name"]) ? $value["game_name"] : null;
                        $data["session_id"] = isset($value["session_id"]) ? $value["session_id"] : null;
                        $data["player_id"] = isset($value["player_id"]) ? $value["player_id"] : null;
                        $data["player_name"] = isset($value["player_name"]) ? $value["player_name"] : null;
                        $data["currency"] = isset($value["currency"]) ? $value["currency"] : null;
                        $data["round_id"] = $round_id;
                        $data["transaction_id"] = isset($value["transaction_id"]) ? $value["transaction_id"] : null;
                        $data["bet_amount"] = isset($value["bet_amount"]) ? $value["bet_amount"] : null;
                        $data["real_bet_amount"] = isset($value["bet_amount"]) ? $value["bet_amount"] : null;
                        $data["result_amount"] =  isset($value["bet_amount"]) ? -abs($value["bet_amount"]) : null;
                        $data["before_balance"] = isset($value["before_balance"]) ? $value["before_balance"] : null;
                        $data["after_balance"] = isset($value["after_balance"]) ? $value["after_balance"] : null;
                        $data["is_bet_loss"] = isset($value["is_bet_loss"]) ? $value["is_bet_loss"] : null;
                        $data["is_bonus_loss"] = isset($value["is_bonus_loss"]) ? $value["is_bonus_loss"] : null;
                        $data["status"] = Game_logs::STATUS_PENDING;
                        $data["start_at"] = isset($value["timestamp"]) ? $value["timestamp"] : null;
                        $data["end_at"] = isset($value["timestamp"]) ? $value["timestamp"] : null;
                        $data["response_result_id"] = isset($value["response_result_id"]) ? $value["response_result_id"] : null;
                        $data["external_unique_id"] = isset($value["external_unique_id"]) ? $value["external_unique_id"] : null;
                     }else{
                        $data["result_amount"] += isset($value["bet_amount"]) ? $value["bet_amount"] : null;
                        $data["end_at"] = isset($value["timestamp"]) ? $value["timestamp"] : null;
                        $data["after_balance"] = isset($value["after_balance"]) ? $value["after_balance"] : null;
                        $data["updated_at"] = $this->CI->utils->getNowForMysql();
                        $data["status"] = Game_logs::STATUS_SETTLED;
                     }
                  }

               }
            }

            $gameRecords[$index] = $data;
            unset($data);
         }

         return $gameRecords;
      }
    }

    /** 
     * Process Result of Transaction
     * 
     * @param int $round_id
     * 
     * @return array
    */
    public function processResultTransaction($round_id)
    {

      $betResult = $this->CI->red_rake_game_transactions_model->queryResultOfBetTransaction($round_id);

      return $betResult;
    }

    /** 
     * Update or Insert for Original game logs Table
     * 
     * @param array $data
     * @param string $queryType
     * 
     * @return int
    */
    private function updateOrInsertOriginalGameLogs($data, $queryType)
    {
      $dataCount=0;
      if(!empty($data)){
          foreach ($data as $record) {
              if ($queryType == 'update') {
                  $record['updated_at'] = $this->utils->getNowForMysql();
                  $this->CI->original_game_logs_model->updateRowsToOriginal($this->getOriginalTable(), $record);
              } else {
                  unset($record['id']);
                  $record['created_at'] = $this->utils->getNowForMysql();
                  $this->CI->original_game_logs_model->insertRowsToOriginal($this->getOriginalTable(), $record);
              }
              $dataCount++;
              unset($record);
          }
      }
      return $dataCount;
   }

    /**
     * Merge Game Logs from Original Game Logs Table
     * 
     * @param string $token
     */
    public function syncMergeToGameLogs($token)
    {
      $enabled_game_logs_unsettle = true;

      return $this->commonSyncMergeToGameLogs($token,
       $this,
       [$this,'queryOriginalGameLogs'],
       [$this,'makeParamsForInsertOrUpdateGameLogsRow'],
       [$this, 'preprocessOriginalRowForGameLogs'],
       $enabled_game_logs_unsettle
      );
    }

   /** 
     * Query Original Game Logs for Merging
     * 
     * @param string $dateFrom where the date start for sync original
     * @param string $dataTo where the date end 
     * 
     * @return array 
    */
    public function queryOriginalGameLogs($dateFrom,$dateTo,$use_bet_time)
    {
        $sqlTime = 'original.end_at >= ? AND original.end_at <= ?';

        $sql = <<<EOD
            SELECT
                original.id as sync_index,
                original.game_id as game_code,
                original.game_name as game_name,
                original.player_id,
                original.player_name,
                original.round_id,
                original.transaction_id,
                original.bet_amount,
                original.result_amount,
                original.after_balance,
                original.start_at,
                original.start_at as bet_at,
                original.end_at,
                original.status,
                original.external_unique_id as external_uniqueid,
                original.md5_sum,
                gd.id as game_description_id,
                gd.game_name as game_description_name,
                gd.game_type_id
            FROM {$this->original_gamelogs_table} as original
            LEFT JOIN game_description as gd ON original.game_id = gd.external_game_id AND
            gd.game_platform_id = ?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
            JOIN game_provider_auth ON original.player_name = game_provider_auth.login_name
            AND game_provider_auth.game_provider_id = ?
            WHERE
            {$sqlTime}
EOD;
        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];
      
        return $var = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql,$params);
    }

   /** 
     * Assemble the data to merge to game_logs table
     * 
     * @param array $row
     * 
     * @return array
    */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        $extra = [
            'table' => $row['round_id']
        ];

        if(empty($row['md5_sum'])){
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row,
                self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        return [
            'game_info' => [
                'game_type_id' => isset($row["game_type_id"]) ? $row["game_type_id"] : null,
                'game_description_id' => isset($row["game_description_id"]) ? $row["game_description_id"] : null,
                'game_code' => isset($row["game_code"]) ? $row["game_code"] : null,
                'game_type' => null,
                'game' => isset($row["game_name"]) ? $row["game_name"] : null,
            ],
            'player_info' => [
                'player_id' => isset($row["player_id"]) ? $row["player_id"] : null,
                'player_username' => isset($row["player_name"]) ? $row["player_name"] : null
            ],
            'amount_info' => [
                'bet_amount' => isset($row["bet_amount"]) ? $row["bet_amount"] : null,
                'result_amount' => isset($row["result_amount"]) ? $row["result_amount"] : null,
                'bet_for_cashback' => isset($row["bet_amount"]) ? $row["bet_amount"] : null,
                'real_betting_amount' => isset($row["bet_amount"]) ? $row["bet_amount"] : null,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => isset($row["after_balance"]) ? $row["after_balance"] : null 
            ],
            'date_info' => [
                'start_at' => isset($row["start_at"]) ? $row["start_at"] : null,
                'end_at' => isset($row["end_at"]) ? $row["end_at"] : null,
                'bet_at' => isset($row["bet_at"]) ? $row["bet_at"] : null,
                'updated_at' => $this->CI->utils->getNowForMysql()
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => isset($row["status"]) ? $row["status"] : null,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number' => isset($row["round_id"]) ? $row["round_id"] : null,
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index' => $row['sync_index'],
                'bet_type' => null # BET_TYPE_MULTI_BET or BET_TYPE_SINGLE_BET
            ],
            'bet_details' => isset($row["bet_details"]) ? $row["bet_details"] : null,
            'extra' => $extra,
            // from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null
        ];
    }

    /**
     * Prepare Original rows, Include process unknown game, pack bet details
     */
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        $this->CI->load->model(array('game_logs'));
        $game_description_id = isset($row['game_description_id']) ? $row['game_description_id'] : null;
        $game_type_id = isset($row['game_type_id']) ? $row["game_type_id"] : null;

        # we process unknown game here
        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }
        
        $bet_details = [
            'roundId' => isset($row['round_id']) ? $row["round_id"] : null,
            "TransactionID" => isset($row["transaction_id"]) ? $row["transaction_id"] : null,
            'PlayerName' => isset($row['player_name']) ? $row["player_name"] : null,
            "GameName" => isset($row["game_name"]) ? $row["game_name"] : null,
        ];
        
        $row['game_description_id' ]= $game_description_id;
        $row['game_type_id'] = $game_type_id;
        $row['bet_details'] = $bet_details;
    }

   /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */
    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_description_id = null;
        $game_name = str_replace("알수없음",$row['game_code'],
                     str_replace("不明",$row['game_code'],
                     str_replace("Unknown",$row['game_code'],$unknownGame->game_name)));
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
   }
 }