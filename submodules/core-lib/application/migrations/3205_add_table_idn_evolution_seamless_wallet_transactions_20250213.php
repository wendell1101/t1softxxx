<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_idn_evolution_seamless_wallet_transactions_20250213 extends CI_Migration {
	private $tableNames = 
        [
            'idn_evolution_seamless_wallet_transactions',
            'idn_evolution_netent_seamless_wallet_transactions',
            'idn_evolution_redtiger_seamless_wallet_transactions',
            'idn_evolution_nlc_seamless_wallet_transactions',
            'idn_evolution_btg_seamless_wallet_transactions',
        ];

	private $originalTable = 'evolution_seamless_wallet_transactions';

	public function up() {
        foreach ($this->tableNames as $tableName){
            if(!$this->db->table_exists($this->tableName)){
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like '.$this->originalTable);
            }
        }
	}

	public function down() {
        foreach ($this->tableNames as $tableName){
            if(!$this->db->table_exists($tableName)){
                $this->dbforge->drop_table($tableName);
            }
        }
	}
}
