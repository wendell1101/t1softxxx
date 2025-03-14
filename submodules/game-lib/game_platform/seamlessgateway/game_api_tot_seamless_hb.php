<?php

require_once dirname(__FILE__) . '/abstract_game_api_tot_seamless_gateway.php';

class Game_api_tot_seamless_hb extends Abstract_game_api_tot_seamless_gateway{

    public function getPlatformCode(){
        return T1_HABANERO_SEAMLESS_GAME_API;
    }

    public function getOriginalPlatformCode(){
        return HABANERO_SEAMLESS_GAMING_API;
    }

    public function __construct(){
        parent::__construct();
    }

}
