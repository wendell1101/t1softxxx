<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_rtg2_seamless_wallet_transactions_20241024 extends CI_Migration {
    private $tableName = 'rtg2_seamless_wallet_transactions';

	public function up() {
	
		if(!$this->db->table_exists($this->tableName)){
			$this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$this->tableName.' like rtg_seamless_wallet_transactions');
	    }
	}

	public function down() {
		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}