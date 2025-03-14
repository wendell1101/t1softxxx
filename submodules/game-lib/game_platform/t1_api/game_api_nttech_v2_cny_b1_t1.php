<?php
require_once dirname(__FILE__) . '/game_api_t1_common.php';

class Game_api_nttech_v2_cny_b1_t1 extends Game_api_t1_common {
	const CURRENCY_TYPE = "CNY";
	const DEFAULT_LANG = "cn";

	public function getPlatformCode(){
		return T1NTTECH_V2_CNY_B1_API;
    }

    public function getOriginalPlatformCode(){
        return NTTECH_V2_API;
    }

    public function __construct(){
        parent::__construct();
        $this->currency_type = self::CURRENCY_TYPE;
        $this->player_lang = self::DEFAULT_LANG;
    }
}
/*end of file*/