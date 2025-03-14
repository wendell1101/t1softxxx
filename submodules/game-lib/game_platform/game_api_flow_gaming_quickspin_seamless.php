<?php
require_once dirname(__FILE__) . '/game_api_flow_gaming_seamless.php';

/**
 * Game Provider: Flow Gaming - Quickspin
 * Game Type: Slots
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2024 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -flow_gaming_seamless_service_api.php
 **/

class Game_api_flow_gaming_quickspin_seamless extends Game_api_flow_gaming_seamless {
    public $seamless_game_api_name = 'FLOW_GAMING_QUICKSPIN_SEAMLESS_API';

    public function __construct() {
        parent::__construct();

        // default tables
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'flow_gaming_quickspin_seamless_game_logs');
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'flow_gaming_quickspin_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'flow_gaming_quickspin_seamless_service_logs');

        $this->ymt_init();
    }

    public function getPlatformCode() {
        return FLOW_GAMING_QUICKSPIN_SEAMLESS_API;
    }
}
//end of class