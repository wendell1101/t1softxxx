<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_pragmaticplay_seamless_api.php';

class Game_api_pragmaticplay_seamless_cny1_api extends Abstract_game_api_common_pragmaticplay_seamless_api {

    public function __construct() {
        parent::__construct();
        $this->original_logs_table_name = 'pragmaticplay_seamless_cny1_game_logs';
        $this->transaction_table_name = 'pragmaticplay_seamless_cny1_wallet_transactions';
    }

    public function isSeamLessGame() {
        return true;
    }

    public function getPlatformCode() {
        return PRAGMATICPLAY_SEAMLESS_CNY1_API;
    }
}

/*end of file*/