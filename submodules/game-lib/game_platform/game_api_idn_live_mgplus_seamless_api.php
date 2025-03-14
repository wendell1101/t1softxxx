<?php
require_once dirname(__FILE__) . '/game_api_mgplus_seamless_api.php';

class Game_api_idn_live_mgplus_seamless_api extends Game_api_mgplus_seamless_api {
    public $original_table;
    public $original_transactions_table;
    public $use_mgplus_seamless_wallet_transactions_table;

    public function __construct(){

        parent::__construct();
        $this->original_table = $this->getSystemInfo('original_table', 'common_seamless_wallet_transactions');
        $this->original_transactions_table = 'common_seamless_wallet_transactions';
        if ($this->use_mgplus_seamless_wallet_transactions_table) {
            $this->original_transactions_table = 'idn_live_mgplus_seamless_wallet_transactions';
        }
    }


    public function getPlatformCode() {
        return IDN_LIVE_MGPLUS_SEAMLESS_GAME_API;
    }
}