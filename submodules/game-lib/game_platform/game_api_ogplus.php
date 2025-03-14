<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_ogplus.php';

class Game_api_ogplus extends Abstract_game_api_common_ogplus {
	const ORIGINAL_TABLE = "ogplus_game_logs";	

	public function getPlatformCode(){
		return OGPLUS_API;
    }

    public function __construct(){ 	
        parent::__construct();
    	$this->original_gamelogs_table = self::ORIGINAL_TABLE;   
    }
}

/*end of file*/

		
