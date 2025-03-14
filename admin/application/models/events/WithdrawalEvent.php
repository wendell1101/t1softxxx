<?php

require_once dirname(__FILE__) . "/BaseEvent.php";

class WithdrawalEvent extends BaseEvent{

    protected $wallet_account_id;
    protected $transaction_id;
    protected $player_id;

    public function extractData($data){
        $this->player_id=$data['player_id'];
        $this->wallet_account_id=$data['wallet_account_id'];
        $this->transaction_id=$data['transaction_id'];

        if( ! empty($data['og_target_db']) ){
            // for mdb
            $this->og_target_db = $data['og_target_db']; // __OG_TARGET_DB
        }

    }

    public function getWalletAccountId(){
        return $this->wallet_account_id;
    }

    public function getTransactionId(){
        return $this->transaction_id;
    }

    public function getPlayerId(){
        return $this->player_id;
    }

}
