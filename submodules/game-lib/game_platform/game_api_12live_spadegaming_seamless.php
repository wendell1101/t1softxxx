<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_12live.php';

class Game_api_12live_spadegaming_seamless extends Abstract_game_api_common_12live {
    
    const SUB_PROVIDER_ID = 14;
    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';

    public function getPlatformCode(){
        return LIVE12_SPADEGAMING_SEAMLESS_API;
    }
    public function getCurrency() {
        return $this->getSystemInfo('currency', 'THB');
    }

    public function __construct(){
        parent::__construct();
        $this->provider_id = self::SUB_PROVIDER_ID;
        $this->original_gamelogs_table = $this->getOriginalTable();
        $this->original_transactions_table = self::ORIGINAL_TRANSACTION_TABLE;
    }

}

/*end of file*/

        
