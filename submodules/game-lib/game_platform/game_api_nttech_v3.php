<?php
require_once dirname(__FILE__) . '/game_api_common_nttech_v2.php';

class Game_api_nttech_v3 extends Game_api_common_nttech_v2 {

    public function __construct() {
        parent::__construct();
        $this->currency_type = $this->getSystemInfo('currency_type');
        $this->player_lang = $this->getSystemInfo('player_lang');
        $this->original_gamelogs_table = $this->getSystemInfo('original_gamelogs_table', 'nttech_v3_game_logs');
    }

    public function getPlatformCode() {
        return NTTECH_V3_API;
    }
}
/*end of file*/