<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_pgsoft3_seamless_wallet_transactions_20231129 extends CI_Migration {

	private $tableName = 'pgsoft3_seamless_wallet_transactions';

	public function up() {
	
		if(!$this->db->table_exists($this->tableName)){
			$this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$this->tableName.' like pgsoft_seamless_wallet_transactions');
	    }
	}

	public function down() {
		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
