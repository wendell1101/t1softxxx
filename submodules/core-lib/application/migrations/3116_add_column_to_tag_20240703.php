<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_column_to_tag_20240703 extends CI_Migration 
{
    private $tableName = 'tag';
    public function up() {
        $fields = array(
            'wdRemark' => array(
				'type' => 'VARCHAR',
                'constraint' => '255',
				'null' => TRUE,
			),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if( ! $this->db->field_exists('wdRemark', $this->tableName) ){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('wdRemark', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'wdRemark');
            }
        }
    }
}
