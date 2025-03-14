<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_solid_gaming.php';

class Game_api_solid_gaming_thb extends Abstract_game_api_common_solid_gaming {
    const ORIGINAL_LOGS_TABLE_NAME = 'solid_gaming_game_logs';

    public function getPlatformCode(){
        return SOLID_GAMING_THB_API;
    }

    public function __construct(){
        $this->originalTable = self::ORIGINAL_LOGS_TABLE_NAME;
        parent::__construct();
    }
}

/*end of file*/
