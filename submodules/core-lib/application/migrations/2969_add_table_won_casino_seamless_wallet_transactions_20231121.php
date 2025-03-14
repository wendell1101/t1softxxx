<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_won_casino_seamless_wallet_transactions_20231121 extends CI_Migration {

    public function up() {
        $tableName="won_casino_seamless_wallet_transactions";
        if (!$this->CI->utils->table_really_exists($tableName)) {
            try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like yeebet_seamless_wallet_transactions');

            }catch(Exception $e){
                $this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
            }
        }
    }

    public function down() {
        if($this->db->table_exists("won_casino_seamless_wallet_transactions")){
            $this->dbforge->drop_table("won_casino_seamless_wallet_transactions");
        }
    }
}