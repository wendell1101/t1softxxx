<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_kingpoker.php';

class Game_api_kingpoker extends Abstract_game_api_common_kingpoker {

    const ORIGINAL_GAMELOGS_TABLE = 'kingpoker_gamelogs';
    
    public function getPlatformCode(){
        return KINGPOKER_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
    }
}
/*end of file*/