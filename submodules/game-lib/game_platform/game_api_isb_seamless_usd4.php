<?php
require_once dirname(__FILE__) . '/game_api_isb_seamless.php';

class Game_api_isb_seamless_usd4 extends Game_api_isb_seamless {
    const ORIGINAL_GAME_LOGS = 'isbseamless_usd4_game_logs';
    const CURRENCY = "USD";
    
    public function getPlatformCode(){
        return ISB_SEAMLESS_USD4_API;
    }
    public function getOriginalTable(){
        return self::ORIGINAL_GAME_LOGS;
    }
    public function getCurrency() {
        return self::CURRENCY;
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = $this->getOriginalTable();
        $this->transaction_table_name = 'isbseamless_usd4_wallet_transactions';
    }

}

/*end of file*/

        
