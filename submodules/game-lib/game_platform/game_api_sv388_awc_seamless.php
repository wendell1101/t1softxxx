<?php
require_once dirname(__FILE__) . '/game_api_awc_universal_seamless.php';

/**
 * Game Provider: AWC
 * Game Type: Cockfighting, Slots, Live Casino
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2023 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -awc_universal_seamless_service_api.php
 **/

class Game_api_sv388_awc_seamless extends Game_api_awc_universal_seamless {
    // default
    const SEAMLESS_GAME_API_NAME = 'SV388_AWC_SEAMLESS_GAME_API';

    public function __construct() {
        parent::__construct();

        // default
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'sv388_awc_seamless_game_logs');
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'sv388_awc_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'sv388_awc_seamless_service_logs');
        $this->platform = $this->getSystemInfo('platform', 'SV388');
        $this->game_type = $this->getSystemInfo('game_type', 'LIVE');
        $this->has_bet_limit_setting = $this->getSystemInfo('has_bet_limit_setting', true);
        $this->bet_limit = $this->getSystemInfo('bet_limit', '{"SV388":{"LIVE":{"maxbet":1000,"minbet":1,"mindraw":1,"matchlimit":1000,"maxdraw":100}}}'); // check bet limit on the docs
    }

    public function getPlatformCode() {
        return SV388_AWC_SEAMLESS_GAME_API;
    }

    public function rebuildBetDetailsFormat($row, $game_type) {
        $bet_details = [];
        $game_info = !empty($row['game_info']) ? json_decode($row['game_info'], true) : [];

        switch ($game_type) {
            case self::GAME_TYPE_COCK_FIGHT:
                if (isset($game_info['live'])) {
                    $bet_details['live'] = $game_info['live'];
                }

                if (isset($game_info['odds'])) {
                    $bet_details['odds'] = $game_info['odds'];
                }

                if (isset($game_info['matchId'])) {
                    $bet_details['matchId'] = $game_info['matchId'];
                }

                if (isset($game_info['matchno'])) {
                    $bet_details['matchno'] = $game_info['matchno'];
                }

                if (isset($game_info['location'])) {
                    $bet_details['location'] = $game_info['location'];
                }

                if (isset($game_info['arenaCode'])) {
                    $bet_details['arenaCode'] = $game_info['arenaCode'];
                }

                if (isset($game_info['eventDate'])) {
                    $bet_details['eventDate'] = $game_info['eventDate'];
                }

                if (isset($game_info['realBetAmount'])) {
                    $bet_details['realBetAmount'] = $game_info['realBetAmount'];
                }
                break;
            default:
                $bet_details = $this->defaultBetDetailsFormat($row);
                break;
        }

        if (empty($bet_details) && !empty($row['bet_details'])) {
            $bet_details = is_array($row['bet_details']) ? $row['bet_details'] : json_decode($row['bet_details'], true);
        }

        if (empty($bet_details)) {
            $bet_details = $this->defaultBetDetailsFormat($row);
        }

        return $bet_details;
    }
}
//end of class