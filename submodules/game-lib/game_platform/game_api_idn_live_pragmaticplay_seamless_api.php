<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_pragmaticplay_seamless_api.php';

class Game_api_idn_live_pragmaticplay_seamless_api extends Abstract_game_api_common_pragmaticplay_seamless_api {

    public function __construct() {
        parent::__construct();
        $this->original_logs_table_name = 'idn_live_pragmaticplay_seamless_game_logs';
        $this->transaction_table_name = 'idn_live_pragmaticplay_seamless_wallet_transactions';
        $this->original_transaction_table_name = $this->transaction_table_name;
        $this->filter_game_list_by_game_types = $this->getSystemInfo('filter_game_list_by_game_types', ['lg']); // vs, bj, cs, rl, bn, bc, lg, sc, empty[all]
    }

    public function isSeamLessGame() {
        return true;
    }

    public function getPlatformCode() {
        return IDN_LIVE_PRAGMATICPLAY_SEAMLESS_API;
    }
}

/*end of file*/