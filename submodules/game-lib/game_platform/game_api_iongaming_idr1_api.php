<?php
require_once dirname(__FILE__) . '/game_api_common_iongaming.php';

class Game_api_iongaming_idr1_api extends Game_api_common_iongaming {
    const ORIGINAL_GAME_LOGS = 'iongaming_idr1_game_logs';
	
	public function getPlatformCode(){
		return ION_GAMING_IDR1_API;
    }

    public function __construct(){
        // $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        // $this->original_gamelogs_table = $this->getSystemInfo('original_table', self::ORIGINAL_GAME_LOGS);
        parent::__construct();
        $this->original_gamelogs_table = $this->getSystemInfo('original_table', self::ORIGINAL_GAME_LOGS);
    }
}

/*end of file*/

		
