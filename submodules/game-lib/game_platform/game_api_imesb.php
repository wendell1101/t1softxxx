<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_imesb.php';

class Game_api_imesb extends Abstract_game_api_common_imesb {
	public function getPlatformCode(){
		return IMESB_API;
    }

    public function __construct(){
        parent::__construct();
        $this->originalTable = 'imesb_game_logs';
    }
}
/*end of file*/