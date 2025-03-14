<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_97lotto.php';

class Game_api_97lotto_seamless extends Abstract_game_api_common_97lotto {
    const ORIGINAL_GAME_LOGS = '';
    const CURRENCY = 'CNY';
    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';
    
    public function getPlatformCode(){
        return LOTTO97_SEAMLESS_GAME_API;
    }
    public function getOriginalTable(){
        return self::ORIGINAL_GAME_LOGS;
    }

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = $this->getOriginalTable();
        $this->currency = $this->getSystemInfo('currency',self::CURRENCY);
        $this->original_transactions_table = self::ORIGINAL_TRANSACTION_TABLE;
    }
    
    public function getTransactionsTable(){
        return $this->original_transactions_table;
    }

}

/*end of file*/

        
