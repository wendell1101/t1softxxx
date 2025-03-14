<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_cq9_game_logs_20200813 extends CI_Migration {

    private $tableName = 'cq9_game_logs';

    public function up() {

        $fields = array(
            "validbet" => array(
				'type' => 'double',
				'null' => true,
            ),
        );

        if(!$this->db->field_exists('validbet', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('validbet', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'validbet');
        }
    }
}