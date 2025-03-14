<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_miki_worlds_game_logs_20231112 extends CI_Migration {

    private $tableName = 'miki_worlds_game_logs';

    public function up() {
        $field = array(
            'game_type_string' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('game_type_string', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('game_type_string', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'game_type_string');
            }
        }
    }
}