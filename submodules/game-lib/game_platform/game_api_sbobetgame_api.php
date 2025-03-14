<?php
require_once dirname(__FILE__) . '/game_api_common_sbobet.php';

class Game_api_sbobetgame_api extends Game_api_common_sbobet {
	const CURRENCY_TYPE = "CNY";
	const SBOBETGAME_ORIG_GAMELOGS_TABLE = "sbobet_game_logs";
	public function getPlatformCode(){
		return SBOBETGAME_API;
    }

    public function __construct(){
    	parent::__construct();
    	$this->currency_type = self::CURRENCY_TYPE;
    	$this->original_gamelogs_table = self::SBOBETGAME_ORIG_GAMELOGS_TABLE;
        $this->default_lauch_game_type = self::SBO_GAME_TYPE['sportsbook'];
    }
}
/*end of file*/