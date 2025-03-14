<?php
require_once dirname(__FILE__) . '/game_api_common_pragmaticplay.php';

class Game_api_pragmaticplay_idr1_api extends Game_api_common_pragmaticplay {
	const ORIGINAL_TABLE = "pragmaticplay_idr1_game_logs";
	const RECORD_PATH = "/var/game_platform/pragmaticplay_idr1";

	public function getPlatformCode(){
        return PRAGMATICPLAY_IDR1_API;
    }

    public function __construct(){
    	// $this->original_table = self::ORIGINAL_TABLE;
        // $this->original_table = $this->getSystemInfo('original_table', self::ORIGINAL_TABLE);
		$this->game_records_path = self::RECORD_PATH;
        parent::__construct();
        $this->original_table = $this->getSystemInfo('original_table', self::ORIGINAL_TABLE);
    }
}