<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_flow_gaming_seamless.php';

class Game_api_flow_gaming_seamless_thb1 extends Abstract_game_api_common_flow_gaming_seamless {

    const ORIGINAL_GAMELOGS_TABLE = 'fg_seamless_thb1_gamelogs';
    const ORIGINAL_TRANSACTION_TABLE = 'fg_seamless_thb1_gamelogs_per_transaction';
    
    public function getPlatformCode(){
        return FLOW_GAMING_SEAMLESS_THB1_API;
    }

    public function __construct(){
        parent::__construct();
        $this->sub_game_provider = $this->getSystemInfo('sub_game_provider', false);
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;
    }
    
    public function getTransactionsTable(){
        return $this->original_transaction_table;
    }
}
/*end of file*/