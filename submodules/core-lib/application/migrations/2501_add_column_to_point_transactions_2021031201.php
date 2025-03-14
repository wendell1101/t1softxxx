<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_point_transactions_2021031201 extends CI_Migration {

    private $tableName='point_transactions';  

	public function up() {
		$fields = array(
            'vip_level_id' => array(
                'type' => 'INT',
                'null' => true,
            ), 
            'vip_group_name' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'vip_level_name' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
		);		
		if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('vip_level_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }          
        }
	}

	public function down() {
		if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('vip_level_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'vip_level_id');
            }    
            if($this->db->field_exists('vip_group_name', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'vip_group_name');
            }  
            if($this->db->field_exists('vip_level_name', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'vip_level_name');
            }  
        }
	}
}