<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_game_description_20231220 extends CI_Migration {

    private $tableName = 'game_description';

    public function up() {
        $column = [
            'game_name' => [
                'type' => 'VARCHAR',
				'constraint' => '1000',
				'null' => true,
            ],
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('game_name', $this->tableName)) {
                $this->dbforge->modify_column($this->tableName, $column);
            }
        }
    }

    public function down() {
    }
}