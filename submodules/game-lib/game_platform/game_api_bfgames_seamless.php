<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_bfgames_seamless.php';

class Game_api_bfgames_seamless extends Abstract_game_api_common_bfgames_seamless {
    const ORIGINAL_TRANSACTIONS = 'bfgames_seamless_wallet_transactions';
    
    public function getPlatformCode(){
        return BFGAMES_SEAMLESS_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;
    }
}

/*end of file*/
