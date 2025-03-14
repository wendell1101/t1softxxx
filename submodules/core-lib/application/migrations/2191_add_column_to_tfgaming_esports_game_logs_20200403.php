<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_tfgaming_esports_game_logs_20200403 extends CI_Migration
{
	private $tableName = 'tfgaming_esports_game_logs';

    public function up() {

        $fields = array(
            'ticket_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '16',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('ticket_type', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('ticket_type', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'ticket_type');
        }
    }
}