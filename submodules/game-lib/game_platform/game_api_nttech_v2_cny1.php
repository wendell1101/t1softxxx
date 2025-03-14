<?php
require_once dirname(__FILE__) . '/game_api_common_nttech_v2.php';

class Game_api_nttech_v2_cny1 extends Game_api_common_nttech_v2 {
	const CURRENCY_TYPE = "CNY";
	const DEDAULT_LANG = "cn";
	const NTTECH_V2_CNY1_ORIG_GAMELOGS_TABLE = "nttech_v2_cny1_game_logs";

	public function getPlatformCode(){
		return NTTECH_V2_CNY_B1_API;
    }

    public function __construct(){
        parent::__construct();
        $this->currency_type = self::CURRENCY_TYPE;
        $this->player_lang = self::DEDAULT_LANG;
        $this->original_gamelogs_table = self::NTTECH_V2_CNY1_ORIG_GAMELOGS_TABLE;
    }
}
/*end of file*/