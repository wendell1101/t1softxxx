<?php
require_once dirname(__FILE__) . '/game_api_common_isb.php';

class Game_api_isb_cny1_api extends Game_api_common_isb {
	const ORIGINAL_TABLE = "isb_cny1_game_logs";

	public function getPlatformCode(){
		return ISB_CNY1_API;
    }

    public function __construct(){
    	$this->original_table = self::ORIGINAL_TABLE;
        parent::__construct();
    }
}

/*end of file*/

		
