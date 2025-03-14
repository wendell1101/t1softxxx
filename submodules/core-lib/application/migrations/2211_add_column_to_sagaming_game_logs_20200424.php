<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_sagaming_game_logs_20200424 extends CI_Migration
{
	private $tableName = 'sagaming_game_logs';

    public function up() {

        $fields = array(
            'Rolling' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('Rolling', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('Rolling', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'Rolling');
        }
    }
}