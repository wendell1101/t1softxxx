<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_point_transactions_20201014 extends CI_Migration {

    private $tableName='point_transactions';  

	public function up() {
		$fields1 = array(
			'current_rate' => array(
				'type' => 'double',
                'null' => true,
			),
		);
		$fields2 = array(
			'source_amount' => array(
				'type' => 'double',
                'null' => true,
			),
		);
		if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('current_rate', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields1);
            }
            if(!$this->db->field_exists('source_amount', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields2);
            }
        }
	}

	public function down() {
		if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('current_rate', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'current_rate');
            }
            if($this->db->field_exists('source_amount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'source_amount');
            }
        }
	}
}