<?php

require_once dirname(__FILE__) . '/game_api_agin.php';

class Game_api_agin_yoplay extends Game_api_agin {

    public function getPlatformCode(){
        return AGIN_YOPLAY_API;
    }

    public function __construct(){
        parent::__construct();
        $parent_api_id= $this->getSystemInfo('parent_api_id', AGIN_API);
        $this->parent_api = $this->utils->loadExternalSystemLibObject($parent_api_id);

        $this->merge_using_sub_provider = true;
        $this->sub_provider_list = ['YOPLAY'];
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = $this->createPlayerInDB($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create account for AG YOPLAY";
        if($return){
            $success = true;
            $this->setGameAccountRegistered($playerId);
            $message = "Successfully created account for AG YOPLAY";
        }
        
        return array("success" => $success, "message" => $message);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $result = array(
            'success' => true,
            'balance' => 0
        );

        return $result;
    }
}
/*end of file*/