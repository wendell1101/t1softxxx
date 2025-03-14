<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_iovation_logs_20200514 extends CI_Migration
{
	private $tableName = 'iovation_logs';

    public function up() {

        $fields1 = array(
            'status' => array(
                "type" => "TINYINT",
                "null" => true		
            ),
        );

        $fields2 = array(
            'type' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
        );

        $fields3 = array(
            'blackbox' => array(
                'type' => 'TEXT',                
                'null' => true,
            ),
        );
        

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('status', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields1);
            }
            if(!$this->db->field_exists('type', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields2);
            }
            if(!$this->db->field_exists('blackbox', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields3);
            }
        }
    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('status', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'status');
            }            
            if($this->db->field_exists('type', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'type');
            }  
            if($this->db->field_exists('blackbox', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'blackbox');
            } 
        }
    }
}