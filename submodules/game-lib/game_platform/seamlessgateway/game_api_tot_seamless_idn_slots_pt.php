<?php

require_once dirname(__FILE__) . '/game_api_tot_seamless_pt.php';

class Game_api_tot_seamless_idn_slots_pt extends Game_api_tot_seamless_pt {

    public function getPlatformCode() {
        return T1_IDN_SLOTS_PT_SEAMLESS_GAME_API;
    }

    public function getOriginalPlatformCode() {
        return IDN_SLOTS_PT_SEAMLESS_GAME_API;
    }

    public function __construct() {
        parent::__construct();
    }
}
