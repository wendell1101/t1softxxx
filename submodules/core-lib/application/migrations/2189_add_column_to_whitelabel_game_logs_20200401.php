<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_whitelabel_game_logs_20200401 extends CI_Migration
{
	private $tableName = 'whitelabel_game_logs';

    public function up() {

        $fields = array(
            'type'  => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'extra' => array(
                'type' => 'json',
                'null' => true,
            )
        );

        if(!$this->db->field_exists('type', $this->tableName) && !$this->db->field_exists('extra', $this->tableName)) {
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('type', $this->tableName) && $this->db->field_exists('extra', $this->tableName)) {
            $this->dbforge->drop_column($this->tableName, 'type');
            $this->dbforge->drop_column($this->tableName, 'extra');
        }
    }
}