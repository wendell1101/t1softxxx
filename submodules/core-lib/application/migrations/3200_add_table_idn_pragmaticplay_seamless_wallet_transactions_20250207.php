<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_idn_pragmaticplay_seamless_wallet_transactions_20250207 extends CI_Migration {
	private $tableName = 'idn_pragmaticplay_seamless_wallet_transactions';
	private $originalTable = 'pragmaticplay_seamless_wallet_transactions';

	public function up() {
        if(!$this->db->table_exists($this->tableName)){
            $this->CI->load->model(['player_model']);
            $this->CI->player_model->runRawUpdateInsertSQL('create table '.$this->tableName.' like '.$this->originalTable);
        }
	}

	public function down() {
        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
	}
}
