<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_pragmaticplay_seamless_api.php';

class Game_api_pragmaticplay_seamless_usd4_api extends Abstract_game_api_common_pragmaticplay_seamless_api {

    public function __construct() {
        parent::__construct();
        $this->original_logs_table_name = 'pragmaticplay_seamless_usd4_game_logs';
        $this->transaction_table_name = 'pragmaticplay_seamless_usd4_wallet_transactions';
    }

    public function isSeamLessGame() {
        return true;
    }

    public function getPlatformCode() {
        return PRAGMATICPLAY_SEAMLESS_USD4_API;
    }
}

/*end of file*/