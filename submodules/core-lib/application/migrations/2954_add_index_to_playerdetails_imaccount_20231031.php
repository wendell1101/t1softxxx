<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_playerdetails_imaccount_20231031 extends CI_Migration {

	private $tableName = 'playerdetails';

	public function up() {
        $this->load->model('player_model');

		if( $this->utils->table_really_exists($this->tableName) ){

            if( $this->db->field_exists('imAccount', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_imAccount', 'imAccount');
            }

			if( $this->db->field_exists('imAccount2', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_imAccount2', 'imAccount2');
            }

			if( $this->db->field_exists('imAccount3', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_imAccount3', 'imAccount3');
            }

			if( $this->db->field_exists('imAccount4', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_imAccount4', 'imAccount4');
            }

			if( $this->db->field_exists('imAccount5', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_imAccount5', 'imAccount5');
            }
        }
	}

	public function down() {

	}
}