<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_hogaming_seamless.php';

class Game_api_hogaming_seamless_idr2 extends Abstract_game_api_common_hogaming_seamless
{
    const ORIGINAL_GAMELOGS_TABLE = 'hogaming_seamless_idr2_game_logs';
    const TRANSACTION_LOGS_TABLE = 'hogaming_seamless_idr2_transaction_logs';
    const CURRENCY = 'IDR';
    const LANGUAGE = 'id';

    public function getPlatformCode()
    {
        return HOGAMING_SEAMLESS_IDR2_GAME_API;
    }
    
    public function __construct()
    {
        parent::__construct();
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
        $this->transaction_logs_table = self::TRANSACTION_LOGS_TABLE;

        $this->currency_type = $this->getSystemInfo('currency', self::CURRENCY);
        $this->api_url = $this->getSystemInfo('url');
        $this->api_key = $this->getSystemInfo('API_KEY');
        $this->api_secret = $this->getSystemInfo('API_SECRET');
        $this->player_mode = $this->getSystemInfo('player_mode', parent::MODE_REAL);
        $this->prefix = $this->getSystemInfo('prefix_for_username', "idnstg");
        $this->language = strtolower($this->getSystemInfo('language', self::LANGUAGE));
        $this->lobby_version = strtoupper($this->getSystemInfo('lobby_version'));
        $this->skin_id = strtoupper($this->getSystemInfo('skin_id', parent::SKIN_001));

        $this->api_bet_logs = $this->getSystemInfo('api_bet_logs');
        $this->web_api_username = $this->getSystemInfo('web_api_username');
        $this->web_api_password = $this->getSystemInfo('web_api_password');
        $this->casino_id = $this->getSystemInfo('casino_id');

        $this->gameTimeToServerTime = $this->getSystemInfo('gameTimeToServerTime');
        $this->serverTimeToGameTime = $this->getSystemInfo('serverTimeToGameTime');

        $this->use_xml_body = false;
        $this->common_wait_seconds = $this->getSystemInfo('common_wait_seconds', 63);
        $this->action = '';
        $this->is_bet_logs = false;

        $this->add_cancelled_bets_in_original_game_logs = $this->getSystemInfo('add_cancelled_bets_in_original_game_logs', true);
        $this->testuser = $this->getSystemInfo('testuser', 'false');
    }
}
