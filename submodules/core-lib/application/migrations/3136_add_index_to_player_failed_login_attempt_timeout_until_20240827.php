<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_player_failed_login_attempt_timeout_until_20240827 extends CI_Migration {
	private $tableName = 'player';

	public function up() {

		$this->load->model('player_model');

		if( $this->utils->table_really_exists($this->tableName) ){
            if( $this->db->field_exists('failed_login_attempt_timeout_until', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_failed_login_attempt_timeout_until', 'failed_login_attempt_timeout_until');
            }
        }
	}

	public function down() {

	}
}