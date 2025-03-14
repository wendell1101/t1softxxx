<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_promorules_20201026 extends CI_Migration {

    private $tableName = 'promorules';

    public function up() {
        $fields = array(
            "excludedPlayerTag_list" => array(
                "type" => "VARCHAR",
                "constraint" => "255",
                "null" => true
            ),
        );

        if(!$this->db->field_exists('excludedPlayerTag_list', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('excludedPlayerTag_list', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'excludedPlayerTag_list');
        }
    }
}