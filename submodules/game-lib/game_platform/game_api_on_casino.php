<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_on_casino.php';

class Game_api_on_casino extends Abstract_game_api_common_on_casino {
	const ORIGINAL_TABLE = "on_casino_game_logs";	

	public function getPlatformCode(){
		return ON_CASINO_GAME_API;
    }

    public function __construct(){ 	
        parent::__construct();
    	$this->original_gamelogs_table = self::ORIGINAL_TABLE;   
    }
}

/*end of file*/

		
