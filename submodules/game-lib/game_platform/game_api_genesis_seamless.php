<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_genesis_seamless.php';

class Game_api_genesis_seamless extends Abstract_game_api_common_genesis_seamless {
	const ORIGINAL_GAMELOGS_TABLE = 'genesis_seamless_game_logs';
	const ORIGINAL_TRANSACTION_TABLE = 'genesis_seamless_transactions';
	
	public function getPlatformCode(){
		return GENESIS_SEAMLESS_API;
    }

    public function __construct(){
        parent::__construct();
    	$this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
    	$this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;
    }
    
    public function getTransactionsTable(){
        return $this->original_transactions_table;
    }
}
/*end of file*/