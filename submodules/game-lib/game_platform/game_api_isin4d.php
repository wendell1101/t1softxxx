<?php
require_once dirname(__FILE__) . '/game_api_common_isin4d.php';

class Game_api_isin4d extends Game_api_common_isin4d {
	const CURRENCY_TYPE = "CNY";
	public function getPlatformCode(){
		return ISIN4D_API;
    }

    public function __construct(){
        parent::__construct();
        $this->currency_type = self::CURRENCY_TYPE;
    }
}
/*end of file*/