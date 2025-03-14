<?php
if(! defined("BASEPATH")){
    exit("No direct script access allowed");
}

require_once dirname(__FILE__) . "/game_api_evolution_seamless_thb1_api.php";


/**
 * Default Class of Evolution Seamless
 */

 class Game_api_evolution_netent_seamless extends Game_api_evolution_seamless_thb1_api
 {
    public $original_seamless_wallet_transactions_table = 'evolution_netent_seamless_wallet_transactions';

    /**
     * Get Platform code of Game API
     * 
     * @return int game platform code
    */
    public function getPlatformCode()
    {
        return EVOLUTION_NETENT_SEAMLESS_GAMING_API;
    }
    
    public function getOriginalTable()
    {
        // return 'evolution_netent_seamless_game_logs';
        $tableName = 'evolution_netent_seamless_game_logs';
        if (!$this->CI->utils->table_really_exists($tableName)) {
            try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName." like evolution_seamless_thb1_game_logs");

            }catch(Exception $e){
                $this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
            }
        }

        return $tableName;
    }
 }