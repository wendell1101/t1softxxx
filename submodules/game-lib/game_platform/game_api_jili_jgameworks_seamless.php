<?php
require_once dirname(__FILE__) . '/game_api_jgameworks_seamless.php';

class Game_api_jili_jgameworks_seamless extends Game_api_jgameworks_seamless {
	
	public function getPlatformCode(){
        return JILI_JGAMEWORKS_SEAMLESS_API;
    }

    public function getGameidProviderCode(){
    	return "jili";
    }
}