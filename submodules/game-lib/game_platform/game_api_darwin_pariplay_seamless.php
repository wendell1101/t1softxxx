<?php
require_once dirname(__FILE__) . '/game_api_pariplay_seamless.php';

class Game_api_darwin_pariplay_seamless extends Game_api_pariplay_seamless {
	
	public function getPlatformCode(){
        return DARWIN_PARIPLAY_SEAMLESS_API;
    }
}