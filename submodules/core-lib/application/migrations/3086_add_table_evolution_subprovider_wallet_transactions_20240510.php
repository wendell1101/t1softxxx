<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_evolution_subprovider_wallet_transactions_20240510 extends CI_Migration {

    private $tableNames = [
        'evolution_btg_seamless_wallet_transactions',
    ];

    public function up() {

        foreach($this->tableNames as $tableName){
            if (!$this->CI->utils->table_really_exists($tableName)) {
                try{
                    $this->CI->load->model(['player_model']);
                    $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like evolution_seamless_wallet_transactions');
    
                }catch(Exception $e){
                    $this->CI->utils->error_log('create table failed: '.$tableName, $e);
                    return null;
                }
            }
        }
        
    }

    public function down() {
        foreach($this->tableNames as $tableName){
            if($this->db->table_exists($tableName)){
                $this->dbforge->drop_table($tableName);
            }
        }
    }
}