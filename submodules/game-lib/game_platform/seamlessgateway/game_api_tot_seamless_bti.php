<?php

require_once dirname(__FILE__) . '/abstract_game_api_tot_seamless_gateway.php';

class Game_api_tot_seamless_bti extends Abstract_game_api_tot_seamless_gateway{

    public function getPlatformCode(){
        return T1_BTI_SEAMLESS_GAME_API;
    }

    public function getOriginalPlatformCode(){
        return BTI_SEAMLESS_GAME_API;
    }

    public function __construct(){
        parent::__construct();

        $this->allow_launch_demo_without_authentication=$this->getSystemInfo('allow_launch_demo_without_authentication', true);
    }

}
