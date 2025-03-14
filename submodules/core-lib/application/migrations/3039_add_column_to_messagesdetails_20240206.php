<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_messagesdetails_20240206 extends CI_Migration {
	private $tableName = 'messagesdetails';

    public function up() {
        $field1 = array(
            'read_At' => array(
                'type' => 'DATETIME',
                'null' => false,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){

            if(!$this->db->field_exists('read_At', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('read_At', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'read_At');
            }
        }
    }
}
///END OF FILE/////