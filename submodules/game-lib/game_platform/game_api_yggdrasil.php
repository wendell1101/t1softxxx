<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_yggdrasil.php';

class Game_api_yggdrasil extends Abstract_game_api_common_yggdrasil {
	const ORIGINAL_TABLE = "yggdrasil_game_logs";	

	public function getPlatformCode(){
		return YGGDRASIL_API;
    }

    public function __construct(){ 	
        parent::__construct();
    	$this->original_gamelogs_table = self::ORIGINAL_TABLE;   
    }
}

/*end of file*/

		
