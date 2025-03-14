<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_columns_to_get_response_contacts_20230124 extends CI_Migration
{
	private $tableName = 'get_response_contacts';


    public function up() {
        $field1 = array(
            'withdrawal_data' => array(
                'type' => 'JSON',
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('withdrawal_data', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('withdrawal_data', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'withdrawal_data');
            }
        }
    }
}
