<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_player_quest_job_state_withdrawConditionId_20240423 extends CI_Migration {
	private $tableName = 'player_quest_job_state';

	public function up() {

		$this->load->model('player_model');

		if( $this->utils->table_really_exists($this->tableName) ){
            if( $this->db->field_exists('withdrawConditionId', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_withdrawConditionId', 'withdrawConditionId');
            }
        }
	}

	public function down() {

	}
}