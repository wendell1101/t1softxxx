<?php
require_once dirname(__FILE__) . '/game_api_common_pragmaticplay.php';

class Game_api_pragmaticplay_idr4_api extends Game_api_common_pragmaticplay {
	const ORIGINAL_TABLE = "pragmaticplay_idr4_game_logs";
	const RECORD_PATH = "/var/game_platform/pragmaticplay_idr4";

	public function getPlatformCode(){
        return PRAGMATICPLAY_IDR4_API;
    }

    public function __construct(){
    	$this->original_table = self::ORIGINAL_TABLE;
		$this->game_records_path = self::RECORD_PATH;
        parent::__construct();
    }
}