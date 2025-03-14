<?php
require_once dirname(__FILE__) . '/game_api_common_isb.php';

class Game_api_isb_idr1_api extends Game_api_common_isb {
	const ORIGINAL_TABLE = "isb_idr1_game_logs";

	public function getPlatformCode(){
		return ISB_IDR1_API;
    }

    public function __construct(){
    	// $this->original_table = self::ORIGINAL_TABLE;
    	// $this->original_table = $this->getSystemInfo('original_table', self::ORIGINAL_TABLE);
        parent::__construct();
        $this->original_table = $this->getSystemInfo('original_table', self::ORIGINAL_TABLE);
        
    }
}

/*end of file*/

		
