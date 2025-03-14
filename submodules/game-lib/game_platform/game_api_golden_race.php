<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_golden_race.php';

class Game_api_golden_race extends Abstract_game_api_common_golden_race {
    const ORIGINAL_GAME_LOGS = 'golden_race_game_logs';
    
    public function getPlatformCode(){
        return GOLDEN_RACE_GAMING_API;
    }

    public function __construct(){
        $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        parent::__construct();
    }
}

/*end of file*/

        
