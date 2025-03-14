<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_ap_game_logs_20240925 extends CI_Migration {

	private $tableName = 'ap_game_logs';
	private $originalTableName = 'pinnacle_game_logs';

	public function up() {
		if(!$this->db->table_exists($this->tableName)){
			$this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$this->tableName.' like '.$this->originalTableName);
	    }
	}

	public function down() {
		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
