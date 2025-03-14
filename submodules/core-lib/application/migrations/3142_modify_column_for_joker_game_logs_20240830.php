<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_joker_game_logs_20240830 extends CI_Migration {

    private $tableName = 'joker_game_logs';

    public function up() {
        $field = [
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ],
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('type', $this->tableName)) {
                $this->dbforge->modify_column($this->tableName, $field);
            }
        }
    }

    public function down() {
    }
}