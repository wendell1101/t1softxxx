<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_flow_gaming_seamless.php';

class Game_api_flow_gaming_netent_seamless extends Abstract_game_api_common_flow_gaming_seamless {
    
    const ORIGINAL_GAMELOGS_TABLE = 'fg_seamless_thb1_gamelogs';
    const ORIGINAL_TRANSACTION_TABLE = 'fg_seamless_thb1_gamelogs_per_transaction';
    
    public function getPlatformCode(){
        return FLOW_GAMING_NETENT_SEAMLESS_API;
    }

    public function __construct(){
        parent::__construct();
        $this->sub_game_provider = 'netent';
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;
    }

	public function syncOriginalGameLogs($token = false)
	{
    	return $this->returnUnimplemented();
	}
}
/*end of file*/