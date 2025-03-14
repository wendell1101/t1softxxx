<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_pretty_gaming_seamless_api.php';

class Game_api_pretty_gaming_seamless_api extends Abstract_game_api_common_pretty_gaming_seamless_api {

    const ORIGINAL_GAMELOGS_TABLE = 'pretty_gaming_seamless_api_gamelogs';
    const ORIGINAL_TRANSACTION_TABLE = 'pretty_gaming_seamless_api_transaction';
    
    public function getPlatformCode(){
        return PRETTY_GAMING_SEAMLESS_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;
    }
    
    public function getTransactionsTable(){
        return $this->original_transaction_table;
    }
}
/*end of file*/