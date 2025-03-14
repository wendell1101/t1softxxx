<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_pgsoft_seamless.php';

class Game_api_idn_pgsoft_seamless extends Abstract_game_api_common_pgsoft_seamless {
    const ORIGINAL_TRANSACTIONS = 'idn_pgsoft_seamless_wallet_transactions';
    
    public function getPlatformCode(){
        return IDN_PGSOFT_SEAMLESS_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;
    }
}

/*end of file*/

        
