<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_creedroomz_seamless.php';

class Game_api_creedroomz_seamless extends Abstract_game_api_common_creedroomz_seamless {
    const ORIGINAL_TRANSACTIONS = 'creedroomz_seamless_wallet_transactions';
    
    public function getPlatformCode(){
        return CREEDROOMZ_SEAMLESS_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;
    }
    
    /*public function getTransactionsTable(){
        return $this->original_transactions_table;
    }*/
}

/*end of file*/
