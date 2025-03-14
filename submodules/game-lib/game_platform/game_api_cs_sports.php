<?php
require_once dirname(__FILE__) . '/game_api_common_cs_sports.php';

class Game_api_cs_sports extends Game_api_common_cs_sports {

	public function getPlatformCode(){
		return CS_SPORTS_API;
    }

}
/*end of file*/