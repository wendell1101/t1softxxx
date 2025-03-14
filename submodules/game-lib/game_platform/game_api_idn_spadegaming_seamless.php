<?php
require_once dirname(__FILE__) . '/game_api_spadegaming_seamless.php';
/**
 * IDN B2B Spade gaming( client credentials )
 * OGP-35521
 *
 * @author  Jerbey Capoquian
 *
 * casino development handle by yeebet
 * 
 *
 * By function:
    
 *
 * 
 * Related File

     - spadegaming_seamless_service_api.php
 */


class Game_api_idn_spadegaming_seamless extends Game_api_spadegaming_seamless {
 
    public function __construct() {
        parent::__construct();

        $this->original_seamless_game_logs_table = "idn_spadegaming_seamless_game_logs";
        $this->original_seamless_wallet_transactions_table = "idn_spadegaming_seamless_wallet_transactions";
        $this->game_seamless_service_logs_table = "idn_spadegaming_seamless_service_logs";
    }

    public function getPlatformCode() {
        return IDN_SPADEGAMING_SEAMLESS_GAME_API;
    }

    public function getSeamlessGameLogsTable() {
        $tableName = $this->original_seamless_game_logs_table;
        if (!$this->CI->utils->table_really_exists($tableName)) {
            try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName." like spadegaming_seamless_game_logs");

            }catch(Exception $e){
                $this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
            }
        }

        return $tableName;
    }

    public function getSeamlessTransactionTable() {
        $tableName = $this->original_seamless_wallet_transactions_table;
        if (!$this->CI->utils->table_really_exists($tableName)) {
            try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName." like spadegaming_seamless_wallet_transactions");

            }catch(Exception $e){
                $this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
            }
        }

        return $tableName;
    }

    public function getGameSeamlessServiceLogsTable() {
        $tableName = $this->game_seamless_service_logs_table;
        if (!$this->CI->utils->table_really_exists($tableName)) {
            try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName." like spadegaming_seamless_service_logs");

            }catch(Exception $e){
                $this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
            }
        }

        return $tableName;
    }
}
