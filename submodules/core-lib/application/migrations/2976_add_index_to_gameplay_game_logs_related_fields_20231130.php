<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_gameplay_game_logs_related_fields_20231130 extends CI_Migration {

	public function up() {
        $this->load->model('player_model');

        $tableName = 'gameplay_game_logs';
        if( $this->utils->table_really_exists($tableName) ){
            if( $this->db->field_exists('trans_date', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_trans_date', 'trans_date');
            }
        }
	}

	public function down() {

	}
}