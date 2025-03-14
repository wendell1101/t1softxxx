<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_sexy_baccarat_game_logs_20200331 extends CI_Migration
{
	private $tableName = 'sexy_baccarat_game_logs';

    public function up() {

        $fields = array(
            'action' => array(
                'type' => 'VARCHAR',
                'constraint' => '16',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('action', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('action', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'action');
        }
    }
}