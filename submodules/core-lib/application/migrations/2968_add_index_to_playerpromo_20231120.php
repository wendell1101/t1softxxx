<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_playerpromo_20231120 extends CI_Migration {

	public function up() {
        $this->load->model('player_model');

        $tableName = 'playerpromo';
        if( $this->utils->table_really_exists($tableName) ){
            // - playerpromo.transactionStatus
            if( $this->db->field_exists('transactionStatus', $tableName) ){
                $this->player_model->addIndex($tableName, 'idx_transactionStatus', 'transactionStatus');
            }
        }
	}

	public function down() {

	}
}