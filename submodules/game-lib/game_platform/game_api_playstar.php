<?php
require_once dirname(__FILE__) . '/game_api_common_playstar.php';

class Game_api_playstar extends Game_api_common_playstar {
	const CURRENCY_TYPE = "CNY";
	public function getPlatformCode(){
		return PLAYSTAR_API;
    }

    public function __construct(){
        parent::__construct();
        $this->currency_type = $this->getSystemInfo('currency', self::CURRENCY_TYPE);
    }
}
/*end of file*/