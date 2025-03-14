<?php
require_once dirname(__FILE__) . '/game_api_isb_seamless.php';

class Game_api_isb_seamless_thb7 extends Game_api_isb_seamless {
    const ORIGINAL_GAME_LOGS = 'isbseamless_thb7_game_logs';
    const CURRENCY = "THB";
    
    public function getPlatformCode(){
        return ISB_SEAMLESS_THB7_API;
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
        $this->transaction_table_name = 'isbseamless_thb7_wallet_transactions';
    }

}

/*end of file*/

        
