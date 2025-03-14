<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_kg_poker.php';

class Game_api_kg_poker extends Abstract_game_api_common_kg_poker {
    
    public function getPlatformCode(){
        return KG_POKER_API;
    }

    public function __construct(){
        // $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        parent::__construct();
    }

    public function getOriginalTable(){
        return 'kg_poker_game_logs';
    }
}

/*end of file*/

        
