<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_idn_slots_and_live_pragmaticplay_seamless_wallet_transactions_20250211 extends CI_Migration {
	private $tableNames = [
        'idn_slots_pragmaticplay_seamless_wallet_transactions',
        'idn_live_pragmaticplay_seamless_wallet_transactions'
    ];

	private $originalTable = 'pragmaticplay_seamless_wallet_transactions';

	public function up() {
        foreach($this->tableNames as $tableName){
            if(!$this->db->table_exists($tableName)){
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like '.$this->originalTable);
            }
        }
        
	}

	public function down() {
        foreach($this->tableNames as $tableName){
            if(!$this->db->table_exists($tableName)){
                $this->dbforge->drop_table($tableName);
            }
        }
	}
}
