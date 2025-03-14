<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_jili.php';

class Game_api_jili extends Abstract_game_api_common_jili {
	const ORIGINAL_TABLE = "jili_game_logs";	

	public function getPlatformCode(){
		return JILI_GAME_API;
    }

    public function __construct(){ 	
        parent::__construct();
    	$this->original_gamelogs_table = self::ORIGINAL_TABLE;   
    }
}

/*end of file*/

		
