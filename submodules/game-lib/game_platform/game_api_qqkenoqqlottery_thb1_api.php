<?php
require_once dirname(__FILE__) . '/game_api_common_qqkeno_qqlottery.php';

class Game_api_qqkenoqqlottery_thb1_api extends Game_api_common_qqkeno_qqlottery {
	const CURRENCY_TYPE = "THB";
	public function getPlatformCode(){
		return QQKENO_QQLOTTERY_THB_B1_API;
    }

    public function __construct(){
    	parent::__construct();
    	$this->currency_type = self::CURRENCY_TYPE;
    }
}
/*end of file*/