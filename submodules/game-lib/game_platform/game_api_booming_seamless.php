<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_booming_seamless.php';

class Game_api_booming_seamless extends Abstract_game_api_common_booming_seamless {

	const ORIGINAL_GAMELOGS_TABLE = 'boomingseamless_game_logs';
	
	public function getPlatformCode(){
		return BOOMING_SEAMLESS_API;
    }

    public function __construct(){
        parent::__construct();
    	$this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
    }
}
/*end of file*/