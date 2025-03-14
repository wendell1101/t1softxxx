<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_iovation_evidence_20220727 extends CI_Migration
{
	private $tableName = 'iovation_evidence';

    public function up() {
        $field1 = array(
            'user_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
        );        

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('user_type', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
        }

        $field2 = array(
            'affiliate_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );        

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('affiliate_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field2);
            }
        }
    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('user_type', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'user_type');
            } 
        }
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('affiliate_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'affiliate_id');
            } 
        }
    }
}