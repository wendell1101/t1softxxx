<?php
require_once dirname(__FILE__) . "/game_api_t1_common.php";

class Game_api_slot_factory_t1 extends Game_api_t1_common
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getPlatformCode()
    {
        return T1SLOTFACTORY_API;
    }

    public function getOriginalPlatformCode()
    {
        return SLOT_FACTORY_SEAMLESS_API;
    }
}