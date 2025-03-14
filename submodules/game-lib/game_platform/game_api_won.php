<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_won.php';

class Game_api_won extends Abstract_game_api_common_won {
	const ORIGINAL_TABLE = "won_game_logs";	

	public function getPlatformCode(){
		return WON_API;
    }

    public function __construct(){ 	
        parent::__construct();
    	$this->original_gamelogs_table = self::ORIGINAL_TABLE;   
    }
}

/*end of file*/

		
