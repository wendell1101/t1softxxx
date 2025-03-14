<?php
require_once dirname(__FILE__) . '/game_api_rtg_seamless.php';

class Game_api_rtg2_seamless extends Game_api_rtg_seamless {
    public $original_seamless_wallet_transactions_table = 'rtg2_seamless_wallet_transactions';
    public $game_seamless_service_logs_table = 'rtg2_seamless_service_logs';
    public $original_seamless_game_logs_table = 'rtg2_seamless_game_logs';

    public function getPlatformCode(){
        return RTG2_SEAMLESS_GAME_API;
    }

    
    /*public function getTransactionsTable(){
        return $this->original_transactions_table;
    }*/
}
