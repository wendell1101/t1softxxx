<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_vivogaming_seamless.php';

class Game_api_vivogaming_seamless_idr1_api extends Abstract_game_api_common_vivogaming_seamless {
    const ORIGINAL_GAME_LOGS = 'vivogaming_seamless_idr1_game_logs';
    const ORIGINAL_TRANSACTIONS = 'vivogaming_transactions_idr1';
    
    public function getPlatformCode(){
        return VIVOGAMING_SEAMLESS_IDR1_API;
    }

    public function __construct(){
        $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;
        parent::__construct();
    }
}

/*end of file*/

        
