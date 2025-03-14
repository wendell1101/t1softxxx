<?php
require_once dirname(__FILE__) . '/game_api_common_nttech.php';

class Game_api_nttech_cny1 extends Game_api_common_nttech {
	const CURRENCY_TYPE = "CNY";
	public function getPlatformCode(){
		return NTTECH_CNY_B1_API;
    }

    public function __construct(){
        parent::__construct();
        $this->currency_type = self::CURRENCY_TYPE;
    }
}
/*end of file*/