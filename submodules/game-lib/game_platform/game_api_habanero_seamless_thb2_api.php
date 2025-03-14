<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_habanero_seamless.php';

class Game_api_habanero_seamless_thb2_api extends Abstract_game_api_common_habanero_seamless {
    const ORIGINAL_GAME_LOGS = 'habanero_seamless_thb2_game_logs';
    const ORIGINAL_TRANSACTIONS = 'habanero_transactions_thb2';
    
    public function getPlatformCode(){
        return HABANERO_SEAMLESS_GAMING_THB2_API;
    }

    public function __construct(){
        $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;
        parent::__construct();
    }
}

/*end of file*/

        
