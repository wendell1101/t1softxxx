<?php
require_once dirname(__FILE__) . '/game_api_kplay_seamless.php';

class Game_api_kplay_evo_seamless extends Game_api_kplay_seamless {

	public function __construct() {
        parent::__construct();
        $this->auth_product_id = 1;
    }
	
	public function getPlatformCode(){
        return KPLAY_EVO_SEAMLESS_GAME_API;
    }
}