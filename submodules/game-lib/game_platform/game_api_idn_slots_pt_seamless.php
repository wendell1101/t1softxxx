<?php
require_once dirname(__FILE__) . '/game_api_pt_seamless.php';

/**
 * Game Provider: PT
 * Game Type: Slots
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2025 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -pt_seamless_service_api.php
 **/

class Game_api_idn_slots_pt_seamless extends Game_api_pt_seamless {
    // GAME API NAME
    public $seamless_game_api_name = 'IDN_SLOTS_PT_SEAMLESS_GAME_API';

    public function __construct() {
        parent::__construct();
        $this->original_seamless_game_logs_table = 'idn_slots_pt_seamless_game_logs';
        $this->original_seamless_wallet_transactions_table = 'idn_slots_pt_seamless_wallet_transactions';
        $this->game_seamless_service_logs_table = 'idn_slots_pt_seamless_service_logs';
        $this->createTableLike($this->original_seamless_wallet_transactions_table, self::ORIGINAL_SEAMLESS_WALLET_TRANSACTIONS_TABLE);
        $this->ymt_init();

        $this->launcher_mode = $this->getSystemInfo('launcher_mode', 'singleOnly');
        $this->is_support_lobby = $this->getSystemInfo('is_support_lobby', false);
        $this->game_type_demo_lobby_supported = $this->getSystemInfo('game_type_demo_lobby_supported', []);
        $this->game_type_lobby_supported = $this->getSystemInfo('game_type_lobby_supported', []);
    }

    public function getPlatformCode() {
        return IDN_SLOTS_PT_SEAMLESS_GAME_API;
    }
}
//end of class