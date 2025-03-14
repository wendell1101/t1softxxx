<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_nex4d.php';

class Game_api_nex4d extends Abstract_game_api_common_nex4d {
	const ORIGINAL_TABLE = "nex4d_game_logs";	

	public function getPlatformCode(){
		return NEX4D_GAME_API;
    }

    public function __construct(){ 	
        parent::__construct();
    	$this->original_gamelogs_table = self::ORIGINAL_TABLE;   
    }
}

/*end of file*/

		
