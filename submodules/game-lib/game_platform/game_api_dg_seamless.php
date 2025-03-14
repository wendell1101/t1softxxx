<?php
/**
 * DG Seamless Integration
 * OGP-14574
 *
 * @author  Erickson Qua
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/abstract_game_api_common_dg.php';

class Game_api_dg_seamless extends Abstract_game_api_dg_common {
    const ORIGINAL_TRANSACTION_TABLE = 'dg_seamless_wallet_transactions';
    
    public function __construct(){
        parent::__construct();
        $this->original_transactions_table = self::ORIGINAL_TRANSACTION_TABLE;
    }
    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode(){
        return DG_SEAMLESS_API;
    }
    
    public function getTransactionsTable(){
        return $this->original_transactions_table;
    }
}
