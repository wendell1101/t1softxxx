<?php
require_once dirname(__FILE__) . '/game_api_oneapi_seamless.php';

class Game_api_oneapi_hacksaw_seamless extends Game_api_oneapi_seamless {
    public $game_platform_id, $subprovider_username_prefix;
    public function getPlatformCode(){
        return ONEAPI_HACKSAW_SEAMLESS_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->game_platform_id = $this->getPlatformCode();
    }
}

/*end of file*/

        
