<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_sexy_baccarat.php';

class Game_api_sexy_baccarat extends Abstract_game_api_common_sexy_baccarat {
    const ORIGINAL_GAME_LOGS = 'sexy_baccarat_game_logs';
    const ORIGINAL_TRANSACTION_TABLE = 'sexy_baccarat_transactions';
  
    public function getPlatformCode(){
        return SEXY_BACCARAT_SEAMLESS_API;
    }

    public function __construct(){
        // $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        parent::__construct();
        $this->original_transactions_table = self::ORIGINAL_TRANSACTION_TABLE;    
    }

    public function getOriginalTable(){
        return self::ORIGINAL_GAME_LOGS;
    }
    
    public function getTransactionsTable(){
        return $this->original_transactions_table;
    }
}

/*end of file*/

        
