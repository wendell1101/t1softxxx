<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_ha_gaming.php';

class Game_api_ha_gaming extends Abstract_game_api_common_ha_gaming {

    const ORIGINAL_GAMELOGS_TABLE = 'ha_gaming_game_logs';
    
    public function getPlatformCode(){
        return HA_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
    }
}
/*end of file*/