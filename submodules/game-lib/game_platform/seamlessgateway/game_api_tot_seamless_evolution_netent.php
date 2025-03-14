<?php

require_once dirname(__FILE__) . '/abstract_game_api_tot_seamless_gateway.php';

class Game_api_tot_seamless_evolution_netent extends Abstract_game_api_tot_seamless_gateway{

    public function getPlatformCode(){
        return T1_EVOLUTION_NETENT_SEAMLESS_GAMING_API;
    }

    public function getOriginalPlatformCode(){
        return EVOLUTION_NETENT_SEAMLESS_GAMING_API;
    }

    public function __construct(){
        parent::__construct();
    }

}