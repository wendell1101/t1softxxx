<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_gd_seamless.php';

class Game_api_gd_seamless extends Abstract_game_api_common_gd_seamless {
    const ORIGINAL_GAME_LOGS = 'gd_seamless_game_logs';
    
    public function getPlatformCode(){
        return GD_SEAMLESS_API;
    }

    public function __construct(){
        $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        parent::__construct();
    }
}

/*end of file*/

        
