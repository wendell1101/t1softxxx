<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_smartsoft_seamless.php';

class Game_api_smartsoft_seamless extends Abstract_game_api_common_smartsoft_seamless {
    const ORIGINAL_TRANSACTIONS = 'smartsoft_seamless_wallet_transactions';
    public $original_transactions_table;
    public function getPlatformCode(){
        return SMARTSOFT_SEAMLESS_GAME_API;
    }
    public function __construct(){
        parent::__construct();
        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;
    }
  
}

/*end of file*/

        
