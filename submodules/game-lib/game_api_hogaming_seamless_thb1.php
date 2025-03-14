<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_hogaming_seamless.php';

class Game_api_hogaming_seamless_thb1 extends Abstract_game_api_common_hogaming_seamless
{
    const ORIGINAL_GAMELOGS_TABLE = 'hogaming_seamless_thb1_game_logs';
    
    public function getPlatformCode()
    {
        return HOGAMING_SEAMLESS_THB1_API;
    }

    public function __construct()
    {
        parent::__construct();
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
    }
}
