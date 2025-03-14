<?php

require_once dirname(__FILE__) . '/abstract_game_api_tot_seamless_gateway.php';

class Game_api_tot_seamless_cmd2 extends Abstract_game_api_tot_seamless_gateway {

    public function getPlatformCode() {
        return T1_CMD2_SEAMLESS_GAME_API;
    }

    public function getOriginalPlatformCode() {
        return CMD2_SEAMLESS_GAME_API;
    }

    public function __construct() {
        parent::__construct();
    }
}