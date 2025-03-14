<?php
require_once dirname(__FILE__) . '/game_api_common_hb.php';

class Game_api_hb_idr1_api extends Game_api_common_hb {
	const ORIGINAL_TABLE = "haba88_idr1_game_logs";

	public function getPlatformCode(){
        return HB_IDR1_API;
    }

    public function __construct(){
    	// $this->original_table = self::ORIGINAL_TABLE;
    	// $this->original_table = $this->getSystemInfo('original_table', self::ORIGINAL_TABLE);
        parent::__construct();
        $this->original_table = $this->getSystemInfo('original_table', self::ORIGINAL_TABLE);
    }
}

/*end of file*/
