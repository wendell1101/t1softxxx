<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_evoplay.php';

class Game_api_evoplay extends Abstract_game_api_common_evoplay {

    const ORIGINAL_GAMELOGS_TABLE = 'evoplay_gamelogs';
    
    public function getPlatformCode(){
        return EVOPLAY_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
    }
}
/*end of file*/