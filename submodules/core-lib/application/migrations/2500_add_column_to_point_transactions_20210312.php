<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_point_transactions_20210312 extends CI_Migration {

    private $tableName='point_transactions';  

	public function up() {
		$fields = array(
			'calculated_points' => array(
				'type' => 'double',
                'null' => true,
			),
            'points_limit_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),
            'points_limit' => array(
				'type' => 'DOUBLE',
				'null' => true,
            ),
            'forfieted_points' => array(
				'type' => 'double',
                'null' => true,
			),
		);		
		if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('calculated_points', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }          
        }
	}

	public function down() {
		if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('calculated_points', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'calculated_points');
            }            
            if($this->db->field_exists('points_limit_type', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'points_limit_type');
            }   
            if($this->db->field_exists('points_limit', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'points_limit');
            }   
            if($this->db->field_exists('forfieted_points', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'forfieted_points');
            }   
        }
	}
}