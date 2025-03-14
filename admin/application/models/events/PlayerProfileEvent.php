<?php

require_once dirname(__FILE__) . "/BaseEvent.php";

class PlayerProfileEvent extends BaseEvent{

    protected $player_id;
    protected $source_method;

    public function extractData($data){
        $this->player_id=$data['player_id'];
        if( !empty($data['source_method']) ){
            $this->source_method=$data['source_method'];
        }
        if( !empty($data['og_target_db']) ){
            // for mdb
            $this->og_target_db=$data['og_target_db']; // __OG_TARGET_DB
        }
    }
    public function getPlayerId(){
        return $this->player_id;
    }

    public function getSourceMethod(){
        return $this->source_method;
    }
}
