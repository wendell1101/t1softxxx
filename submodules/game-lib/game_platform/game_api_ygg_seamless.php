<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_ygg_seamless.php';

class Game_api_ygg_seamless extends Abstract_game_api_common_ygg_seamless {
    const ORIGINAL_GAME_LOGS = '';
    const ORIGINAL_TRANSACTIONS = 'ygg_seamless_wallet_transactions';
    
    public function getPlatformCode(){
        return YGG_SEAMLESS_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;        
    }
}

/*end of file*/

        
