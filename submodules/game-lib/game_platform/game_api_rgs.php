<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_rgs.php';

class Game_api_rgs extends Abstract_game_api_common_rgs {
	const ORIGINAL_GAMELOGS_TABLE = 'rgs_game_logs';
	
	public function getPlatformCode(){
		return RGS_API;
    }

    public function __construct(){
        parent::__construct();
    	$this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
    }
}