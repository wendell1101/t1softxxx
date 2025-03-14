<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_miki_worlds.php';

class Game_api_miki_worlds extends Abstract_game_api_common_miki_worlds {
	const ORIGINAL_TABLE = "miki_worlds_game_logs";	

	public function getPlatformCode(){
		return MIKI_WORLDS_GAME_API;
    }

    public function __construct(){ 	
        parent::__construct();
    	$this->original_gamelogs_table = self::ORIGINAL_TABLE;   
    }
}

/*end of file*/

		
