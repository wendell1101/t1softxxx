<?php
require_once dirname(__FILE__) . '/game_api_softswiss_seamless.php';

class Game_api_softswiss_wazdan_seamless extends Game_api_softswiss_seamless {

    public function __construct() {
        parent::__construct();
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'softswiss_wazdan_seamless_wallet_transactions');
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'softswiss_wazdan_seamless_game_logs');
    }

    public function getPlatformCode() {
        return SOFTSWISS_WAZDAN_SEAMLESS_GAME_API;
    }
}