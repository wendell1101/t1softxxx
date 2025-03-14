<?php
require_once dirname(__FILE__) . '/game_api_wm_seamless.php';

class Game_api_wm2_seamless extends Game_api_wm_seamless {

    public $request;

    public $original_gamelogs_table;
    public $original_transactions_table;

	public function getPlatformCode(){
		return WM2_SEAMLESS_GAME_API;
    }

    public function __construct(){
        parent::__construct();

    	$this->original_gamelogs_table = $this->getSystemInfo('original_gamelogs_table', 'wm2_seamless_game_logs');
        $this->original_transactions_table = $this->getSystemInfo('original_transactions_table', 'wm2_casino_transactions');
    }

}//end of class