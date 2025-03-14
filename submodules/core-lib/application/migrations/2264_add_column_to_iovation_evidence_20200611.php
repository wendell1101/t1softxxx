<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_iovation_evidence_20200611 extends CI_Migration
{
	private $tableName = 'iovation_evidence';

    public function up() {

        $fields4 = array(
            'evidence_status' => array(
                "type" => "TINYINT",
                "null" => true	
            ),
        );
        

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('evidence_status', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields4);
            }
        }
    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('evidence_status', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'evidence_status');
            }  
        }
    }
}