<?php
require_once dirname(__FILE__) . '/game_api_common_idn.php';

class Game_api_idnpoker extends Game_api_common_idn {

    const ORIGINAL_LOGS_TABLE_NAME = 'idnpoker_game_logs';

    public function getPlatformCode() {
        return IDNPOKER_API;
    }

    public function __construct() {
        parent::__construct();
    }


}

/*end of file*/
