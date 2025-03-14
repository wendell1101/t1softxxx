<?php
require_once dirname(__FILE__) . '/game_api_common_nttech_v2.php';

class Game_api_awc_horsebook extends Game_api_common_nttech_v2 {
	
	public function getPlatformCode(){
		return AWC_HORSEBOOK_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->currency_type = $this->getSystemInfo('currency_type');
        $this->player_lang = $this->getSystemInfo('player_lang');
    }

    
}
/*end of file*/