<?php
require_once dirname(__FILE__) . '/game_api_common_nttech_v2.php';

class Game_api_king_maker extends Game_api_common_nttech_v2 {
	const CURRENCY_TYPE = "THB";
	const DEDAULT_LANG = "th";
	const KING_MAKER_ORIG_GAMELOGS_TABLE = "king_maker_game_logs";

	public function getPlatformCode(){
		return KING_MAKER_GAMING_API;
    }

    public function __construct(){
        parent::__construct();
        $this->currency_type = self::CURRENCY_TYPE;
        $this->player_lang = self::DEDAULT_LANG;
        $this->original_gamelogs_table = self::KING_MAKER_ORIG_GAMELOGS_TABLE;
    }
}
/*end of file*/