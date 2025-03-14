<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_iovation_logs_20220628 extends CI_Migration
{
	private $tableName = 'iovation_logs';

    public function up() {
        $fields = array(
            'user_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
        );        

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('user_type', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('user_type', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'user_type');
            } 
        }
    }
}