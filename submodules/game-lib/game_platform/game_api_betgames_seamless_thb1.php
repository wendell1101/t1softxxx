<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_betgames.php';

class Game_api_betgames_seamless_thb1 extends Abstract_game_api_common_betgames {
    const ORIGINAL_GAME_LOGS = 'betgames_game_logs';
    const CURRENCY = "THB";
    
    public function getPlatformCode(){
        return BETGAMES_SEAMLESS_THB1_GAME_API;
    }
    public function getOriginalTable(){
        return self::ORIGINAL_GAME_LOGS;
    }
    public function getCurrency() {
        return self::CURRENCY;
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = $this->getOriginalTable();
    }

}

/*end of file*/

        
