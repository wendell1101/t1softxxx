<?php
require_once dirname(__FILE__) . '/game_api_oneapi_seamless.php';

class Game_api_oneapi_3oaks_seamless extends Game_api_oneapi_seamless {
    public $game_platform_id, $subprovider_username_prefix;
    public function getPlatformCode(){
        return ONEAPI_3OAKS_SEAMLESS_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->game_platform_id = $this->getPlatformCode();
    }
}

/*end of file*/

        
