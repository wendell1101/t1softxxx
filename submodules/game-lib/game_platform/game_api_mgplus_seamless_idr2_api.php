<?php
require_once dirname(__FILE__) . '/game_api_mgplus_seamless_idr1_api.php';

class Game_api_mgplus_seamless_idr2_api extends Game_api_mgplus_seamless_idr1_api {
	const ORIGINAL_TABLE = "mgplus_seamless_idr2_game_logs";
	const PRODUCT_ID = "smg"; # smg is Microgaming
    const CURRENCY = "IDR"; 

	public function getPlatformCode(){
		return MGPLUS_SEAMLESS_IDR2_API;
    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function __construct(){
    	$this->product_id = self::PRODUCT_ID;
        parent::__construct();
        $this->original_table = $this->getSystemInfo('original_table', self::ORIGINAL_TABLE);
        $this->currency = $this->getSystemInfo('currency', self::CURRENCY);
    }
}
/*end of file*/