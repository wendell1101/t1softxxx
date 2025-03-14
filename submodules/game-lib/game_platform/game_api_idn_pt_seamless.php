<?php
require_once dirname(__FILE__) . '/game_api_pt_seamless.php';

/**
 * Game Provider: PT
 * Game Type: Slots, Live Games, Virtual Sports, Card Games, Mini Games, Table Games
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

class Game_api_idn_pt_seamless extends Game_api_pt_seamless {
    // GAME API NAME
    public $seamless_game_api_name = 'IDN_PT_SEAMLESS_GAME_API';

    public function __construct() {
        parent::__construct();
        $this->original_seamless_game_logs_table = 'idn_pt_seamless_game_logs';
        $this->original_seamless_wallet_transactions_table = 'idn_pt_seamless_wallet_transactions';
        $this->game_seamless_service_logs_table = 'idn_pt_seamless_service_logs';
        $this->createTableLike($this->original_seamless_wallet_transactions_table, self::ORIGINAL_SEAMLESS_WALLET_TRANSACTIONS_TABLE);
        $this->ymt_init();
    }

    public function getPlatformCode() {
        return IDN_PT_SEAMLESS_GAME_API;
    }
}
//end of class