<?php

require_once dirname(__FILE__) . "/BaseEvent.php";

class DepositEvent extends BaseEvent{

    protected $sale_order_id;
    protected $transaction_id;
    protected $player_id;
    protected $payment_account_id;

    public function extractData($data){
        $this->player_id=$data['player_id'];
        $this->sale_order_id=$data['sale_order_id'];
        $this->transaction_id=$data['transaction_id'];
        $this->payment_account_id=$data['payment_account_id'];
        if( !empty($data['og_target_db']) ){
            // for mdb
            $this->og_target_db=$data['og_target_db']; // __OG_TARGET_DB
        }
    }

    public function getSaleOrderId(){
        return $this->sale_order_id;
    }

    public function getTransactionId(){
        return $this->transaction_id;
    }

    public function getPlayerId(){
        return $this->player_id;
    }

    public function getPaymentAccountId() {
        return $this->payment_account_id;
    }

        /**
     * Get __OG_TARGET_DB
     *
     * @param bool $isEnabledMDB Usually be the return of utils::isEnabledMDB().
     * @param array $multiple_databases_of_config For confrm the database is exists.
     * Usually be the return of utils::getConfig('multiple_databases').
     *
     * @return null|string If its null, the database does Not exist Or Disabled MDB.
     */
    public function getOgTargetDb($isEnabledMDB, $multiple_databases_of_config){
        $og_target_db = null;
        if ( $isEnabledMDB ) {
            if( isset($multiple_databases_of_config[$this->og_target_db]['default']) ){
                $og_target_db = $this->og_target_db;
            }
        }

        return $og_target_db;
    } // EOF getOgTargetDb()
}
