<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_s128.php';

class Game_api_s128 extends Abstract_game_api_common_s128 {
	const ORIGINAL_TABLE = "s128_game_logs";	

	public function getPlatformCode(){
		return S128_GAME_API;
    }

    public function __construct(){ 	
        parent::__construct();
    	$this->original_gamelogs_table = self::ORIGINAL_TABLE;   
    }
}

/*end of file*/

		
