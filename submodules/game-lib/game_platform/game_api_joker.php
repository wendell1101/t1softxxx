<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_joker.php';

class Game_api_joker extends abstract_game_api_common_joker {
	public function getPlatformCode(){
		return JOKER_API;
    }

    public function __construct(){
        parent::__construct();
        
        $this->originalTable = 'joker_game_logs';
    }

}
/*end of file*/