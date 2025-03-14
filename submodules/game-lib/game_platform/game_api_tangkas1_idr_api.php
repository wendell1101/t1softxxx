<?php
require_once dirname(__FILE__) . '/game_api_common_tangkas1.php';

class Game_api_tangkas1_idr_api extends Game_api_common_tangkas1 {
	const CURRENCY_CONVERSION = 1;

	public function getPlatformCode(){
		return TANGKAS1_IDR_API;
    }

    public function __construct(){
    	parent::__construct();
    	$this->currency_conversion = $this->getSystemInfo('currency_conversion',self::CURRENCY_CONVERSION);
    }
}
/*end of file*/