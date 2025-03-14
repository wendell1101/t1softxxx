<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_habanero_seamless.php';

class Game_api_habanero_seamless_vnd3_api extends Abstract_game_api_common_habanero_seamless {
    const ORIGINAL_GAME_LOGS = 'habanero_seamless_vnd3_game_logs';
    const ORIGINAL_TRANSACTIONS = 'habanero_transactions_vnd3';
    
    public function getPlatformCode(){
        return HABANERO_SEAMLESS_GAMING_VND3_API;
    }

    public function __construct(){
        $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;
        parent::__construct();
    }
}

/*end of file*/

        
