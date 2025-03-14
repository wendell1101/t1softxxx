<?php
require_once dirname(__FILE__) . '/game_api_common_onebook.php';

class Game_api_onebook extends Game_api_common_onebook {
	const CURRENCY_TYPE = "CNY";
	const DEDAULT_LANG = "cn";
	const ONEBOOK_ORIG_GAMELOGS_TABLE = "onebook_game_logs";

	public function getPlatformCode(){
		return ONEBOOK_API;
    }

    public function __construct(){
        parent::__construct();
        $this->currency_type = self::CURRENCY_TYPE;
        $this->original_gamelogs_table = self::ONEBOOK_ORIG_GAMELOGS_TABLE;
        $this->wallet_type = "SPORTSBOOK";
    }
}
/*end of file*/