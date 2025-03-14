<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_point_transactions_20210308b extends CI_Migration {

    private $tableName='point_transactions';  

	public function up() {
		$fields1 = array(
			'date_within' => array(
				'type' => 'DATE',
				'null' => true,
			),
		);		
		if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('date_within', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields1);
            }          
        }
	}

	public function down() {
		if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('date_within', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'date_within');
            }            
        }
	}
}