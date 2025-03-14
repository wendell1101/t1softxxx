<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_player_ip_last_request_20230427 extends CI_Migration {

	private $tableName = 'player_ip_last_request';

	public function up() {
        $this->load->model('player_model');

		if( $this->utils->table_really_exists($this->tableName) ){
            if( $this->db->field_exists('ip', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_ip', 'ip');
            }
        }
	}

	public function down() {

	}
}