<?php
require_once dirname(__FILE__) . '/game_api_common_isb.php';

class Game_api_isb_idr4_api extends Game_api_common_isb {
	const ORIGINAL_TABLE = "isb_idr4_game_logs";

	public function getPlatformCode(){
		return ISB_IDR4_API;
    }

    public function __construct(){
    	$this->original_table = self::ORIGINAL_TABLE;
        parent::__construct();
    }
}

/*end of file*/
		
