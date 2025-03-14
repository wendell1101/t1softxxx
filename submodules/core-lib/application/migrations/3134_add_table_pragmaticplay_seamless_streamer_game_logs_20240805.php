<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_pragmaticplay_seamless_streamer_game_logs_20240805 extends CI_Migration {

    private $tableName = 'pragmaticplay_seamless_streamer_game_logs';
	private $originalTableName = 'pragmaticplay_seamless_game_logs';
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
