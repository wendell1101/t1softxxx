<?php

require_once dirname(__FILE__) . "/BaseEvent.php";

class InternalMessageEvent extends BaseEvent{

    protected $player_id;
    protected $message_id; // messages.messageId
    protected $source_method;
    protected $extra_info;


    public function extractData($data){
        if( ! empty($data['player_id'])){
            $this->player_id = $data['player_id'];
        }
        if( ! empty($data['message_id'])){
            $this->message_id = $data['message_id'];
        }
        $this->source_method = $data['source_method'];

        if( ! empty($data['extra_info'])){
            $this->extra_info = $data['extra_info'];
        }

        if( ! empty($data['og_target_db']) ){
            // for mdb
            $this->og_target_db = $data['og_target_db']; // __OG_TARGET_DB
        }
    }

    public function getSourceMethod(){
        return $this->source_method;
    }
    public function getExtraInfo(){
        return $this->extra_info;
    }

    public function getPlayerId(){
        return $this->player_id;
    }

    public function getMessageId(){
        return $this->message_id;
    }

}