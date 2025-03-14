<?php
require_once dirname(__FILE__) . '/game_api_common_pragmaticplay.php';

class Game_api_pragmaticplay_thb2_api extends Game_api_common_pragmaticplay {
	const ORIGINAL_TABLE = "pragmaticplay_thb2_game_logs";
	const RECORD_PATH = "/var/game_platform/pragmaticplay_thb2";

	public function getPlatformCode(){
        return PRAGMATICPLAY_THB2_API;
    }

    public function __construct(){
    	$this->original_table = self::ORIGINAL_TABLE;
		$this->game_records_path = self::RECORD_PATH;
        parent::__construct();
    }
}