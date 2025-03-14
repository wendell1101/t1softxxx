<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/game_api_cmd_seamless.php';

/**
 * Game Provider: CMD
 * Game Type: Sports
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2013-2022 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -cmd_seamless_service_api.php
 **/

class Game_api_cmd2_seamless extends Game_api_cmd_seamless {
    const SEAMLESS_GAME_API_NAME = 'CMD2_SEAMLESS_GAME_API';

    public function __construct() {
        parent::__construct();
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'cmd2_seamless_game_logs');
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'cmd2_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'cmd2_seamless_service_logs');
    }

    public function getPlatformCode() {
        return CMD2_SEAMLESS_GAME_API;
    }
}
//end of class