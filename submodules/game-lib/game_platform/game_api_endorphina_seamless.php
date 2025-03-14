<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_endorphina_seamless.php';

class Game_api_endorphina_seamless extends Abstract_game_api_common_endorphina_seamless {
    public $original_transactions_table;
    public function getPlatformCode(){
        return ENDORPHINA_SEAMLESS_GAME_API;
    }
    public function __construct(){
        parent::__construct();
        $this->original_transactions_table = 'endorphina_seamless_wallet_transactions';
    }
  
}

/*end of file*/

        
