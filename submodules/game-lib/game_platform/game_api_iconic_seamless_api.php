<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_iconic_seamless.php';

class Game_api_iconic_seamless_api extends Abstract_game_api_common_iconic_seamless {
    const TRANSACTIONS_GAME_LOGS = 'iconic_seamless_game_logs';
    const ORIGINAL_GAME_LOGS = 'original_iconic_seamless_game_logs';
    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';
    
    public function getPlatformCode(){
        return ICONIC_SEAMLESS_API;
    }

    public function __construct(){
        $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        parent::__construct();
        $this->original_transactions_table = self::ORIGINAL_TRANSACTION_TABLE;
    }
    
    public function getTransactionsTable(){
        return $this->original_transactions_table;
    }
}

/*end of file*/

        
