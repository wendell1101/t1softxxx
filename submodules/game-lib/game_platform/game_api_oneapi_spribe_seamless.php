<?php
require_once dirname(__FILE__) . '/game_api_oneapi_seamless.php';

class Game_api_oneapi_spribe_seamless extends Game_api_oneapi_seamless {
    public $game_platform_id, $subprovider_username_prefix;
    public function getPlatformCode(){
        return ONEAPI_SPRIBE_SEAMLESS_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->game_platform_id = $this->getPlatformCode();
    }

/**
   *
   * perpare original rows, include process unknown game, pack bet details, convert game status
   *
   * @param  array &$row
   */
   public function preprocessOriginalRowForGameLogs(array &$row){
       $game_code =  $row['game_code'];
       $game_desc = $this->CI->game_description_model->getGameDescByGameCode($game_code, $this->getPlatformCode());
       if (empty($game_desc)) {
           list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
           $row['game_description_id'] = $game_description_id;
           $row['game_type_id'] = $game_type_id;
           $row['game_name'] = 'unknown';
       }else{
           $row['game_description_id'] = $game_desc['id'];
           $row['game_type_id'] = $game_desc['game_type_id'];
           $row['game_name'] = $game_desc['game_name'];
       }
  
       if($this->enable_merging_rows){ 
           $row['after_balance']   = $row['after_balance'] + $row['win_amount'];
           #get total win amounts including free spins
           $table = $this->getTransactionsTable();
           $totalAmounts = $this->queryTotalAmountByRound($row);
           $row['win_amount'] = isset($totalAmounts['total_win']) ? $totalAmounts['total_win'] : 0;
           $row['result_amount'] = $row['win_amount'] - $row['bet_amount'];

           if (isset($totalAmounts['after_balance'])) {
               $row['after_balance'] = $totalAmounts['after_balance'];
           }
       }else{

           $row['result_amount'] = $row['win_amount'] - $row['bet_amount'];
       }

       $row['start_at'] = isset($row['bet_time']) ? $row['bet_time'] : $row['transaction_date'];
       $row['end_at'] = isset($row['settled_time']) ? $row['settled_time'] : $row['transaction_date'];
   }
}

/*end of file*/

        
