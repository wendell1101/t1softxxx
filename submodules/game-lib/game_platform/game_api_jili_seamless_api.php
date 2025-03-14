<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_jili_seamless.php';

class Game_api_jili_seamless_api extends Abstract_game_api_common_jili_seamless {
    const TRANSACTIONS_GAME_LOGS = '';
    const ORIGINAL_GAME_LOGS = '';
    const ORIGINAL_TRANSACTION_TABLE = 'jili_seamless_wallet_transactions';
    
    public function getPlatformCode(){
        return JILI_SEAMLESS_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        $this->original_transactions_table = self::ORIGINAL_TRANSACTION_TABLE;
    }
    
    public function getTransactionsTable(){
        return $this->original_transactions_table;
    }
}

/*end of file*/
