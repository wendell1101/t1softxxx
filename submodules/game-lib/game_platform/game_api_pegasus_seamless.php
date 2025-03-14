<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_pegasus_seamless.php';

class Game_api_pegasus_seamless extends Abstract_game_api_common_pegasus_seamless {
    public $original_transactions_table;
    public function getPlatformCode(){
        return PEGASUS_SEAMLESS_GAME_API;
    }
    public function __construct(){
        parent::__construct();
        $this->original_transactions_table = 'pegasus_seamless_wallet_transactions';
    }
  
}

/*end of file*/

        
