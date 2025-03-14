<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_messages_date_20240827 extends CI_Migration {
	private $tableName = 'messages';

	public function up() {

		$this->load->model('player_model');

		if( $this->utils->table_really_exists($this->tableName) ){
            if( $this->db->field_exists('date', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_date', 'date');
            }
        }
	}

	public function down() {

	}
}