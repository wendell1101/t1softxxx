<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_game_description_20240307 extends CI_Migration {

    private $tableName = 'game_description';

    public function up() {
        $column = [
            'game_order' => [
                'type' => 'INT',
                'null' => false,
                'default' => 0,
            ],
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('game_order', $this->tableName)) {
                $this->dbforge->modify_column($this->tableName, $column);
            }
        }
    }

    public function down() {
    }
}