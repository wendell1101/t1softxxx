<?php

require_once dirname(__FILE__) . '/abstract_game_api_tot_seamless_gateway.php';

class Game_api_tot_seamless_ygg_dcs extends Abstract_game_api_tot_seamless_gateway {

    public function getPlatformCode() {
        return T1_YGG_DCS_SEAMLESS_GAME_API;
    }

    public function getOriginalPlatformCode() {
        return YGG_DCS_SEAMLESS_GAME_API;
    }

    public function __construct() {
        parent::__construct();
    }

}
