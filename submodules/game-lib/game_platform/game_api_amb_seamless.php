<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_amb_seamless.php';

class Game_api_amb_seamless extends Abstract_game_api_common_amb_seamless {
    const ORIGINAL_GAME_LOGS = 'amb_seamless_game_logs';
    const ORIGINAL_TRANSACTIONS = 'amb_transactions';
    
    public function getPlatformCode(){
        return AMB_SEAMLESS_GAME_API;
    }

    public function __construct(){
        $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;
        parent::__construct();
    }

    public function getTransactionsTable(){
        return $this->original_transactions_table;
    }
}

/*end of file*/

        
