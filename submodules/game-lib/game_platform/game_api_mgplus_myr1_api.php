<?php
require_once dirname(__FILE__) . '/game_api_common_mgplus.php';

class Game_api_mgplus_myr1_api extends Game_api_common_mgplus {
	const ORIGINAL_TABLE = "mgplus_myr1_game_logs";
	const PRODUCT_ID = "smg"; # smg is Microgaming

	public function getPlatformCode(){
		return MGPLUS_MYR1_API;
    }

    public function __construct(){
    	$this->original_table = self::ORIGINAL_TABLE;
    	$this->product_id = self::PRODUCT_ID;
        parent::__construct();
    }
}
/*end of file*/