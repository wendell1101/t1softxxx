<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_slot_factory.php';

class Game_api_slot_factory_seamless extends Abstract_game_api_common_slot_factory {
    const ORIGINAL_GAME_LOGS = 'slot_factory_game_logs';
    
    public function getPlatformCode(){
        return SLOT_FACTORY_SEAMLESS_API;
    }

    public function __construct(){
        // $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        parent::__construct();
    }

    public function getOriginalTable(){
        return self::ORIGINAL_GAME_LOGS;
    }

    public function isSeamLessGame()
    {
       return false;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        
        // create player on game provider auth
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for slot factory api";
        if($return){
            $success = true;
            $message = "Successfull create account for slot factory api";
        }

        $this->utils->debug_log('<---------------Slot Factory------------> Succes: ', $success, 'Message: ', $message);
        
        return array("success" => $success, "message" => $message);
 
    }

    public function depositToGame($userName, $amount, $transfer_secure_id=null){
        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null){
        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }


    public function queryPlayerBalance($playerName) {
        
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true, 
            'balance' => $balance
        );

        $this->utils->debug_log('<---------------Slot Factory------------> Query Player Balance: ', $result);

        return $result;

    }
}

/*end of file*/

        
