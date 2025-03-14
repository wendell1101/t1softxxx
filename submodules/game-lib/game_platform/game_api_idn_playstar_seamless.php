<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_playstar_seamless.php';

class Game_api_idn_playstar_seamless extends Abstract_game_api_common_playstar_seamless {
	const CURRENCY_TYPE = "USD";
	public function getPlatformCode(){
		return IDN_PLAYSTAR_SEAMLESS_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->currency_type = $this->getSystemInfo('currency', self::CURRENCY_TYPE);
    }

    public function isSeamLessGame(){
        return true;
    }

}
/*end of file*/