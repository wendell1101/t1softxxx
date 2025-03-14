<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_asiastar.php';

class Game_api_asiastar extends abstract_game_api_common_asiastar {
	const CURRENCY_TYPE = "CNY";

	public function getPlatformCode(){
		return ASIASTAR_API;
    }

    public function __construct(){
        parent::__construct();
        
        //const ORIGINAL_LOGS_TABLE_NAME = 'asiastar_game_logs';
        $this->ORIGINAL_LOGS_TABLE_NAME = 'asiastar_game_logs';
        $this->currency_type = self::CURRENCY_TYPE;
    }

}
/*end of file*/