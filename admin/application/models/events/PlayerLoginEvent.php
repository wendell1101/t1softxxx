<?php

require_once dirname(__FILE__) . "/BaseEvent.php";

class PlayerLoginEvent extends BaseEvent{

    protected $player_id;
    protected $login_ip;
    protected $login_info;
    protected $source_method;

    public function extractData($data){
        $this->player_id=$data['player_id'];
        $this->login_ip=$data['login_ip'];
        $this->login_info=$data['login_info'];
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

    public function getLoginIp(){
        return $this->login_ip;
    }

    public function getLoginInfo(){
        return $this->login_info;
    }

    public function getSourceMethod(){
        return $this->source_method;
    }
}
