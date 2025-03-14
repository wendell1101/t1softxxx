<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_dongsen.php';

class Game_api_vnd1_dongsen_esports extends Abstract_game_api_common_dongsen {
    const ORIGINAL_GAME_LOGS = 'dongsen_esports_vnd1_game_logs';
    
    public function getPlatformCode(){
        return DONGSEN_ESPORTS_VND1_API;
    }

    public function __construct(){
        $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        parent::__construct();
    }
}

/*end of file*/

        
