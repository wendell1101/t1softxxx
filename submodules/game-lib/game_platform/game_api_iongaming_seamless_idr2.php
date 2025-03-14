<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_iongaming_seamless.php';

class Game_api_iongaming_seamless_idr2 extends Abstract_game_api_common_iongaming_seamless {

    const ORIGINAL_TRANSACTION_TABLE = 'iongaming_seamless_transactions_idr2';
    
    public function getPlatformCode(){
        return IONGAMING_SEAMLESS_IDR2_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;
    }
}
/*end of file*/