<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_sa_gaming_seamless.php';

class Game_api_sa_gaming_seamless_thb1 extends Abstract_game_api_common_sa_gaming_seamless {

    const ORIGINAL_GAMELOGS_TABLE = 'sa_gaming_seamless_thb1_game_logs';
    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';
    
    public function getPlatformCode(){
        return SA_GAMING_SEAMLESS_THB1_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;   
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;
        $this->game_provider_id = SA_GAMING_SEAMLESS_THB1_API;
    }
    
    public function getTransactionsTable(){
        return $this->original_transactions_table;
    }
}
/*end of file*/