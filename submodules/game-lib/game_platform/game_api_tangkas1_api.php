<?php
require_once dirname(__FILE__) . '/game_api_common_tangkas1.php';

class Game_api_tangkas1_api extends Game_api_common_tangkas1 
{
	const CURRENCY_CONVERSION = 100;

	public function getPlatformCode()
	{
		return TANGKAS1_API;
    }

    public function __construct()
    {
    	$this->currency_conversion = self::CURRENCY_CONVERSION;
        parent::__construct();
    }
}
/*end of file*/