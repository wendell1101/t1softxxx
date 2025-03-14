<?php
require_once dirname(__FILE__) . '/game_api_common_hogaming.php';

class Game_api_hogaming extends Game_api_common_hogaming {
	public function getPlatformCode(){
		return HOGAMING_API;
    }

    public function __construct(){
    	parent::__construct();
    	$this->currency_type = $this->getSystemInfo('currency_type',"CNY");
    }
}
/*end of file*/