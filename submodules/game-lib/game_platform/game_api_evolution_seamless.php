<?php
if(! defined("BASEPATH")){
    exit("No direct script access allowed");
}

require_once dirname(__FILE__) . "/game_api_evolution_seamless_thb1_api.php";


/**
 * Default Class of Evolution Seamless
 */

 class Game_api_evolution_seamless extends Game_api_evolution_seamless_thb1_api
 {
    public $original_seamless_wallet_transactions_table = 'evolution_seamless_wallet_transactions';

    /**
     * Get Platform code of Game API
     * 
     * @return int game platform code
    */
    public function getPlatformCode()
    {
        return EVOLUTION_SEAMLESS_GAMING_API;
    }
    
    public function getOriginalTable()
    {
        return 'evolution_seamless_game_logs';
    }

    public function isSupportsLobby(){
        return $this->getSystemInfo('is_support_lobby', true);
    }
 }