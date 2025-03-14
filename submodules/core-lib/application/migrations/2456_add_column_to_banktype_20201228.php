<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_banktype_20201228 extends CI_Migration {

    private $tableName = 'banktype';

    public function up() {

        $field1 = array(
            'is_hidden' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'default' => '0'
            ),
        );
        $field2 = array(
           'deleted_at' => array(
            'type' => 'DATETIME',
            'null' => true,
        ),
       );

        if(!$this->db->field_exists('is_hidden', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field1);
        }
        if(!$this->db->field_exists('deleted_at', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field2);
        }
    }

    public function down() {
        if($this->db->field_exists('is_hidden', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'is_hidden');
        }
        if($this->db->field_exists('deleted_at', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'deleted_at');
        }
    }
}