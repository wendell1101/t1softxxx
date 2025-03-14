<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_sbobet_v2.php';

class Game_api_sbobet_v2 extends Abstract_game_api_common_sbobet_v2 {
    const ORIGINAL_GAME_LOGS = 'sbobet_game_logs_v2';
    
    public function getPlatformCode(){
        return SBOBETV2_GAME_API;
    }

    public function __construct(){
        $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        parent::__construct();
    }
}

/*end of file*/

        
