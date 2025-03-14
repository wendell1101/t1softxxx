<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_slot_factory.php';

class Game_api_slot_factory extends Abstract_game_api_common_slot_factory {
    const ORIGINAL_GAME_LOGS = 'slot_factory_game_logs';
    
    public function getPlatformCode(){
        return SLOT_FACTORY_GAME_API;
    }

    public function __construct(){
        // $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        parent::__construct();
    }

    public function getOriginalTable(){
        return self::ORIGINAL_GAME_LOGS;
    }
}

/*end of file*/

        
