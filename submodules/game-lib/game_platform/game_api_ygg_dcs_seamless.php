<?php
require_once dirname(__FILE__) . '/game_api_dcs_universal_seamless.php';

/**
 * Game Provider: DCS
 * Game Type: Slots
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2023 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -dcs_universal_seamless_service_api.php
 **/

class Game_api_ygg_dcs_seamless extends Game_api_dcs_universal_seamless {
    // default
    const SEAMLESS_GAME_API_NAME = 'YGG_DCS_SEAMLESS_GAME_API';

    public function __construct() {
        parent::__construct();

        // default
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'ygg_dcs_seamless_game_logs');
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'ygg_dcs_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'ygg_dcs_seamless_service_logs');
        $this->provider = $this->getSystemInfo('provider', self::PROVIDER_LIST['yg']['code']);

        $this->ymt_init();
    }

    public function getPlatformCode() {
        return YGG_DCS_SEAMLESS_GAME_API;
    }
}
//end of class