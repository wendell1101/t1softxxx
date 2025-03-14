<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_playertag_20230830 extends CI_Migration {

	private $tableName = 'playertag';

	public function up() {
        $this->load->model('player_model');

		if( $this->utils->table_really_exists($this->tableName) ){
            if( $this->db->field_exists('isDeleted', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_isDeleted', 'isDeleted');
            }
        }
	}

	public function down() {

	}
}