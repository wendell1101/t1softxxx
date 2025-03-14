<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_12live.php';

class Game_api_12live_seamless extends Abstract_game_api_common_12live {
    const ORIGINAL_GAME_LOGS = 'pgsoft_seamless_game_logs';
    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';
    
    public function getPlatformCode(){
        return LIVE12_SEAMLESS_GAME_API;
    }
    public function getOriginalTable(){
        return self::ORIGINAL_GAME_LOGS;
    }
    public function getCurrency() {
        return $this->getSystemInfo('currency', 'THB');
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = $this->getOriginalTable();
        $this->original_transactions_table = self::ORIGINAL_TRANSACTION_TABLE;
    }
    
    public function getTransactionsTable(){
        return $this->original_transactions_table;
    }

}

/*end of file*/

        
