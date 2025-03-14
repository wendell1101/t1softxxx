<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_queen_maker.php';

class Game_api_onegame extends Abstract_game_api_common_queen_maker {

    const ORIGINAL_TABLE = 'onegame_game_logs';
    
    public function getPlatformCode(){
        return ONEGAME_GAME_API;
    }


    public function __construct(){
        parent::__construct();
        $this->original_game_logs_table = self::ORIGINAL_TABLE;
        $this->currency = $this->getSystemInfo('currency', 'THB');
    }

    public function getCurrency() {
        return $this->currency;
    }
}

/*end of file*/

        
