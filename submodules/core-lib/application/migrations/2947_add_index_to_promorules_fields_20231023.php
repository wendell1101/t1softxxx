<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_promorules_fields_20231023 extends CI_Migration {

	private $tableNames = ['promorules'];


	public function up() {
        $this->load->model('player_model');

        foreach($this->tableNames as $tableName){
            if( $this->utils->table_really_exists($tableName) ){
                if( $this->db->field_exists('promoCategory', $tableName) ){
                    $this->player_model->addIndex($tableName, 'idx_promoCategory', 'promoCategory');
                }
                if( $this->db->field_exists('createdBy', $tableName) ){
                    $this->player_model->addIndex($tableName, 'idx_createdBy', 'createdBy');
                }
                if( $this->db->field_exists('updatedBy', $tableName) ){
                    $this->player_model->addIndex($tableName, 'idx_updatedBy', 'updatedBy');
                }
            }
        }
	}

	public function down() {

	}
}