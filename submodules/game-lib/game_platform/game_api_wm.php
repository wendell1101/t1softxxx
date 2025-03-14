<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_wm.php';

class Game_api_wm extends Abstract_game_api_common_wm {
	const ORIGINAL_GAMELOGS_TABLE = 'wm_game_logs';
	
	public function getPlatformCode(){
		return WM_API;
    }

    public function __construct(){
        parent::__construct();
    	$this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
    }
}