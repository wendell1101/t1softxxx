<?php

require_once dirname(__FILE__) . "/BaseEvent.php";

class RegisterEvent extends BaseEvent{

    protected $player_id;

    public function extractData($data){
        $this->player_id=$data['player_id'];
        if( !empty($data['og_target_db']) ){
            // for mdb
            $this->og_target_db=$data['og_target_db']; // __OG_TARGET_DB
        }
    }

    public function getPlayerId(){
        return $this->player_id;
    }
}