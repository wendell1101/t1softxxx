<?php
require_once dirname(__FILE__) . '/game_api_common_pragmaticplay.php';

class Game_api_pragmaticplay_idr3_api extends Game_api_common_pragmaticplay {
	const ORIGINAL_TABLE = "pragmaticplay_idr3_game_logs";
	const RECORD_PATH = "/var/game_platform/pragmaticplay_idr3";

	public function getPlatformCode(){
        return PRAGMATICPLAY_IDR3_API;
    }

    public function __construct(){
    	$this->original_table = self::ORIGINAL_TABLE;
		$this->game_records_path = self::RECORD_PATH;
        parent::__construct();
    }
}