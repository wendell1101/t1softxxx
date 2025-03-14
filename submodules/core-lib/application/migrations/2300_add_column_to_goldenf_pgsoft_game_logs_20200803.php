<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_goldenf_pgsoft_game_logs_20200803 extends CI_Migration {

    private $tableName = 'goldenf_pgsoft_game_logs';

    public function up() {

        $fields = array(
            'parent_bet_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
        );

        if(!$this->db->field_exists('parent_bet_id', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('parent_bet_id', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'parent_bet_id');
        }
    }
}