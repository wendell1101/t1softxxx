<?php
require_once dirname(__FILE__) . '/game_api_common_isb.php';

class Game_api_isb_myr2_api extends Game_api_common_isb {
	const ORIGINAL_TABLE = "isb_myr2_game_logs";

	public function getPlatformCode(){
		return ISB_MYR2_API;
    }

    public function __construct(){
    	$this->original_table = self::ORIGINAL_TABLE;
        parent::__construct();
    }
}

/*end of file*/

		
