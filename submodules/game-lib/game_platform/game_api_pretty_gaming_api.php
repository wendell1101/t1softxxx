<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_pretty_gaming_api.php';

class Game_api_pretty_gaming_api extends Abstract_game_api_common_pretty_gaming_api {

    public function __construct() {
        parent::__construct();
        $this->original_gamelogs_table = 'pretty_gaming_gamelogs';
    }

    public function getPlatformCode(){
        return PRETTY_GAMING_API;
    }
}

/*end of file*/