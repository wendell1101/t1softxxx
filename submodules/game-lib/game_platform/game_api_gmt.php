<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_gmt.php';

class Game_api_gmt extends Abstract_game_api_common_gmt {

    const ORIGINAL_TABLE = 'gmt_game_logs';
    
    public function getPlatformCode(){
        return GMT_GAME_API;
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

        
