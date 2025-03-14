<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_aff_logs_20250113 extends CI_Migration {
	private $tableName = 'aff_logs';

	public function up() {
		$this->load->model('player_model');

		if( $this->utils->table_really_exists($this->tableName) ){

            if( $this->db->field_exists('username', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_username', 'username');
            }
            if( $this->db->field_exists('affiliate_id', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_affiliate_id', 'affiliate_id');
            }
            if( $this->db->field_exists('action', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_action', 'action');
            }
        }
	}

	public function down() {
	}
}
