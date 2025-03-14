<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_tom_horn_seamless.php';

class Game_api_tom_horn2_seamless extends Abstract_game_api_common_tom_horn_seamless {
    public $original_transactions_table;
    public function getPlatformCode(){
        return TOM_HORN2_SEAMLESS_GAME_API;
    }
    public function __construct(){
        parent::__construct();
        $this->original_transactions_table = 'tom_horn_seamless_wallet_transactions';
    }
  
}

/*end of file*/

        
