<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_ruby_play.php';

class Game_api_ruby_play extends Abstract_game_api_common_ruby_play {
    const ORIGINAL_GAME_LOGS = 'ruby_play_game_logs';
    
    public function getPlatformCode(){
        return RUBYPLAY_SEAMLESS_API;
    }
    public function getOriginalTable(){
        return self::ORIGINAL_GAME_LOGS;
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = $this->getOriginalTable();
    }

}

/*end of file*/

        
