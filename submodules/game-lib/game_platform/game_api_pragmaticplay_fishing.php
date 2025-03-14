<?php
require_once dirname(__FILE__) . '/game_api_pragmaticplay.php';


class Game_api_pragmaticplay_fishing extends Game_api_pragmaticplay
{

    public function __construct() {
        parent::__construct();
        $this->data_type_for_sync = $this->getSystemInfo('data_type_for_sync',['R2']);
    }

    public function getPlatformCode() {
        return PRAGMATIC_PLAY_FISHING_API;
    }
}