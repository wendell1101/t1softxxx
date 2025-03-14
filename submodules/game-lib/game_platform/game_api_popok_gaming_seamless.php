<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_popok_gaming_seamless.php';

class Game_api_popok_gaming_seamless extends Abstract_game_api_common_popok_gaming_seamless {
    public $original_transactions_table;
    public function getPlatformCode(){
        return POPOK_GAMING_SEAMLESS_GAME_API;
    }
    public function __construct(){
        parent::__construct();
        $this->original_transactions_table = 'popok_gaming_seamless_wallet_transactions';
    }
  
}

/*end of file*/

        
