<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_om_lotto.php';

class Game_api_om_lotto extends Abstract_game_api_common_om_lotto {

    const ORIGINAL_TABLE = 'om_lotto_game_logs';
    
    public function getPlatformCode(){
        return OM_LOTTO_GAME_API;
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

        
