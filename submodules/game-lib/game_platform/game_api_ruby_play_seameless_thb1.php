<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_ruby_play.php';

class Game_api_ruby_play_seameless_thb1 extends Abstract_game_api_common_ruby_play {
    const ORIGINAL_GAME_LOGS = 'ruby_play_thb1_game_logs';
    const CURRENCY = "THB";
    
    public function getPlatformCode(){
        return RUBYPLAY_SEAMLESS_THB1_API;
    }
    public function getOriginalTable(){
        return self::ORIGINAL_GAME_LOGS;
    }
    public function getCurrency() {
        return self::CURRENCY;
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = $this->getOriginalTable();
    }

}

/*end of file*/

        
