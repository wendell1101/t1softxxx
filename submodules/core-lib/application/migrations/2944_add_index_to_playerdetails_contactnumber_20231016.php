<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_playerdetails_contactnumber_20231016 extends CI_Migration {

	private $tableNames = ['playerdetails'];


	public function up() {
        $this->load->model('player_model');

        foreach($this->tableNames as $tableName){
            if( $this->utils->table_really_exists($tableName) ){
                if( $this->db->field_exists('contactNumber', $tableName) ){
                    $this->player_model->addIndex($tableName, 'idx_contactNumber', 'contactNumber');
                }
            }
        }
	}

	public function down() {

	}
}