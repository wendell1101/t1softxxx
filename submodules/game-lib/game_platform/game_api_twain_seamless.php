<?php
require_once dirname(__FILE__) . '/game_api_betgames_seamless.php';
require_once dirname(__FILE__) . '/year_month_table_module.php';

/**
 * Game Provider: Twain (under Betgames)
 * Game Type: Sports
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2023 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -betgames_seamless_service_api.php
 **/

class Game_api_twain_seamless extends Game_api_betgames_seamless {
    // default tables
    public $original_seamless_game_logs_table;
    public $original_seamless_wallet_transactions_table;
    public $game_seamless_service_logs_table;

    // additional
    public $ws_url;
    public $show_balance;
    public $show_logo;
    public $theme;
    public $dynamic_height;

    public $seamless_game_api_name = 'TWAIN_SEAMLESS_GAME_API';

    public function __construct() {
        parent::__construct();

        // default tables
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'twain_seamless_game_logs');
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'twain_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'twain_seamless_service_logs');

        // start monthly tables
        $this->initialize_monthly_transactions_table = $this->getSystemInfo('initialize_monthly_transactions_table', true);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', false);
        $this->force_check_previous_transactions_table = $this->getSystemInfo('force_check_previous_transactions_table', false);
        $this->force_check_other_transactions_table = $this->getSystemInfo('force_check_other_transactions_table', false);
        $this->use_monthly_service_logs_table = $this->getSystemInfo('use_monthly_service_logs_table', true);

        $this->ymt_initialize($this->original_seamless_wallet_transactions_table, $this->use_monthly_transactions_table ? $this->use_monthly_transactions_table : $this->initialize_monthly_transactions_table);

        if ($this->use_monthly_transactions_table) {
            $this->original_seamless_wallet_transactions_table = $this->ymt_get_current_year_month_table();
            $this->previous_table = $this->ymt_get_previous_year_month_table();
        }

        if ($this->use_monthly_service_logs_table) {
            $this->game_seamless_service_logs_table = $this->ymt_get_current_year_month_table($this->game_seamless_service_logs_table);
        }
        // end monthly tables

        // additional
        $this->provider = $this->getSystemInfo('provider', 'twain');
        $this->ws_url = $this->getSystemInfo('ws_url', '');
        $this->show_balance = $this->getSystemInfo('show_balance', 1);
        $this->show_logo = $this->getSystemInfo('show_logo', 1);
        $this->theme = $this->getSystemInfo('theme', 'default');
        $this->dynamic_height = $this->getSystemInfo('dynamic_height', 1);
    }

    public function getPlatformCode() {
        return TWAIN_SEAMLESS_GAME_API;
    }

    public function queryForwardGame($player_name, $extra = null) {
        $this->http_method = self::HTTP_METHOD_GET;
        $this->CI->load->model(['common_token']);

        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
        $is_demo_mode = $this->utils->isDemoMode($game_mode);

        if (!empty($player_name) && !$is_demo_mode) {
            $player_id = $this->getPlayerIdFromUsername($player_name);
            $token = $this->getPlayerToken($player_id);
            $this->CI->common_token->updatePlayerToken($player_id, $token, $this->token_timeout_seconds);
        } else {
            $token = "-";
        }

        if (!empty($this->language)) {
            $language = $this->language;
        } else {
            if (isset($extra['language'])) {
                $language = $extra['language'];
            } else {
                $language = null;
            }
        }

        $language = $this->getLauncherLanguage($language);

        if ($this->force_language) {
            $language = $this->language;
        }

        if (!empty($extra['home_link'])) {
            $home_url = $extra['home_link'];
        } elseif (!empty($extra['extra']['home_link'])) {
            $home_url = $extra['extra']['home_link'];
        } else {
            $home_url = $this->home_url;
        }

        if ($is_mobile) {
            $platform_id = 1; // mobile
        } else {
            $platform_id = 0; // desktop
        }

        $params = [
            'clientUrl' => $this->client_url,
            'apiUrl' => $this->api_url,
            'wsUrl' => $this->ws_url,
            'partnerCode' => $this->partner_code,
            'token' => $token,
            'locale' => $language,
            'timezone' => $this->timezone,
            'oddsFormat' => $this->odds_format,
            'showBalance' => $this->show_balance,
            'showLogo' => $this->show_logo,
            'theme' => $this->theme,
            'dynamicHeight' => $this->dynamic_height,
            'homeUrl' => $home_url,
            'platformID' => $platform_id,
        ];

        if (!empty($game_code)) {
            $params['gameId'] = $game_code;
        }

        $result = [
            'params' => $params,
        ];

        unset($params['clientUrl']);
        $result['url'] = ltrim($this->client_url, '/') . '/#/' . '?' . http_build_query($params);

        return $result;
    }
}
//end of class