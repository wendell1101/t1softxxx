<?php
require_once dirname(__FILE__) . '/game_api_common_hb.php';

class Game_api_hb_thb1_api extends Game_api_common_hb {
	const ORIGINAL_TABLE = "haba88_thb1_game_logs";

	public function getPlatformCode(){
        return HB_THB1_API;
    }

    public function __construct(){
    	$this->original_table = self::ORIGINAL_TABLE;
        parent::__construct();
    }
}

/*end of file*/
