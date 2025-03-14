<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_pgsoft_seamless.php';

class Game_api_pgsoft3_seamless extends Abstract_game_api_common_pgsoft_seamless {
    const ORIGINAL_TRANSACTIONS = 'pgsoft3_seamless_wallet_transactions';
    
    public function getPlatformCode(){
        return PGSOFT3_SEAMLESS_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;
    }
    
    /*public function getTransactionsTable(){
        return $this->original_transactions_table;
    }*/
}

/*end of file*/

        
