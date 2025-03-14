<?php
require_once dirname(__FILE__) . '/game_api_common_hogaming.php';

class Game_api_hogaming_idr1_api extends Game_api_common_hogaming {
	const CURRENCY_TYPE = "IDR";
	const HG_IDR1_ORIG_GAMELOGS_TABLE = "hogaming_idr1_game_logs";
	public function getPlatformCode(){
		return HOGAMING_IDR_B1_API;
    }

    public function __construct(){
    	parent::__construct();
    	$this->currency_type = self::CURRENCY_TYPE;
    	// $this->original_gamelogs_table = self::HG_IDR1_ORIG_GAMELOGS_TABLE
        $this->original_gamelogs_table = $this->getSystemInfo('original_table', self::HG_IDR1_ORIG_GAMELOGS_TABLE);;
    }
}
/*end of file*/