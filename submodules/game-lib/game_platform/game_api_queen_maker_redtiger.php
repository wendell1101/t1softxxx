<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_queen_maker.php';

class Game_api_queen_maker_redtiger extends Abstract_game_api_common_queen_maker {

    const ORIGINAL_TABLE = 'queen_maker_redtiger_game_logs';
    
    public function getPlatformCode(){
        return QUEEN_MAKER_REDTIGER_GAME_API;
    }


    public function __construct(){
        parent::__construct();
        $this->original_game_logs_table = self::ORIGINAL_TABLE;
        $this->currency = $this->getSystemInfo('currency', 'VND');
    }

    public function getCurrency() {
        return $this->currency;
    }
}

/*end of file*/

        
