<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_sa_gaming_seamless.php';

class Game_api_sa_gaming_seamless extends Abstract_game_api_common_sa_gaming_seamless {

    const ORIGINAL_GAMELOGS_TABLE = 'sa_gaming_seamless_common_game_logs';
    
    public function getPlatformCode(){
        return SA_GAMING_SEAMLESS_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
        $this->game_provider_id = SA_GAMING_SEAMLESS_API;
    }
    
    public function getTransactionsTable(){
        return $this->original_transaction_table;
    }
}
/*end of file*/