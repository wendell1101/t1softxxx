<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_hydako.php';

class Game_api_hydako extends Abstract_game_api_common_hydako {

	const CURRENCY_CODE = 'USD';
	const COUNTRY_CODE = 'EN';
	const ORIGINAL_TABLE = "hydako_game_logs";
    
    public function getPlatformCode(){
        return HYDAKO_GAME_API;
    }

    public function currency() {
    	return self::CURRENCY_CODE;
    }

    public function countryCode() {
    	return self::COUNTRY_CODE;
    }

    public function __construct(){
        parent::__construct();
        $this->currency = $this->currency();
    	$this->country_code = $this->countryCode();
        $this->language = $this->getSystemInfo('language', 'en');
        $this->original_game_logs_table = self::ORIGINAL_TABLE;
    }
}

/*end of file*/

        
