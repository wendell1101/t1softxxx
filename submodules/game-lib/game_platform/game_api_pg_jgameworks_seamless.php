<?php
require_once dirname(__FILE__) . '/game_api_jgameworks_seamless.php';

class Game_api_pg_jgameworks_seamless extends Game_api_jgameworks_seamless {
	
	public function getPlatformCode(){
        return PG_JGAMEWORKS_SEAMLESS_API;
    }

    public function getGameidProviderCode(){
    	return "pg";
    }
}