<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_wgb.php';

class Game_api_wgb extends Abstract_game_api_common_wgb {
	const ORIGINAL_TABLE = "wgb_game_logs";	

	public function getPlatformCode(){
		return WGB_GAME_API;
    }

    public function __construct(){ 	
        parent::__construct();
    	$this->original_gamelogs_table = self::ORIGINAL_TABLE;   
    }
}

/*end of file*/

		
