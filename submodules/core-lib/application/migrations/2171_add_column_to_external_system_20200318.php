<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_external_system_20200318 extends CI_Migration
{
	private $tableName = 'external_system';

    public function up() {

        $fields = array(
            'seamless' => array(
                'type' => 'tinyint',
                'null' => false,
                'default' => 0,
            ),
        );

        if(!$this->db->field_exists('seamless', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('seamless', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'seamless');
        }
    }
}