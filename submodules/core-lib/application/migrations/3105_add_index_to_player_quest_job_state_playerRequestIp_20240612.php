<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_player_quest_job_state_playerRequestIp_20240612 extends CI_Migration {
	private $tableName = 'player_quest_job_state';

	public function up() {

		$this->load->model('player_model');

		if( $this->utils->table_really_exists($this->tableName) ){
            if( $this->db->field_exists('playerRequestIp', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_playerRequestIp', 'playerRequestIp');
            }
        }
	}

	public function down() {

	}
}