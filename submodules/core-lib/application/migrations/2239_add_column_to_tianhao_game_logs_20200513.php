<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_tianhao_game_logs_20200513 extends CI_Migration
{
	private $tableName = "tianhao_game_logs";

    public function up() {

        $fields = array(
            'jack_pot' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('jack_pot', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('jack_pot', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'jack_pot');
        }
    }
}